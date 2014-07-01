#
#  A Ruby wrapper for the Workbooks API documented at http://www.workbooks.com/api
#
#  Last commit $Id: workbooks_api.rb 22501 2014-07-01 12:17:25Z jkay $
#  License: www.workbooks.com/mit_license
#
#  Significant methods in the class Workbooks:
#    initialize                - create an API object, specifying various options
#    login                     - authenticate
#    logout                    - terminate logged-in session
#    get                       - get a list of objects, or show an object
#    create                    - create objects
#    update                    - update objects
#    delete                    - delete objects
#    batch                     - create, update, and delete objects together
#    condensed_status          - use this to quickly check the response
#    log                       - a simple log interface, sends logs to self.logger
#
#  Tested on ruby 1.8.7 (Ubuntu 12.04 LTS) and on ruby 2.0.0 (Mac OS X 10.9.3)
#

require 'net/https'
require 'base64'
require 'rubygems'
require 'cgi'
# Ruby 2.0 and later has JSON support in stdlib. On earlier versions you may want to "gem install json_pure"
require 'json'

# WorkbooksApi raises WorkbooksApiExceptions.
class WorkbooksApiException < StandardError
  attr :workbooks_api
  attr :last_response
  
  def initialize(workbooks_api=nil, last_response=nil)
    @workbooks_api = workbooks_api
    @last_response = last_response
  end
  
  def message
    super + "\n" + last_response.inspect
  end
end

# The API returns WorkbooksApiResponse objects. These behave a bit like hashes.
class WorkbooksApiResponse
  attr :response
  
  def initialize(response)
    @response = response
    response
  end
  
  def [](elem)
    @response[elem]
  end

  # Various shortcuts to common elements of the response
  def data
    self['data']
  end
  
  def affected_objects
    self['affected_objects']
  end
  
  def affected_object_information
    self['affected_object_information']
  end

  def total
    self['total'].nil? ? nil : self['total'].to_i
  end
      
  # Evaluate the response to determine how successful it was, returns one of :failed, :not_ok, :ok
  #   :failed - this is unexpected.
  #   :not_ok - something in the request could not be satisfied; you should check the errors and warnings.
  #   :ok     - completely successful.
  def condensed_status
    return :failed if self['success'].nil? # Unexpected failure - there should always be a 'success' element
    return :not_ok if !self['errors'].to_a.empty?
    self.affected_object_information.to_a.each do |affected|
      return :failed if affected['success'].nil?
      return :not_ok if !affected['success'] # There are warnings or errors indicated which prevented complete success.
    end
    return :ok
  end
  
  # Checks the response is as expected. Raises an exception if the response is not, otherwise returns the response.
  # * expected - the expected type of response, defaults to :ok
  # * raise_on_error - the exception to raise if the response is not as expected.
  def assert(expected=:ok, raise_on_error='Unexpected response from Workbooks API')
    if (self.condensed_status != expected)
      raise WorkbooksApiException.new(self, response), raise_on_error
    end
    self
  end
  
  # Extract ids and lock_versions from the affected_objects of the response and return them as an Array of Hashes,
  # one per affected object.
  def id_versions
    self.affected_objects.map { |affected|
      {
        'id' => affected['id'], 
        'lock_version' => affected['lock_version'],
      }
    }
  end
end

class WorkbooksApi

  API_VERSION = 1; # Used to select the default Workbooks server-side behaviour
  SESSION_COOKIE = 'Workbooks-Session'
  DEFAULT_CONNECT_TIMEOUT = 120
  DEFAULT_REQUEST_TIMEOUT = 120
  DEFAULT_SERVICE = 'https://secure.workbooks.com'
  
  MAX_LOG_SIZE = 1048576 # A hard limit of 1 MegaByte to limit the size of a log message from log()
  
  attr_reader :session_id
  attr_reader :api_key
  attr_reader :username
  attr_reader :logical_database_id
  attr_reader :database_instance_id
  attr_reader :authenticity_token
  attr_reader :api_version # default: the version defined in this file
  attr_reader :logged_in
  attr_reader :application_name
  attr_reader :user_agent
  attr_reader :connect_timeout
  attr_reader :request_timeout
  attr_reader :verify_peer # default: true (false is NOT correct for Production use)
  attr_reader :service
  attr_reader :last_request_duration
  attr_reader :user_queues # when logged in contains an array of user queues
  attr :http
  attr :cookies
  attr :logger
  attr :max_log_size
  attr :http_debug_output

  # The content_type governs the encoding used for data transfer to the Service. Two forms are
  # supported in this binding; use FORM_DATA for file uploads.
  FORM_URL_ENCODED = 'application/x-www-form-urlencoded'
  FORM_DATA = 'multipart/form-data'
  
  #
  # Initialise the Workbooks API. Pass in a hash of parameterss.
  #
  #   Mandatory settings
  #   - :application_name: You should specify a descriptive name for your application such as 'Freedom Plugin' or 'Mactools Widget'
  #   - :user_agent: A technical name for the application including version number e.g. 'Mactools/0.9.2' as defined in HTTP.
  #   Optional settings
  #   - :service: The location of the Workbooks service (defaults to https://secure.workbooks.com)
  #   - :api_key or :username: the user to login with
  #   - :session_id: a sessionID to reconnect to
  #   - :logical_database_id: the databaseID which the session_id is associated with
  #   - :api_version: used to request a specific server-side behaviour. Normally this should be left as API_VERSION
  #   - :connect_timeout: how long to wait for a connection to be established in seconds (default: 120)
  #   - :request_timeout: how long to wait for a response in seconds (default: 120)
  #   - :verify_peer: whether to verify the peer's SSL certificate. Set this to false for some test environments but do not 
  #       do this in Production.
  #   - :logger: to get logging output set to an instance of Logger in which case Logger#add is called
  #   - :http_debug_output: if :logger is also set HTTP debug output is also generated
  #
  def initialize(params={})
    @application_name = params[:application_name] or raise WorkbooksApiException.new, 'An application name is required'
    @user_agent = params[:user_agent] or raise WorkbooksApiException.new, 'A user agent is required'

    @api_version = params[:api_version] || API_VERSION
    @connect_timeout = params[:connect_timeout] || DEFAULT_CONNECT_TIMEOUT
    @request_timeout = params[:request_timeout] || DEFAULT_REQUEST_TIMEOUT
    @verify_peer = params.has_key?(:verify_peer) ? params[:verify_peer] : true
    @service = params[:service] || DEFAULT_SERVICE
    @logger = params[:logger]
    @http_debug_output = params[:http_debug_output]
    @max_log_size = params[:max_log_size] || MAX_LOG_SIZE
    @session_id = params[:session_id]
    @api_key = params[:api_key]
    @username = params[:username]
  end

  #
  # A simple logging interface which sends messages to the logger (if one is set up)
  # * msg a string to be logged
  # * expression any values to output with the message
  # * level optional: as supported by Logger (Logger::DEBUG, Logger::INFO, Logger::WARN, Logger::Error, Logger::FATAL).
  # * log_size_limit the maximum size msg that will be logged.
  # Returns the expression which was passed in.
  #
  def log(msg, expression=nil, level=Logger::DEBUG, log_size_limit=4096)
    if !@logger.nil?
      msg << " «#{expression.inspect}»"
      log_size_limit = [log_size_limit, max_log_size].min
      if msg.size > log_size_limit
        msg = "#{msg[0 .. log_size_limit/2]} ... (#{msg.size-log_size_limit} bytes) ... #{msg[-log_size_limit/2 .. -1]}"
      end
      self.logger.add(level, "#{msg}\n")
    end
    expression
  end

  #
  # Get the active database as a string to embed in a URL (useful for web processes).
  #
  def get_database_instance_ref
    Base64.encode64((database_instance_id.to_i+17).to_s).chomp.reverse
  end

  # Helper method which evaluates a response, see WorkbooksApiResponse#condensed_status.
  def condensed_status(response)
    response.condensed_status
  end
  
  # 
  # Check responses are expected, see WorkbooksApiResponse#assert. 
  # Raises an exception if the response is not expected, otherwise returns the response.
  # * response - a response from the service API.
  # * expected - the expected type of response, defaults to :ok
  # * raise_on_error - the exception to raise if the response is not as expected.
  # 
  def assert_response(response, expected=:ok, raise_on_error='Unexpected response from Workbooks API')
    response.assert(expected, raise_on_error)
  end
  
  # Extract ids and lock_versions from the :affected_objects, see WorkbooksApiResponse#id_versions.
  def id_versions(response)
    response.id_versions
  end

  # 
  # Login to the service to set up a session.
  #   Optional settings, specified in a hash represenet credentials and other options to the login API endpoint
  #   - :api_key: An API key (this is preferred over username/password).
  #   - username: The user's login name (required if not set using setUsername) or using an API key.
  #   - password: The user's login password. Either this or a session_id must be specified.
  #   - session_id: The ID of a session to reconnect to. Either this or a password must be specified.
  #   - logical_database_id: The ID of a database to select - not required when the user has access to exactly one.
  #   others as defined in the API documentation (e.g. _time_zone, _strict_attribute_checking, _per_object_transactions).
  # returns a hash containing the http status, any failure reason, the decoded json
  # 
  # A successful login returns an http status of 200 (Net::HTTPOK).
  # If more than one database is available the http status is 403 (Net::HTTPForbidden), the failure reason 
  #   is 'no_database_selection_made' and the set of databases to choose from are in the decoded json beneath the 'databases' 
  #   key. Repeat the login() call, passing in a logical_database_id: you might use the 'default_database_id' value which 
  #   was returned in the previous login attempt.
  # Otherwise the login has failed outright: see the Workbooks API documentation for a list of the possible http statuses.
  # 
  def login(params={})
    params[:api_key] ||= @api_key if @api_key 
    params[:username] ||= @username if @username
    unless params[:api_key] || params[:username]
      raise WorkbooksApiException.new(self), 'An :api_key or a :username must be supplied'
    end
    
    params[:session_id] ||= @session_id if @session_id && !params[:password]
    if params[:api_key].nil? && params[:password].nil? && params[:session_id].nil?
      raise WorkbooksApiException.new(self), 'A :password or :session_id must be supplied unless using an :api_key' 
    end
    
    params[:logical_database_id] ||= @logical_database_id if @logical_database_id
    if params[:logical_database_id].nil? && params[:session_id] && params[:password].nil?
      raise WorkbooksApiException.new(self), 'A :logical_database_id must be supplied when trying to re-connect to a session'
    end
    
    # These default settings can be overridden by the caller.
    params[:_application_name] ||= @application_name
    params[:_strict_attribute_checking] = params[:_strict_attribute_checking].nil? ? true : params[:_strict_attribute_checking]
    params[:api_version] ||= @api_version
    
    response = make_request('login.api', :post, params)
    parsed_response = JSON.parse(response.body) rescue []
    # The authenticity_token is valid for a specific session and is required when any modifications are attempted.
    if response.is_a?(Net::HTTPOK)
      @logged_in = true
      @user_queues = parsed_response['my_queues'].map{ |queue_name, queue_id| {queue_name.to_s => queue_id} }
      @authenticity_token = parsed_response['authenticity_token']
      @database_instance_id = parsed_response['database_instance_id']
    end
    
    retval = {
      :http_status => response.code,
      :failure_message => parsed_response['failure_message'],
      :response => WorkbooksApiResponse.new(parsed_response),
    }
    
    #log('login() returns', retval)
    retval
  end

  # 
  # Logout from the service.
  # 
  # Returns a hash of :success - whether it succeeded, :http_status, :response - the response object
  # 
  # A successful logout will return a :success value of true
  # 
  def logout
    response = make_request('logout', :post, {}, [], :follow_redirects => false)
    @logged_in = false # force a login regardless of the server-side state
    @authenticity_token = nil
    
    retval = {
      :success => response.is_a?(Net::HTTPRedirection),
      :http_status => response.code,
      :response => WorkbooksApiResponse.new(response)
    }
    
    #log('logout() returns', retval)
    retval
  end

  # 
  # Make a request to an endpoint on the service to read or list objects. You must have logged in first
  # * endpoint - selects the portion of the API to use, e.g. 'crm/organisations'
  # * params - the parameters to the API call - filter, limit, column selection as a hash
  #   each hash element can have a simple value or be an array of values e.g. for column selection.
  # * options - (optional) options to pass through to make_request() potentially including :content_type. 
  # Returns the decoded json response as a WorkbooksApiResponse if :decode_json is true (default), or the raw response if not.
  # 
  # As usual, check the API documentation for further information.
  # 
  def get(endpoint, params=nil, options={})
    url_encode = options[:content_type].nil? ? true : options[:content_type]
    
    array_params = [] # those where the value is an array, not simply a value
    params ||= {}
    params.each do |key, value|
      if value.is_a?(Array)
        if key.to_s == '_filters[]' # '_filters[]' should be either an array of filters or a single filter
          value = [value] if !value[0].is_a?(Array) # deal with single filter
          value.each do |filter|
            array_params << '_ff[]=' + (url_encode ? URI::encode(filter[0].to_s) : filter[0].to_s)
            array_params << '_ft[]=' + (url_encode ? URI::encode(filter[1].to_s) : filter[1].to_s)
            array_params << '_fc[]=' + (filter[2].nil? ? true : (url_encode ? URI::encode(filter[2].to_s) : filter[2].to_s))
          end
        else
          value.each do |array_value|
            array_params << key.to_s + '=' + (url_encode ? URI::encode(array_value.to_s) : array_value.to_s)
          end
        end
      end
    end
    params.reject! { |key, value| value.is_a?(Array) } # Remove items just processed

    api_call(endpoint, :get, params, array_params, options)
  end

  # 
  # Interface as per get() but if the response is not :ok it also logs an error and raises an exception.
  # 
  def assert_get(*args)
    assert_response(get(*args))
  end

  # 
  # Make a request to an endpoint on the service to create objects. You must have logged in first.
  # 
  # * endpoint - selects the portion of the API to use, e.g. 'crm/organisations'.
  # * objs - an array of objects to create
  # * params a set of additional parameters to send along with the data, for example
  #   {:_per_object_transactions => true} to change the commit behaviour.
  # * options - as hash to pass through to make_request()
  # Returns the decoded json response as a WorkbooksApiResponse if :decode_json is true (default), or the raw response if not.
  # As usual, check the API documentation for further information.
  # 
  def create(endpoint, objs, params={}, options={})
    batch(endpoint, objs, params, :create, options);
  end

  # 
  # Interface as per create() but if the response is not :ok it also logs an error and raises an exception.
  # 
  def assert_create(*args)
    assert_response(create(*args))
  end

  # 
  # Make a request to an endpoint on the service to update objects. You must have logged in first.
  # 
  # * endpoint - selects the portion of the API to use, e.g. 'crm/organisations'.
  # * objs - an array of objects to update, specifying the id and lock_version of each together with the values to set.
  # * params a set of additional parameters to send along with the data, for example
  #   {:_per_object_transactions => true} to change the commit behaviour.
  # * options - as hash to pass through to make_request()
  # Returns the decoded json response as a WorkbooksApiResponse if :decode_json is true (default), or the raw response if not.
  # 
  # As usual, check the API documentation for further information.
  # 
  def update(endpoint, objs, params={}, options={})
    batch(endpoint, objs, params, :update, options);
  end

  # 
  # Interface as per update() but if the response is not :ok it also logs an error and raises an exception.
  # 
  def assert_update(*args)
    assert_response(update(*args))
  end

  # 
  # Make a request to an endpoint on the service to delete objects. You must have logged in first.
  # 
  # * endpoint - selects the portion of the API to use, e.g. 'crm/organisations'.
  # * objs - an array of objects to delete, specifying the id and lock_version of each.
  # * params a set of additional parameters to send along with the data, for example
  #   {:_per_object_transactions => true} to change the commit behaviour.
  # * options - as hash to pass through to make_request()
  # Returns the decoded json response as a WorkbooksApiResponse if :decode_json is true (default), or the raw response if not.
  # 
  # As usual, check the API documentation for further information.
  # 
  def delete(endpoint, objs, params={}, options={})
    batch(endpoint, objs, params, :delete, options);
  end

  # 
  # Interface as per delete() but if the response is not :ok it also logs an error and raises an exception.
  # 
  def assert_delete(*args)
    assert_response(delete(*args))
  end

  # 
  # Make a request to an endpoint on the service to operate on multiple objects. You must have logged in first.
  # You can request a combination of :create, :update and :delete operations, to be batched together.
  # This is the core method upon which other methods are implemented which perform a subset of these operations.
  # 
  # * endpoint - selects the portion of the API to use, e.g. 'crm/organisations'.
  # * objs - an array of objects to create, update or delete.
  # * params a set of additional parameters to send along with the data, for example
  #   {:_per_object_transactions => true} to change the commit behaviour.
  # * method - the method (:create, :update or :delete) which is to be used if not specified for an object.
  # * options - as hash to pass through to make_request()
  # Returns the decoded json response as a WorkbooksApiResponse if :decode_json is true (default), or the raw response if not.
  
  # 
  # As usual, check the API documentation for further information.
  # 
  def batch(endpoint, objs, params={}, method=nil, options={})
    #log('batch() called', { :endpoint => endpoint, :objs => objs, :params => params, :method => method, :options => options })

    objs = [objs] unless objs.is_a?(Array) # If just one object was passed in, turn it into an array.

    filter_params = encode_method_params(objs, method)
    url_encode = options[:content_type].nil? ? true : (options[:content_type] == FORM_URL_ENCODED)
    ordered_post_params = full_square(objs, url_encode)
    response = api_call(endpoint, :put, params, filter_params + ordered_post_params, options)
    
    #log('batch() returns', response)
    response
  end

  # 
  # Interface as per batch() but if the response is not :ok it also logs an error and raises an exception.
  # 
  def assert_batch(*args)
    assert_response(batch(*args))
  end

  # 
  # Ensure we are logged in
  # 
  def ensure_login
    if !@logged_in
      raise WorkbooksApiException.new(self), 'Not logged in'
    end
  end

  # 
  # Make a call to an endpoint on the service.
  # 
  # * endpoint - selects the portion of the API to use, e.g. 'crm/organisations'.
  # * method - the restful method: one of :get, :put, :post, :delete.
  # * post_params - a hash of uniquely-named parameters to add to the POST body.
  # * ordered_post_params - a simple array of additional parameters, to use for the POST body (may have duplicate keys e.g. 'id[]')
  # * options - extras to pass through to make_request()
  # Returns the decoded json response as a WorkbooksApiResponse if :decode_json is true (default), or the raw response if not.
  # 
  # As usual, check the API documentation for further information.
  # 
  def api_call(endpoint, method, post_params={}, ordered_post_params=[], options={})
    #log('api_call() called', { :endpoint => endpoint, :method => method, :post_params => post_params, :ordered_post_params => ordered_post_params, :options => options })

    # Clients using API Keys normally pass those on each request; otherwise establish a session to span multiple requests.
    if @api_key
      post_params[:api_key] ||= @api_key
      post_params[:api_version] ||= @api_version
      post_params[:application_name] ||= @application_name
      post_params[:user_agent] ||= @user_agent
    else
      ensure_login
    end
    
    # API calls are always to a '.api' endpoint; the caller does not have to include this. Including ANY extension will prevent
    # '.api' from being appended.
    endpoint = endpoint + '.api' unless endpoint.match(/\.\w{3,4}/)

    response = make_request(endpoint, method, post_params, ordered_post_params, options)
    
    if !response.is_a?(Net::HTTPOK)
      raise WorkbooksApiException.new(self, response), "Non-OK response (#{response.code})"
    end
    
    if options[:decode_json] === false
      retval = response.body
    else
      begin
        retval = WorkbooksApiResponse.new(JSON.parse(response.body))
      rescue Exception => e
        raise WorkbooksApiException.new(self, response), "JSON decode failure"
      end
    end
    
    #log('api_call() returns', retval)
    retval
  end

  # 
  # Builds and sends an HTTP request.
  # 
  # Assuming the service can be contacted a Net::HTTPResponse object is returned, otherwise an exception is raised.
  # 
  # * endpoint - selects the portion of the API to use, e.g. 'crm/organisations'.
  # * method - the restful method - one of :get, :put, :post, :delete
  # * post_params - a hash of uniquely-named parameters to add to the POST body.
  # * ordered_post_params - a simple array of additional parameters, to use for the POST body (may have duplicate keys e.g. 'id[]')
  # * options - additional options...
  #      * :content_type which defaults to 'application/x-www-form-urlencoded'
  #      * :follow_redirects which defaults to true (the service may issue redirects a rate limiting measure)
  # Returns a Net::HTTPResponse object
  #
  def make_request(endpoint, method, post_params={}, ordered_post_params=[], options={})
    #log('make_request() called', { :endpoint => endpoint, :method => method, :post_params => post_params, :ordered_post_params => ordered_post_params, :options => options })
    
    start_time = Time.now.utc

    content_type = options[:content_type] || WorkbooksApi::FORM_URL_ENCODED
    url_params = {
      :_dc => (start_time.to_f * 1000).to_i # cache-buster
    }
    url = get_url(endpoint, url_params)
    post_params[:_method] ||= method.to_s.upcase
    post_params[:client] ||= 'api'
    post_params[:_authenticity_token] ||= @authenticity_token if @authenticity_token && method != :get 

    post_fields = nil
    
    if content_type == WorkbooksApi::FORM_URL_ENCODED
      post_fields = url_encode_fields(post_params)
      if ordered_post_params && ordered_post_params.size > 0
        ordered_post_params = [ordered_post_params] unless ordered_post_params.is_a?(Array)
        post_fields = post_fields + '&' + ordered_post_params.join('&')
      end
    else # Use 'multipart/form-data' which is efficient for file transfer
      fields = []
      post_params.each do |key, value|
        if value.is_a?(Array)
          value.each { |v| fields << { key => v } }
        else
          fields << { key => value }
        end
      end
      ordered_post_params.each do |p|
        if p.is_a?(String)
          (key, value) = p.split(/=/, 2)
          fields << { key => value }
        else 
          fields << p
        end
      end
      
      boundary = "----------------------------form-data-#{'%08x%08x%08x' % [rand(0xffffffff), Time.now.to_i, rand(0xffffffff)]}"
      content_type = "#{FORM_DATA}; boundary=#{boundary}"
      
      body = []
      fields.each do |f|
        f.each do |key, value|
          if value.is_a?(Hash) && value.has_key?(:file) && value[:file].is_a?(File)
            body << "--#{boundary}"
            body << "Content-Disposition: form-data; name=\"#{key}\"; filename=\"#{File.basename(value[:file_name])}\""
            body << "Content-Type: #{value[:file_content_type]}"
            body << ''
            body << value[:file].read
          else
            body << "--#{boundary}"
            body << "Content-Disposition: form-data; name=\"#{key}\""
            body << ''
            body << value
          end
        end
      end
      body << "--#{boundary}--"
      body << ''
      
      post_fields = body.join("\r\n")
    end

    #log("post_fields, first 1000 bytes", post_fields[0..999])

    service = URI(@service)
    @http ||= Net::HTTP.new(service.host, service.port) # Reuse the same connection if we can.

    @http.set_debug_output(@logger) if @logger && @http_debug_output
    @http.open_timeout = @connect_timeout
    @http.read_timeout = @request_timeout
    if service.scheme == 'https'
      @http.use_ssl = true
      @http.verify_mode = (@verify_peer ? OpenSSL::SSL::VERIFY_PEER : OpenSSL::SSL::VERIFY_NONE)
    end

    response = nil
    request_type = (method == :get ? Net::HTTP::Get : Net::HTTP::Post)

    begin
      http_request = request_type.new(url)
      http_request['Content-Type'] = content_type
      http_request['Content-Length'] = post_fields.size if post_fields.is_a?(String)
      http_request['Cookie'] = @cookies.map{ |cookie| cookie.split(';')[0] }.join(";") if @cookies
      http_request['User-Agent'] = @user_agent
      http_request.body = post_fields

      response = @http.request(http_request)
      #log("response #{response.code} #{response.class.to_s}, first 100 bytes", response.body[0..99])
    end while response.is_a?(Net::HTTPRedirection) && (!options.has_key?(:follow_redirects) || options[:follow_redirects])

    end_time = Time.now.utc
    @last_request_duration = end_time.to_f - start_time.to_f

    # Process response cookies. The Workbooks-Session cookie is always present when you have used login() to establish
    # a session. Note that the Workbooks-Session in the response may be different from that in the request.
    cookie_header = response['Set-Cookie']
    if cookie_header
      @cookies = cookie_header.split(', ')
      @cookies.each do |cookie|
        if matches = cookie.match(/^#{SESSION_COOKIE}=([^;]+)/)
          @session_id = matches[1]
        end
      end
    end

    #log('make_request() returns', response)
    response
  end

  # 
  # Depending on the method (:create/:update/:delete) the objects passed to Workbooks
  # have certain minimum requirements. Callers may specify a method for each object
  # or assume the same operation for all objects.
  # 
  # * obj_array - an array of objects to be encoded, modified in place
  # * method - (:create/:update/:delete) which is to be used if not specified for an object. (can be nil: an error if unspecified)
  # Returns an array representing the filter which is required to define the working set of objects.
  # 
  def encode_method_params(obj_array, method)
    #log('encode_method_params() called', { :obj_array => obj_array, :method => method })
    
    filter_ids = []
    
    obj_array.each do |obj|
      obj_method = method
      if obj.has_key?(:method)
        obj_method = obj[:method]
        obj.delete(:method)
      end
      
      case obj_method
        when :create
          obj[:__method] = 'POST'
          # Must not specify a current id and lock_version (or if you do they should both be zero)
          if (obj.has_key?('id') && obj['id'] > 0) || (obj.has_key?('lock_version') && obj['lock_version'] > 0)
            raise WorkbooksApiException.new(self), "Neither 'id' nor 'lock_version' can be set to create an object"
          end
          obj['id'] = 0;
          obj['lock_version'] = 0;
          filter_ids << 0

        when :update
          obj[:__method] = 'PUT'
          # Must specify a current id and lock_version
          if (!obj.has_key?('id') || obj['id'] == 0 || !obj.has_key?('lock_version'))
            raise WorkbooksApiException.new(self), "Both 'id' and 'lock_version' must be set to update an object"
          end
          filter_ids << obj['id']

        when :delete
          obj[:__method] = 'DELETE'
          # Must specify a current id and lock_version
          if (!obj.has_key?('id') || obj['id'] == 0 || !obj.has_key?('lock_version'))
            raise WorkbooksApiException.new(self), "Both 'id' and 'lock_version' must be set to delete an object"
          end
          filter_ids << obj['id']

        else
          raise WorkbooksApiException.new(self), "Unexpected method: #{method}"
      end
    end
    
    # Must include a filter to 'select' the set of objects being operated upon
    filter = []
    filter_ids.uniq!

    if filter_ids.size > 0
      filter << '_fm=or' if filter_ids.size > 1
      filter_ids.each do |filter_id|
        filter << '_ff[]=id' << '_ft[]=eq' << "_fc[]=#{filter_id}"
      end
    end

    #log('encode_method_params() returns', filter)
    filter
  end

  # 
  # The Workbooks wire protocol requires that each key which is used in any object be
  # present in all objects, and delivered in the right order. Callers of this binding
  # library will omit keys from some objects and not from others. Some special values
  # are used in this encoding - :null_value: and :no_value:.
  # 
  # * obj_array - an array of objects to be encoded
  # * url_encode - a boolean: whether to URL encode them, defaults to true
  # Returns an array which is the (encoded) set of objects suitable for passing to Workbooks
  # 
  def full_square(obj_array, url_encode=true)
    #log('full_square() called', { :obj_array => obj_array, :url_encode => url_encode })

    retval = []

    # Get the full set of hash keys for all of the objects in obj_array
    unique_keys = obj_array.map { |o| o.keys }.flatten.uniq.sort{ |a,b| a.to_s <=> b.to_s }

    # The full square array is one with a value for every key in every object
    obj_array.each do |obj|
      unique_keys.each do |key|
        value = obj[key]
        if obj.has_key?(key) && obj[key].nil?
          value = ':null_value:'
        elsif !obj.has_key?(key)
          value = ':no_value:'
        end
        
        unnested_key = unnest_key(key)
        
        if value.is_a?(Hash) && value.has_key?(:file) && value[:file].is_a?(File)
          retval << { "#{unnested_key}[]" => value }
        elsif value.is_a?(Array)
          new_val = '[' << value.map{|v| v.to_s }.join(',') << ']'
          retval << (url_encode ? "#{CGI::escape(unnested_key)}[]=#{CGI::escape(new_val)}" : "#{unnested_key}[]=#{new_val}")
        else
          retval << (url_encode ? "#{CGI::escape(unnested_key)}[]=#{CGI::escape(value.to_s)}" : "#{unnested_key}[]=#{value}")
        end
      end
    end
    
    #log('full_square() returns', retval)
    retval
  end

  # 
  # Normalise any nested keys so they have the expected format for the wire, i.e. 
  # convert things like this:
  #   org_lead_party[main_location[email]]
  # into this:   
  #   org_lead_party[main_location][email]
  # 
  # Parameter: attribute_name - the attribute name with potentially nested square brackets
  # Returns the unnested attribute name
  # 
  def unnest_key(attribute_name)
    #log('unnest_key() called', { :attribute_name => attribute_name })
    
    retval = attribute_name.to_s
    
    if retval.match(/\]\]$/) # If it does not end in ']]' then it is not a nested key.
      parts = retval.split(/[\[\]]+/)
      retval = parts[0] << '[' << parts[1..-1].join('][') << ']'
    end
    
    #log('unnest_key() returns', retval)
    retval
  end
  
  # URL encode and concatenate fields, separating them with an ampersand
  def url_encode_fields(fields)
    #log('url_encode_fields() called', { :fields => fields })
    
    retval = fields.map {|k, v|
      if v.instance_of?(Array)
        v.map {|e| "#{CGI::escape(k.to_s)}=#{CGI::escape(e.to_s)}"}.join('&')
      else
        "#{CGI::escape(k.to_s)}=#{CGI::escape(v.to_s)}"
      end
    }.join('&')

    #log('url_encode_fields() returns', retval)
    retval
  end
  
  # 
  # Construct a URL for the current Workbooks service including path and parameters.
  # Parameters:
  # * - the path
  # * - optional array of query params to append
  # Returns the URL for the given parameters
  # 
  def get_url(path, query_params=[])
    #log('get_url() called', { :path => path, :query_params => query_params })
    
    url = @service;
    url = url + '/' if path[0] != '/'
    url = url + path

    if query_params.size > 0
      url = url + '?' + url_encode_fields(query_params)
    end

    #log('get_url() returns', url)
    url
  end

end # class WorkbooksApi
