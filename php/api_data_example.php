<?php
  
/**
 *   Processes often need to store their state between runs. The 'API Data' facility provides
 *   a simple way to do this.
 *
 *   A demonstration of using the Workbooks API via a thin PHP wrapper to store and retrieve
 *   state.
 *
 *   Last commit $Id: api_data_example.php 21910 2014-05-07 09:58:02Z jkay $
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
 * Create API Data items
 */
$test_values = array(
  'the answer'              => 42,
  'poppins'                 => 'Supercalifragilisticexpealidocious',
  'null'                    => NULL,
  'hundred thousand characters' => str_repeat('123456789 ', 10000 ),
  'multibyte_characters'    => 'д е ё ж з и й к л  字 字',
);
$create_api_data = array(
  array ('key' => 'api_data_example: the answer',               'value' => $test_values['the answer']),
  array ('key' => 'api_data_example: poppins',                  'value' => $test_values['poppins']),
  array ('key' => 'api_data_example: null',                     'value' => $test_values['null']),
  array ('key' => 'api_data_example: hundred thousand characters',  'value' => $test_values['hundred thousand characters']),
  array ('key' => 'api_data_example: multibyte characters',     'value' => $test_values['multibyte_characters']),
);
$response = $workbooks->assertCreate('automation/api_data', $create_api_data);
$object_id_lock_versions = $workbooks->idVersions($response);


/*
 * Update a couple of those API Data items
 */
$update_api_data = array(
  array (
    'id'                                   => $object_id_lock_versions[0]['id'],
    'lock_version'                         => $object_id_lock_versions[0]['lock_version'],
    'value'                                => 43,
  ),
  array (
    'id'                                   => $object_id_lock_versions[2]['id'],
    'lock_version'                         => $object_id_lock_versions[2]['lock_version'],
    'value'                                => 'null points',
  ),
);
$response = $workbooks->assertUpdate('automation/api_data', $update_api_data);
$object_id_lock_versions = $workbooks->idVersions($response);

/*
 * Fetch four of them back, all available fields
 */
$get_api_data = array(
  '_sort'                => 'id',                                    // Sort by 'id'
  '_dir'                 => 'ASC',                                   //   in ascending order
  '_fm'                  => 'or',
  '_ff[]'                => array('key', 'key', 'key', 'key'),
  '_ft[]'                => array('eq', 'eq', 'eq', 'eq'),
  '_fc[]'                => array(
    'api_data_example: the answer',               
    'api_data_example: null',                     
    'api_data_example: hundred thousand characters',   
    'api_data_example: multibyte characters',     
  ),
);
$response = $workbooks->assertGet('automation/api_data', $get_api_data);
$workbooks->log('Fetched data', $response['data']);

/*
 * Fetch a single item using the alternate filter syntax
 */
$response = $workbooks->assertGet('automation/api_data', array('_filter_json' => '[["key", "eq", "api_data_example: poppins"]]'));
$workbooks->log('Fetched a data item', $response['data']);

/*
 * Attempt to fetch an item which does not exist
 */
$get_non_existent_api_data = array(
  '_ff[]'                => 'key',
  '_ft[]'                => 'eq',
  '_fc[]'                => 'api_data_example: no such record exists',
);
$response = $workbooks->get('automation/api_data', $get_non_existent_api_data);
if ($response['total'] <> 0) {
  $workbooks->log('Bad response for non-existent item', $response);
  testExit($workbooks, $exit_error);
}
$workbooks->log('Response for non-existent item', $response);

/*
 * Delete all data items which are visible to this user. 
 */
$response = $workbooks->assertGet('automation/api_data', array('_select_columns[]' => array('id', 'lock_version')));
$workbooks->log('Items to delete', $response['data']);

$response = $workbooks->assertDelete('automation/api_data', $response['data']);

testExit($workbooks);

?>

