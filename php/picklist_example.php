<?php

/**
 *   A demonstration of using the Workbooks API to operate on Picklists via a thin PHP wrapper.
 *
 *   Last commit $Id: picklist_example.php 28656 2015-12-04 17:12:37Z jfinch $
 *
 *       The MIT License
 *
 *       Copyright (c) 2008-2015, Workbooks Online Limited.
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
 * Fetch a picklist id, required to obtain the picklist entries.
 */
$picklist_select = [
  '_filters[]'     => ['name', 'eq', 'Case Statuses'],
];

$response = $workbooks->assertGet('admin/picklists.api', $picklist_select);
$picklist_id = $response['data'][0]['id'];

/*
 * Fetch picklist entries
 */
$picklist_entries_select = [
  '_filters[]'     => ['picklist_id', 'eq', $picklist_id],
];

$response = $workbooks->assertGet('admin/picklist_entries.api', $picklist_entries_select);

/*
 * Create a Picklist
 */
$create_picklist = [
  'name' => 'API Created Picklist',
  'description' => 'Here is an example of a picklist created via the Workbooks API',
];

$response = $workbooks->assertCreate('admin/picklists.api', $create_picklist);
$created_picklist = $response['affected_objects'][0];
list($picklist_id, $picklist_lock_version) = [$created_picklist['id'], $created_picklist['lock_version']];

/*
 * Create Picklist Entries
 */
$create_picklist_entries = [
  [
    'picklist_id' => $picklist_id,
    'value' => 'Vanilla Cheesecake',
  ],
  [
    'picklist_id' => $picklist_id,
    'value' => 'Strawberry Cheesecake',
  ],
  [
    'picklist_id' => $picklist_id,
    'value' => 'Lemon Cheesecake',
  ],
];

$response = $workbooks->assertCreate('admin/picklist_entries.api', $create_picklist_entries);
$created_picklist_entries = $response['affected_objects'];

/*
 * Update a Picklist
 */

$update_picklist = [
  'id' => $picklist_id,
  'lock_version' => $picklist_lock_version,
  'name' => 'Favourite Cheesecakes',
];

$response = $workbooks->assertUpdate('admin/picklists.api', $update_picklist);
$picklist_object_id_lock_versions = $workbooks->idVersions($response);

/*
 * Delete a Picklist
 */
$response = $workbooks->assertDelete('admin/picklists.api', $picklist_object_id_lock_versions);
