<?php
  
/**
 *   A demonstration of using the Workbooks API via a thin PHP wrapper
 *
 *   Last commit $Id: project_example.php 63936 2024-09-03 21:33:12Z jmonahan $
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

/* If not already authenticated or running under the Workbooks Process Engine create a session */
require 'test_login_helper.php';

/*
 * We now have a valid logged-in session. This script does a series of 'CRUD' (Create, Read, Update, Delete) operations.
 */

/*
 * PROJECTS.
 */

$start_date = date('Y-m-d');
$end_date = date('Y-m-d', strtotime("tomorrow"));
$workbooks->log($start_date, $end_date);

/*
 * Create a new project.
 */

$workbooks->log('Create a new project');

$project = array(
  'name'        => 'New Project',
  'description' => 'This Project was created through the API using a php script.',
  'start_date'  => $start_date,
  'end_date'    => $end_date,
  'project_currency' => 'GBP',
);

$response = $workbooks->assertCreate('project/projects', $project);
$object_id_lock_versions = $workbooks->idVersions($response);
$workbooks->log('Created Object:', $response);

/*
 * Retrieve the project.
 */

$workbooks->log('Fetch the created project');

$filter = array(
  '_start' => '0',                                     // Starting from the 'zeroth' record
  '_limit' => '100',                                   // fetch up to 100 records
  '_sort'  => 'id',                                    // Sort by 'id'
  '_ff[]'  => array('created_through', 'id'),          // Filter by this column
  '_ft[]'  => array('eq',              'eq'),          // equals
  '_fc[]'  => array('php_test_client', $object_id_lock_versions[0]['id']),
);

$response = $workbooks->assertGet('project/projects', $filter);
$project_id = $response['data'][0]['id'];
$task_statuses_picklist_id = $response['data'][0]['task_statuses_picklist_id'];
$workbooks->log('Fetched Project:', $response['data']);

/*
 * Update the project.
 */

$workbooks->log('Update the project name');

$update_project = array (
    'id'           => $object_id_lock_versions[0]['id'],
    'lock_version' => $object_id_lock_versions[0]['lock_version'],
    'name'         => 'Updated Project'
);

$response = $workbooks->assertUpdate('project/projects', $update_project);
$project_id_lock_versions = $workbooks->idVersions($response);
$workbooks->log('Updated Project:', $response);

/*
 * Retrieve the project's picklist.
 */

$workbooks->log('Fetch the project\'s picklist');

$filter = array(
  '_start' => '0',                                                     // Starting from the 'zeroth' record
  '_limit' => '100',                                                   // fetch up to 100 records
  '_sort'  => 'id',                                                    // Sort by 'id'
  '_ff[]'  => array('id',                       'parent_type'),        // Filter by this column
  '_ft[]'  => array('eq',                              'eq'),          // equals
  '_fc[]'  => array($task_statuses_picklist_id, 'Private::Project::Project'),
);

$response = $workbooks->assertGet('admin/picklists', $filter);
$picklist_id = $response['data'][0]['id'];
$workbooks->log('Fetched Picklist:', $response['data']);

/*
 * Retrieve the project picklist's default value.
 */

$workbooks->log('Fetch the projects picklist\'s default value');

$filter = array(
  '_start' => '0',                                     // Starting from the 'zeroth' record
  '_limit' => '100',                                   // fetch up to 100 records
  '_sort'  => 'id',                                    // Sort by 'id'
  '_ff[]'  => array('picklist_id','default_value'),    // Filter by this column
  '_ft[]'  => array('eq','eq'),                        // equals
  '_fc[]'  => array($picklist_id, '1'),
);

$response = $workbooks->assertGet('admin/picklist_entries', $filter);
$picklist_entry_id = $response['data'][0]['id'];
$workbooks->log('Fetched Picklist Entry:', $response['data']);

/*
 * Create a new picklist entry.
 */

$workbooks->log('Create a new picklist entry');

$picklist_entry = array(
  'picklist_id'   => $picklist_id,
  'value'         => 'API Project Task Status',
  'display_order' => '99',
  'default_value' => '1',
 );
 
$response = $workbooks->assertCreate('admin/picklist_entries', $picklist_entry);
$object_id_lock_versions = $workbooks->idVersions($response);
$workbooks->log('Created Object:', $response);

/*
 * Retrieve the project picklist's default value.
 * Should now be the object we just created.
 */

$workbooks->log('Fetch the projects picklist\'s new default value');

$filter = array(
  '_start' => '0',                                     // Starting from the 'zeroth' record
  '_limit' => '100',                                   // fetch up to 100 records
  '_sort'  => 'id',                                    // Sort by 'id'
  '_ff[]'  => array('picklist_id','default_value'),    // Filter by this column
  '_ft[]'  => array('eq','eq'),                        // equals
  '_fc[]'  => array($picklist_id, '1'),
);

$response = $workbooks->assertGet('admin/picklist_entries', $filter);
$status_value = $response['data'][0]['value'];
$workbooks->log('Fetched Picklist Entry:', $response['data']);

/*
 * Retrieve the project picklist's old default value.
 * It should no longer be the default.
 */

$workbooks->log('Fetch the projects picklist\'s old default value');

$filter = array(
  '_start' => '0',                                     // Starting from the 'zeroth' record
  '_limit' => '100',                                   // fetch up to 100 records
  '_sort'  => 'id',                                    // Sort by 'id'
  '_ff[]'  => array('id'),                             // Filter by this column
  '_ft[]'  => array('eq'),                             // equals
  '_fc[]'  => array($picklist_entry_id),
);

$response = $workbooks->assertGet('admin/picklist_entries', $filter);
$workbooks->log('Fetched Picklist Entry:', $response['data']);

/*
 * Create a new project task.
 */

$workbooks->log('Create a new project task');

$project_task = array(
  'name'               => 'Test Project Task',
  'activity_status'    => $status_value,
  'due_datetime'       => $end_date,
  'parent_record_id'   => $project_id,
  'parent_record_type' => 'Private::Project::Project',
);

$response = $workbooks->assertCreate('activity/project_tasks', $project_task);
$object_id_lock_versions = $workbooks->idVersions($response);
$workbooks->log('Created Object:', $response);

/*
 * Retrieve the project task.
 */

$workbooks->log('Fetch the created project task');

$filter = array(
  '_start' => '0',                                     // Starting from the 'zeroth' record
  '_limit' => '100',                                   // fetch up to 100 records
  '_sort'  => 'id',                                    // Sort by 'id'
  '_ff[]'  => array('created_through', 'id'),          // Filter by this column
  '_ft[]'  => array('eq',              'eq'),          // equals
  '_fc[]'  => array('php_test_client', $object_id_lock_versions[0]['id']),
);

$response = $workbooks->assertGet('activity/project_tasks', $filter);
$workbooks->log('Fetched Project Task:', $response['data']);

/*
 * Update the project task.
 */

$workbooks->log('Update the project task name');

$update_project_task = array (
  'id'           => $object_id_lock_versions[0]['id'],
  'lock_version' => $object_id_lock_versions[0]['lock_version'],
  'name'         => 'Updated Project Task'
);

$response = $workbooks->assertUpdate('activity/project_tasks', $update_project_task);
$project_task_id_lock_versions = $workbooks->idVersions($response);
$workbooks->log('Updated Project Task:', $response);

/*
 * Delete the project task.
 */

$workbooks->log('Delete the project task');

$response = $workbooks->assertDelete('activity/project_tasks', $project_task_id_lock_versions);


/*
 * Delete the project.
 */

$workbooks->log('Delete the project');

$response = $workbooks->assertDelete('project/projects', $project_id_lock_versions);

testExit($workbooks);

?>
