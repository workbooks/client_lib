<?php
  
/**
 *   A demonstration of using the Workbooks API to operate on Cases via a thin PHP wrapper.
 *
 *   Last commit $Id$
 *
 *       The MIT License
 *
 *       Copyright (c) 2008-2010, Workbooks Online Limited.
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
 * Extract ids and lock_versions from the 'affected_objects' in a response and return them as an Array of Arrays.
 */
function affected_object_id_versions($response) {
  $retval = array();
  foreach ($response['affected_objects'] as &$affected) {
    $retval[]= array(
      'id'           => $affected['id'], 
      'lock_version' => $affected['lock_version'],
    );
  }
  return $retval;
}


/*
 * Initialise the Workbooks API object
 */
$workbooks = new WorkbooksApi(array(
  'application_name'   => 'php_cases_example',                   // Mandatory, should be the "human name" for the client
  'user_agent'         => 'php_cases_example/0.1',               // Mandatory, should include version number
  
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
 * We now have a valid logged-in session. This script does a series of 'CRUD' (Create, Read, Update, Delete) operations.
 */

/*
 * 1. Find the CaseQueue to put the case on (we are looking for 'Unassigned').
 */
$case_queue_filter_limit_select = array(
  '_ff[]'                => array('queue_type'),
  '_ft[]'                => array('eq'),             
  '_fc[]'                => array('Unassigned'), 
  '_select_columns[]'    => array(                             // An array, of columns to select
    'id',
    'lock_version',
    'name',
  )
);
$response = $workbooks->get('crm/case_queues', $case_queue_filter_limit_select);
assert_response($workbooks, $response, 'ok');
$workbooks->log('Fetched objects', $response['data']);
$unassigned_queue_id = $response['data'][0]['id'];


/*
 * 2. Discover IDs for picklist entries.
 *
 * Some items are picklists whose values can be configured by the customer. Those picklist IDs are listed in the 
 * API meta-data and these do not change.
 *
 * 2a. Case Priority: Medium
 */
$case_priority_picklist_id = 33;
$case_priority_picklist_api = 'picklist_data/Private_PicklistEntry/id/value';
$response = $workbooks->get($case_priority_picklist_api, array('picklist_id' => $case_priority_picklist_id));
assert_response($workbooks, $response, 'ok');
$workbooks->log('Fetched objects', $response['data']);
foreach ($response['data'] as &$entry) {
  $case_priority_medium_id = $entry['id'];
  if (preg_match("/MEDIUM/i", $entry['value'])) {
    break;
  }
}

/*
 * 2b. Case Source: Web
 */
$case_source_picklist_id = 36;
$case_source_picklist_api = 'picklist_data/Private_PicklistEntry/id/value';
$response = $workbooks->get($case_source_picklist_api, array('picklist_id' => $case_source_picklist_id));
assert_response($workbooks, $response, 'ok');
$workbooks->log('Fetched objects', $response['data']);
foreach ($response['data'] as &$entry) {
  $case_source_web_id = $entry['id'];
  if (preg_match("/WEB/i", $entry['value'])) {
    break;
  }
}

/*
 * 2c. Case Status: New
 */
$case_status_picklist_id = 35;
$case_status_picklist_api = 'picklist_data/Private_PicklistEntry/id/value';
$response = $workbooks->get($case_status_picklist_api, array('picklist_id' => $case_status_picklist_id));
assert_response($workbooks, $response, 'ok');
$workbooks->log('Fetched objects', $response['data']);
foreach ($response['data'] as &$entry) {
  $case_status_new_id = $entry['id'];
  if (preg_match("/NEW/i", $entry['value'])) {
    break;
  }
}

/*
 * 2d. Case Type: General
 */
$case_type_picklist_id = 37;
$case_type_picklist_api = 'picklist_data/Private_PicklistEntry/id/value';
$response = $workbooks->get($case_type_picklist_api, array('picklist_id' => $case_type_picklist_id));
assert_response($workbooks, $response, 'ok');
$workbooks->log('Fetched objects', $response['data']);
foreach ($response['data'] as &$entry) {
  $case_type_general_id = $entry['id'];
  if (preg_match("/GENERAL/i", $entry['value'])) {
    break;
  }
}

/*
 * 2e. Case Product Category: Services
 */
$case_product_category_picklist_id = 5;
$case_product_category_picklist_api = 'picklist_data/Private_PicklistEntry/id/value';
$response = $workbooks->get($case_product_category_picklist_api, array('picklist_id' => $case_product_category_picklist_id));
assert_response($workbooks, $response, 'ok');
$workbooks->log('Fetched objects', $response['data']);
foreach ($response['data'] as &$entry) {
  $case_product_category_services_id = $entry['id'];
  if (preg_match("/SERVICES/i", $entry['value'])) {
    break;
  }
}

/*
 * 3. Find someone with the surname 'Dean' and a phone number starting '020'. In reality if the case contact were not found you
 *    would create a new Person (see people_example.php). Here we just select a few columns to retrieve and take the first matching
 *    entry.
 */
$filter_limit_select = array(
  '_start'               => '0',                                                    // Starting from the 'zeroth' record
  '_limit'               => '100',                                                  //   fetch up to 100 records
  '_sort'                => 'id',                                                   // Sort by 'id'
  '_dir'                 => 'ASC',                                                  //   in ascending order
  '_ff[]'                => array('person_last_name', 'main_location[telephone]'),  // Filter by these columns
  '_ft[]'                => array('eq',               'bg'),                        //   equals, begins-with
  '_fc[]'                => array('Dean',             '020'),                       //   values to match
  '_select_columns[]'    => array(                                                  // An array, of columns to select
    'id',
    'name',
    'main_location[telephone]',
    'main_location[email]',
  )
);
$response = $workbooks->get('crm/people', $filter_limit_select);
assert_response($workbooks, $response, 'ok');
$workbooks->log('Fetched objects', $response['data']);
$contact_id = $response['data'][0]['id'];
$contact_phone = $response['data'][0]['main_location[telephone]'];
$contact_email = $response['data'][0]['main_location[email]'];

/*
 * 4. Let's create a case now we have everything we want to know.
 */
$create_one_case = array(
  'assigned_to'           => $unassigned_queue_id,                                    // 'Unassigned' CaseQueue ID as retrieved above
  'case_priority_id'      => $case_priority_medium_id,                                // From picklist as retrieved above: 'Medium'
  'case_source_id'        => $case_source_web_id,                                     // From picklist as retrieved above: 'Web'
  'case_status_id'        => $case_status_new_id,                                     // From picklist as retrieved above: 'New'
  'case_type_id'          => $case_type_general_id,                                   // From picklist as retrieved above: 'General'
  'contact_person_id'     => $contact_id,                                             // Refers to the primary contact for the case
  'contact_email'         => $contact_email,                                          //  their email address
  'contact_phone_number'  => $contact_phone,                                          //  their phone number
  'name'                  => 'Account query regarding Credit Card expiry',            // Brief, one-line, case summary
  'problem'               => 'Here is an <b>HTML</b> essay describing what<br/>the problem is.', // Text area
  'product_category_id'   => $case_product_category_services_id                       // From picklist as retrieved above: 'Services'
);

$response = $workbooks->create('crm/cases', $create_one_case);
assert_response($workbooks, $response, 'ok');
$case_object_id_lock_versions = affected_object_id_versions($response);
$case_reference = $response['affected_objects'][0]['object_ref'];
$workbooks->log('Created case, reference', $case_reference);

/*
 * 5. Delete the case which was created in this script
 */
$response = $workbooks->delete('crm/cases', $case_object_id_lock_versions);
assert_response($workbooks, $response, 'ok');

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

