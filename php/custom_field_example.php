<?php
  
/*   A demonstration of using the Workbooks API to create, update, delete custom fields via a thin PHP  wrapper
 *   The example also shows how to set a value on a record for a given custom field - note that this is exactly
 *   the same as settign a value on any other field. 
 *   Three different types of custom fields are being tested here  - checkbox, picklist and iframe
 *
 *   Last commit $Id: custom_field_example.php 27550 2015-09-24 14:02:04Z swhitehouse $
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

#
# Function to wait for custom fields to attain a certain state.
#

function wait_for_custom_fields_to_attain_state( $workbooks, $state, $timeout, $id_array ) {  
  #
  # Now wait for the custom fields to become 'available'
  #
  
  $i = 0;
  while ( $i < $timeout ) {

    /*
     * Get the newly created custom fields
     */
    
    $ids_to_filter_on = implode(",", $id_array );
  
    $filter_limit_select = array(
      '_start'               => '0',                                     // Starting from the 'zeroth' record
      '_limit'               => '10',                                     // fetch up to 10 records
      '_sort'                => 'id',                                    // Sort by 'id'
      '_dir'                 => 'ASC',
      '_ff[]'                => 'id',
      '_ft[]'                => 'eq',                                   
      '_fc[]'                => "{$ids_to_filter_on}",                     
      '_select_columns[]'    => array(
        'id',
        'status'
      )
    );
  
    $response = $workbooks->assertGet('admin/custom_fields', $filter_limit_select);
    $custom_fields = $response['data'];
    $workbooks->log("Status and ids of newly created custom fields: ", $custom_fields );
    
    # Check the status of the returned custom fields
    
    $match = true;
    foreach ( $custom_fields as $cf ) {
      if ( $cf['status'] != $state ) {
        $match = false;
        break;
      }
    }
  
    if ( $match == true ) {
      $workbooks->log("Newly craeted/updated Custom field are all status: ", $state );
      break;
    }
    else {
      $workbooks->log("Waiting ($i) for all async created/updated fields to reach required status" );
    }

    sleep(1);
    $i = $i + 1;
  }

  if ( $i == $timeout ) {
     $workbooks->log("ERROR: Timeout waiting for custom fields to attain a status of ${state}");
     return(false);
  }
  
  return(true);
}


/*
 * We now have a valid logged-in session. This script does a series of 'CRUD' (Create, Read, Update, Delete) operations.
 */

if ( true ) {

/*
 * Create custom fields 
 */

$create_custom_fields = array(
  array (
    'title'                                => 'Checkbox custom field',
    'resource_type'                        => 'Private::Crm::Person',
    'data_type'                            => 'boolean',
    'is_searchable'                        => false
  ),
  array (
    'title'                                => 'Picklist custom field',
    'resource_type'                        => 'Private::Crm::Person',
    'data_type'                            => 'picklist',
    'picklist_id'                          => 1
  ),
  array (
     'title'                                => 'Iframe custom field',
     'resource_type'                        => 'Private::Crm::Person',
     'default_height'                       => 300,
     'min_height'                           => 300,
     'data_type'                            => 'iframe',
    'linked_url'                            => 'https://www.google.co.uk/maps?q=workbooks&ion=1&espv=2&bav=on.2,or.r_cp.&bvm=bv.102022582,d.ZGU&biw=1912&bih=961&dpr=1&um=1&ie=UTF-8&sa=X&ved=0CAwQ_AUoAGoVChMIi_PvrpjnxwIVMVrbCh1y1AYv'
  ),
);

$response = $workbooks->assertCreate('admin/custom_fields', $create_custom_fields);
$created_custom_fields = $response['affected_objects']; 
$workbooks->log('Created custom fields', $created_custom_fields);
$object_id_lock_versions = $workbooks->idVersions($response);

foreach (array(0, 1, 2) as $value) {
  $id = $created_custom_fields[$value]['id'];
  $status = $created_custom_fields[$value]['status'];
  
  if ( $status != 'Available' ) {
    $workbooks->log("Newly created custom field, id: $id,  does not have \"Available\" status: ", $status );
  }
  else {
		$workbooks->log("Newly created custom field, id: $id, has status: ", $status );
  }
}


/*
 * Update the custom fields. You must specify the 'id' and 'lock_version' of records you want to update.
 */

$update_custom_fields = array(

  array (
    'id'                                   => $object_id_lock_versions[0]['id'],
    'lock_version'                         => $object_id_lock_versions[0]['lock_version'],
    'default_value_boolean'                => true
  ),
  array (
    'id'                                   => $object_id_lock_versions[1]['id'],
    'lock_version'                         => $object_id_lock_versions[1]['lock_version'],
    'picklist_id'                          => 2,
    'string_width'                         => '100',  // This will cause a delay before the custom fields modifification is completed.
  ),
  array (
    'id'                                   => $object_id_lock_versions[2]['id'],
    'lock_version'                         => $object_id_lock_versions[2]['lock_version'],
    'linked_url'                           => '@cf_sales_lead_hubspot_profile@'
  ),
);

$response = $workbooks->assertUpdate('admin/custom_fields', $update_custom_fields);
$updated_custom_fields = $response['affected_objects'];
$workbooks->log('Updated custom fields', $updated_custom_fields);
# Get the lock versons os that we can later delete the custom fields
$object_id_lock_versions = $workbooks->idVersions($response);

/*
 * List the last 10 custom fields that have a status of 'available'
 */

$filter_limit_select = array(
  '_start'               => '0',                                     // Starting from the 'zeroth' record
  '_limit'               => '10',                                    //   fetch up to 10 records
  '_sort'                => 'id',                                    // Sort by 'id'
  '_dir'                 => 'DESC',
  '_ff[]'                => 'status',
  '_ft[]'                => 'eq',                                    //   equals
  '_fc[]'                => 'available',                             //   this script
  '_select_columns[]'    => array(                                   // An array, of columns to select
    'id',
    'lock_version',
    'name',
    'updated_at',
    'resource_type',
    'status',
  )
);
$response = $workbooks->assertGet('admin/custom_fields', $filter_limit_select);
$workbooks->log('Fetched last 10 custom fields', $response['data']);

/*
* Update value for Checkbox and Picklist custom fields on first Person record found. IFrame values are fixed 
*/

$fetch = array(
    '_start'            => '0',
    '_limit'            => '1',
    '_sort'             => 'id',
    '_dir'              => 'ASC',
  );
  
$first_person = $workbooks->assertGet('crm/people', $fetch);

$picklist_entry = $workbooks->assertGet('picklist_data/Private_PicklistEntry/id/value', array('picklist_id' => 2));
$workbooks->log('Fetched picklist entries', $picklist_entry['data']);

$person_update = array(
   'id'                                             => $first_person['data'][0]['id'],
   'lock_version'                                   => $first_person['data'][0]['lock_version'],
   $created_custom_fields[0]['value_column_name']   => true,
   $created_custom_fields[1]['value_column_name']   => $picklist_entry['data'][0]['value']
);

$response = $workbooks->assertUpdate('crm/people', $person_update);
$updated_people = $response['affected_objects'];
$workbooks->log('Updated people', $updated_people);

/*
 * Delete the custom fields created in this script
 */

$response = $workbooks->assertDelete('admin/custom_fields', $object_id_lock_versions);
$deleted_custom_fields = $response['affected_objects'];
$workbooks->log('Deleted custom_fields', $deleted_custom_fields);

}

####################################################################################################################
#
# Now lets create and modify the custom fields asynchronously
#

/*
 * Create custom fields 
 */

if ( true ) {
  

$create_custom_fields = array(
  array (
    'title'                                => 'Checkbox custom field',
    'resource_type'                        => 'Private::Crm::Person',
    'data_type'                            => 'boolean',
    'is_searchable'                        => false
  ),
  array (
    'title'                                => 'Picklist custom field',
    'resource_type'                        => 'Private::Crm::Person',
    'data_type'                            => 'picklist',
    'picklist_id'                          => 1
  ),
  array (
     'title'                                => 'Iframe custom field',
     'resource_type'                        => 'Private::Crm::Person',
     'default_height'                       => 300,
     'min_height'                           => 300,
     'data_type'                            => 'iframe',
    'linked_url'                            => 'https://www.google.co.uk/maps?q=workbooks&ion=1&espv=2&bav=on.2,or.r_cp.&bvm=bv.102022582,d.ZGU&biw=1912&bih=961&dpr=1&um=1&ie=UTF-8&sa=X&ved=0CAwQ_AUoAGoVChMIi_PvrpjnxwIVMVrbCh1y1AYv'
  ),
);

$use_async =  array('_asynchronous_cf' => 'true');
$response = $workbooks->assertCreate('admin/custom_fields', $create_custom_fields, $use_async );
$created_custom_fields = $response['affected_objects'];
$workbooks->log('Created async custom fields', $created_custom_fields);
$object_id_lock_versions = $workbooks->idVersions($response);
$workbooks->log( "object_id_lock_versions: ", $object_id_lock_versions );
wait_for_custom_fields_to_attain_state( $workbooks, 'Available', 240, array( $object_id_lock_versions[0]['id'], $object_id_lock_versions[1]['id'], $object_id_lock_versions[2]['id'] ) );

/*
 * Update those custom fields. You must specify the 'id' and 'lock_version' of records you want to update.
 */

$update_custom_fields = array(

  array (
    'id'                                   => $object_id_lock_versions[0]['id'],
    'lock_version'                         => $object_id_lock_versions[0]['lock_version'],
    'default_value_boolean'                => true,
  ),
  array (
    'id'                                   => $object_id_lock_versions[1]['id'],
    'lock_version'                         => $object_id_lock_versions[1]['lock_version'],
    'picklist_id'                          => 2,
    'string_width'                         => '100',  // This will cause a delay before the custom fields modifification is completed.
  ),
  array (
    'id'                                   => $object_id_lock_versions[2]['id'],
    'lock_version'                         => $object_id_lock_versions[2]['lock_version'],
    'linked_url'                           => '@cf_sales_lead_hubspot_profile@',
  ),
);

$response = $workbooks->assertUpdate('admin/custom_fields', $update_custom_fields, $use_async );
$updated_custom_fields = $response['affected_objects'];
$workbooks->log('Updated custom fields', $updated_custom_fields);
# Get the lock versons os that we can later delete the custom fields
$object_id_lock_versions = $workbooks->idVersions($response);
wait_for_custom_fields_to_attain_state( $workbooks, 'Available', 240, array( $object_id_lock_versions[0]['id'], $object_id_lock_versions[1]['id'], $object_id_lock_versions[2]['id'] ) );

/*
 * Delete the custom fields created in this script
 */

$response = $workbooks->assertDelete('admin/custom_fields', $object_id_lock_versions, $use_async );
$deleted_custom_fields = $response['affected_objects'];
$workbooks->log('Deleted custom_fields', $deleted_custom_fields);

}

testExit($workbooks);

?>