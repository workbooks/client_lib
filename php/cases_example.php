<?php
  
/**
 *   A demonstration of using the Workbooks API to operate on Cases via a thin PHP wrapper.
 *
 *   Last commit $Id: cases_example.php 18524 2013-03-06 11:15:59Z jkay $
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
 * Find the CaseQueue to put the case on (we are looking for 'Unassigned').
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
$response = $workbooks->assertGet('crm/case_queues', $case_queue_filter_limit_select);
$workbooks->log('Fetched objects', $response['data']);
$unassigned_queue_id = $response['data'][0]['id'];


/*
 * Discover IDs for picklist entries.
 *
 * Some items are picklists whose values can be configured by the customer. Those picklist IDs are listed in the 
 * API meta-data and these do not change.
 *
 * Case Priority: Medium
 */
$case_priority_picklist_id = 33;
$case_priority_picklist_api = 'picklist_data/Private_PicklistEntry/id/value';
$response = $workbooks->assertGet($case_priority_picklist_api, array('picklist_id' => $case_priority_picklist_id));
$workbooks->log('Fetched objects', $response['data']);
foreach ($response['data'] as &$entry) {
  $case_priority_medium_id = $entry['id'];
  if (preg_match("/MEDIUM/i", $entry['value'])) {
    break;
  }
}

/*
 * Case Source: Web
 */
$case_source_picklist_id = 36;
$case_source_picklist_api = 'picklist_data/Private_PicklistEntry/id/value';
$response = $workbooks->assertGet($case_source_picklist_api, array('picklist_id' => $case_source_picklist_id));
$workbooks->log('Fetched objects', $response['data']);
foreach ($response['data'] as &$entry) {
  $case_source_web_id = $entry['id'];
  if (preg_match("/WEB/i", $entry['value'])) {
    break;
  }
}

/*
 * Case Status: 'New' and 'In progress'
 */
$case_status_picklist_id = 35;
$case_status_picklist_api = 'picklist_data/Private_PicklistEntry/id/value';
$response = $workbooks->assertGet($case_status_picklist_api, array('picklist_id' => $case_status_picklist_id));
$workbooks->log('Fetched objects', $response['data']);
foreach ($response['data'] as &$entry) {
  if (preg_match("/NEW/i", $entry['value'])) {
    $case_status_new_id = $entry['id'];
  }
  if (preg_match("/IN PROGRESS/i", $entry['value'])) {
    $case_status_in_progress_id = $entry['id'];
  }
}

/*
 * Case Type: General
 */
$case_type_picklist_id = 37;
$case_type_picklist_api = 'picklist_data/Private_PicklistEntry/id/value';
$response = $workbooks->assertGet($case_type_picklist_api, array('picklist_id' => $case_type_picklist_id));
$workbooks->log('Fetched objects', $response['data']);
foreach ($response['data'] as &$entry) {
  $case_type_general_id = $entry['id'];
  if (preg_match("/GENERAL/i", $entry['value'])) {
    break;
  }
}

/*
 * Case Product Category: Services
 */
$case_product_category_picklist_id = 5;
$case_product_category_picklist_api = 'picklist_data/Private_PicklistEntry/id/value';
$response = $workbooks->assertGet($case_product_category_picklist_api, array('picklist_id' => $case_product_category_picklist_id));
$workbooks->log('Fetched objects', $response['data']);
foreach ($response['data'] as &$entry) {
  $case_product_category_services_id = $entry['id'];
  if (preg_match("/SERVICES/i", $entry['value'])) {
    break;
  }
}

/*
 * Find someone with the surname 'Dean' and a phone number starting '020'. In reality if the case contact were not found you
 * would create a new Person (see people_example.php). Here we just select a few columns to retrieve and take the first matching
 * entry.
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
$response = $workbooks->assertGet('crm/people', $filter_limit_select);
$workbooks->log('Fetched objects', $response['data']);
$contact_id = $response['data'][0]['id'];
$contact_phone = $response['data'][0]['main_location[telephone]'];
$contact_email = $response['data'][0]['main_location[email]'];

/*
 * Let's create a case now we have everything we want to know.
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

$response = $workbooks->assertCreate('crm/cases', $create_one_case);
$case_object_id_lock_versions = $workbooks->idVersions($response);
$case_reference = $response['affected_objects'][0]['object_ref'];
$workbooks->log('Created case, reference', $case_reference);

/*
 * Update the case
 */
$update_case = array (
    'id'                                   => $case_object_id_lock_versions[0]['id'],
    'lock_version'                         => $case_object_id_lock_versions[0]['lock_version'],
    'case_status_id'                       => $case_status_in_progress_id,
);

$response = $workbooks->assertUpdate('crm/cases', $update_case);
$case_object_id_lock_versions = $workbooks->idVersions($response);
$workbooks->log('Updated case status', $response);

/*
 * Delete the case which was created in this script
 */
$response = $workbooks->assertDelete('crm/cases', $case_object_id_lock_versions);

testExit($workbooks);

?>

