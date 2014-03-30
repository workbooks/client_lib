<?php
  
/**
 *   A demonstration of using the Workbooks API via a thin PHP wrapper
 *
 *   Last commit $Id: simple_example.php 21375 2014-03-11 10:17:36Z jkay $
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
 * Create three organisations
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

$response = $workbooks->assertCreate('crm/organisations', $create_three_organisations);
$object_id_lock_versions = $workbooks->idVersions($response);

/*
 * Update those organisations
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

$response = $workbooks->assertUpdate('crm/organisations', $update_three_organisations);
$object_id_lock_versions = $workbooks->idVersions($response);

/*
 * Combined call to Create, Update and Delete several organisations
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

$response = $workbooks->assertBatch('crm/organisations', $batch_organisations);
$object_id_lock_versions = $workbooks->idVersions($response);

/*
 * Create a single organisation
 */
$create_one_organisation = array(
  'method'                               => 'CREATE',
  'name'                                 => 'Birkbeck Burgers',
  'industry'                             => 'Food',
  'main_location[country]'               => 'United Kingdom',
  'main_location[county_province_state]' => 'Oxfordshire',
  'main_location[town]'                  => 'Oxford',
);
$response = $workbooks->assertCreate('crm/organisations', $create_one_organisation);
$created_id_lock_versions = $workbooks->idVersions($response);
$object_id_lock_versions = array_merge(array($object_id_lock_versions[0]), array($object_id_lock_versions[1]), $created_id_lock_versions);

/*
 * List the first hundred organisations in Berkshire, just selecting a few columns to retrieve
 */
$filter_limit_select = array(
  '_start'               => '0',                                     // Starting from the 'zeroth' record
  '_limit'               => '100',                                   //   fetch up to 100 records
  '_sort'                => 'id',                                    // Sort by 'id'
  '_dir'                 => 'ASC',                                   //   in ascending order
  '_filters[]'           => array('main_location[county_province_state]', 'bg', 'Berkshire'), // 'begins with'
  '_select_columns[]'    => array(                                   // An array, of columns to select
    'id',
    'lock_version',
    'name',
    'main_location[town]',
    'updated_by_user[person_name]',
  )
);
$response = $workbooks->assertGet('crm/organisations', $filter_limit_select);
$workbooks->log('Fetched objects', $response['data']);

/*
 * Delete the remaining organisations which were created in this script
 */
$response = $workbooks->assertDelete('crm/organisations', $object_id_lock_versions);

testExit($workbooks);

?>

