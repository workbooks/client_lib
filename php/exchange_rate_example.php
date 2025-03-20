<?php
  
/**
 *   A demonstration of using the Workbooks API via a thin PHP wrapper
 *
 *   Last commit $Id: exchange_rate_example.php 66031 2025-03-17 16:49:24Z jkay $
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

$from_date =  date('Y-m-d');

/*
 * Do not attempt to create the rates again if they already exist.
 */
$filter = array(
    '_start'               => '0',                                     // Starting from the 'zeroth' record
    '_limit'               => '100',                                   // fetch up to 100 records
    '_sort'                => 'id',                                    // Sort by 'id'
    '_ff[]'                => array('created_through','id'),            // Filter by this column
    '_ft[]'                => array('eq','eq'),                        // equals
    '_fc[]'                => array('php_test_client', $object_id_lock_versions[0]['id'].','.$object_id_lock_versions[1]['id'] ),
    
);

$response = $workbooks->assertGet('accounting/exchange_rates', ['_filters' => ['from_date', 'eq', $from_date]]);
if (count($response['data']) > 0) {
  $workbooks->log('Already exists', $response);
  testExit($workbooks);
}

/*
 * Add a new Exchange Rate between GBP and EUR
 */

$workbooks->log('Create a pair of exchange rates');

$exchange_rates = array(
  array (
    'base_currency'                => 'GBP',
    'term_currency'                => 'EUR',
    'exchange_rate'                => '1.51',
    'from_date'                    => $from_date,
  ),
  array (
    'base_currency'                => 'EUR',
    'term_currency'                => 'GBP',
    'exchange_rate'                => '0.61',
    'from_date'                    => $from_date,
  ),
);

$response = $workbooks->assertCreate('accounting/exchange_rates', $exchange_rates);
$object_id_lock_versions = $workbooks->idVersions($response);
$workbooks->log('Created objects', $response);

/* Retreive our rates*/

$workbooks->log('Fetch the created pair');

$filter = array(
    '_start'               => '0',                                     // Starting from the 'zeroth' record
    '_limit'               => '100',                                   // fetch up to 100 records
    '_sort'                => 'id',                                    // Sort by 'id'
    '_ff[]'                => array('created_through','id'),            // Filter by this column
    '_ft[]'                => array('eq','eq'),                        // equals
    '_fc[]'                => array('php_test_client', $object_id_lock_versions[0]['id'].','.$object_id_lock_versions[1]['id'] ),
    
);

$response = $workbooks->assertGet('accounting/exchange_rates', $filter);
$workbooks->log('Fetched Rates size:', sizeof($response['data']));
$workbooks->log('Fetched Rates:', $response['data']);

if ( sizeof( $response['data']) != 2 ) {
  throw new Exception("Unexpected response from Get request, expected two results");
}

/*
 *
 * Updates and deletes are not supported, ensure they fail with the correct error 
 * ==============================================================================
 *
 */

$workbooks->log('Update the created pair');

$update_rate = array(
  array (
    'id'                                   => $object_id_lock_versions[0]['id'],
    'lock_version'                         => $object_id_lock_versions[0]['lock_version'],
    'exchange_rate'                        => 10,
  ),
);

$response = $workbooks->Update('accounting/exchange_rates', $update_rate);
$workbooks->log('Update Response', $response);

if ( $response['failures'][0] != 'Update or Delete Operation not supported for Exchange Rates' ) {
  throw new Exception("Unexpected response from update request");
}

$workbooks->log('Delete the created pair');

/* This is expected to fail, as delete and update operations are not supported at this time */

$response = $workbooks->Delete('accounting/exchange_rates', $object_id_lock_versions);
$workbooks->log('Delete Response', $response);

if ( $response['failures'][0] != 'Update or Delete Operation not supported for Exchange Rates' ) {
  throw new Exception("Unexpected response from delete request");
}

testExit($workbooks);

?>

