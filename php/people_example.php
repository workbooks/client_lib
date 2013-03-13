<?php
  
/**
 *   A demonstration of using the Workbooks API to operate on People via a thin PHP wrapper.
 *   The created_through_reference and created_through attributes are used as if the caller
 *   were synchronising with an external service.
 *
 *   Last commit $Id: people_example.php 18524 2013-03-06 11:15:59Z jkay $
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
 * Create two people, tagging with their identifiers in the external system. Up to 100 can be done in one batch.
 */
$create_two_people = array(
  array (
    'name'                                 => 'Richard Richards',
    'created_through_reference'            => '101',                      # The ID of the corresponding record in the external system
    'main_location[country]'               => 'United Kingdom',
    'main_location[county_province_state]' => 'Berkshire',
    'main_location[fax]'                   => '01234 567890',
    'main_location[postcode]'              => 'RG99 9RG',
    'main_location[street_address]'        => '100 Civvy Street',
    'main_location[telephone]'             => '01234 456789',
    'main_location[town]'                  => 'Beading',
    'no_email_soliciting'                  => false,
    'no_phone_soliciting'                  => true,
    'no_post_soliciting'                   => true,
    'person_first_name'                    => 'Richard',
    'person_middle_name'                   => '',
    'person_last_name'                     => 'Richards',
    'person_personal_title'                => 'Mr.',
    'website'                              => 'www.richards.me.uk',    
  ),
  array (
    'name'                                 => 'Steve Stevens',
    'created_through_reference'            => '102',                      # The ID of the corresponding record in the external system
    'main_location[country]'               => 'United Kingdom',
    'main_location[county_province_state]' => 'Berkshire',
    'main_location[postcode]'              => 'RG99 7RG',
    'main_location[street_address]'        => '10 Castle Street',
    'main_location[telephone]'             => '0345 6456789',
    'main_location[town]'                  => 'Reading',
    'no_email_soliciting'                  => true,
    'no_phone_soliciting'                  => false,
    'no_post_soliciting'                   => true,
    'person_first_name'                    => 'Steve',
    'person_middle_name'                   => 'Samuel',
    'person_last_name'                     => 'Stevens',
  ),
);

$response = $workbooks->assertCreate('crm/people', $create_two_people);
$object_id_lock_versions = $workbooks->idVersions($response);

/*
 * Update those two people. You must specify the 'id' and 'lock_version' of records you want to update.
 */
$update_two_people = array(
  array (
    'id'                                   => $object_id_lock_versions[0]['id'],
    'lock_version'                         => $object_id_lock_versions[0]['lock_version'],
    'no_email_soliciting'                  => true,
    'main_location[telephone]'             => '07900 456789',
  ),
  array (
    'id'                                   => $object_id_lock_versions[1]['id'],
    'lock_version'                         => $object_id_lock_versions[1]['lock_version'],
    'name'                                 => 'Stephen Stevens',
    'person_first_name'                    => 'Stephen',
  ),
);

$response = $workbooks->assertUpdate('crm/people', $update_two_people);
$object_id_lock_versions = $workbooks->idVersions($response);

/*
 * List up to the first hundred people matching our 'created_through' attribute value, just selecting a few columns to retrieve
 */
$filter_limit_select = array(
  '_start'               => '0',                                     // Starting from the 'zeroth' record
  '_limit'               => '100',                                   //   fetch up to 100 records
  '_sort'                => 'id',                                    // Sort by 'id'
  '_dir'                 => 'ASC',                                   //   in ascending order
  '_ff[]'                => 'created_through',                       // Filter by this column
  '_ft[]'                => 'eq',                                    //   equals
  '_fc[]'                => 'test_client',                           //   this script
  '_select_columns[]'    => array(                                   // An array, of columns to select
    'id',
    'lock_version',
    'name',
    'main_location[telephone]',
    'main_location[town]',
    'updated_at',
    'updated_by_user[person_name]',
  )
);
$response = $workbooks->assertGet('crm/people', $filter_limit_select);
$workbooks->log('Fetched objects', $response['data']);

/*
 * Delete the people which were created in this script
 */
$response = $workbooks->assertDelete('crm/people', $object_id_lock_versions);

/*
 * List every person in the system, in alphabetic name order, whether deleted or not, which 
 * has been updated in the last twenty weeks. 
 *
 * If you are trying to have an external system replicate the contents of Workbooks, this is 
 * the technique you would use, perhaps only requesting records which were updated since the
 * time of the last fetch.
 */

date_default_timezone_set('UTC');
$twenty_weeks_ago = time() - (20 * 7 * 24 * 60 * 60);
$updated_since = date('D M d H:i:s e Y', $twenty_weeks_ago);

$all_records = array();
$fetched = -1;
$start = 0;
while ($fetched <> 0) {
  $fetch_chunk = array(
    '_start'               => $start,
    '_limit'               => 100,       // The maximum 
    
    '_sort'                => 'id',      // Sort by 'id' to ensure every record is fetched
    '_dir'                 => 'ASC',     //   in ascending order

                                         // Filter 
    '_fm'                  =>       '1          AND (2 OR           3)',
    '_ff[]'                => array('updated_at',   'is_deleted',  'is_deleted'),
    '_ft[]'                => array('ge',           'true',        'false'     ), //   'ge' = "is on or after"
    '_fc[]'                => array($updated_since, '',            ''          ),

    '_select_columns[]'    => array(     // An array, of columns to select. Fewer = faster
      'id',                              //   Unique, incrementing, key for each record
      'lock_version',                    //   Required if updating a record
      'is_deleted',                      //   True or False
      'object_ref',                      //   Also unique, more friendly key for each record

      'name',                            //   Full name
      'created_through',                 //   Source system
      'created_through_reference',       //   Reference given by source system
      'employer_link',                   //   Employer Organisation ID

      'main_location[email]',
      'main_location[fax]',
      'main_location[mobile]',
      'main_location[telephone]',
      'main_location[street_address]', 
      'main_location[town]',
      'main_location[county_province_state]',
      'main_location[postcode]',
      'main_location[country]',
      
      'no_email_soliciting',

      'person_first_name',
      'person_job_role',
      'person_job_title',
      'person_last_name',
      'person_middle_name',
      'person_personal_title',
      'person_salutation',
      'person_suffix',

      'created_at',
      'created_by_user[person_name]',
      'updated_at',
      'updated_by_user[person_name]',
    )
  );
  $response = $workbooks->assertGet('crm/people', $fetch_chunk);
  // $workbooks->log('Records from ' . $start, $response);
  foreach ($response['data'] as $record) {
    $all_records[] = $record;
  }
  $fetched = count($response['data']);
  $workbooks->log('Fetched', $fetched);
  $start += $fetched;
}
$workbooks->log('All records fetched', $all_records);
$workbooks->log('Total fetched', count($all_records));

testExit($workbooks);

?>

