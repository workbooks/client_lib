<?php
  
/*   A demonstration of using the Workbooks API to create, update, delete actions via a thin PHP wrapper
 *   The example also shows how to set a value on a record for a given action.
 *   Two different types of action are being tested here  - web action (process) and scheduled action (process)
 *
 *   Last commit $Id: action_example.php 49692 2020-12-11 10:51:02Z ebeckett $
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
 * We now have a valid logged-in session.
 */




/*
 * WEB PROCESSES.
 */


/*
 * Create the web processes
 */

$create_web_processes = array(
  array (
    'title'                         => 'Web Process',
    'name'                          => 'WebProcessTest1',
    'script_id'                     => 1,
    'script_name'                   => 'Hello User',
    'web_identifier'                => 'Test1',
    'authenticated_run_as'          => 101854,
    'log_level'                     => 'debug',
    'log_level_expiry'              => '2020-12-10T12:00:00.000Z',
  ),
  array (
    'title'                         => 'Web Process 2',
    'name'                          => 'WebProcessTest2',
    'script_id'                     => 1,
    'script_name'                   => 'Hello User',
    'web_identifier'                => 'Test1',
  ),
);

$response = $workbooks->assertCreate('automation/web_actions', $create_web_processes);
$created_processes = $response['affected_objects']; 
$workbooks->log('Created web processes', $created_processes);
$object_id_lock_versions = $workbooks->idVersions($response);

foreach (array(0,1) as $value) {
  $id = $created_processes[$value]['id'];
  $status = $created_processes[$value]['status'];
  
  # Log each record seperatelty, so that we can see the all the fields without truncation.
  $workbooks->log("Created web process $value", $created_processes[$value]);
  
}


/*
 * Update the web processes
 */

$update_web_processes = array(
  array (
    'id'                            => $object_id_lock_versions[0]['id'],
    'lock_version'                  => $object_id_lock_versions[0]['lock_version'],
    'authenticated_run_as'          => 0,
    'log_level'                     => 'error',
    'log_level_expiry'              => '2020-12-15T12:00:00.000Z',
  ),
  array (
    'id'                            => $object_id_lock_versions[1]['id'],
    'lock_version'                  => $object_id_lock_versions[1]['lock_version'],
    'name'                          => 'WebProcessTest2Updated',
  ),
);

$response = $workbooks->assertUpdate('automation/web_actions', $update_web_processes);
$updated_web_processes = $response['affected_objects'];
$workbooks->log('Updated web processes', $updated_web_processes);
# Get the lock versons os that we can later delete the custom fields
$object_id_lock_versions = $workbooks->idVersions($response);


/*
 * Read the web processes
 */

$filter_limit_select = array(
  '_start'               => '0',                                     // Starting from the 'zeroth' record
  '_limit'               => '2',                                    //   fetch up to 10 records
  '_sort'                => 'id',                                    // Sort by 'id'
  '_dir'                 => 'DESC',
  '_select_columns[]'    => array(                                   // An array, of columns to select
    'id',
    'lock_version',
    'name',
    'script_id',
    'script_name',
    'web_identifier',
    'authenticated_run_as',
    'log_level',
    'log_level_expiry',
  )
);
$response = $workbooks->assertGet('automation/web_actions', $filter_limit_select);
$workbooks->log('Fetched last web process', $response['data']);


/*
 * Delete the web processes
 */

$response = $workbooks->assertDelete('automation/web_actions', $object_id_lock_versions);
$deleted_web_processes = $response['affected_objects'];
$workbooks->log('Deleted web processes', $deleted_web_processes);




/*
 * SCHEDULED PROCESSES.
 */


/*
 * Create the scheduled processes.
 */

$create_scheduled_processes = array(
  array (
    'title'                         => 'Scheduled Process',
    'name'                          => 'ScheduledProcessTest1',
    'script_id'                     => 2,
    'script_name'                   => 'Post XML',
    'web_identifier'                => 'Test2',
    'authenticated_run_as'          => 106,
    'log_level'                     => 'debug',
    'log_level_expiry'              => '2020-12-10T12:00:00.000Z',
  ),
  array (
    'title'                         => 'Scheduled Process 2',
    'name'                          => 'ScheduledProcessTest2',
    'script_id'                     => 2,
    'script_name'                   => 'Post XML',
    'web_identifier'                => 'Test2',
  ),
);

$response = $workbooks->assertCreate('automation/scheduled_actions', $create_scheduled_processes);
$created_processes = $response['affected_objects']; 
$workbooks->log('Created scheduled processes', $created_processes);
$object_id_lock_versions = $workbooks->idVersions($response);

foreach (array(0,1) as $value) {
  $id = $created_processes[$value]['id'];
  $status = $created_processes[$value]['status'];
  
  # Log each record seperatelty, so that we can see the all the fields without truncation.
  $workbooks->log("Created scheduled process $value", $created_processes[$value]);
  
}

/*
 * Update the scheduled processes
 */

$update_scheduled_processes = array(
  array (
    'id'                            => $object_id_lock_versions[0]['id'],
    'lock_version'                  => $object_id_lock_versions[0]['lock_version'],
    'authenticated_run_as'          => 71508,
    'log_level'                     => 'error',
    'log_level_expiry'              => '2020-12-20T12:00:00.000Z',
  ),
  array (
    'id'                            => $object_id_lock_versions[1]['id'],
    'lock_version'                  => $object_id_lock_versions[1]['lock_version'],
    'name'                          => 'ScheduledProcessTest2Updated',
  ),
);

$response = $workbooks->assertUpdate('automation/scheduled_actions', $update_scheduled_processes);
$updated_scheduled_processes = $response['affected_objects'];
$workbooks->log('Updated scheduled processes', $updated_scheduled_processes);
# Get the lock versons os that we can later delete the custom fields
$object_id_lock_versions = $workbooks->idVersions($response);


/*
 * Read the scheduled processes
 */

$filter_limit_select = array(
  '_start'               => '0',                                     // Starting from the 'zeroth' record
  '_limit'               => '2',                                    //   fetch up to 10 records
  '_sort'                => 'id',                                    // Sort by 'id'
  '_dir'                 => 'DESC',
  '_select_columns[]'    => array(                                   // An array, of columns to select
    'id',
    'lock_version',
    'name',
    'script_id',
    'script_name',
    'web_identifier',
    'authenticated_run_as',
    'log_level',
    'log_level_expiry',
  )
);
$response = $workbooks->assertGet('automation/scheduled_actions', $filter_limit_select);
$workbooks->log('Fetched last scheduled process', $response['data']);


/*
 * Delete the scheduled processes
 */

$response = $workbooks->assertDelete('automation/scheduled_actions', $object_id_lock_versions);
$deleted_scheduled_processes = $response['affected_objects'];
$workbooks->log('Deleted scheduled processes', $deleted_scheduled_processes);




testExit($workbooks);

?>