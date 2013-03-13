<?php
  
/**
 *   A demonstration of using the Workbooks API to operate on Opportunities and a number of
 *   related objects via a thin PHP wrapper
 *
 *   Last commit $Id: opportunity_example.php 18524 2013-03-06 11:15:59Z jkay $
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
 * Create two opportunities with different fields populated
 */
$create_two_opportunities = array(
  array(
    'description'                          => 'Opportunity One',
    'created_through_reference'            => '12345',
    'document_date'                        => '01 Oct 2010',
  ),
  array(
    'description'                          => 'Opportunity Two',
    'created_through_reference'            => '12345',
    'document_date'                        => '01 Nov 2010',
  ),
);

$response = $workbooks->assertCreate('crm/opportunities', $create_two_opportunities);
$opportunities_object_id_lock_versions = $workbooks->idVersions($response);


/*
 * Update those opportunities; an up to date id and lock_version are required to do this
 */
$update_two_opportunities = array(
  array (
    'id'                                   => $opportunities_object_id_lock_versions[0]['id'],
    'lock_version'                         => $opportunities_object_id_lock_versions[0]['lock_version'],
    'document_currency'                    => 'GBP',
    'home_currency'                        => 'GBP',
    'comment'                              => 'Updating an opportunity...',
  ),
  array (
    'id'                                   => $opportunities_object_id_lock_versions[1]['id'],
    'lock_version'                         => $opportunities_object_id_lock_versions[1]['lock_version'],
    'document_currency'                    => 'GBP',
    'home_currency'                        => 'GBP',
    'fao'                                  => 'CTO'
  ),
);

$response = $workbooks->assertUpdate('crm/opportunities', $update_two_opportunities);
$opportunities_object_id_lock_versions = $workbooks->idVersions($response);


/*
 * List a maximum of 10 people in the system whose surname begins with P
 */
$filter_limit_select = array(
  '_start'               => '0',                                     // Starting from the 'zeroth' record
  '_limit'               => '10',                                    //   fetch up to 10 records
  '_sort'                => 'id',                                    // Sort by 'id'
  '_dir'                 => 'ASC',                                   //   in ascending order
  '_ff[]'                => 'person_last_name',                      // Filter by this column
  '_ft[]'                => 'bg',                                    //   begins with
  '_fc[]'                => 'P',                                     //   'P'
  '_select_columns[]'    => array(                                   // An array, of columns to select
    'id',
    'lock_version',
    'name',
    'person_first_name',
    'person_last_name',
    'updated_at',
    'updated_by_user[person_name]',
  )
);
$response = $workbooks->assertGet('crm/people', $filter_limit_select);
$workbooks->log('Fetched objects', $response['data']);
$people_data = $response['data'];


/*
 * Set the first Person found to be a Competitor on the first new Opportunity,
 * and the second and third People as Partners on the second new Opportunity
 */
$set_opportunity_contact = array(
  array(
    'id'                                   => $opportunities_object_id_lock_versions[0]['id'],
    'lock_version'                         => $opportunities_object_id_lock_versions[0]['lock_version'],
    'opportunity_contact_ids'              => array($people_data[0]['id']),
    'opportunity_contact_roles'            => array('Competitor'),
  ),
  array(
    'id'                                   => $opportunities_object_id_lock_versions[1]['id'],
    'lock_version'                         => $opportunities_object_id_lock_versions[1]['lock_version'],
    'opportunity_contact_ids'              => array($people_data[1]['id'], $people_data[2]['id']),
    'opportunity_contact_roles'            => array('Partner', 'Partner'),
  ),
);

$response = $workbooks->assertUpdate('crm/opportunities', $set_opportunity_contact);
$workbooks->log('Response', $response);
$opportunities_object_id_lock_versions = $workbooks->idVersions($response);


/*
 * List the newly added and updated Opportunities
 */
$people_filter_limit_select = array(
  '_start'               => '0',                                     // Starting from the 'zeroth' record
  '_limit'               => '2',                                     //   fetch up to 2 records
  '_sort'                => 'id',                                    // Sort by 'id'
  '_dir'                 => 'DESC',                                  //   in descending order
  '_select_columns[]'    => array(                                   // An array, of columns to select
    'id',
    'lock_version',
    'description',
    'opportunity_contact_ids',
    'opportunity_contact_roles',
    'document_currency',
    'home_currency',
    'comment',
    'fao',
    'document_date',
    'created_through_reference'
  )
);
$response = $workbooks->assertGet('crm/people', $people_filter_limit_select);
$workbooks->log('Fetched objects', $response['data']);


/*
 * Delete the Opportunities which were created in this script
 */
$response = $workbooks->assertDelete('crm/opportunities', $opportunities_object_id_lock_versions);

testExit($workbooks);

?>
