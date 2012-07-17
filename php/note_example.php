<?php
  
/**
 *   A demonstration of using the Workbooks API via a thin PHP wrapper to CRUD notes
 *
 *   Last commit $Id$
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

require 'workbooks_api.php';

/* If not running under the Workbooks Process Engine create a session */
require 'test_login_helper.php';

/*
 * We now have a valid logged-in session. This script does a series of 'CRUD' (Create, Read, Update, Delete) operations.
 */

/*
 * Create a single organisation
 */
$create_one_organisation = array(
  'method'                               => 'CREATE',
  'name'                                 => 'Nogo Supermarkets',
  'industry'                             => 'Food',
  'main_location[country]'               => 'United Kingdom',
  'main_location[county_province_state]' => 'Oxfordshire',
  'main_location[town]'                  => 'Oxford',
);
$response = $workbooks->assertCreate('crm/organisations', $create_one_organisation);
$created_organisation_id_lock_versions = $workbooks->idVersions($response);

/*
 * Create three Notes associated with that organisation
 */

/*
 * Update the first Note associated with that organisation
 */

/*
 * Delete one the last Note associated with that organisation
 */
 
/*
 * List the two Notes we have left
 */
$filter_limit_select = array(
  '_start'               => '0',                                     // Starting from the 'zeroth' record
  '_limit'               => '100',                                   //   fetch up to 100 records
  '_sort'                => 'id',                                    // Sort by 'id'
  '_dir'                 => 'ASC',                                   //   in ascending order
  '_ff[]'                => 'main_location[county_province_state]',  // Filter by this column
  '_ft[]'                => 'ct',                                    //   containing
  '_fc[]'                => 'Berkshire',                             //   'Berkshire'
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
 * Delete the the organisation (and any related notes!) which was created in this script
 */
$response = $workbooks->assertDelete('crm/organisations', $created_organisation_id_lock_versions);

testExit($workbooks);

?>

