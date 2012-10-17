<?php
  
/**
 *   Login wrapper for Workbooks for API test purposes. This version uses an API Key to
 *   authenticate which is the recommended approach unless you are running under the 
 *   Process Engine which will set up a session for you automatically without requiring
 *   an API key.
 *
 *   Last commit $Id$
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
if(!function_exists('testLogin')) {
  function testLogin($service          = 'http://localhost:3000',      // Set to NULL to use the production service
                     $application_name = 'test_client', 
                     $user_agent       = 'test_client/0.1', 
                     $verify_peer      = false,
                     $api_key          = '01234-56789-01234-56789-01234-56789-01234-56789') {
  
    /*
     * Initialise the Workbooks API object
     */
    $service_params = array(
      'application_name'   => $application_name,                        // Mandatory, should be the "human name" for the client
      'user_agent'         => $user_agent,                              // Mandatory, should include version number
      
      // The following settings are used in Workbooks auto-test environment and are not typical.
      'logger_callback'    => array('WorkbooksApi', 'logAllToStdout'),  // A noisy logger
      'connect_timeout'    => 120,                                      // Optional, if unset defaults to 20 seconds
      'request_timeout'    => 120,                                      // Optional, if unset defaults to 20 seconds
      'verify_peer'        => $verify_peer,                             // Optional, if unset defaults to checking the peer SSL certificate
      
      'api_key'            => $api_key,
    );
    if (isset($service)) {
      $service_params['service'] = $service;
    }
    
    $workbooks = new WorkbooksApi($service_params);
    return $workbooks;
  }
}

/*
 * Exit. Does not return!
 */
 
if(!function_exists('testExit')) {
  function testExit($workbooks, $exit_code = 0) {
    if ($exit_code == 0) { 
      $workbooks->log('script exited', 'OK', 'info');
    } else {
      $workbooks->log('script exited with error', $exit_code, 'error');
    }
    exit($exit_code);
  }
}

if ($workbooks == NULL) {
  /* Not already authenticated or running under the Workbooks Process Engine, so setup a session */
  $workbooks = testLogin();
}

?>

