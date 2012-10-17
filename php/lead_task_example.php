<?php
  
/**
 *   A demonstration of using the Workbooks API to create a lead and related task
 *   object via a thin PHP wrapper
 *
 *   Last commit $Id: lead_task_example.php 16982 2012-07-31 11:28:14Z jkay $
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
 * Create a Lead
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

$response = $workbooks->assertCreate('crm/sales_leads', $create_one_sales_lead);
$sales_lead_object_id_lock_versions = $workbooks->idVersions($response);
$lead_id = $sales_lead_object_id_lock_versions[0]['id'];

/*
 * Create an organisation.
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

$response = $workbooks->assertCreate('crm/organisations', $create_one_organisation);
$organisation_object_id_lock_versions = $workbooks->idVersions($response);
$organisation_id = $organisation_object_id_lock_versions[0]['id'];

/*
 * Create a person working for that organisation
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

$response = $workbooks->assertCreate('crm/people', $create_one_person);
$person_object_id_lock_versions = $workbooks->idVersions($response);
$person_id = $person_object_id_lock_versions[0]['id'];

/*
 * Find the ActivityQueue to put the task on (we are looking for 'Unassigned')
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
$response = $workbooks->assertGet('activity/activity_queues', $activity_queue_filter_limit_select);
$workbooks->log('Fetched objects', $response['data']);
$unassigned_queue_id = $response['data'][0]['id'];

/*
 * Create a Task.
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

$response = $workbooks->assertCreate('activity/tasks', $create_one_task);
$task_object_id_lock_versions = $workbooks->idVersions($response);
$task_id = $task_object_id_lock_versions[0]['id'];

/*
 * Link the Task to the Lead
 */
$create_one_activity_link = array(
  'activity_id'                               => $task_id,
  'activity_type'                             => 'Private::Activity::Task',
  'resource_id'                               => $lead_id,
  'resource_type'                             => 'Private::Crm::SalesLead',
);

$response = $workbooks->assertCreate('activity/activity_links', $create_one_activity_link);
 
testExit($workbooks);

?>

