<?php
  
/**
 *   A demonstration of using the Workbooks API to operate on Campaigns and a number of
 *   related objects via a thin PHP wrapper
 *
 *   Last commit $Id: campaign_example.php 12114 2011-03-30 16:02:23Z jkay $
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
  'application_name'   => 'PHP campaigns test client',           // Mandatory, should be the "human name" for the client
  'user_agent'         => 'php_campaigns_test_client/0.1',       // Mandatory, should include version number
  
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
 * 1. Create two campaigns
 */
$create_two_campaigns = array(
  array(
    'name'                                 => 'Efficiency Counts',
    'created_through_reference'            => '12345',
    'start_date'                           => '01 Oct 2010',
    'end_date'                             => '31 Oct 2010',
    'budget'                               => '1000 GBP',
    'cost_adjustment'                      => '100 GBP',
    'target_revenue'                       => '20000 GBP',
    'status'                               => 'Planned',
    'category'                             => 'Seminar',
    'goals'                                => 'Educate 20 thought-leaders',
  ),
  array(
    'name'                                 => 'Consumption Costs',
    'created_through_reference'            => '12346',
    'start_date'                           => '01 Nov 2010',
    'end_date'                             => '30 Nov 2010',
    'budget'                               => '750 GBP',
    'cost_adjustment'                      => '0 GBP',
    'target_revenue'                       => '10000 GBP',
    'status'                               => 'Planned',
    'category'                             => 'Seminar',
    'goals'                                => 'Follow-on to the Efficiency Counts event',
  ),
);

$response = $workbooks->create('crm/campaigns', $create_two_campaigns);
assert_response($workbooks, $response, 'ok');
$campaign_object_id_lock_versions = affected_object_id_versions($response);

/*
 * 2. Update those campaigns
 */
$update_two_campaigns = array(
  array (
    'id'                                   => $campaign_object_id_lock_versions[0]['id'],
    'lock_version'                         => $campaign_object_id_lock_versions[0]['lock_version'],
    'budget'                               => '1500 GBP',
    'cost_adjustment'                      => '600 GBP',
    'start_date'                           => '08 Oct 2010',
  ),
  array (
    'id'                                   => $campaign_object_id_lock_versions[1]['id'],
    'lock_version'                         => $campaign_object_id_lock_versions[1]['lock_version'],
    'name'                                 => 'Consumption Costs (UK)',
  ),
);

$response = $workbooks->update('crm/campaigns', $update_two_campaigns);
assert_response($workbooks, $response, 'ok');
$campaign_object_id_lock_versions = affected_object_id_versions($response);
$campaign_id = $campaign_object_id_lock_versions[0]['id'];

/*
 * 3. List up to the first ten campaigns of status 'Planned', just selecting a few columns to retrieve
 */
$campaign_filter_limit_select = array(
  '_start'               => '0',                                     // Starting from the 'zeroth' record
  '_limit'               => '10',                                    //   fetch up to 10 records
  '_sort'                => 'id',                                    // Sort by 'id'
  '_dir'                 => 'ASC',                                   //   in ascending order
  '_ff[]'                => 'status',                                // Filter by this column
  '_ft[]'                => 'ct',                                    //   containing
  '_fc[]'                => 'Planned',                               //   'Planned'
  '_select_columns[]'    => array(                                   // An array, of columns to select
    'id',
    'lock_version',
    'name',
    'start_date',
    'end_date',
    'goals',
    'budget',
  )
);
$response = $workbooks->get('crm/campaigns', $campaign_filter_limit_select);
assert_response($workbooks, $response, 'ok');
$workbooks->log('Fetched objects', $response['data']);

/*
 * 4. Create three new Campaign Membership Statuses for the first Campaign
 */
$create_three_campaign_membership_statuses = array(
  array(
    'status'                               => 'Potential',
    'created_through_reference'            => '12347',
    'marketing_campaign_id'                => $campaign_id,
  ),
  array(
    'status'                               => 'Cancelled',
    'created_through_reference'            => '12348',
    'marketing_campaign_id'                => $campaign_id,
  ),
  array(
    'status'                               => 'Booked',
    'created_through_reference'            => '12349',
    'marketing_campaign_id'                => $campaign_id,
  ),
);

$response = $workbooks->create('crm/campaign_membership_status', $create_three_campaign_membership_statuses);
assert_response($workbooks, $response, 'ok');
$campaign_membership_status_object_id_lock_versions = affected_object_id_versions($response);
$campaign_membership_status_ids = array(
  'Potential' => $campaign_membership_status_object_id_lock_versions[0]['id'],
  'Cancelled' => $campaign_membership_status_object_id_lock_versions[1]['id'],
  'Booked'    => $campaign_membership_status_object_id_lock_versions[2]['id'],
);

/*
 * 5. List a couple of the default Campaign Statuses for a Campaign (we delete these next)
 */
$campaign_membership_status_filter_limit_select = array(
  '_fm'               => '1 AND (2 OR 3)',                                                // Define how to use the following criteria
  '_ff[]'             => array('marketing_campaign_id',  'status',      'status'),        // Filter by these three criteria
  '_ft[]'             => array('eq',                     'eq',          'eq'),             
  '_fc[]'             => array($campaign_id,             'Interested',  'Not Interested'), 
  '_select_columns[]' => array(                                                           // An array, of columns to select
    'id',
    'lock_version',
    'name',
    'status',
  )
);
$response = $workbooks->get('crm/campaign_membership_status', $campaign_membership_status_filter_limit_select);
assert_response($workbooks, $response, 'ok');
$workbooks->log('Fetched objects', $response['data']);

/*
 * 6. Delete the default Campaign Statuses just fetched so we are left with our custom ones plus any mandatory ones
 */
$response = $workbooks->delete('crm/campaign_membership_status', $response['data']);
assert_response($workbooks, $response, 'ok');

/*
 * 7. Add an organisation.
 */
$create_one_organisation = array(
  array(
    'created_through_reference'            => '567',
    'name'                                 => 'Heroic Consultants',
    'industry'                             => 'Professional Services',
    'main_location[country]'               => 'United Kingdom',
    'main_location[county_province_state]' => 'Berkshire',
    'main_location[fax]'                   => '01234567890',
    'main_location[postcode]'              => 'RG1 1AA',
    'main_location[street_address]'        => '1 Main Street',
    'main_location[telephone]'             => '01223344556',
    'main_location[town]'                  => 'Readington',
    'organisation_annual_revenue'          => '12500000 GBP',
    'website'                              => 'http://www.heroicconsultants.ltd.uk/',
  ),
);

$response = $workbooks->create('crm/organisations', $create_one_organisation);
assert_response($workbooks, $response, 'ok');
$organisation_object_id_lock_versions = affected_object_id_versions($response);
$organisation_id = $organisation_object_id_lock_versions[0]['id'];

/*
 * 8. Add two people working for that organisation
 */
$create_two_people = array(
  array(
    'created_through_reference'            => '569',
    'employer_link'                        => $organisation_id,
    'name'                                 => 'James Aa',
    'main_location[email]'                 => 'jimbo@kay.com',
    'main_location[mobile]'                => '0777 666 555',
    'person_first_name'                    => 'Jimbo',
    'person_last_name'                     => 'Aa',
    'person_job_title'                     => 'Senior Consultant',
  ),
  array(
    'created_through_reference'            => '570',
    'employer_link'                        => $organisation_id,
    'name'                                 => 'Xavier Xe',
    'main_location[email]'                 => 'xavier@xe.com',
    'main_location[mobile]'                => '0777 666 444',
    'person_first_name'                    => 'Xavier',
    'person_last_name'                     => 'Xe',
    'person_job_title'                     => 'Principal Consultant',
  ),
);

$response = $workbooks->create('crm/people', $create_two_people);
assert_response($workbooks, $response, 'ok');
$people_object_id_lock_versions = affected_object_id_versions($response);

/*
 * 9. Find two people. For this example, just get the last two by ID.
 */
$people_filter_limit_select = array(
  '_start'               => '0',                                     // Starting from the 'zeroth' record
  '_limit'               => '2',                                     //   fetch up to 2 records
  '_sort'                => 'id',                                    // Sort by 'id'
  '_dir'                 => 'DESC',                                  //   in descending order
  '_select_columns[]'    => array(                                   // An array, of columns to select
    'id',
    'lock_version',
    'name',
  )
);
$response = $workbooks->get('crm/people', $people_filter_limit_select);
assert_response($workbooks, $response, 'ok');
$workbooks->log('Fetched objects', $response['data']);

/*
 * 10. Add those two people as Campaign members with statuses.
 */
$create_two_campaign_members = array(
  array(
    'campaign_membership_status_id'        => $campaign_membership_status_ids['Potential'],
    'created_through_reference'            => '9870',
    'marketing_campaign_id'                => $campaign_id,
    'member_name'                          => 'James Aa',
    'party_or_lead_id'                     => $response['data'][0]['id'],
    'party_or_lead_type'                   => 'Private::Crm::Person',
  ),
  array(
    'campaign_membership_status_id'        => $campaign_membership_status_ids['Booked'],
    'created_through_reference'            => '9871',
    'marketing_campaign_id'                => $campaign_id,
    'member_name'                          => 'Xavier Xe',
    'party_or_lead_id'                     => $response['data'][1]['id'],
    'party_or_lead_type'                   => 'Private::Crm::Person',
  ),
);

$response = $workbooks->create('crm/campaign_membership', $create_two_campaign_members);
assert_response($workbooks, $response, 'ok');
$campaign_membership_object_id_lock_versions = affected_object_id_versions($response);

/*
 * 12. Change Status of a Campaign member.
 */
$update_one_campaign_member_status = array(
  array (
    'id'                                   => $campaign_membership_object_id_lock_versions[1]['id'],
    'lock_version'                         => $campaign_membership_object_id_lock_versions[1]['lock_version'],
    'campaign_membership_status_id'        => $campaign_membership_status_ids['Cancelled'],
  ),
);

$response = $workbooks->update('crm/campaign_membership', $update_one_campaign_member_status);
assert_response($workbooks, $response, 'ok');
$campaign_member_status_object_id_lock_versions = affected_object_id_versions($response);

/*
 * 13. Delete a Campaign member.
 */
$response = $workbooks->delete('crm/campaign_membership', $campaign_member_status_object_id_lock_versions);
assert_response($workbooks, $response, 'ok');

/*
 * 14. Delete the campaigns which were created in this script
 */
$response = $workbooks->delete('crm/campaigns', $campaign_object_id_lock_versions);
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

