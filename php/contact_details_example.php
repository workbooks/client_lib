<?php
  
/**
 *   A demonstration of using the Workbooks API via a thin PHP wrapper
 *
 *   Last commit $Id: contact_details_example.php 56095 2022-10-11 09:58:27Z hsurendralal $
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

/*
 * If not already authenticated or running under the Workbooks Process Engine create a session
 */
require 'test_login_helper.php';

/*
 * We now have a valid logged-in session. This script does a series of 'CRUD' (Create, Read, Update, Delete) operations.
 */

/*
 * Create an organisation
 */
$create_organisation = array(
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
    'website'                              => 'www.freedomandlight.com'
  )
);

$response_org = $workbooks->assertCreate('crm/organisations', $create_organisation);
$org_object_id_lock_versions = $workbooks->idVersions($response_org);

/*
 * Create three Contact Details for the new Organisation
 */
$create_contact_details = array(
  array (
    'street_address'                       => 'Unit 1 Great Eastern Street',
    'town'                                 => 'Reading',
    'county_province_state'                => 'Berkshire',
    'postcode'                             => 'RG88 8RG',
    'country'                              => 'United Kingdom',
    'email'                                => 'test@workbooks.com',
    'alternate_email'                      => 'test@test.workbooks.com',
    'fax'                                  => '0123 456789',
    'telephone'                            => '0118 118 118',
    'alternate_telephone'                  => '0123 456789',
    'mobile'                               => '0777 666 555',
    'location_name'                        => 'Other',
    'party_id'                             => $response_org['affected_objects'][0]['id']
  ),
  array (
    'street_address'                       => '100 Main Street',
    'town'                                 => 'Beading',
    'postcode'                             => 'RG99 9RG',
    'country'                              => 'United Kingdom',
    'location_name'                        => 'Work',
    'alternate_email'                      => 'test@workbooks.com',
    'party_id'                             => $response_org['affected_objects'][0]['id']
  ),
  array (
    'street_address'                       => '100 Civvy Street',
    'town'                                 => 'Reading',
    'postcode'                             => 'RG77 7RG',
    'country'                              => 'United Kingdom',
    'location_name'                        => 'Home',
    'email'                                => 'test@workbooks.com',
    'party_id'                             => $response_org['affected_objects'][0]['id']
    )
);

$response = $workbooks->assertCreate('crm/party_locations', $create_contact_details);
$object_id_lock_versions = $workbooks->idVersions($response);

/*
 * Update those Contact Details
 */
$update_three_contact_details = array(
  array (
    'id'                                   => $object_id_lock_versions[0]['id'],
    'lock_version'                         => $object_id_lock_versions[0]['lock_version'],
    'map_position'                         => '0.00000,-0.00000,found,25,OSM',
    'alternate_telephone'                  => '0987 654 321',
    'email'                                => 'test2@workbooks.com'
  ),
  array (
    'id'                                   => $object_id_lock_versions[1]['id'],
    'lock_version'                         => $object_id_lock_versions[1]['lock_version'],
    'street_address'                       => '200 Alternate Street',
    'postcode'                             => 'RG88 8RG'
  ),
  array (
    'id'                                   => $object_id_lock_versions[2]['id'],
    'lock_version'                         => $object_id_lock_versions[2]['lock_version'],
    'mobile'                               => '0777 111 222',
    'town'                                 => 'Beading'
  )
);

$response = $workbooks->assertUpdate('crm/party_locations', $update_three_contact_details);
$object_id_lock_versions = $workbooks->idVersions($response);

/*
 * Get Contact Detail records matching on email or alternate_email
 */
$filter_limit_select = array(
    '_ff[]' => 'email',
    '_ft[]' => 'eq',
    '_fc[]' => 'test@workbooks.com',
    '_ff[]' => 'alternate_email',
    '_ft[]' => 'eq',
    '_fc[]' => 'test@workbooks.com',
    '_fm'   => 'or',
    '_select_columns[]'    => array(
    'id',
    'lock_version',
    'street_address'
  )
);

$response = $workbooks->assertGet('crm/party_locations', $filter_limit_select);
$workbooks->log('Fetched objects', $response['data']);

/*
 * Combined call to Create, Update and Delete several Contact Detail records
 */
$batch_contact_details = array(
  array (
    'method'                               => 'CREATE',
    'street_address'                       => '900 Great Portland Street',
    'town'                                 => 'London',
    'postcode'                             => 'W1 11A',
    'country'                              => 'United Kingdom',
    'location_name'                        => 'Other',
    'party_id'                             => $response_org['affected_objects'][0]['id']
  ),
  array (
    'method'                               => 'UPDATE',
    'id'                                   => $object_id_lock_versions[0]['id'],
    'lock_version'                         => $object_id_lock_versions[0]['lock_version'],
    'street_address'                       => '300 New Cavendish Road'
  ),
  array (
    'method'                               => 'DELETE',
    'id'                                   => $object_id_lock_versions[1]['id'],
    'lock_version'                         => $object_id_lock_versions[1]['lock_version']
  ),
  array (
    'method'                               => 'DELETE',
    'id'                                   => $object_id_lock_versions[2]['id'],
    'lock_version'                         => $object_id_lock_versions[2]['lock_version']
  )
);

$response = $workbooks->assertBatch('crm/party_locations', $batch_contact_details);
$object_id_lock_versions = $workbooks->idVersions($response);

/*
 * List all Contact Details for Workbooks' postcode, just selecting a few columns to retrieve
 */
$filter_limit_select = array(
  '_start'               => '0',                                     // Starting from the 'zeroth' record
  '_limit'               => '100',                                   //   fetch up to 100 records
  '_sort'                => 'id',                                    // Sort by 'id'
  '_dir'                 => 'ASC',                                   //   in ascending order
  '_filters[]'           => array('postcode', 'eq', 'RG6 1AZ'),      // Filter where postcode 'equals' Workbooks' postcode (RG6 1AZ)
  '_select_columns[]'    => array(                                   // An array, of columns to select
    'id',
    'lock_version',
    'street_address',
    'location_name',
    'party_id'
  )
);

$response = $workbooks->assertGet('crm/party_locations', $filter_limit_select);
$workbooks->log('Fetched objects', $response['data']);

/*
 * Delete the organisation which was created in this script
 */
$response = $workbooks->assertDelete('crm/organisations', $org_object_id_lock_versions);

testExit($workbooks);

?>

