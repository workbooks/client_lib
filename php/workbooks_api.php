<?php

/**
 *   A PHP wrapper for the Workbooks API documented at http://www.workbooks.com/api
 *
 *   Last commit $Id: workbooks_api.php 17456 2012-10-05 14:53:59Z jkay $
 *
 *       The MIT License
 *
 *       Copyright (c) 2008-2011, Workbooks Online Limited.
 *       
 *       Permission is hereby granted, free of charge, to any person obtaining a copy
 *       of this software and associated documentation files (the "Software"), to deal
 *       in the Software without restriction, including without limitation the rights
 *       to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 *       copies of the Software, and to permit persons to whom the Software is
 *       furnished to do so, subject to the following conditions:
 *       
 *       The above copyright notice and this permission notice shall be included in
 *       all copies or substantial portions of the Software.
 *       
 *       THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *       IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *       FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 *       AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 *       LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 *       OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 *       THE SOFTWARE.
 *
 *
 *   Significant methods in the class Workbooks:
 *     new                       - create an API object, specifying various options
 *     login                     - authenticate
 *     logout                    - terminate logged-in session (automatic when object destroyed)
 *     get                       - get a list of objects, or show an object
 *     create                    - create objects
 *     update                    - update objects
 *     delete                    - delete objects
 *     batch                     - create, update, and delete objects together
 *     getSessionId/setSessionId - use these to connect to an existing session
 *     condensedStatus           - use this to quickly check the response
 *
 *   NOTE: When this file is included, if certain values with names beginning '_workbooks_' 
 *         exist in the array '$params' then those values are used to establish a connection
 *         back to the Workbooks service, putting a handle to it in the variable '$workbooks'.
 *         This technique is used by scripts running under the Workbooks Process Engine. 
 *
 *   Requirements: Uses CURL and JSON PHP extensions since these are the most widely available.
 */

if (!function_exists('curl_init')) {
  throw new Exception('workbooks.php uses the CURL PHP extension');
}
if (!function_exists('json_decode')) {
  throw new Exception('workbooks.php uses the JSON PHP extension');
}


/**
 * Thrown when an API call returns an exception.
 */
class WorkbooksApiException extends Exception
{
  /**
   * The result from the API server that represents the exception information.
   */
  protected $result;

  /**
   * Make a new API Exception with the given result.
   *
   * @param Array $result the result from the API server
   */
  public function __construct($result) {
    $msg = $result['error']['message'];
    $code = isset($result['error_code']) ? $result['error_code'] : 0;    
    // If we have access to the Workbooks API object, log all that we can
    if (isset($result['workbooks_api'])) {
      $result['workbooks_api']->log('new WorkbooksApiException', array($msg, $code, $this));
    }

    parent::__construct($msg, $code);
  }
}


class WorkbooksApi
{

  const VERSION = '1.1';

  /**
   * Instance variables
   */
  protected $curl_handle = NULL;
  protected $curl_options = NULL;
  protected $logger_callback = NULL;
  protected $session_id = NULL;
  protected $api_key = NULL;
  protected $username = NULL;
  protected $logical_database_id = NULL;
  protected $authenticity_token = NULL;
  protected $api_logging_key = NULL; // when available requests server-side logging of API requests/responses
  protected $api_logging_seq = 0;    // used for server-side logging of API requests/responses
  protected $login_state = false;    // true => logged in
  protected $auto_logout = true;     // true => call logout() in destroy hook
  protected $application_name = NULL;
  protected $user_agent = NULL;
  protected $connect_timeout = 120;  // 2 minutes
  protected $request_timeout = 120;
  protected $verify_peer = true;     // false is not correct for Production use.
  protected $service = 'https://secure.workbooks.com';
  protected $last_request_duration = NULL;
  protected $user_queues = NULL;     // when logged in contains an array of user queues

  /**
   * Those HTTP status codes of particular significance to the API.
   */
  const HTTP_STATUS_OK = 200;
  const HTTP_STATUS_FOUND = 302;
  const HTTP_STATUS_FORBIDDEN = 403;

  /**
   * The Workbooks session cookie
   */
  const SESSION_COOKIE = 'Workbooks-Session';

  /**
   * Initialise the Workbooks API
   *
   * The configuration:
   *   Mandatory settings
   *   - application_name: You should specify a descriptive name for your application such as 'Freedom Plugin' or 'Mactools Widget'
   *   - user_agent: A technical name for the application including version number e.g. 'Mactools/0.9.2' as defined in HTTP.
   *   Optional settings
   *   - service: The FQDN of the Workbooks service (defaults to secure.workbooks.com)
   *   - logger_callback: an array of (class, name of function) to pass debugging output to. The function takes two 
   *       string arguments: msg and level.  In the absence of a logger_callback, no logging is done by this library.
   *       WorkbooksApi::logAllToStdout() is provided as an example: pass
   *         array('Workbooks', 'logAllToStdout').
   *   - api_key or username: the user to login with
   *   - session_id: a sessionID to reconnect to
   *   - logical_database_id: the databaseID which the session_id is associated with
   *   - api_logging_key: if specified this is used to identify a Process Log to attach API logging records to
   *   - connect_timeout: how long to wait for a connection to be established in seconds (default: 20)
   *   - request_timeout: how long to wait for a response in seconds (default: 20)
   *   - verify_peer: whether to verify the peer's SSL certificate. Set this to false for some test environments but do not 
   *       do this in Production.
   * @param Array $params the Workbooks connection configuration
   */
  public function __construct($params) {
    if (isset($params['logger_callback'])) {
      $this->setLoggerCallback($params['logger_callback']);
    }
    // $this->log('new() called with params', $params);

    if (isset($params['application_name'])) {
      $this->setApplicationName($params['application_name']);
    } else {
      throw new Exception('An application name must be supplied');
    }

    if (isset($params['user_agent'])) {
      $this->setUserAgent($params['user_agent']);
    } else {
      throw new Exception('A user agent must be supplied');
    }

    if (isset($params['service'])) {
      $this->setService($params['service']);
    }

    if (isset($params['connect_timeout'])) {
      $this->setConnectTimeout($params['connect_timeout']);
    }
    
    if (isset($params['request_timeout'])) {
      $this->setRequestTimeout($params['request_timeout']);
    }

    if (isset($params['api_key'])) {
      $this->setApiKey($params['api_key']);
    }
    
    if (isset($params['username'])) {
      $this->setUsername($params['username']);
    }
    
    if (isset($params['session_id'])) {
      $this->setSessionId($params['session_id']);
    }
    
    if (isset($params['logical_database_id'])) {
      $this->setLogicalDatabaseId($params['logical_database_id']);
    }
    
    if (isset($params['api_logging_key'])) {
      $this->setApiLoggingKey($params['api_logging_key']);
      $this->resetApiLoggingSeq();
    }
    
    if (isset($params['verify_peer'])) {
      $this->setVerifyPeer($params['verify_peer']);
    }

    $curl_handle = curl_init();
    $this->setCurlHandle($curl_handle);

    $this->curl_options = array(
      CURLOPT_USERAGENT      => $this->getUserAgent(),
      CURLOPT_CONNECTTIMEOUT => $this->getConnectTimeout(),
      CURLOPT_TIMEOUT        => $this->getRequestTimeout(),
      CURLOPT_SSL_VERIFYPEER => $this->getVerifyPeer()
    );
    
    // $this->log('new() returns', $this);
    return $this;
  }
  
  /**
   * Shutdown the Workbooks API. Logout will happen automatically when this goes out of scope 
   * unless you call setAutoLogout to change its behaviour.
   */
  public function __destruct() {
    if (isset($this->curl_handle)){
      if ($this->getLoginState() && $this->getAutoLogout()) {
        $this->logout();
      }
      $this->destroyCurlHandle();
    }
  }

  /**
   * Set the Curl handle.
   *
   * @param Resource $curl_handle a Curl handle as might be returned by curl_init()
   */
  public function setCurlHandle($curl_handle) {
    $this->curl_handle = $curl_handle;
    return $this;
  }

  /**
   * Get the Curl handle.
   *
   * @return Resource $curl_handle a Curl handle as might be returned by curl_init()
   */
  public function getCurlHandle() {
    return $this->curl_handle;
  }
  
  /**
   * Clear down the Curl handle, destroying it.
   */
  public function destroyCurlHandle() {
    if (isset($this->curl_handle)) {
      curl_close($this->curl_handle);
      unset($this->curl_handle);
    }
  }

  /**
   * Set the last request duration.
   *
   * @param Float $last_request_duration the duration of the last request, in seconds
   */
  protected function setLastRequestDuration($last_request_duration) {
    $this->last_request_duration = $last_request_duration;
    return $this;
  }
  
  /**
   * Get the last request duration.
   *
   * @return Float $last_request_duration the duration of the last request, in seconds
   */
  public function getLastRequestDuration() {
    return round($this->last_request_duration,6);
  }

  /**
   * Set the user_queues list.
   *
   * @param Array $user_queues - an array of queues as returned at login time
   */
  protected function setUserQueues(&$user_queues) {
    $this->user_queues = $user_queues;
    return $user_queues;
  }
  
  /**
   * Get the user_queues list.
   *
   * @return Array $user_queues - an array of queues as returned at login time
   */
  public function getUserQueues() {
    return $this->user_queues;
  }

  /**
   * Set the application name.
   *
   * @param String $application_name the application name
   */
  public function setApplicationName($application_name) {
    $this->application_name = $application_name;
    return $this;
  }

  /**
   * Get the application name.
   *
   * @return String the application name
   */
  public function getApplicationName() {
    return $this->application_name;
  }
  
  /**
   * Set the user agent string.
   *
   * @param String $user_agent the user agent string
   */
  public function setUserAgent($user_agent) {
    $this->user_agent = $user_agent;
    return $this;
  }

  /**
   * Get the user agent string.
   *
   * @return String the user agent string
   */
  public function getUserAgent() {
    return $this->user_agent;
  }

  /**
   * Set the connect timeout.
   *
   * @param Integer $connect_timeout the connect timeout
   */
  public function setConnectTimeout($connect_timeout) {
    $this->connect_timeout = $connect_timeout;
    if ($this->curl_handle != NULL) {
      curl_setopt($this->curl_handle, CURLOPT_CONNECTTIMEOUT, $connect_timeout);
    }
    return $this;
  }

  /**
   * Get the connect timeout.
   *
   * @return Integer the connect timeout
   */
  public function getConnectTimeout() {
    return $this->connect_timeout;
  }

  /**
   * Set the request timeout.
   *
   * @param Integer $request_timeout the request timeout
   */
  public function setRequestTimeout($request_timeout) {
    $this->request_timeout = $request_timeout;
    if ($this->curl_handle != NULL) {
      curl_setopt($this->curl_handle, CURLOPT_TIMEOUT, $request_timeout);
    }
    return $this;
  }

  /**
   * Get the request timeout.
   *
   * @return Integer the request timeout
   */
  public function getRequestTimeout() {
    return $this->request_timeout;
  }

  /**
   * Set the API Key used to login.
   *
   * @param String $api_key the API key
   */
  public function setApiKey($api_key) {
    $this->api_key = $api_key;
    return $this;
  }

  /**
   * Get the API Key used to login.
   *
   * @return String $api_key the API key
   */
  public function getApiKey() {
    return $this->api_key;
  }

  /**
   * Set the user name used to login/reconnect.
   *
   * @param String $username the login name
   */
  public function setUsername($username) {
    $this->username = $username;
    return $this;
  }

  /**
   * Get the user name used to login/reconnect.
   *
   * @return String $username the login name
   */
  public function getUsername() {
    return $this->username;
  }

  /**
   * Set the logical database ID used to login/reconnect.
   *
   * @param Integer $logical_database_id the ID of the database the session is associated with
   */
  public function setLogicalDatabaseId($logical_database_id) {
    $this->logical_database_id = $logical_database_id;
    return $this;
  }

  /**
   * Get the logical database ID used to login/reconnect.
   *
   * @return Integer $logical_database_id the ID of the database the session is associated with
   */
  public function getLogicalDatabaseId() {
    return $this->logical_database_id;
  }

  /**
   * Set the API logging key: this is used to associate API request/response log 
   * records with the log for an invocation of a Process. Only useful when running
   * within the Process Engine environment.
   *
   * @param String $api_logging_key the API logging key
   */
  public function setApiLoggingKey($api_logging_key) {
    $this->api_logging_key = $api_logging_key;
    return $this;
  }

  /**
   * Get the API logging key.
   *
   * @return String the API logging key
   */
  public function getApiLoggingKey() {
    return $this->api_logging_key;
  }
  
  /**
   * Reset the API logging sequence number which is used in conjunction with API request/response
   * log records. Only useful when running within the Process Engine environment. Each request
   * gets a new sequence number.
   */
  public function resetApiLoggingSeq() {
    $this->api_logging_seq = 0;
    return $this;
  }

  /**
   * Get the next API logging sequence number.
   *
   * @return Integer the API logging sequence number
   */
  public function nextApiLoggingSeq() {
    $this->api_logging_seq++;
    return $this->api_logging_seq;
  }
  
  /**
   * Set the flag for verifying the peer's SSL certificate. When connecting to test servers
   * you may need to turn peer verification off.
   *
   * @param Boolean $verify_peer whether to verify the peer's SSL certificate
   */
  public function setVerifyPeer($verify_peer) {
    $this->verify_peer = $verify_peer;
    return $this;
  }

  /**
   * Get the request timeout.
   *
   * @return Boolean whether to verify the peer's SSL certificate
   */
  public function getVerifyPeer() {
    return $this->verify_peer;
  }

  /**
   * Set the service name.
   *
   * @param String $service the FQDN of the Workbooks service
   */
  public function setService($service) {
    $this->service = $service;
    return $this;
  }

  /**
   * Get the service name.
   *
   * @return String the service name
   */
  public function getService() {
    return $this->service;
  }

  /**
   * Set the login_state.
   *
   * @param Boolean $login_state true if logged in, false otherwise
   */
  public function setLoginState($login_state) {
    $this->login_state = $login_state;
    return $this;
  }

  /**
   * Get the login_state.
   *
   * @return Boolean true if logged in, false otherwise
   */
  public function getLoginState() {
    return $this->login_state;
  }
  
  /**
   * Set the auto_logout flag.
   *
   * @param Boolean $auto_logout true to logout automatically, false to leave the session
   */
  public function setAutoLogout($auto_logout) {
    $this->auto_logout = $auto_logout;
    return $this;
  }
  
  /**
   * Get the auto_logout flag.
   *
   * @return Boolean $auto_logout true to logout automatically, false to leave the session
   */
  public function getAutoLogout() {
    return $this->auto_logout;
  }

  
  /**
   * Set the Authenticity Token. This is unique to each session.
   *
   * @param String $authenticity_token the Authenticity Token.
   */
  protected function setAuthenticityToken($authenticity_token) {
    $this->authenticity_token = $authenticity_token;
    return $this;
  }

  /**
   * Get the Authenticity Token
   *
   * @return String the Authenticity Token
   */
  public function getAuthenticityToken() {
    return $this->authenticity_token;
  }

  /**
   * Set the session ID which is sent in a cookie to the server.
   *
   * @param String $session_id the session ID received from Workbooks.
   */
  public function setSessionId($session_id) {
    $this->session_id = $session_id;
    return $this;
  }

  /**
   * Get the session ID
   *
   * @return String the session ID
   */
  public function getSessionId() {
    return $this->session_id;
  }
  
  /**
   * Get the session cookie
   *
   * @return String the session cookie
   */
  public function getSessionCookie() {
    $session_cookie = NULL;
    if (isset($this->session_id)) {
      $session_cookie = WorkbooksApi::SESSION_COOKIE . '=' . $this->session_id;
    }
    return $session_cookie;
  }
  
  /**
   * Set the logger callback. In the absence of this, no log output is generated.
   *
   * @param Array $logger_callback a class and function to pass (level, msg) to
   *   e.g.  setLoggerCallback(array('WorkbooksApi', 'logAllToStdout'))
   */
  public function setLoggerCallback($logger_callback) {
    $this->logger_callback = $logger_callback;
    return $this;
  }

  /**
   * Call the logger function, if any.
   *
   * @param String $msg a string to be logged
   * @param Mixed $expression any values to output with the message
   * @param String $level optional: one of 'error', 'warning', 'notice', 'info', 'debug' (the default), or 'output'
   */
  public function log($msg, $expression='nil', $level='debug') {
    if (isset($this->logger_callback)) {
      if ($expression != 'nil') {
        $msg .= ' «' . var_export($expression, true) . '»';
      }

      call_user_func($this->logger_callback, $msg, $level);
    }
  }
  
  /**
   * A sample logger, this one passes all messages to stdout.
   * @param String $msg a string to be logged
   * @param String $level one of 'error', 'warning', 'notice', 'info', 'debug', 'output'
   */
  public function logAllToStdout($msg, $level) {
    echo "\n\n[" . $level .'] ' . $msg . "\n\n";
  }

  /**
   * A sample logger, this one passes all messages to stdout and flushes the buffer
   * @param String $msg a string to be logged
   * @param String $level one of 'error', 'warning', 'notice', 'info', 'debug', 'output'
   */
  public function logAllToStdoutAndFlush($msg, $level) {
    self::logAllToStdout(preg_replace('/\n\n+/m', "\n", $msg), $level);
    // Now flush the output buffer
    ob_flush();
  }
  
  /**
   * Helper function to send headers when running as a Web Process. 
   */    
  public function header($str) {
    echo "\n\n[header] {$str}\n\n";
  }
  
  /**
   * Helper function to send output when running as a Web Process. 
   */    
  public function output($str) {
    echo "\n\n[output] {$str}\n\n";
  }
  
  /**
   * Helper function which evaluates a response to determine how successful it was
   * @param Array $response a response from the service API
   * @return String One of: 'failed', 'ok', 'not-ok'
   *   'failed' - this is unexpected.
   *   'not-ok' - something in the request could not be satisfied; you should check the errors and warnings.
   *   'ok'     - completely successful.
   */
  public function condensedStatus(&$response) {
    $status = 'ok';
    if (!isset($response['success'])) {
      return 'failed'; // Unexpected failure - there should always be a 'success' element
    } elseif (!$response['success']){
      return 'failed'; // Something was quite wrong, not just a validation failure
    } elseif (isset($response['errors'])) {
      $status = 'not-ok';
    } elseif (!isset($response['affected_object_information']) || !is_array($response['affected_object_information'])) {
      return 'ok';
    } else {
      foreach ($response['affected_object_information'] as &$affected) {
        if (!isset($affected['success'])) {
          return 'failed'; // Again, this is unexpected.
        }
        if (!$affected['success']) {
          $status = 'not-ok'; // There will be warnings or errors indicated which prevented complete success.
        }
      }
    }
    return $status;
  }
  
  /**
   * Check responses are expected. Raises an exception if the response is not.
   * @param Array $response a response from the service API.
   * @param String $expected the expected type of response, defaults to 'ok'.
   * @param String $raise_on_error the exception to raise if the response is not as expected.
   */
  public function assertResponse(&$response, $expected='ok', $raise_on_error='Unexpected response from Workbooks API') {
    $condensed_status = $this->condensedStatus($response);
    if ($condensed_status != $expected) {
      $this->log('Received an unexpected response', array ($condensed_status, $response, $expected));
      throw new Exception($raise_on_error);
    }
  }
  
  /*
   * Extract ids and lock_versions from the 'affected_objects' of the response and return them as an Array of Arrays.
   * @param Array $response a response from the service API.
   * @return Array a set of id and lock_version values, one per affected object.
   */
  public function idVersions(&$response) {
    $retval = array();
    foreach ($response['affected_objects'] as &$affected) {
      $retval[]= array(
        'id'           => $affected['id'], 
        'lock_version' => $affected['lock_version'],
      );
    }
    return $retval;
  }

  /**
   * Login to the service to set up a session.
   *   Optional settings
   *   - api_key: An API key (this is preferred over username/password).
   *   - username: The user's login name (required if not set using setUsername) or using an API key.
   *   - password: The user's login password. Either this or a session_id must be specified.
   *   - session_id: The ID of a session to reconnect to. Either this or a password must be specified.
   *   - logical_database_id: The ID of a database to select - not required when the user has access to exactly one.
   *   others as defined in the API documentation (e.g. _time_zone, _strict_attribute_checking, _per_object_transactions).
   * @param Array $params credentials and other options to the login API endpoint.
   * @return Array (Integer the http status, String any failure reason, Array the decoded json)
   *
   * A successful login returns an http status of 200 (WorkbooksApi::HTTP_STATUS_OK).
   * If more than one database is available the http status is 403 (WorkbooksApi::HTTP_STATUS_FORBIDDEN), the failure reason 
   *   is 'no_database_selection_made' and the set of databases to choose from are in the decoded json beneath the 'databases' 
   *   key. Repeat the login() call, passing in a logical_database_id: you might use the 'default_database_id' value which 
   *   was returned in the previous login attempt.
   * Otherwise the login has failed outright: see the Workbooks API documentation for a list of the possible http statuses.
   */
  public function login($params) {
    // $this->log('login() called with params', $params);
    if (empty($params['api_key'])) {
      $params['api_key'] = $this->getApiKey();
    }
    if (empty($params['username'])) {
      $params['username'] = $this->getUsername();
    }
    if (empty($params['api_key']) && empty($params['username'])) {
      throw new Exception('An API key or a username must be supplied');
    }
    
    if (empty($params['password']) && empty($params['session_id'])) {
      $params['session_id'] = $this->getSessionId();
    }
    if (empty($params['api_key']) && empty($params['password']) && empty($params['session_id'])) {
      throw new Exception('A password or session_id must be supplied unless using an API Key');
    }

    if (empty($params['logical_database_id'])) {
      $params['logical_database_id'] = $this->getLogicalDatabaseId();
    }
    
    if (empty($params['logical_database_id']) && !empty($params['session_id']) && empty($params['password'])){
      throw new Exception('A logical database ID must be supplied when trying to re-connect to a session');
    }

    // These default settings can be overridden by the caller.
    $params = array_merge(array(
        '_application_name'          => $this->getApplicationName(),
        'json'                       => 'pretty',
        '_strict_attribute_checking' => true,
    ), $params);
    
    $sr = self::makeRequest('login.api', 'POST', $params);
    $http_status =& $sr['http_status'];
    $response = json_decode($sr['http_body'], true);
    
    // The authenticity_token is valid for a specific session and is required when any modifications are attempted.
    if ($http_status == WorkbooksApi::HTTP_STATUS_OK) {
      $this->setLoginState(true);
      $this->setUserQueues($response['my_queues']);
      $this->setAuthenticityToken($response['authenticity_token']);
    }
    
    $retval = array(
      'http_status'     => $http_status, 
      'failure_message' => is_array($response) && array_key_exists('failure_message', $response) ? $response['failure_message'] : '',
      'response'        => $response
    );
    // $this->log('login() returns', $retval, 'info');
    return $retval;
  }

  /**
   * Logout from the service.
   *
   * @return Array of hashes: 'success' - whether it succeeded, 'http_status', 'response' - the response body
   * 
   * A successful logout will return a 'success' value of true
   */
  public function logout() {
    $sr = self::makeRequest('logout', 'POST', array());
    
    $this->setLoginState(false); // force a login regardless of the server-side state
    $this->setAuthenticityToken(NULL);
    
    $http_status =& $sr['http_status'];
    $response =& $sr['http_body'];
    $success = ($http_status == WorkbooksApi::HTTP_STATUS_FOUND) ? true : false; 

    $retval = array(
      'success'         => $success,
      'http_status'     => $http_status, 
      'response'        => $response  
    );
    // $this->log('logout() returns', $retval, 'info');
    return $retval;
  }

  /**
   * Make a request to an endpoint on the service to read or list objects. You must have logged in first
   * @param String $endpoint selects the portion of the API to use, e.g. 'crm/organisations'
   * @param Array $params the parameters to the API call - filter, limit, column selection as an array of hashes;
   *   each hash element can have a simple value or be an array of values e.g. for column selection.
   * @param Boolean $decode_json whether to decode json
   * @return Array the decoded json response if $decode_json is true (default), or the raw response if not
   * @throws WorkbooksApiException
   *
   * As usual, check the API documentation for further information.
   */
  public function get($endpoint, $params, $decode_json=true) {
    $array_params = array(); // those where the value is an array, not simply a value
    foreach(array_keys($params) as $k) {
      if (is_array($params[$k])) {
        foreach ($params[$k] as &$array_value) {
          $array_params[] = $k . '=' . urlencode($array_value);
        }
        unset($params[$k]);
      }
    }
    $array_param_string = implode("&", $array_params);
    return $this->apiCall($endpoint, 'GET', $params, $array_param_string, $decode_json);
  }

  /**
   * Interface as per get() but if the response is not 'ok' it also logs an error and raises an exception.
   */
  public function assertGet($endpoint, $params, $decode_json=true) {
     $response = $this->get($endpoint, $params, $decode_json);
     $this->assertResponse($response);
     return $response;
  }

  /**
   * Make a request to an endpoint on the service to create objects. You must have logged in first.
   *
   * @param String $endpoint selects the portion of the API to use, e.g. 'crm/organisations'.
   * @param Array $objs an array of objects to create
   * @param Array a set of additional parameters to send along with the data, for example
   *   array('_per_object_transactions' => true) to change the commit behaviour.
   * @return Array the decoded response.
   * @throws WorkbooksApiException
   *
   * As usual, check the API documentation for further information.
   */
  public function create($endpoint, &$objs, $params=array(), $method='none') {
    return $this->batch($endpoint, $objs, $params, 'CREATE');
  }

  /**
   * Interface as per create() but if the response is not 'ok' it also logs an error and raises an exception.
   */
  public function assertCreate($endpoint, &$objs, $params=array(), $method='none') {
     $response = $this->create($endpoint, $objs, $params, $method);
     $this->assertResponse($response);
     return $response;
  }

  /**
   * Make a request to an endpoint on the service to update objects. You must have logged in first.
   *
   * @param String $endpoint selects the portion of the API to use, e.g. 'crm/organisations'.
   * @param Array $objs an array of objects to update, specifying the id and lock_version of each
   *   together with the values to set.
   * @param Array a set of additional parameters to send along with the data, for example
   *   array('_per_object_transactions' => true) to change the commit behaviour.
   * @return Array the decoded response.
   * @throws WorkbooksApiException
   *
   * As usual, check the API documentation for further information.
   */
  public function update($endpoint, &$objs, $params=array(), $method='none') {
    return $this->batch($endpoint, $objs, $params, 'UPDATE');
  }

  /**
   * Interface as per update() but if the response is not 'ok' it also logs an error and raises an exception.
   */
  public function assertUpdate($endpoint, &$objs, $params=array(), $method='none') {
     $response = $this->update($endpoint, $objs, $params, $method);
     $this->assertResponse($response);
     return $response;
  }

  /**
   * Make a request to an endpoint on the service to delete objects. You must have logged in first.
   *
   * @param String $endpoint selects the portion of the API to use, e.g. 'crm/organisations'.
   * @param Array $objs an array of objects to delete, specifying the id and lock_version of each.
   * @param Array a set of additional parameters to send along with the data, for example
   *   array('_per_object_transactions' => true) to change the commit behaviour.
   * @return Array the decoded response.
   * @throws WorkbooksApiException
   *
   * As usual, check the API documentation for further information.
   */
  public function delete($endpoint, &$objs, $params=array(), $method='none') {
    return $this->batch($endpoint, $objs, $params, 'DELETE');
  }

  /**
   * Interface as per delete() but if the response is not 'ok' it also logs an error and raises an exception.
   */
  public function assertDelete($endpoint, &$objs, $params=array(), $method='none') {
     $response = $this->delete($endpoint, $objs, $params, $method);
     $this->assertResponse($response);
     return $response;
  }

  /**
   * Make a request to an endpoint on the service to operate on multiple objects. You must have logged in first.
   * You can request a combination of CREATE, UPDATE and DELETE operations, to be batched together.
   * This is the core method upon which other methods are implemented which perform a subset of these operations.
   *
   * @param String $endpoint selects the portion of the API to use, e.g. 'crm/organisations'.
   * @param Array $objs an array of objects to create, update or delete.
   * @param Array a set of additional parameters to send along with the data, for example
   *   array('_per_object_transactions' => true) to change the commit behaviour.
   * @param $method String The method (CREATE/UPDATE/DELETE) which is to be used if 
   *   not specified for an object.
   * @return Array the decoded response.
   * @throws WorkbooksApiException
   *
   * As usual, check the API documentation for further information.
   */
  public function batch($endpoint, &$objs, $params=array(), $method='none') {
    // $this->log('batch() called with params', array($endpoint, $objs));
    
    // If just one object was passed in, turn it into an array.
    if (!array_key_exists(0, $objs)) {
      $objs = array($objs);
    }  

    $filter_params = $this->encodeMethodParams($objs, $method);
    $encoded_post_params = $this->fullSquare($objs);
    $response = $this->apiCall($endpoint, 'PUT', $params, $filter_params . '&' . $encoded_post_params);

    // $this->log('batch() returns', $response, 'info');
    return $response;
  }
  
  /**
   * Interface as per batch() but if the response is not 'ok' it also logs an error and raises an exception.
   */
  public function assertBatch($endpoint, &$objs, $params=array(), $method='none') {
     $response = $this->batch($endpoint, $objs, $params, $method);
     $this->assertResponse($response);
     return $response;
  }
  
  /**
   * Ensure we are logged in; if not then reconnect to the service if possible.
   */
  protected function ensureLogin() {
    if (!$this->getLoginState() && 
        $this->getUsername() &&
        $this->getSessionId() &&
        $this->getLogicalDatabaseId()) {

      /*
       * Probably running under the Process Engine: the required values are all available. Do the login now.
       * 
       * A login failure results in its being logged in the Process Log and if the process is scheduled
       * then it is queued to be retried later.
       */

      // Exit codes which mean something to the Workbooks Process Engine.
      $exit_ok = 0;
      $exit_retry = 1;

      try {
        $login_response = $this->login(array());
        if ($login_response['http_status'] <> WorkbooksApi::HTTP_STATUS_OK) {
          $this->log('Workbooks connection unsuccessful.', $login_response['failure_message'], 'error');
          exit($exit_retry); // retry later if the Action is scheduled
        }
      }
      catch(Exception $e) {
        $this->log('Workbooks connection unsuccessful', $e->getMessage(), 'error');
          exit($exit_retry); // retry later if the Action is scheduled
      }
    }
    
    if ($this->getLoginState() == false) {
      $e = new WorkbooksApiException(array(
        'workbooks_api' => $this,
        'error'         => array(
          'message'       => 'Not logged in',
          'type'          => 'WorkbooksLoginException',
        ),
      ));
      $this->destroyCurlHandle();
      throw $e;        
    }
  }

  /**
   * Make a call to an endpoint on the service, reconnecting to the session first if necessary if running beneath the Process Engine.
   *
   * @param String $endpoint selects the portion of the API to use, e.g. 'crm/organisations'.
   * @param String $method the restful method - one of 'GET', 'PUT', 'POST', 'DELETE'.
   * @param Array $post_params optional set of parameters to add to the POST body.
   * @param String $encoded_post_params optional additional parameters, this time url-encoded, to use for the POST body
   * @param Boolean $decode_json whether to decode json
   * @return Array the decoded json response if $decode_json is true (default), or the raw response if not.
   * @throws WorkbooksApiException
   *
   * As usual, check the API documentation for further information.
   */
  public function apiCall($endpoint, $method, $post_params=array(), $encoded_post_params='', $decode_json=true) {
    // $this->log('apiCall() called with params', array($endpoint, $method, $post_params, $encoded_post_params, $decode_json));

    // Clients using API Keys normally pass those on each request; otherwise establish a session to span multiple requests.
    if ($this->getApiKey()) {
      $post_params['api_key'] = $this->getApiKey();
    } else {
      $this->ensureLogin();
    }
    
    // API calls are always to a '.api' endpoint; the caller does not have to include this.    
    // Including ANY extension will prevent '.api' from being appended.
    if (!preg_match('/\.\w{3,4}/', $endpoint)) {
      $endpoint .= '.api';
    }

    $sr = self::makeRequest($endpoint, $method, $post_params, $encoded_post_params);
    $http_status = $sr['http_status'];
    
    if ($http_status <> WorkbooksApi::HTTP_STATUS_OK) {
      $e = new WorkbooksApiException(array(
        'workbooks_api' => $this,
        'error_code'    => $http_status,
        'error'         => array(
          'message'       => 'Non-OK response (' . $http_status . ')',
          'type'          => 'WorkbooksServiceException',
          'response'      => $sr['response']
        ),
      ));
      $this->destroyCurlHandle();
      throw $e;
    }

    if ($decode_json) {
      $response = json_decode($sr['http_body'], true);
    } else {
      $response = $sr['http_body'];
    }

    // $this->log('apiCall() returns', $response, 'info');
    return $response;
  }

  /**
   * Builds and sends an HTTP request.
   *
   * Exceptions are raised if curl reports an error, for example a failure to 
   * resolve the service name, or an inability to make a connection to the service.
   * Assuming the service can be contacted errors and warnings are passed back so
   * the caller can capture the http_status of the response.
   *
   * @param String $endpoint selects the portion of the API to use, e.g. 'crm/organisations'.
   * @param String $method the restful method - one of 'GET', 'PUT', 'POST', 'DELETE'.
   * @param Array $post_params a set of parameters to add to the POST body.
   * @param String $encoded_post_params optional additional parameters, this time url-encoded, to use for the POST body
   * @return Array (Integer the http status, String the response text)
   * @throws WorkbooksApiException
   */
  public function makeRequest($endpoint, $method, $post_params, $encoded_post_params='') {
    // $this->log('makeRequest() called with params', array($endpoint, $method, $post_params, $encoded_post_params));

    $start_time = microtime(true);
    $url_params = array(
      '_dc'     => round($start_time*1000), // cache-buster
    );
    $url = $this->getUrl($endpoint, $url_params);

    $post_params = array_merge(array(
      '_method' => strtoupper($method),
      'client'  => 'api',
    ), $post_params);
    
    $api_logging_key = $this->getApiLoggingKey();
    $api_logging_seq = $this->nextApiLoggingSeq();
    if (isset($api_logging_key)) {
      $post_params = array_merge(array(
        'api_logging_key' => $api_logging_key,
        'api_logging_seq' => $api_logging_seq,
      ), $post_params);
    }
    
    if ($method != 'GET' && $this->getAuthenticityToken()) {
      $post_params = array_merge(array(
         '_authenticity_token' => $this->getAuthenticityToken()
      ), $post_params);
    }
    
    $http_query = http_build_query($post_params, NULL, '&');
    if ($encoded_post_params != '') {
      $http_query .= '&' . $encoded_post_params; 
    }
    $curl_handle = $this->getCurlHandle();

    curl_setopt_array($curl_handle, $this->curl_options);
    curl_setopt_array($curl_handle, array (
      CURLOPT_URL            => $url,
      CURLOPT_POSTFIELDS     => $http_query,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_HEADER         => true,
      CURLOPT_FOLLOWLOCATION => false,
      CURLOPT_POST           => true,
      CURLOPT_HTTPHEADER     => array(
        'Content-type: application/x-www-form-urlencoded',
        'Expect:',                                          // Prevent 'Expect: 100-continue' 
    )));

    $cookie = $this->getSessionCookie();
    if (isset($cookie)) {
      curl_setopt($curl_handle, CURLOPT_COOKIE, $cookie);
    }

    if (isset($api_logging_key)) {
      $this->log($api_logging_seq, array($method, $endpoint), 'api_request');
    }
    // Make the request, await the response. Timeouts as above.
    $curl_result_with_headers = curl_exec($curl_handle);
    if (isset($api_logging_key)) {
      $this->log($api_logging_seq, array($method, $endpoint), 'api_response');
    }

    // $this->log('RESPONSE', $curl_result_with_headers);
    
    if ($curl_result_with_headers === false) {
      $e = new WorkbooksApiException(array(
        'workbooks_api' => $this,
        'error_code'    => curl_errno($curl_handle),
        'error'         => array(
          'message'       => curl_error($curl_handle) . '(' . curl_errno($curl_handle) . ')',
          'type'          => 'CurlException'
        ),
      ));
      $this->destroyCurlHandle();
      throw $e;
    }

    // Separate out the HTTP status, the session cookie and the (JSON) body.
    list($headers, $body) = explode("\r\n\r\n", $curl_result_with_headers, 2); 
    //   HTTP/1.1 302 Found
    preg_match('/HTTP\/.* ([0-9]+) .*/', $headers, $status);
    $http_status = (is_array($status) && (count($status) > 1)) ? intval($status[1]) : 0;
    
    if ($http_status == 0) {
      $e = new WorkbooksApiException(array(
        'workbooks_api' => $this,
        'error_code'    => 0,
        'error'         => array(
          'message'       => 'HTTP status not found: bad request?',
          'type'          => 'BadRequest',
          'response'      => $curl_result_with_headers
        ),
      ));
      $this->destroyCurlHandle();
      throw $e;
    }

    // Extract the session_id from the response and retain it for future requests. This
    // may be a different ID from the one the client may have just sent. 
    //   Set-Cookie: Workbooks-Session=7c67eba894177c768b4f0b84090704b7; path=/; secure; HttpOnly
    preg_match('/^Set-Cookie: Workbooks-Session=(.*?);/m', $headers, $session_cookie);
    $this->setSessionId((is_array($session_cookie) && (count($session_cookie) > 1)) ? $session_cookie[1]: '');
    
    $end_time = microtime(true);
    $this->setLastRequestDuration($end_time - $start_time);
    
    $retval = array('http_status' => $http_status, 'http_body' => &$body);
    // $this->log('makeRequest() returns', $retval);
    return $retval;
  }

  /*
   * Depending on the method (Create/Update/Delete) the objects passed to Workbooks
   * have certain minimum requirements. Callers may specify a method for each object
   * or assume the same operation for all objects.
   *
   * @param $obj_array Array Objects to be encoded, *modified in place*
   * @param $method String The method (CREATE/UPDATE/DELETE) which is to be used if 
   *   not specified for an object.
   * @return String a set of parameters representing the filter which is required to 
   *   define the working set of objects.
   */
  protected function encodeMethodParams(&$obj_array, $method) {
    // $this->log('encodeMethodParams() called with params', array($obj_array, $method));
    $filter_ids = array();
    foreach ($obj_array as &$obj) {
      $method_key = (array_key_exists('method', $obj) ? 'method' : '__method');
      if (isset($obj[$method_key])) {
        $obj_method = $obj[$method_key];
        unset($obj[$method_key]);
      } else {
        $obj_method = $method;
      }
      switch (strtoupper($obj_method)) {
        case 'CREATE':
          $obj['__method'] = 'POST';
          // Must not specify a current id and lock_version (or if you do they should both be zero)
          if ((isset($obj['id']) && $obj['id'] <> 0) || 
              (isset($obj['lock_version']) && $obj['lock_version'] <> 0)) {
            throw new WorkbooksApiException(array(
              'workbooks_api' => $this,
              'error'         => array(
                'message'       => 'Neither \'id\' nor \'lock_version\' can be set to create an object',
                'type'          => 'WorkbooksApiException',
                'object'        => $obj
            )));
          }
          $obj['id'] = '0';
          $obj['lock_version'] = '0';
          $filter_ids[]= '0';
          break;
        case 'UPDATE':
          $obj['__method'] = 'PUT';
          // There must be an id and lock_version
          if (!isset($obj['id']) || !isset($obj['lock_version'])) {
            throw new WorkbooksApiException(array(
              'workbooks_api' => $this,
              'error'         => array(
                'message'       => 'Both \'id\' and \'lock_version\' must be set to update an object',
                'type'          => 'WorkbooksApiException',
                'object'        => $obj
            )));
          }
          $filter_ids[]= $obj['id'];
          break;
        case 'DELETE':
          $obj['__method'] = 'DELETE';
          // There must be an id and lock_version
          if (!isset($obj['id']) || !isset($obj['lock_version'])) {
            throw new WorkbooksApiException(array(
              'workbooks_api' => $this,
              'error'         => array(
                'message'       => 'Both \'id\' and \'lock_version\' must be set to delete an object',
                'type'          => 'WorkbooksApiException',
                'object'        => $obj
            )));
          }
          $filter_ids[]= $obj['id'];
        break;
        default:
        throw new WorkbooksApiException(array(
          'workbooks_api' => $this,
          'error'         => array(
            'message'       => 'Unexpected method: ' . $method,
            'type'          => 'WorkbooksApiException',
            'object'        => $obj
        )));
      }
    }
    
    // Must include a filter to 'select' the set of objects being operated upon
    if (count($filter_ids) > 0) {
      $filter = '_fm=or';
      foreach ($filter_ids as &$filter_id) {
        $filter .= '&_ff[]=id&_ft[]=eq&_fc[]=' . $filter_id;
      }
    }
      
    // $this->log('encodeMethodParams() results in', array($filter, $obj_array));
    return $filter;
  }
   
  /*
   * The Workbooks wire protocol requires that each key which is used in any object be
   * present in all objects, and delivered in the right order. Callers of this binding
   * library will omit keys from some objects and not from others. Some special values
   * are used in this encoding - :null_value and :no_value.
   *
   * @param $obj_array Array Objects to be encoded
   * @return String the encoded array, suitable for passing to Workbooks
   */
  protected function fullSquare($obj_array) {
    // $this->log('fullSquare() called with params', $obj_array);
    
    // Get the full set of keys
    $all_keys = array();
    foreach ($obj_array as &$obj) {
      $all_keys = array_merge($all_keys, array_keys($obj));
    }
    $unique_keys = array_unique($all_keys);

    // Keep the order of attributes consistent for each encoded object
    asort($unique_keys);

    // The full square array is one with a value for every key in every object
    $url_encoded_objects = array();
    foreach ($obj_array as &$obj) {
      foreach ($unique_keys as $key) {
        if (array_key_exists($key, $obj) && $obj[$key] == NULL) {
          $value = ':null_value:';
        } elseif (!isset($obj[$key])) {
          $value = ':no_value:';
        } else {
          $value = $obj[$key];
        }
        
        $unnested_key = $this->unnestKey($key);
        if (is_array($value)) {
          $new_val = "[";
          foreach ($value as $val) {
            if ($new_val != "[") {
              $new_val .= ",";
            }
            $new_val .= $val;
          }
          $new_val .= "]";
          $url_encoded_objects[] = (urlencode($unnested_key) . '[]=' . urlencode($new_val));
        } else {
          $url_encoded_objects[] = (urlencode($unnested_key) . '[]=' . urlencode($value));
        }
      }
    }
    
    // $this->log('fullSquare() array for return', $url_encoded_objects);
    $retval = implode('&', $url_encoded_objects);
    // $this->log('fullSquare() returns', $retval);
    return $retval;
  }

  /**
   * Normalise any nested keys so they have the expected format for the wire, i.e. 
   * convert things like this:
   *   org_lead_party[main_location[email]]
   * into this:   
   *   org_lead_party[main_location][email]
   *
   * @param $attribute_name String the attribute name with potentially nested square brackets
   * @return String the unnested attribute name
   */
  protected function unnestKey($attribute_name) {
    // $this->log('unnestKey() called with param', $attribute_name);
    
    // If it does not end in ']]' then it is not a nested key.
    if (!preg_match('/\]\]$/', $attribute_name)) {
      return $attribute_name;
    }
    // Otherwise it is nested: split and re-join
    $parts = preg_split('/[\[\]]+/', $attribute_name, 0, PREG_SPLIT_NO_EMPTY);
    $retval= $parts[0] . '[' . join('][', array_slice($parts, 1)) . ']';
    
    // $this->log('unnestKey() returns', $retval);
    return $retval;
  }
  
  /**
   * Construct a URL for the current Workbooks service including path and parameters.
   *
   * @param $path String the path
   * @param $query_params Array optional query params to append
   * @return String the URL for the given parameters
   */
  protected function getUrl($path, &$query_params=array()) {
    // $this->log('getUrl() called with params', array($path, $query_params));
    
    $url = $this->getService();
    
    if ($path[0] !== '/') {
        $url .= '/';
    }
    $url .= $path;

    if ($query_params) {
      $url .= '?' . http_build_query($query_params, NULL, '&');
    }
    
    // $this->log('getUrl() returns', $url);
    return $url;
  }

} // class Workbooks

/*
 * Initialise $workbooks if running under the Workbooks Process Engine and the right set
 * of parameters are available to allow authentication using a session_id. If the Process
 * Engine is not being used then you will need to build your own $workbooks using the
 * constructor and login yourself.
 */

$workbooks = NULL;
if (isset($params) && 
    isset($params['_workbooks_client_name']) &&
    isset($params['_workbooks_protocol']) &&
    isset($params['_workbooks_username']) &&
    isset($params['_workbooks_session_id']) &&
    isset($params['_workbooks_logical_database_id']) &&
    isset($params['_workbooks_api_logging_key'])) {

  $workbooks = new WorkbooksApi(array(
    'logger_callback'     => array('WorkbooksApi', 'logAllToStdoutAndFlush'),  // A noisy logger
    'application_name'    => $params['_workbooks_client_name'],
    'user_agent'          => $params['_workbooks_client_name'] . '/1.0',
    'service'             => $params['_workbooks_protocol'] . '://'. $_SERVER['REMOTE_ADDR'],
    'username'            => $params['_workbooks_username'],
    'session_id'          => $params['_workbooks_session_id'],
    'logical_database_id' => $params['_workbooks_logical_database_id'],
    'api_logging_key'     => $params['_workbooks_api_logging_key'],
    'verify_peer'         => false,
  ));

  // Setup PHP special arrays: $_SERVER, $_GET, $_POST, $_COOKIE.
  $_POST = array();
  foreach(array_keys($params) as $k) {
    if (preg_match('/^_server_(.*)/m', $k, $var_name)) {
      $_SERVER[$var_name[1]] = $params[$k];
    }
    if (preg_match('/^_query_(.*)/m', $k, $var_name)) {
      $_GET[$var_name[1]] = $params[$k];
    }
    if (preg_match('/^_post_(.*)/m', $k, $var_name)) {
      $_POST[$var_name[1]] = $params[$k];
    }
    if (preg_match('/^_cookie_(.*)/m', $k, $var_name)) {
      $_COOKIE[$var_name[1]] = $params[$k];
    }
  } // foreach $params

  // Turn output buffering on
  ob_start();
}

?>
