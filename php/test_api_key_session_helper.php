<?php
  
/**
 *   Login wrapper for Workbooks for API test purposes. This version uses an API Key with an
 *   explicit Login/Logout requests. An alternative is to skip the Login/Logout and just pass
 *   the API Key on each request: see test_login_helper.php
 *
 *   If you are running under the Process Engine none of this is necessary.
 *
 *   Last commit $Id: test_api_key_session_helper.php 63933 2024-09-03 14:06:12Z jmonahan $
 *
 *       The MIT License
 *
 *       Copyright (c) 2008-2012, Workbooks Online Limited.
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
 */

$exit_error = 1;
$exit_ok = 0;

/* 
 * Login to Workbooks and return a handle to the workbooks connection
 */
function testLogin($service          = 'http://localhost:3000',      // Set to NULL to use the production service
                   $application_name = 'php_test_client', 
                   $user_agent       = 'php_test_client/0.1', 
                   $verify_peer      = false, 
                   $api_key          = '01234-56789-01234-56789-01234-56789-01234-56789') {

  // allow the server, API key and request timeout to be overridden with environment variables
  $server_env = getenv("WB_SERVICE");
  $api_key_env = getenv("WB_API_KEY");
  $request_timeout_env = getenv("WB_REQUEST_TIMEOUT");

  if ($server_env) {
    $service = $server_env;
  }

  if ($api_key_env) {
    $api_key = $api_key_env;
  }

  $request_timeout = 120;

  if ($request_timeout_env) {
    $request_timeout = $request_timeout_env;
  }
    
  /*
   * Initialise the Workbooks API object
   */
  $service_params = array(
    'application_name'   => $application_name,                        // Mandatory, should be the "human name" for the client
    'user_agent'         => $user_agent,                              // Mandatory, should include version number
    
    // The following settings are used in Workbooks auto-test environment and are not typical.
    'logger_callback'    => array('WorkbooksApi', 'logAllToStdout'),  // A noisy logger
    'connect_timeout'    => 120,                                      // Optional, if unset defaults to 20 seconds
    'request_timeout'    => $request_timeout,                         // Optional, if unset defaults to 20 seconds
    'verify_peer'        => $verify_peer,                             // Optional, if unset defaults to checking the peer SSL certificate
  );
  if (isset($service)) {
    $service_params['service'] = $service;
  }
  
  $workbooks = new WorkbooksApi($service_params);
  
  /*
   * Connect to the service and login
   */
  $login_params = array(
    'api_key' => $api_key,
  );

  $workbooks->log('login commences', __FILE__, 'debug');
  
  $login = $workbooks->login($login_params);
  
  if ($login['http_status'] <> WorkbooksApi::HTTP_STATUS_OK) {
    $workbooks->log('Login failed.', $login, 'error');
    exit($exit_error);
  }
  
  /*
   * We now have a valid logged-in session.
   */
  $workbooks->log('login complete', __FILE__, 'info');

  return $workbooks;
}

/*
 * Logout and Exit. Does not return!
 */
function testExit($workbooks, $exit_code = 0) {
  /*
   * Logout
   * Arguably testing for successful logout is a bit of a waste of effort...
   */
  $logout = $workbooks->logout();
  if (!$logout['success']) {
    $workbooks->log('Logout failed.', $logout, 'error');
    exit($exit_error);
  }
  $workbooks->log('logout complete', __FILE__, 'info');
  if ($exit_code == 0) { 
    $workbooks->log('script exited', 'OK', 'info');
  } else {
    $workbooks->log('script exited with error', $exit_code, 'error');
  }
  exit($exit_code);
}

if ($workbooks == NULL) {
  /* Not already authenticated or running under the Workbooks Process Engine, so setup a session */
  $workbooks = testLogin();
}

?>

