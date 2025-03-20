<?php
  
/*   A demonstration of using the Workbooks API to create, read, update and delete custom record types via a thin PHP wrapper
 *   including the creation of custom fields and create, read, update and deletion of custom records.
 *
 *   Last commit $Id: custom_record_type_example.php 66039 2025-03-18 15:19:55Z jkay $
 *
 *       The MIT License
 *
 *       Copyright (c) 2008-2018, Workbooks Online Limited.
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

function delete_prior() {
  global $workbooks;
  /*
  * Delete any custom record types created by previous runs of this test.
  */
  $custom_record_types = $workbooks->assertGet('admin/custom_record_types', 
    ['_filters[]' => ['created_through_reference', 'bg', 'custom_record_type_example']]);
  if (count($custom_record_types['data']) > 0) {
    $delete_custom_record_types = [];
    foreach ($custom_record_types['data'] as $custom_record_type) {
      $delete_custom_record_types[]= [
        'id' => (string)$custom_record_type['id'],
        'lock_version' => (string)$custom_record_type['lock_version'],
      ];
    }
    $workbooks->log('Deleting prior custom record types', $delete_custom_record_types);
    $delete_response = $workbooks->assertDelete('admin/custom_record_types', $delete_custom_record_types);
    $deleted_custom_record_types = $delete_response['affected_objects']; 
    $workbooks->log('Deleted custom record types', $deleted_custom_record_types);
    return(TRUE);
  }
  return(FALSE);
}

if (delete_prior()) {
  $workbooks->log('Deleted custom record types so not running this test a second time');
  testExit($workbooks);
}

/*
* Create two custom record types: Vehicles, Territories
*/
$create_custom_record_types = [
  [
    'name' => 'Vehicle', # Should be singular and start with a capital letter.
    'name_plural' => 'Vehicles', # Should be plural and start with a capital letter.
    'object_ref_prefix' => 'VEHICLE', # Should be singular and in uppercase.
    'route_suffix' => 'vehicles', # Should be plural and lowercase.
    'description' => 'Trains, Planes and Automobiles',
    'help_url' => 'https://www.workbooks.com/help/custom_record_types', # User documentation link
    'icon_class' => 'plugin',
    'created_through_reference' => 'custom_record_type_example-vehicle',
  ], [
    'name' => 'Territory',
    'name_plural' => 'Territories',
    'object_ref_prefix' => 'TER',
    'route_suffix' => 'areas',
    'description' => 'Areas where business is done',
    'icon_class' => 'location',
    'created_through_reference' => 'custom_record_type_example-area',
  ]
];

$workbooks->log('Creating custom record types', $create_custom_record_types);
$create_response = $workbooks->assertCreate('admin/custom_record_types', $create_custom_record_types);
$created_custom_record_types = $create_response['affected_objects']; 
$workbooks->log('Created custom record types', $created_custom_record_types);

/*
* Update those one of those custom record types
*/
$update_custom_record_types = [
  [
    'id' => (string)$created_custom_record_types[1]['id'],
    'lock_version' => (string)$created_custom_record_types[1]['lock_version'],
    'name' => 'Geographic Area', 
    'name_plural' => 'Geographic Areas',
  ]
];

$workbooks->log('Updating custom record types', $update_custom_record_types);
$update_response = $workbooks->assertUpdate('admin/custom_record_types', $update_custom_record_types);
$updated_custom_record_types = $update_response['affected_objects']; 
$workbooks->log('Updated custom record types', $updated_custom_record_types);

/*
* Fetch definitions of those the vehicle custom record type
*/
$vehicle_record_types = $workbooks->assertGet('admin/custom_record_types', 
  ['_filters[]' => ['created_through_reference', 'eq', 'custom_record_type_example-vehicle'],
]);
$vehicle_record_type = $vehicle_record_types['data'][0]['resource_type'];

/*
* Create custom fields on the vehicles record type. There are no records yet so doing this synchronously is fast.
*/
$create_custom_fields = [
  [
    'title' => 'Last Service Date',
    'resource_type' => $vehicle_record_type,
    'data_type' => 'date',
  ], [
    'title' => 'Registration Plate',
    'resource_type' => $vehicle_record_type,
    'data_type' => 'string',
    'is_searchable' => true,
    'is_indexed' => true
  ]
];

$response = $workbooks->assertCreate('admin/custom_fields', $create_custom_fields);
$created_custom_fields = $response['affected_objects']; 
$workbooks->log('Created custom fields', $created_custom_fields);
$object_id_lock_versions = $workbooks->idVersions($response);

/*
* Fetch definitions of those custom fields
*/
$custom_fields = $workbooks->assertGet('admin/custom_fields', [ '_filters[]' => ['resource_type', 'eq', $vehicle_record_type] ]);
$workbooks->log('Configured custom fields', $custom_fields);

/*
* Now CRUD a couple of custom records
*/
$create_vehicles = [
  [
    'name' => 'Ford Van',
    'cf_vehicle_registration_plate' => 'PR3F3CT',
  ], [
    'name' => 'Mercedes Van',
    'cf_vehicle_registration_plate' => 'B3NZIN3',
  ]
];

$workbooks->log('Creating custom records', $create_vehicles);
$create_response = $workbooks->assertCreate('custom_record/vehicles', $create_vehicles);
$created_custom_records = $create_response['affected_objects']; 
$workbooks->log('Created custom records', $created_custom_records);

$update_vehicles = [
  [
    'id' => (string)$created_custom_records[0]['id'],
    'lock_version' => (string)$created_custom_records[0]['lock_version'],
    'cf_vehicle_last_service_date' => '31 Jan 2018',
  ]
];

$workbooks->log('Updating custom records', $update_vehicles);
$update_response = $workbooks->assertUpdate('custom_record/vehicles', $update_vehicles);
$updated_vehicles = $update_response['affected_objects']; 
$workbooks->log('Updated custom records', $updated_vehicles);

$delete_vehicles = [
  [
    'id' => (string)$updated_vehicles[0]['id'],
    'lock_version' => (string)$updated_vehicles[0]['lock_version'],
  ]
];

$workbooks->log('Deleting custom records', $delete_vehicles);
$delete_response = $workbooks->assertDelete('custom_record/vehicles', $delete_vehicles);
$deleted_vehicles = $delete_response['affected_objects']; 
$workbooks->log('Deleted custom records', $deleted_vehicles);

$vehicles = $workbooks->assertGet('custom_record/vehicles', []);
$workbooks->log('Fetched custom records', $vehicles);

if (count($vehicles['data']) <> 1) {
  $workbooks->log('Unexpected number of records!', $vehicles);
  testExit($workbooks, $exit_error);
}


delete_prior();

testExit($workbooks);
