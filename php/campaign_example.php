<?php
  
/**
 *   A demonstration of using the Workbooks API to operate on Campaigns and a number of
 *   related objects via a thin PHP wrapper
 *
 *   Last commit $Id: campaign_example.php 16982 2012-07-31 11:28:14Z jkay $
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

require_once 'workbooks_api.php';

/* If not running under the Workbooks Process Engine create a session */
require 'test_login_helper.php';

/*
 * We now have a valid logged-in session. This script does a series of 'CRUD' (Create, Read, Update, Delete) operations.
 */


/*
 * Create two campaigns
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

$response = $workbooks->assertCreate('crm/campaigns', $create_two_campaigns);
$campaign_object_id_lock_versions = $workbooks->idVersions($response);

/*
 * Update those campaigns
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

$response = $workbooks->assertUpdate('crm/campaigns', $update_two_campaigns);
$campaign_object_id_lock_versions = $workbooks->idVersions($response);
$campaign_id = $campaign_object_id_lock_versions[0]['id'];

/*
 * List up to the first ten campaigns of status 'Planned', just selecting a few columns to retrieve
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
$response = $workbooks->assertGet('crm/campaigns', $campaign_filter_limit_select);
$workbooks->log('Fetched objects', $response['data']);

/*
 * Create three new Campaign Membership Statuses for the first Campaign
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

$response = $workbooks->assertCreate('crm/campaign_membership_status', $create_three_campaign_membership_statuses);
$campaign_membership_status_object_id_lock_versions = $workbooks->idVersions($response);
$campaign_membership_status_ids = array(
  'Potential' => $campaign_membership_status_object_id_lock_versions[0]['id'],
  'Cancelled' => $campaign_membership_status_object_id_lock_versions[1]['id'],
  'Booked'    => $campaign_membership_status_object_id_lock_versions[2]['id'],
);

/*
 * List a couple of the default Campaign Statuses for a Campaign (we delete these next)
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
$response = $workbooks->assertGet('crm/campaign_membership_status', $campaign_membership_status_filter_limit_select);
$workbooks->log('Fetched objects', $response['data']);

/*
 * Delete the default Campaign Statuses just fetched so we are left with our custom ones plus any mandatory ones
 */
$response = $workbooks->assertDelete('crm/campaign_membership_status', $response['data']);

/*
 * Add an organisation.
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

$response = $workbooks->assertCreate('crm/organisations', $create_one_organisation);
$organisation_object_id_lock_versions = $workbooks->idVersions($response);
$organisation_id = $organisation_object_id_lock_versions[0]['id'];

/*
 * Add two people working for that organisation
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

$response = $workbooks->assertCreate('crm/people', $create_two_people);
$people_object_id_lock_versions = $workbooks->idVersions($response);

/*
 * Find two people. For this example, just get the last two by ID.
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
$response = $workbooks->assertGet('crm/people', $people_filter_limit_select);
$workbooks->log('Fetched objects', $response['data']);

/*
 * Add those two people as Campaign members with statuses.
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

$response = $workbooks->assertCreate('crm/campaign_membership', $create_two_campaign_members);
$campaign_membership_object_id_lock_versions = $workbooks->idVersions($response);

/*
 * Change Status of a Campaign member.
 */
$update_one_campaign_member_status = array(
  array (
    'id'                                   => $campaign_membership_object_id_lock_versions[1]['id'],
    'lock_version'                         => $campaign_membership_object_id_lock_versions[1]['lock_version'],
    'campaign_membership_status_id'        => $campaign_membership_status_ids['Cancelled'],
  ),
);

$response = $workbooks->assertUpdate('crm/campaign_membership', $update_one_campaign_member_status);
$campaign_member_status_object_id_lock_versions = $workbooks->idVersions($response);

/*
 * Delete a Campaign member.
 */
$response = $workbooks->assertDelete('crm/campaign_membership', $campaign_member_status_object_id_lock_versions);

/*
 * Delete the campaigns which were created in this script
 */
$response = $workbooks->assertDelete('crm/campaigns', $campaign_object_id_lock_versions);

testExit($workbooks);

?>

