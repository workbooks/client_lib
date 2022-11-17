<?php
  
/*   A demonstration of using the Workbooks API to create, read, update and delete accounting years and accounting periods via a thin PHP wrapper.
 *
 *   Last commit $Id: accounting_year_example.php 53690 2022-02-08 11:40:22Z klawless $
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
 * ACCOUNTING YEARS.
 */

/*
 * Create a new accounting year.
 * 
 * The year position specifies if you want to create one after your latest or before your earliest current accounting year. 
 * Creating an accounting year automatically creates the accounting periods contained within it, the number and their duration 
 * is dictated by the year_style.
 */
$create_one_accounting_year = array(
  'name'                                 => 'Accounting Year',
  'year_position'                        => 'After Last',          // 'Before First' or 'After Last'
  'year_style'                           => 'Calendar Months',     // 'Quarters', 'Calendar Months' or '4-4-5 week pattern'
);

$response = $workbooks->assertCreate('accounting/accounting_years', $create_one_accounting_year);
$object_id_lock_versions = $workbooks->idVersions($response);

/*
 * Retreive an existing accounting year.
 */
$get_one_accounting_year = array(
  '_start'               => '0',
  '_limit'               => '1',
  '_sort'                => 'created_at',     // Retrieve the most recently created year, which will be the one made by this script.
  '_dir'                 => 'DESC',
  '_select_columns[]'    => array(
    'id',
    'created_by',
    'updated_by',
    'created_at',
    'updated_at',
    'came_from_migration',
    'name',
    'own_organisation_id',
    'from_date',
    'thru_date',
  )
);

$response = $workbooks->assertGet('accounting/accounting_years', $get_one_accounting_year);
$workbooks->log('Fetched objects', $response['data']);

/*
 * Retreive all the accounting periods within a given accounting year.
 */
$get_all_accounting_periods_for_one_accounting_year = array(
  '_start'               => '0',
  '_limit'               => '12',
  '_sort'                => 'from_date',
  '_dir'                 => 'ASC',
  '_ff[]'                => 'accounting_year_id',     // Retrieve all accounting periods associated with the given year, ordered by start date.
  '_ft[]'                => 'eq',
  '_fc[]'                => $object_id_lock_versions[0]['id'],
  '_select_columns[]'    => array(
    'id',
    'created_by',
    'updated_by',
    'created_at',
    'updated_at',
    'lock_version',
    'name',
    'accounting_year_id',
    'from_date',
    'thru_date',
    'full_name',
  )
);

$response = $workbooks->assertGet('accounting/accounting_periods', $get_all_accounting_periods_for_one_accounting_year);
$workbooks->log('Fetched objects', $response['data']);

/*
 * Add a new accounting period to an existing accounting year, will be appended to the end of the year.
 * 
 * There cannot be any gaps between periods so increment the thru_date of the latest period by one day to find the from_date of your new period.
 * The thru_date of the new period will be 30 days from the start. This is a completely arbitrary value.
 */

$from_date = strval(date('Y-m-d', strtotime( end($response['data'])['thru_date'] . " +1 days")));
$thru_date = strval(date('Y-m-d', strtotime( $from_date . " +30 days")));

$create_one_accounting_period = array(
  'name'                                 => 'Accounting Period',
  'accounting_year_id'                   => $object_id_lock_versions[0]['id'],     // Must specify id and lock version, has to be the latest existing year.
  'from_date'                            => $from_date,
  'thru_date'                            => $thru_date,                            // Can also specify the sub_period.
);

$response = $workbooks->assertCreate('accounting/accounting_periods', $create_one_accounting_period);
$period_object_id_lock_versions = $workbooks->idVersions($response);

/*
 * Update an existing accounting year by providing it's id and lock version. Can only update the name.
 */
$update_one_accounting_year = array(
  'id'                                   => $object_id_lock_versions[0]['id'],     // Specify id and lock version of year we just created.
  'lock_version'                         => $object_id_lock_versions[0]['lock_version'],
  'name'                                 => 'UPDATED Accounting Year',
);

$response = $workbooks->assertUpdate('accounting/accounting_years', $update_one_accounting_year);
$object_id_lock_versions = $workbooks->idVersions($response);

/*
 * Update an existing accounting period by providing it's id and lock version. Can only update the name.
 */
$update_one_accounting_period = array(
  'id'                                   => $period_object_id_lock_versions[0]['id'],     // Specify id and lock version of period we just created.
  'lock_version'                         => $period_object_id_lock_versions[0]['lock_version'],
  'name'                                 => 'UPDATED Accounting Period',
);

$response = $workbooks->assertUpdate('accounting/accounting_periods', $update_one_accounting_period);
$period_object_id_lock_versions = $workbooks->idVersions($response);

/*
 * Retreive the accounting year to see our changes.
 */
$get_one_accounting_year = array(
  '_start'               => '0',
  '_limit'               => '1',
  '_sort'                => 'created_at',
  '_dir'                 => 'DESC',
  '_select_columns[]'    => array(
    'id',
    'created_by',
    'updated_by',
    'created_at',
    'updated_at',
    'came_from_migration',
    'name',
    'own_organisation_id',
    'from_date',
    'thru_date',
  )
);

$response = $workbooks->assertGet('accounting/accounting_years', $get_one_accounting_year);
$workbooks->log('Fetched objects', $response['data']);

/*
 * Retreive all the accounting periods within the year to see the newly created one.
 */
$get_all_accounting_periods_for_one_accounting_year = array(
  '_start'               => '0',
  '_limit'               => '13',                     // Increased limit to account for the new period.
  '_sort'                => 'from_date',
  '_dir'                 => 'ASC',
  '_ff[]'                => 'accounting_year_id',     // Retrieve all accounting periods associated with the given year, ordered by start date.
  '_ft[]'                => 'eq',
  '_fc[]'                => $object_id_lock_versions[0]['id'],
  '_select_columns[]'    => array(
    'id',
    'created_by',
    'updated_by',
    'created_at',
    'updated_at',
    'lock_version',
    'name',
    'accounting_year_id',
    'from_date',
    'thru_date',
    'full_name',
  )
);

$response = $workbooks->assertGet('accounting/accounting_periods', $get_all_accounting_periods_for_one_accounting_year);
$workbooks->log('Fetched objects', $response['data']);

/*
 * Delete the additional accounting period appended to the accounting year, then delete the accounting year itself.
 * You can only delete the latest accounting year and the latest accounting period from within the latest accounting year.
 * If you delete all accounting periods from an accounting year it will also delete the accounting year itself.
 */
$response = $workbooks->assertDelete('accounting/accounting_periods', $period_object_id_lock_versions);
$response = $workbooks->assertDelete('accounting/accounting_years', $object_id_lock_versions);

testExit($workbooks);

?>