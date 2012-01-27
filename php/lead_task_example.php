<?php
  
/**
 *   A demonstration of using the Workbooks API to create a lead and related task
 *   object via a thin PHP wrapper
 *
 *   Last commit $Id: lead_task_example.php 14702 2011-11-11 21:13:05Z gbarlow $
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
  'application_name'   => 'PHP lead_activity test client',       // Mandatory, should be the "human name" for the client
  'user_agent'         => 'php_lead_activity_test_client/0.1',   // Mandatory, should include version number
  
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
 * We now have a valid logged-in session. This script creates a single Lead, an associated Task, a Person and an Organisation.
 */

/*
 * 1. Create a Lead
 */
$create_one_sales_lead = array(
  'created_through_reference'                => '2345',
  'lead_source_type'                         => 'Web Lead',
  'org_lead_party[name]'                     => 'Salem Products',
  'org_lead_party[main_location[telephone]]' => '0123456789',
  'org_lead_party[main_location[email]]'     => 'sales@salemproducts.com',
  'org_lead_party[main_location[country]]'   => 'United Kingdom',
  'person_lead_party[name]'                  => 'Samuel Stevens',
);

$response = $workbooks->create('crm/sales_leads', $create_one_sales_lead);
assert_response($workbooks, $response, 'ok');
$sales_lead_object_id_lock_versions = affected_object_id_versions($response);
$lead_id = $sales_lead_object_id_lock_versions[0]['id'];

/*
 * 2. Create an organisation.
 */
$create_one_organisation = array(
  'created_through_reference'            => '56789',
  'name'                                 => 'Salem Products',
  'industry'                             => 'Professional Services',
  'main_location[country]'               => 'United Kingdom',
  'main_location[county_province_state]' => 'Berkshire',
  'main_location[fax]'                   => '01234567890',
  'main_location[postcode]'              => 'RG1 1AA',
  'main_location[street_address]'        => '1 Main Street',
  'main_location[telephone]'             => '0123456789',
  'main_location[town]'                  => 'Readington',
  'organisation_annual_revenue'          => '12500000 GBP',
  'website'                              => 'http://www.salemproducts.ltd.uk/',
);

$response = $workbooks->create('crm/organisations', $create_one_organisation);
assert_response($workbooks, $response, 'ok');
$organisation_object_id_lock_versions = affected_object_id_versions($response);
$organisation_id = $organisation_object_id_lock_versions[0]['id'];

/*
 * 3. Create a person working for that organisation
 */
$create_one_person = array(
  'created_through_reference'            => '56901',
  'employer_link'                        => $organisation_id,
  'name'                                 => 'Samuel Stevens',
  'main_location[mobile]'                => '0777 666 555',
  'person_first_name'                    => 'Samuel',
  'person_last_name'                     => 'Stevens',
  'person_job_title'                     => 'Product Development Director',
);

$response = $workbooks->create('crm/people', $create_one_person);
assert_response($workbooks, $response, 'ok');
$person_object_id_lock_versions = affected_object_id_versions($response);
$person_id = $person_object_id_lock_versions[0]['id'];

/*
 * 4. Find the ActivityQueue to put the task on (we are looking for 'Unassigned')
 */
$activity_queue_filter_limit_select = array(
  '_ff[]'                => array('queue_type'),
  '_ft[]'                => array('eq'),             
  '_fc[]'                => array('Unassigned'), 
  '_select_columns[]'    => array(                             // An array, of columns to select
    'id',
    'lock_version',
    'name',
  )
);
$response = $workbooks->get('activity/activity_queues', $activity_queue_filter_limit_select);
assert_response($workbooks, $response, 'ok');
$workbooks->log('Fetched objects', $response['data']);
$unassigned_queue_id = $response['data'][0]['id'];

/*
 * 5. Create a Task.
 */
$create_one_task = array(
  'activity_priority'                         => 'Medium',
  'activity_status'                           => 'New',
  'activity_type'                             => 'To-do',
  'assigned_to'                               => $unassigned_queue_id,
  'created_through_reference'                 => '4567',
  'description'                               => '<b>Initial Call</b> (rich text area content)',
  'due_date'                                  => '31 Dec 2019',
  'name'                                      => 'Initial Call to Salem Products (Samuel Stevens)',
  'primary_contact_id'                        => $person_id,
  'primary_contact_type'                      => 'Private::Crm::Person',
  'reminder_datetime'                         => 'Tue Dec 24 09:00:00 UTC 2019',
  'reminder_enabled'                          => true,
);

$response = $workbooks->create('activity/tasks', $create_one_task);
assert_response($workbooks, $response, 'ok');
$task_object_id_lock_versions = affected_object_id_versions($response);
$task_id = $task_object_id_lock_versions[0]['id'];

/*
 * 6. Link the Task to the Lead
 */
$create_one_activity_link = array(
  'activity_id'                               => $task_id,
  'activity_type'                             => 'Private::Activity::Task',
  'resource_id'                               => $lead_id,
  'resource_type'                             => 'Private::Crm::SalesLead',
);

$response = $workbooks->create('activity/activity_links', $create_one_activity_link);
assert_response($workbooks, $response, 'ok');
$activity_link_object_id_lock_versions = affected_object_id_versions($response);
 
exit($exit_ok);

?>

