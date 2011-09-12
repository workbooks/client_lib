<?php
  
/**
 *   A demonstration of using the Workbooks API to fetch metadata via a thin PHP wrapper
 *
 *   Last commit $Id: metadata_example.php 13905 2011-09-12 08:30:24Z jkay $
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
 */

require 'workbooks_api.php';

$exit_error = 1;
$exit_ok = 0;

/*
 * A couple of simple helper functions for this example script.
 */
 
/*
 * Check responses are expected. Exits if the response is not.
 */
function assert_response($workbooks, $response, $expected='ok', $exit_on_error=1) {
  $condensed_status = WorkbooksApi::condensedStatus($response);
  if ($condensed_status != $expected) {
    $workbooks->log('Received an unexpected response', array ($condensed_status, $response, $expected));
    exit($exit_on_error);
  }
}

/*
 * Initialise the Workbooks API object
 */
$workbooks = new WorkbooksApi(array(
  'application_name'   => 'PHP test client',                     // Mandatory, should be the "human name" for the client
  'user_agent'         => 'php_test_client/0.1',                 // Mandatory, should include version number
  
  // The following settings are used in Workbooks auto-test environment and are not typical.
  'logger_callback'    => array('WorkbooksApi', 'logAllToStdout'),  // A noisy logger
  'connect_timeout'    => 120,                                   // Optional, defaults to 20 seconds
  'request_timeout'    => 120,                                   // Optional, defaults to 20 seconds
  'service'            => 'http://localhost:3000',               // Optional, defaults to the Production Workbooks service
  'verify_peer'        => false,                                 // Optional, defaults to checking the peer SSL certificate
));

$workbooks->log('Running test script', __FILE__, 'info');

/*
 * Connect to the service and login
 */
$login_params = array(
  'username' => 'james.kay@workbooks.com',
//  'username' => 'apidemo@workbooks.com',
  'password' => 'abc123',
);

$login = $workbooks->login($login_params);

if ($login['http_status'] == WorkbooksApi::HTTP_STATUS_FORBIDDEN && $login['response']['failure_reason'] == 'no_database_selection_made') {
  //$workbooks->log('Database selection required', $login, 'error');
  
  /*
   * Multiple databases are available, and we must choose one. 
   * A good UI might remember the previously-selected database or use $databases to present a list of databases for the user to choose from. 
   */
  $default_database_id = $login['response']['default_database_id'];
  $databases = $login['response']['databases'];
  
  /*
   * For this test script we simply select the one which was the default when the user last logged in to the Workbooks user interface. This 
   * would not be correct for most API clients since the user's choice on any particular session should not necessarily change their choice 
   * for all of their API clients.
   */
  $login = $workbooks->login(array_merge($login_params, array('logical_database_id' => $default_database_id)));
}

if ($login['http_status'] <> WorkbooksApi::HTTP_STATUS_OK) {
  $workbooks->log('Login failed.', $login, 'error');
  exit($exit_error);
}


/*
 * We now have a valid logged-in session. 
 */

/*
 * 1. List all the class names
 */
$fetch_summary = array(
  '_select_columns[]'    => array(                                   // An array, of columns to select
      'class_name',
  )
);
$response = $workbooks->get('metadata/types', $fetch_summary);
$workbooks->log('Fetched objects', $response);
assert_response($workbooks, $response, 'ok');
$workbooks->log('Fetched objects', $response['data']);

/*
 * 2. A set of data for a defined set of class names
 */
$fetch_some = array(
  'class_names[]' => array(                                          // An array, of class_names to fetch
      'Private::Searchable',
      'Private::Crm::Person',
      'Private::Crm::Organisation',
      'Private::Crm::Case',
    ),
  '_select_columns[]'    => array(                                   // An array, of columns to select
      'class_name',
      'base_class_name',
      'human_class_name',
      'human_class_name_plural',
      'human_class_description',
      'instances_described_by',
      'icon',
      'help_url',
      'controller_path',
  )
);
$response = $workbooks->get('metadata/types', $fetch_some);
$workbooks->log('Fetched objects', $response);
assert_response($workbooks, $response, 'ok');
$workbooks->log('Fetched objects', $response['data']);

/*
 * 3. Lots of data, including associations and fields, for a single class name
 */
$fetch_some_more = array(
  'class_names[]' => array(                                          // An array, of class_names to fetch
      'Private::Crm::Case',
    ),
  '_select_columns[]'    => array(                                   // An array, of columns to select
      'class_name',
      'base_class_name',
      'human_class_name',
      'human_class_name_plural',
      'human_class_description',
      'instances_described_by',
      'icon',
      'help_url',
      'controller_path',
      'fields',
      'associations',
  )
);
$response = $workbooks->get('metadata/types', $fetch_some_more);
$workbooks->log('Fetched objects', $response);
assert_response($workbooks, $response, 'ok');
$workbooks->log('Fetched objects', $response['data']);

/*
 * 4. Fetch everything
 */
$fetch_all = array();
$response = $workbooks->get('metadata/types', $fetch_all);
$workbooks->log('Fetched objects', $response);
assert_response($workbooks, $response, 'ok');
$workbooks->log('Fetched objects', $response['data']);

/*
 * Logout
 * Arguably testing for successful logout is a bit of a waste of effort...
 */
$logout = $workbooks->logout();
if (!$logout['success']) {
  $workbooks->log('Logout failed.', $logout, 'error');
  exit($exit_error);
}

exit($exit_ok);

?>

