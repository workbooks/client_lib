#!/usr/bin/env python3

# A Python wrapper for the Workbooks API documented at http://www.workbooks.com/api
# Required modules: requests
#
# Last commit $Id: workbooks_api.py 60964 2024-01-04 13:00:20Z jkay $
# Copyright (c) 2008-2023, Workbooks Online Limited.

# The MIT License
#
# Permission is hereby granted, free of charge, to any person obtaining a copy
# of this software and associated documentation files (the "Software"), to deal
# in the Software without restriction, including without limitation the rights
# to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
# copies of the Software, and to permit persons to whom the Software is
# furnished to do so, subject to the following conditions:
#
# The above copyright notice and this permission notice shall be included in
# all copies or substantial portions of the Software.
# 
# THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
# IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
# FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
# AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
# LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
# OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
# THE SOFTWARE.

# Significant methods in the class WorkbooksApi:
#   new                       - create an API object, specifying various options
#   login                     - authenticate
#   logout                    - terminate logged-in session
#   get                       - get a list of objects, or show an object
#   create                    - create objects
#   update                    - update objects
#   delete                    - delete objects
#   batch                     - create, update, and delete objects together
#   response                  - gather response from an asynchonous request
#   getSessionId/setSessionId - use these to connect to an existing session
#   condensedStatus           - use this to quickly check the response

# Tested on Python 3.11 on macOS Sonoma 14.2 

import logging
import json
import requests
import base64
import time
import os
import urllib.parse
import re

# WorkbooksApi raises WorkbooksApiExceptions.
class WorkbooksApiException(Exception):
    def __init__(self, workbooks_api, message):
        self.workbooks_api = workbooks_api
        self.message = message

    def __str__(self):
        return self.message + "\n" + str(self.workbooks_api)

# The API returns WorkbooksApiResponse objects. These behave a bit like hashes.
class WorkbooksApiResponse:
    def __init__(self, workbooks_api, decoded_response):
        self.workbooks_api = workbooks_api
        self.decoded_response = decoded_response
    
    def __str__(self):
        return f"WorkbooksApiResponse: {str(self.decoded_response)}"

    def __getitem__(self, elem):
        return self.decoded_response.get(elem)

    # Various shortcuts to common elements of the response
    def data(self):
        return self.decoded_response.get('data', [])

    def affected_objects(self):
        return self.decoded_response.get('affected_objects', [])

    def affected_object_information(self):
        return self.decoded_response.get('affected_object_information', [])

    def total(self):
        total = self.decoded_response.get('total')
        return int(total) if total else None

    # Evaluate the response to determine how successful it was, returns one of 'failed', 'not_ok', 'ok'
    #   'failed' - this is unexpected.
    #   'not_ok' - something in the request could not be satisfied; you should check the errors and warnings.
    #   'ok'     - completely successful.
    def condensed_status(self):
        success = self.decoded_response.get('success')
        errors = self.decoded_response.get('errors', [])
        affected_objects = self.affected_object_information()

        if success is None:
            # Unexpected failure - there should always be a 'success' element
            return 'failed'
        if not success or any(errors):
            return 'not_ok'
        if affected_objects:
            for affected in affected_objects:
                if not affected.get('success', False):
                    # There are warnings or errors indicated which prevented complete success.
                    return 'not_ok'
        return 'ok'

    # Checks the response is as expected. Raises an exception if the response is not, otherwise returns the response.
    # * expected - the expected type of response, defaults to 'ok'
    # * raise_on_error - the exception to raise if the response is not as expected.
    def assert_status(self, expected='ok', raise_on_error='Unexpected response from Workbooks API'):
        if self.condensed_status() != expected:
            raise WorkbooksApiException(self, raise_on_error)
        return self

    # Extract ids and lock_versions from the affected_objects of the response and return them as an Array of Hashes,
    # one per affected object.
    def id_versions(self):
        affected_objects = self.decoded_response.get('affected_objects', [])
        return [{'id': affected.get('id'), 'lock_version': affected.get('lock_version')} for affected in affected_objects]

class WorkbooksApi:

    API_VERSION = 1 # Used to select the default Workbooks server-side behaviour
    SESSION_COOKIE = 'Workbooks-Session'
    DEFAULT_CONNECT_TIMEOUT = 120
    DEFAULT_REQUEST_TIMEOUT = 120
    DEFAULT_SERVICE = 'https://secure.workbooks.com'

    MAX_LOG_SIZE = 1048576 # A hard limit of 1 MegaByte to limit the size of a log message from self.log()

    # The content_type governs the encoding used for data transfer to the Service. Two forms are
    # supported in this binding; use FORM_DATA for file uploads.
    FORM_URL_ENCODED = 'application/x-www-form-urlencoded'
    FORM_DATA = 'multipart/form-data'
    
    # Internal logging option for this API - normally these are not output, control via self.api_debug
    API_DEBUG_LEVEL = 'debug_api'

    # Initialise the Workbooks API. Pass in a hash of parameterss.
    #
    #   Mandatory settings
    #   - 'application_name': You should specify a descriptive name for your application such as 'Freedom Plugin' or 'Mactools Widget'
    #   - 'user_agent': A technical name for the application including version number e.g. 'Mactools/0.9.2' as defined in HTTP.
    #   Optional settings
    #   - 'service': The location of the Workbooks service (defaults to https://secure.workbooks.com)
    #   - 'api_key' or 'username': the user to login with
    #   - 'session_id': a sessionID to reconnect to
    #   - 'logical_database_id': the databaseID which the session_id is associated with
    #   - 'api_version': used to request a specific server-side behaviour. Normally this should be left as API_VERSION
    #   - 'connect_timeout': how long to wait for a connection to be established in seconds (default: 120)
    #   - 'request_timeout': how long to wait for a response in seconds (default: 120)
    #   - 'verify_peer': whether to verify the peer's SSL certificate. Set this to false for some test environments.
    #   - 'fast_login': whether to skip generating certain items (e.g. my_queues) during login
    #   - 'logger': to get logging output set to an instance of Logger in which case Logger#add is called
    #   - 'api_debug': set to True to output internal logging from this class
    #   - 'http_debug_output': if :logger is also set HTTP debug output is also generated
    #   - 'audit_lifetime_days': if set to a positive integer audit records expire and are automatically deleted 
    #   - 'json_utf8_encoding':  if set defines the on-the-wire utf8 encoding used between the server and client.
    #                           'u4' (\uNNNN) for backward compatibility, default raw utf8
    def __init__(self, params={}):
        self.application_name = params.get('application_name')
        self.user_agent = params.get('user_agent')
        self.api_version = params.get('api_version', self.API_VERSION)
        self.connect_timeout = params.get('connect_timeout', self.DEFAULT_CONNECT_TIMEOUT)
        self.request_timeout = params.get('request_timeout', self.DEFAULT_REQUEST_TIMEOUT)
        self.verify_peer = params.get('verify_peer', True)
        self.fast_login = params.get('fast_login', True)
        self.json_utf8_encoding = params.get('json_utf8_encoding', '')
        self.service = params.get('service', self.DEFAULT_SERVICE)
        self.logger = params.get('logger')
        self.audit_lifetime_days = params.get('audit_lifetime_days')
        self.http_debug_output = params.get('http_debug_output')
        self.max_log_size = params.get('max_log_size', self.MAX_LOG_SIZE)
        self.session_id = params.get('session_id')
        self.api_key = params.get('api_key')
        self.username = params.get('username')
        self.logical_database_id = params.get('logical_database_id')
        self.process_start_time = int(time.time())
        self.authenticity_token = None
        self.cookies = None
        self.last_response = None # Response object when set.
        self.api_debug = params.get('api_debug', False)

    # def __str__(self):
    #     return vars(self)
    #     return super().__str__() + "\nWorkbooksApi:XXX\n" # + str(self.workbooks_api) + "\n" + message +

    # A simple logging interface which sends messages to the logger (if one is set up).
    # * msg a string to be logged.
    # * expression any values to output with the message.
    # * level optional: as supported by Logger (or API_DEBUG_LEVEL for internal logging, enabled via self.api_debug).
    # * log_size_limit the maximum size msg that will be logged.
    # Returns the expression which was passed in.
    def log(self, msg, expression=None, level=logging.DEBUG, log_size_limit=4096):
        if level == self.API_DEBUG_LEVEL and not self.api_debug:
            return None
        if self.logger:
            if expression is not None:
                msg += f" «{expression}»"
            log_size_limit = min(log_size_limit, self.max_log_size)
            if len(msg) > log_size_limit:
                msg = f"{msg[:log_size_limit//2]} ... ({len(msg)-log_size_limit} bytes) ... {msg[-log_size_limit//2:]}"
            if level == self.API_DEBUG_LEVEL:
                level = logging.DEBUG
            self.logger.log(level, f"{msg}\n\n")
        return expression

    # Get the active database as a string to embed in a URL (useful for web processes).
    def get_database_instance_ref(self):
        self.ensure_login()
        return base64.encodebytes(str(int(self.database_instance_id)+17).encode()).rstrip().decode()[::-1]

    # Get the elapsed time since the process started in seconds
    # returns (int) the elapsed time in seconds
    def get_elapsed_process_time(self):
        return int(time.time()) - self.process_start_time

    # Get the process time allowed in seconds or nil if not set
    # returns (int) the process time allowed in seconds or nil if not set
    def get_process_timeout(self):
        return int(os.environ['TIMEOUT']) if 'TIMEOUT' in os.environ else None

    # Get the process time remaining in seconds or None if no timeout is set
    # returns (int) the time remaining in seconds or None
    def get_process_time_remaining(self):
        process_timeout = self.get_process_timeout()
        if process_timeout is not None:
            time_left = process_timeout - self.get_elapsed_process_time()
            return max(time_left, 0)
        return None

    # Helper method which evaluates a response, see WorkbooksApiResponse#condensed_status.
    def condensed_status(self, response):
        return response.condensed_status()

    # Check responses are expected, see WorkbooksApiResponse#assert. 
    # Raises an exception if the response is not expected, otherwise returns the response.
    # * response - a response from the service API.
    # * expected - the expected type of response, defaults to 'ok'.
    # * raise_on_error - the exception to raise if the response is not as expected.
    def assert_response(self, response, expected='ok', raise_on_error='Unexpected response from Workbooks API'):
        return response.assert_status(expected, raise_on_error)

    # Extract ids and lock_versions from the 'affected_objects', see WorkbooksApiResponse#id_versions.
    def id_versions(self, response):
        return response.id_versions()

    def uri_encode(self, string):
        return urllib.parse.quote(string)

    # Login to the service to set up a session.
    #   Optional settings, specified in a hash represenet credentials and other options to the login API endpoint
    #   - api_key: An API key (this is preferred over username/password).
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
    def login(self, params=None):
        if params is None:
            params = {}

        params.setdefault('api_key', self.api_key)
        params.setdefault('username', self.username)

        if not params.get('api_key') and not params.get('username'):
            raise WorkbooksApiException(self, 'An api_key or a username must be supplied')

        if params.get('api_key') is None and params.get('password') is None and params.get('session_id') is None:
            raise WorkbooksApiException(self, 'A password or session_id must be supplied unless using an api_key')

        if params.get('logical_database_id') is None and params.get('session_id') and params.get('password') is None:
            raise WorkbooksApiException(self, 'A logical_database_id must be supplied when trying to re-connect to a session')

        # These default settings can be overridden by the caller.
        params.setdefault('_application_name', self.application_name)
        params.setdefault('_strict_attribute_checking', True)
        params.setdefault('api_version', self.api_version)
        params.setdefault('_fast_login', self.fast_login)
        if self.json_utf8_encoding and self.json_utf8_encoding != '':
            params['json_utf8_encoding'] = self.json_utf8_encoding

        self.last_response = self.make_request('login.api', 'post', params)
        parsed_response = None
        try:
            parsed_response = self.last_response.json()
        except requests.exceptions.JSONDecodeError:
            pass

        # The authenticity_token is valid for a specific session and is required when any modifications are attempted.
        if isinstance(self.last_response, requests.models.Response) and self.last_response.status_code == requests.codes.ok:
            self.logged_in = True
            if parsed_response.get('my_queues'):
                self.user_queues = [{str(queue_name): queue_id} for queue_name, queue_id in parsed_response['my_queues'].items()]
            self.authenticity_token = parsed_response.get('authenticity_token')
            self.database_instance_id = parsed_response.get('database_instance_id')
            self.login_response = parsed_response

        retval = {
            'http_status': response.status_code,
            'failure_message': parsed_response.get('failure_message'),
            'response': WorkbooksApiResponse(self, parsed_response),
        }

        return retval

    # Logout from the service.
    # Returns a hash of 'success' - whether it succeeded, 'http_status'
    # A successful logout will return a 'success' value of True
    def logout(self):
        self.last_response = self.make_request('logout', 'post', {}, [], {'follow_redirects':False})
        self.logged_in = False  # force a login regardless of the server-side state
        self.authenticity_token = None

        retval = {
            'success': isinstance(self.last_response, requests.models.Response) and self.last_response.is_redirect,
            'http_status': self.last_response.status_code
        }

        return retval

    # Make a request to an endpoint on the service to read or list objects. You must have logged in first
    # * endpoint - selects the portion of the API to use, e.g. 'crm/organisations'
    # * params - the parameters to the API call - filter, limit, column selection as a hash
    #   each hash element can have a simple value or be an array of values e.g. for column selection.
    # * options - (optional) options to pass through to make_request() potentially including :content_type. 
    # Returns the decoded json response as a WorkbooksApiResponse if 'decode_json' is True (default), or the raw response if not.
    # As usual, check the API documentation for further information.
    def get(self, endpoint, params=None, options=None):
        if options is None:
            options = {}

        url_encode = options.get('content_type', True) if 'content_type' in options else True

        array_params = []  # For storing parameters where the value is an array, not a single value
        params = params if params is not None else {}
        params = params.copy()

        for key, value in list(params.items()):  # Using list() to avoid dictionary size change during iteration
            if isinstance(value, list):
                if str(key) == '_filters[]':
                    if not isinstance(value[0], list):  # Handling a single filter case
                        value = [value]

                    for filter_item in value:
                        array_params.append('_ff[]=' + (urllib.parse.quote(filter_item[0]) if url_encode else filter_item[0]))
                        array_params.append('_ft[]=' + (urllib.parse.quote(filter_item[1]) if url_encode else filter_item[1]))
                        array_params.append('_fc[]=' + (urllib.parse.quote(filter_item[2]) if filter_item[2] is not None and url_encode else str(filter_item[2])))

                else:
                    for array_value in value:
                        array_params.append(key + '=' + (urllib.parse.quote(array_value) if url_encode else array_value))

                del params[key]  # Remove items just processed

        return self.api_call(endpoint, 'get', params, array_params, options)

    # Interface as per get() but if the response is not 'ok' it also logs an error and raises an exception.
    def assert_get(self, *args):
        response = self.get(*args)
        self.assert_response(response)
        return response
        
    # Make a request to an endpoint on the service to create objects. You must have logged in first.
    # * endpoint - selects the portion of the API to use, e.g. 'crm/organisations'.
    # * objs - an array of objects to create
    # * params a set of additional parameters to send along with the data, for example
    #   '_per_object_transactions' True to change the commit behaviour.
    # * options - as hash to pass through to make_request()
    # Returns the decoded json response as a WorkbooksApiResponse if 'decode_json' is True (default), or the raw response if not.
    # As usual, check the API documentation for further information.
    def create(self, endpoint, objs, params=None, options=None):
        return self.batch(endpoint, objs, params, 'create', options)

    # Interface as per create() but if the response is not 'ok' it also logs an error and raises an exception.
    def assert_create(self, *args):
        response = self.create(*args)
        self.assert_response(response)
        return response

    # Make a request to an endpoint on the service to update objects. You must have logged in first.
    # * endpoint - selects the portion of the API to use, e.g. 'crm/organisations'.
    # * objs - an array of objects to update, specifying the id and lock_version of each together with the values to set.
    # * params a set of additional parameters to send along with the data, for example
    #   '_per_object_transactions' True to change the commit behaviour.
    # * options - as hash to pass through to make_request()
    # Returns the decoded json response as a WorkbooksApiResponse if 'decode_json' is True (default), or the raw response if not.
    # As usual, check the API documentation for further information.
    def update(self, endpoint, objs, params=None, options=None):
        return self.batch(endpoint, objs, params, 'update', options)

    # Interface as per update() but if the response is not 'ok' it also logs an error and raises an exception.
    def assert_update(self, *args):
        response = self.update(*args)
        self.assert_response(response)
        return response

    # Make a request to an endpoint on the service to delete objects. You must have logged in first.
    # * endpoint - selects the portion of the API to use, e.g. 'crm/organisations'.
    # * objs - an array of objects to delete, specifying the id and lock_version of each.
    # * params a set of additional parameters to send along with the data, for example
    #   '_per_object_transactions' True to change the commit behaviour.
    # * options - as hash to pass through to make_request()
    # Returns the decoded json response as a WorkbooksApiResponse if 'decode_json' is True (default), or the raw response if not.
    # As usual, check the API documentation for further information.
    def delete(self, endpoint, objs, params=None, options=None):
        return self.batch(endpoint, objs, params, 'delete', options)

    # Interface as per delete() but if the response is not 'ok' it also logs an error and raises an exception.
    def assert_delete(self, *args):
        response = self.delete(*args)
        self.assert_response(response)
        return response
        
    # Make a request to an endpoint on the service to operate on multiple objects. You must have logged in first.
    # You can request a combination of 'create', 'update' and 'delete' operations, to be batched together.
    # This is the core method upon which other methods are implemented which perform a subset of these operations.
    # * endpoint - selects the portion of the API to use, e.g. 'crm/organisations'.
    # * objs - an array of objects to create, update or delete.
    # * params a set of additional parameters to send along with the data, for example
    #   '_per_object_transactions' True to change the commit behaviour.
    # * method - the method ('create', 'update' or 'delete') which is to be used if not specified for an object.
    # * options - as hash to pass through to make_request()
    # Returns the decoded json response as a WorkbooksApiResponse if 'decode_json' is True (default), or the raw response if not.
    # As usual, check the API documentation for further information.
    def batch(self, endpoint, objs, params=None, method=None, options=None):
        self.log('batch() called', {'endpoint': endpoint, 'objs': objs, 'params': params, 'method': method, 'options': options}, self.API_DEBUG_LEVEL)
        
        objs = objs if isinstance(objs, list) else [objs]  # If just one object was passed in, turn it into a list.

        filter_params = self.encode_method_params(objs, method)
        url_encode = options.get('content_type', True) if options and 'content_type' in options else True
        ordered_post_params = self.full_square(objs, url_encode)
        response = self.api_call(endpoint, 'put', params, filter_params + ordered_post_params, options)

        self.log('batch() returns', response, self.API_DEBUG_LEVEL)
        return response

    # Interface as per batch() but if the response is not 'ok' it also logs an error and raises an exception.
    def assert_batch(self, *args):
        response = self.batch(*args)
        self.assert_response(response)
        return response

    # Ensure we are logged in
    def ensure_login(self):
        if not self.logged_in:
            self.login()
    
    # Make a call to an endpoint on the service.
    # * endpoint - selects the portion of the API to use, e.g. 'crm/organisations'.
    # * method - the restful method: one of 'get', 'put', 'post', 'delete'.
    # * post_params - a hash of uniquely-named parameters to add to the POST body.
    # * ordered_post_params - a simple array of additional parameters, to use for the POST body (may have duplicate keys e.g. 'id[]')
    # * options - extras to pass through to make_request()
    # Returns the decoded json response as a WorkbooksApiResponse if 'decode_json' is True (default), or the raw response if not.
    # As usual, check the API documentation for further information.
    def api_call(self, endpoint, method, post_params=None, ordered_post_params=None, options=None):
        self.log('api_call() called', {
            'endpoint': endpoint,
            'method': method,
            'post_params': post_params,
            'ordered_post_params': ordered_post_params,
            'options': options
        }, self.API_DEBUG_LEVEL)
        
        if post_params is None:
            post_params = {}
        if ordered_post_params is None:
            ordered_post_params = []
        if options is None:
            options = {}

        if self.api_key:
            post_params.setdefault('api_key', self.api_key)
            post_params.setdefault('api_version', self.api_version)
            post_params.setdefault('application_name', self.application_name)
            post_params.setdefault('user_agent', self.user_agent)
            if self.json_utf8_encoding:
                post_params['json_utf8_encoding'] = self.json_utf8_encoding
        else:
            self.ensure_login()

        # Ensure the endpoint format for API calls
        if not endpoint.endswith('.api'):
            endpoint += '.api'

        self.last_response = self.make_request(endpoint, method, post_params, ordered_post_params, options)
        if self.last_response.status_code != 200:
            raise WorkbooksApiException(self, f"Non-OK response ({self.last_response.status_code})")

        if options.get('decode_json', True):
            try:
                retval = WorkbooksApiResponse(self, self.last_response.json())
            except requests.exceptions.JSONDecodeError as e:
                raise WorkbooksApiException(self, "JSON decode failure")
        else:
            retval = self.last_response.text

        self.log('api_call() returns', retval, self.API_DEBUG_LEVEL)
        return retval

    # Builds and sends an HTTP request.
    # Assuming the service can be contacted a Net::HTTPResponse object is returned, otherwise an exception is raised.
    # * endpoint - selects the portion of the API to use, e.g. 'crm/organisations'.
    # * method - the restful method - one of 'get', 'put', 'post', 'delete'
    # * post_params - a hash of uniquely-named parameters to add to the POST body.
    # * ordered_post_params - a simple array of additional parameters, to use for the POST body (may have duplicate keys e.g. 'id[]')
    # * options - additional options...
    #      * 'content_type' which defaults to 'application/x-www-form-urlencoded'
    #      * 'follow_redirects' which defaults to True (the service may issue redirects a rate limiting measure)
    # Returns a Response object.
    def make_request(self, endpoint, method, post_params={}, ordered_post_params=[], options={}):
        log_data = {
            'endpoint': endpoint,
            'method': method,
            'post_params': post_params,
            'ordered_post_params': ordered_post_params,
            'options': options
        }
        self.log('make_request() called', log_data, self.API_DEBUG_LEVEL)
    
        start_time = time.time()

        content_type = options.get('content_type', 'application/x-www-form-urlencoded')
        url_params = {
            '_dc': int(start_time * 1000)  # cache-buster
        }
    
        # Add a request parameter _max_request_duration to tell the server how long this process client has left
        process_time_remaining = self.get_process_time_remaining()
        if process_time_remaining:
            url_params['_max_request_duration'] = process_time_remaining
    
        url = self.get_url(endpoint, url_params)
        post_params.setdefault('_method', method.upper())
        post_params.setdefault('client', 'api')
        if method != 'get' and self.authenticity_token:
            post_params.setdefault('_authenticity_token',self.authenticity_token)
        post_params.setdefault('_audit_lifetime_days', self.audit_lifetime_days)

        post_fields = None
    
        if content_type == 'application/x-www-form-urlencoded':
            post_fields = urllib.parse.urlencode(post_params)
            if ordered_post_params and len(ordered_post_params) > 0:
                ordered_post_params = [ordered_post_params] if not isinstance(ordered_post_params, list) else ordered_post_params
                post_fields = post_fields + '&' + '&'.join(ordered_post_params)
        else:
            fields = []
            for key, value in post_params.items():
                if isinstance(value, list):
                    for v in value:
                        fields.append({key: v})
                else:
                    fields.append({key: value})
            for p in ordered_post_params:
                if isinstance(p, str):
                    key, value = p.split('=', 1)
                    fields.append({key: value})
                else:
                    fields.append(p)
        
            boundary = "----------------------------form-data-" + '%08x%08x%08x' % (randrange(0xffffffff), int(start_time), randrange(0xffffffff))
            content_type = f"multipart/form-data; boundary={boundary}"
        
            body = []
            for f in fields:
                for key, value in f.items():
                    if isinstance(value, dict) and 'file' in value and isinstance(value['file'], File):
                        body.append(f"--{boundary}")
                        body.append(f"Content-Disposition: form-data; name=\"{key}\"; filename=\"{os.path.basename(value['file_name'])}\"")
                        body.append(f"Content-Type: {value['file_content_type']}")
                        body.append('')
                        body.append(value['file'].read())
                    else:
                        body.append(f"--{boundary}")
                        body.append(f"Content-Disposition: form-data; name=\"{key}\"")
                        body.append('')
                        body.append(value)
        
            body.append(f"--{boundary}--")
            body.append('')
        
            post_fields = '\r\n'.join(str(e) for e in body)

        self.log("post_fields, first 1000 bytes", post_fields[:1000], self.API_DEBUG_LEVEL)

        response = None
        session = requests.Session()
    
        while True:
            headers = { 'Content-Type': content_type }
            if self.cookies:
                headers['Cookie'] = '; '.join(cookie.split(';')[0] for cookie in self.cookies)
            if self.user_agent:
                headers['User-Agent'] = self.user_agent

            response = session.request(method, url, data=post_fields, headers=headers)
        
            if not options.get('follow_redirects', True) or response.status_code != 302:
                break
    
        end_time = time.time()
        self.last_request_duration = end_time - start_time
    
        # Process response cookies.
        cookie_header = response.headers.get('Set-Cookie')
        if cookie_header:
            self.cookies = cookie_header.split(', ')
            for cookie in self.cookies:
                matches = re.match(rf'^{self.SESSION_COOKIE}=([^;]+)', cookie)
                if matches:
                    self.session_id = matches[1]
    
        self.log('make_request() returns', response, self.API_DEBUG_LEVEL)
        return response

    # Depending on the method (create/update/delete) the objects passed to Workbooks
    # have certain minimum requirements. Callers may specify a method for each object
    # or assume the same operation for all objects.
    # * obj_array - an array of objects to be encoded, modified in place
    # * method - (create/update/delete) which is to be used if not specified for an object (can be None: an error if unspecified)
    # Returns an array representing the filter which is required to define the working set of objects...
    def encode_method_params(self, obj_array, method):
        self.log('encode_method_params() called', { 'obj_array': obj_array, 'method': method }, self.API_DEBUG_LEVEL)
    
        filter_ids = []
    
        for obj in obj_array:
            obj_method = method
            if 'method' in obj:
                obj_method = obj['method']
                del obj['method']
        
            if obj_method == 'create':
                obj['__method'] = 'POST'
                if ('id' in obj and obj['id'] > 0) or ('lock_version' in obj and obj['lock_version'] > 0):
                    raise WorkbooksApiException(self, 'Neither "id" nor "lock_version" can be set to create an object')
                obj['id'] = 0
                obj['lock_version'] = 0
                filter_ids.append(0)

            elif obj_method == 'update':
                obj['__method'] = 'PUT'
                if 'id' not in obj or obj['id'] == 0 or 'lock_version' not in obj:
                    raise WorkbooksApiException(self, 'Both "id" and "lock_version" must be set to update an object')
                filter_ids.append(obj['id'])

            elif obj_method == 'delete':
                obj['__method'] = 'DELETE'
                if 'id' not in obj or obj['id'] == 0 or 'lock_version' not in obj:
                    raise WorkbooksApiException(self, 'Both "id" and "lock_version" must be set to delete an object')
                filter_ids.append(obj['id'])

            else:
                raise WorkbooksApiException(self, f'Unexpected method: {method}')
    
        # Must include a filter to 'select' the set of objects being operated upon
        filter = []
        filter_ids = list(set(filter_ids))  # Remove duplicates
    
        if len(filter_ids) > 0:
            if len(filter_ids) > 1:
                filter.append('_fm=or')
            for filter_id in filter_ids:
                filter.extend(['_ff[]=id', '_ft[]=eq', f'_fc[]={filter_id}'])
    
        self.log('encode_method_params() returns', filter, self.API_DEBUG_LEVEL)
        return filter

    # The Workbooks wire protocol requires that each key which is used in any object be
    # present in all objects, and delivered in the right order. Callers of this binding
    # library will omit keys from some objects and not from others. Some special values
    # are used in this encoding - :null_value: and :no_value:.
    # 
    # * obj_array - an array of objects to be encoded
    # * url_encode - a boolean: whether to URL encode them, defaults to true
    # Returns an array which is the (encoded) set of objects suitable for passing to Workbooks
    def full_square(self, obj_array, url_encode=True):
        self.log('full_square() called', { 'obj_array': obj_array, 'url_encode': url_encode }, self.API_DEBUG_LEVEL)

        retval = []

        # Get the full set of hash keys for all of the objects in obj_array
        unique_keys = sorted(set().union(*[list(obj.keys()) for obj in obj_array]))

        # The full square array is one with a value for every key in every object
        for obj in obj_array:
            for key in unique_keys:
                value = obj.get(key)
                if key not in obj:
                    value = ':no_value:'
                elif obj[key] is None:
                    value = ':null_value:'

                unnested_key = self.unnest_key(key)

                if isinstance(value, dict) and 'file' in value: # and isinstance(value['file'], File):
                    retval.append({f"{unnested_key}[]": value})
                elif isinstance(value, list):
                    new_val = '[' + ','.join(map(str, value)) + ']'
                    if url_encode:
                        retval.append(f"{urllib.parse.quote(unnested_key)}[]={urllib.parse.quote(new_val)}")
                    else:
                        retval.append(f"{unnested_key}[]={new_val}")
                else:
                    if url_encode:
                        retval.append(f"{urllib.parse.quote(unnested_key)}[]={urllib.parse.quote(str(value))}")
                    else:
                        retval.append(f"{unnested_key}[]={value}")

        self.log('full_square() returns', retval, self.API_DEBUG_LEVEL)
        return retval

    # Normalise any nested keys so they have the expected format for the wire, i.e. 
    # convert things like this:
    #   org_lead_party[main_location[email]]
    # into this:   
    #   org_lead_party[main_location][email]
    # 
    # Parameter: attribute_name - the attribute name with potentially nested square brackets
    # Returns the unnested attribute name
    def unnest_key(self, attribute_name):
        self.log('unnest_key() called', { 'attribute_name': attribute_name }, self.API_DEBUG_LEVEL)

        retval = str(attribute_name)

        if retval.endswith(']]'):  # If it does not end in ']]', then it is not a nested key.
            parts = retval.split('[')
            retval = parts[0] + '[' + ']['.join(parts[1:])  # Nest the key properly

        self.log('unnest_key() returns', retval, self.API_DEBUG_LEVEL)
        return retval

    # URL encode and concatenate fields, separating them with an ampersand
    def url_encode_fields(self, fields):
        self.log('url_encode_fields() called', { 'fields': fields }, self.API_DEBUG_LEVEL)

        retval = '&'.join([f"{urllib.parse.quote(str(k))}={urllib.parse.quote(str(v))}" if not isinstance(v, list)
                           else '&'.join([f"{urllib.parse.quote(str(k))}={urllib.parse.quote(str(e))}" for e in v])
                           for k, v in fields.items()])

        self.log('url_encode_fields() returns', retval, self.API_DEBUG_LEVEL)
        return retval

    # Construct a URL for the current Workbooks service including path and parameters.
    # Parameters:
    # * - the path
    # * - optional array of query params to append
    # Returns the URL for the given parameters
    def get_url(self, path, query_params=None):
        self.log('get_url() called', { 'path': path, 'query_params': query_params }, self.API_DEBUG_LEVEL)

        url = self.service
        if path[0] != '/':
            url += '/'
        url += path

        if query_params:
            url += '?' + self.url_encode_fields(query_params)

        self.log('get_url() returns', url, self.API_DEBUG_LEVEL)
        return url
