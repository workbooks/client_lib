#
#  Login wrapper for Workbooks for API test purposes. This version uses an API Key to
#  authenticate which is the recommended approach unless you are running under the 
#  Process Engine which will set up a session for you automatically without requiring
#  an API key.
#
#  Last commit $Id: test_login_helper.rb 51691 2021-07-22 16:10:06Z jmonahan $
#  License: www.workbooks.com/mit_license
#

require 'logger'

class WorkbooksApiTestLoginHelper
  
  attr :workbooks
  
  def initialize    
    logger = Logger.new(STDOUT)
    logger.level = Logger::DEBUG
    logger.formatter = proc { |level, time, progname, msg| "[#{level}] #{msg}" }
    
    # Use HTTP on cruise02; HTTPS otherwise.
    service_url = (ENV['RAILS_ENV'] == 'test') ? 'http://localhost:3000' : 'https://localhost:3000'
    @workbooks = WorkbooksApi.new(
      :service => service_url,                                      # Omit this to use the production service
      :application_name => 'ruby_test_client',                      # Please give your application a useful name
      :user_agent => 'ruby_test_client/0.1',                        # Please give your application a useful label
      :api_key => '01234-56789-01234-56789-01234-56789-01234-56789',
      :logger => logger,                                            # Omit this for silence from the binding
      :http_debug_output => true,                                   # Noisy, so omit this for production use
      :verify_peer => false                                         # Omit this for production use: NOT recommended
    )
  end
  
end