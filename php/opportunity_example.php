<?php
  
/**
 *   A demonstration of using the Workbooks API to operate on Opportunities and a number of
 *   related objects via a thin PHP wrapper
 *
 *   Last commit $Id: opportunity_example.php 13905 2011-09-12 08:30:24Z jkay $
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
    $workbooks->log('Received an unexpected response', array($condensed_status, $response, $expected));
    exit($exit_on_error);
  }
}

/*
 * Extract ids and lock_versions from the 'affected_objects' in a response and
 * return them as an array of arrays.
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
  'application_name'   => 'PHP opportunities test client',           // Mandatory, should be the "human name" for the client
  'user_agent'         => 'php_opportunities_test_client/0.1',       // Mandatory, should include version number
  
  // The following settings are used in Workbooks auto-test environment and are not typical.
  'logger_callback'    => array('WorkbooksApi', 'logAllToStdout'),   // A noisy logger
  'connect_timeout'    => 120,                                       // Optional, defaults to 20 seconds
  'request_timeout'    => 120,                                       // Optional, defaults to 20 seconds
  'service'            => 'http://localhost:3000',                   // Optional, defaults to the Production Workbooks service
  'verify_peer'        => false,                                     // Optional, defaults to checking the peer SSL certificate
));

$workbooks->log('Running test script', __FILE__, 'info');

/*
 * Connect to the service and login
 */
$login_params = array(
  'username' => 'james.kay@workbooks.com',
  'password' => 'abc123',
);

$login = $workbooks->login($login_params);

if ($login['http_status'] == WorkbooksApi::HTTP_STATUS_FORBIDDEN && $login['response']['failure_reason'] == 'no_database_selection_made') {
  /*
   * Multiple databases are available, so one must be choosen. 
   * A good UI might remember the previously selected database or use $databases to
   * present a list of databases for the user to choose from. 
   */
  $default_database_id = $login['response']['default_database_id'];
  $databases = $login['response']['databases'];
  
  /*
   * For this test script simply select the one which was the default when the user
   * last logged in to the Workbooks user interface. This would not be correct for
   * most API clients since the user's choice on any particular session should not
   * necessarily change their choice for all of their API clients.
   */
  $login = $workbooks->login(array_merge($login_params, array('logical_database_id' => $default_database_id)));
}

if ($login['http_status'] <> WorkbooksApi::HTTP_STATUS_OK) {
  $workbooks->log('Login failed.', $login, 'error');
  exit($exit_error);
}


/*
 * There is now have a valid logged-in session. This script does a series of 'CRUD' (Create,
 * Read, Update, Delete) operations.
 */


/*
 * 1. Create two opportunities with different fields populated
 */
$create_two_opportunities = array(
  array(
    'description'                          => 'Opportunity One',
    'created_through_reference'            => '12345',
    'document_date'                        => '01 Oct 2010',
  ),
  array(
    'description'                          => 'Opportunity Two',
    'created_through_reference'            => '12345',
    'document_date'                        => '01 Nov 2010',
  ),
);

$response = $workbooks->create('crm/opportunities', $create_two_opportunities);
assert_response($workbooks, $response, 'ok');
$opportunities_object_id_lock_versions = affected_object_id_versions($response);


/*
 * 2. Update those opportunities; an up to date id and lock_version are required to do this
 */
$update_two_opportunities = array(
  array (
    'id'                                   => $opportunities_object_id_lock_versions[0]['id'],
    'lock_version'                         => $opportunities_object_id_lock_versions[0]['lock_version'],
    'document_currency'                    => 'GBP',
    'home_currency'                        => 'GBP',
    'comment'                              => 'Updating an opportunity...',
  ),
  array (
    'id'                                   => $opportunities_object_id_lock_versions[1]['id'],
    'lock_version'                         => $opportunities_object_id_lock_versions[1]['lock_version'],
    'document_currency'                    => 'GBP',
    'home_currency'                        => 'GBP',
    'fao'                                  => 'CTO'
  ),
);

$response = $workbooks->update('crm/opportunities', $update_two_opportunities);
assert_response($workbooks, $response, 'ok');
$opportunities_object_id_lock_versions = affected_object_id_versions($response);


/*
 * 3. List a maximum of 10 people in the system whose surname begins with P
 */
$filter_limit_select = array(
  '_start'               => '0',                                     // Starting from the 'zeroth' record
  '_limit'               => '10',                                    //   fetch up to 10 records
  '_sort'                => 'id',                                    // Sort by 'id'
  '_dir'                 => 'ASC',                                   //   in ascending order
  '_ff[]'                => 'person_last_name',                      // Filter by this column
  '_ft[]'                => 'bg',                                    //   begins with
  '_fc[]'                => 'P',                                     //   'P'
  '_select_columns[]'    => array(                                   // An array, of columns to select
    'id',
    'lock_version',
    'name',
    'person_first_name',
    'person_last_name',
    'updated_at',
    'updated_by_user[person_name]',
  )
);
$response = $workbooks->get('crm/people', $filter_limit_select);
assert_response($workbooks, $response, 'ok');
$workbooks->log('Fetched objects', $response['data']);
$people_data = $response['data'];


/*
 * 4. Set the first Person found to be a Competitor on the first new Opportunity,
 *    and the second and third People as Partners on the second new Opportunity
 */
$set_opportunity_contact = array(
  array(
    'id'                                   => $opportunities_object_id_lock_versions[0]['id'],
    'lock_version'                         => $opportunities_object_id_lock_versions[0]['lock_version'],
    'opportunity_contact_ids'              => array($people_data[0]['id']),
    'opportunity_contact_roles'            => array('Competitor'),
  ),
  array(
    'id'                                   => $opportunities_object_id_lock_versions[1]['id'],
    'lock_version'                         => $opportunities_object_id_lock_versions[1]['lock_version'],
    'opportunity_contact_ids'              => array($people_data[1]['id'], $people_data[2]['id']),
    'opportunity_contact_roles'            => array('Partner', 'Partner'),
  ),
);

$response = $workbooks->update('crm/opportunities', $set_opportunity_contact);
assert_response($workbooks, $response, 'ok');
$workbooks->log('Response', $response);
$opportunities_object_id_lock_versions = affected_object_id_versions($response);


/*
 * 5. List the newly added and updated Opportunities
 */
$people_filter_limit_select = array(
  '_start'               => '0',                                     // Starting from the 'zeroth' record
  '_limit'               => '2',                                     //   fetch up to 2 records
  '_sort'                => 'id',                                    // Sort by 'id'
  '_dir'                 => 'DESC',                                  //   in descending order
  '_select_columns[]'    => array(                                   // An array, of columns to select
    'id',
    'lock_version',
    'description',
    'opportunity_contact_ids',
    'opportunity_contact_roles',
    'document_currency',
    'home_currency',
    'comment',
    'fao',
    'document_date',
    'created_through_reference'
  )
);
$response = $workbooks->get('crm/people', $people_filter_limit_select);
assert_response($workbooks, $response, 'ok');
$workbooks->log('Fetched objects', $response['data']);


/*
 * 6. Delete the Opportunities which were created in this script
 */
$response = $workbooks->delete('crm/opportunities', $opportunities_object_id_lock_versions);
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
