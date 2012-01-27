<?php
  
/**
 *   A demonstration of using the Workbooks API via a thin PHP wrapper
 *
 *   Last commit $Id: simple_example.php 14702 2011-11-11 21:13:05Z gbarlow $
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
 * We now have a valid logged-in session. This script does a series of 'CRUD' (Create, Read, Update, Delete) operations.
 */


/*
 * 1. Create three organisations
 */
$create_three_organisations = array(
  array (
    'name'                                 => 'Freedom & Light Ltd',
    'created_through_reference'            => '12345',
    'industry'                             => 'Media & Entertainment',
    'main_location[country]'               => 'United Kingdom',
    'main_location[county_province_state]' => 'Berkshire',
    'main_location[fax]'                   => '0234 567890',
    'main_location[postcode]'              => 'RG99 9RG',
    'main_location[street_address]'        => '100 Main Street',
    'main_location[telephone]'             => '0123 456789',
    'main_location[town]'                  => 'Beading',
    'no_phone_soliciting'                  => true,
    'no_post_soliciting'                   => true,
    'organisation_annual_revenue'          => '10000000',
    'organisation_category'                => 'Marketing Agency',
    'organisation_company_number'          => '12345678',
    'organisation_num_employees'           => 250,
    'organisation_vat_number'              => 'GB123456',
    'website'                              => 'www.freedomandlight.com',    
  ),
  array (
    'name'                                 => 'Freedom Power Tools Limited',
    'created_through_reference'            => '12346',
  ),
  array (
    'name'                                 => 'Freedom o\' the Seas Recruitment',
    'created_through_reference'            => '12347',
  ),
);

$response = $workbooks->create('crm/organisations', $create_three_organisations);
assert_response($workbooks, $response, 'ok');
$object_id_lock_versions = affected_object_id_versions($response);

/*
 * 2. Update those organisations
 */
$update_three_organisations = array(
  array (
    'id'                                   => $object_id_lock_versions[0]['id'],
    'lock_version'                         => $object_id_lock_versions[0]['lock_version'],
    'name'                                 => 'Freedom & Light Unlimited',
    'main_location[postcode]'              => 'RG66 6RG',
    'main_location[street_address]'        => '199 High Street',
  ),
  array (
    'id'                                   => $object_id_lock_versions[1]['id'],
    'lock_version'                         => $object_id_lock_versions[1]['lock_version'],
    'name'                                 => 'Freedom Power',
  ),
  array (
    'id'                                   => $object_id_lock_versions[2]['id'],
    'lock_version'                         => $object_id_lock_versions[2]['lock_version'],
    'name'                                 => 'Sea Recruitment',
  ),
);

$response = $workbooks->update('crm/organisations', $update_three_organisations);
assert_response($workbooks, $response, 'ok');
$object_id_lock_versions = affected_object_id_versions($response);

/*
 * 3. Combined call to Create, Update and Delete several organisations
 */
$batch_organisations = array(
  array (
    'method'                               => 'CREATE',
    'name'                                 => 'Abercrombie Pies',
    'industry'                             => 'Food',
    'main_location[country]'               => 'United Kingdom',
    'main_location[county_province_state]' => 'Berkshire',
    'main_location[town]'                  => 'Beading',
  ),
  array (
    'method'                               => 'UPDATE',
    'id'                                   => $object_id_lock_versions[0]['id'],
    'lock_version'                         => $object_id_lock_versions[0]['lock_version'],
    'name'                                 => 'Lights \'R Us',
    'main_location[postcode]'              => NULL,   # Clear the postcode.
  ),
  array (
    'method'                               => 'DELETE',
    'id'                                   => $object_id_lock_versions[1]['id'],
    'lock_version'                         => $object_id_lock_versions[1]['lock_version'],
  ),
  array (
    'method'                               => 'DELETE',
    'id'                                   => $object_id_lock_versions[2]['id'],
    'lock_version'                         => $object_id_lock_versions[2]['lock_version'],
  ),
);

$response = $workbooks->batch('crm/organisations', $batch_organisations);
assert_response($workbooks, $response, 'ok');
$object_id_lock_versions = affected_object_id_versions($response);

/*
 * 4. Create a single organisation
 */
$create_one_organisation = array(
  'method'                               => 'CREATE',
  'name'                                 => 'Birkbeck Burgers',
  'industry'                             => 'Food',
  'main_location[country]'               => 'United Kingdom',
  'main_location[county_province_state]' => 'Oxfordshire',
  'main_location[town]'                  => 'Oxford',
);
$response = $workbooks->create('crm/organisations', $create_one_organisation);
assert_response($workbooks, $response, 'ok');
$created_id_lock_versions = affected_object_id_versions($response);
$object_id_lock_versions = array_merge(array($object_id_lock_versions[0]), array($object_id_lock_versions[1]), $created_id_lock_versions);

/*
 * 5. List the first hundred organisations in Berkshire, just selecting a few columns to retrieve
 */
$filter_limit_select = array(
  '_start'               => '0',                                     // Starting from the 'zeroth' record
  '_limit'               => '100',                                   //   fetch up to 100 records
  '_sort'                => 'id',                                    // Sort by 'id'
  '_dir'                 => 'ASC',                                   //   in ascending order
  '_ff[]'                => 'main_location[county_province_state]',  // Filter by this column
  '_ft[]'                => 'ct',                                    //   containing
  '_fc[]'                => 'Berkshire',                             //   'Berkshire'
  '_select_columns[]'    => array(                                   // An array, of columns to select
    'id',
    'lock_version',
    'name',
    'main_location[town]',
    'updated_by_user[person_name]',
  )
);
$response = $workbooks->get('crm/organisations', $filter_limit_select);
assert_response($workbooks, $response, 'ok');
$workbooks->log('Fetched objects', $response['data']);

/*
 * 6. Delete the remaining organisations which were created in this script
 */
$response = $workbooks->delete('crm/organisations', $object_id_lock_versions);
assert_response($workbooks, $response, 'ok');

exit($exit_ok);

?>

