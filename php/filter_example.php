<?php
  
/**
 *   A demonstration of using the Workbooks API to find records using "filters" via a thin PHP wrapper
 *
 *   Last commit $Id: filter_example.php 21411 2014-03-14 00:12:52Z jkay $
 *
 *       The MIT License
 *
 *       Copyright (c) 2008-2014, Workbooks Online Limited.
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
 * You can choose range (_start, _limit), sort order (_sort, _dir), column selection (_select_columns) and 
 * a filter which can include boolean logic (and/or/not). The filter can have several possible structures
 * which are equivalent.
 * 
 */
 
/*
 * Some basic options which are used in the following examples.
 * 
 * An array, of columns to select. Discover these in the API 'meta data' document, here:
 *    http://www.workbooks.com/api-reference
 */
$select_columns = array( 
  'id',
  'lock_version',
  'name',
  'object_ref',
  'updated_by_user[person_name]',
  'main_location[county_province_state]',
  'main_location[street_address]',
);

/*
 * Start/Limit, Sort/Direction, Column selection
 */
$limit_select = array(
  '_skip_total_rows'     => true,  // Omit the 'total number of qualifying entries' from the response: *significantly* faster.
  '_start'               => '0',   // Starting from the 'zeroth' record (this is an offset)
  '_limit'               => '5',   //   fetch up to 5 records
  '_sort'                => 'id',  // Sort by 'id'
  '_dir'                 => 'ASC', //   in ascending order (=> oldest record first)
  '_select_columns[]'    => $select_columns,
);

// First filter structure: specify arrays for Fields ('_ff[]'), comparaTors ('_ft[]'), Contents ('_fc[]').
// Note that 'ct' (contains) is MUCH slower than equals. 'not_blank' requires Contents to compare with, but this is ignored.
$filter1 = array_merge($limit_select, array(
  '_ff[]' => array('main_location[county_province_state]', 'main_location[county_province_state]', 'main_location[street_address]'),
  '_ft[]' => array('eq',                                   'ct',                                   'not_blank'),
  '_fc[]' => array('Berkshire',                            'Yorkshire',                             ''),
  '_fm' => '(1 OR 2) AND 3',                            // How to combine the above clauses, without this: 'AND'.
));
$response1 = $workbooks->assertGet('crm/organisations', $filter1);
$workbooks->log('Fetched objects using filter1', array($filter1, $response1['data']));

// The equivalent using a second filter structure: a JSON-formatted string  array of arrays containg 'field, comparator, contents'
$filter2 = array_merge($limit_select, array(
  '_filter_json' => '['.
    '["main_location[county_province_state]", "eq", "Berkshire"],' .
    '["main_location[county_province_state]", "ct", "Yorkshire"],' .
    '["main_location[street_address]", "not_blank", ""]' .
    ']',
  '_fm' => '(1 OR 2) AND 3',                            // How to combine the above clauses, without this: 'AND'.
));
$response2 = $workbooks->assertGet('crm/organisations', $filter2);
$workbooks->log('Fetched objects using filter2', array($filter2, $response2['data']));

// The equivalent using a third filter structure: an array of filters, each containg 'field, comparator, contents'.
$filter3 = array_merge($limit_select, array(
  '_filters[]'     => array(
     array('main_location[county_province_state]', 'eq', 'Berkshire'),
     array('main_location[county_province_state]', 'ct', 'Yorkshire'),
     array('main_location[street_address]', 'not_blank', ''),
  ),
  '_fm' => '(1 OR 2) AND 3',                            // How to combine the above clauses, without this: 'AND'.
));
$response3 = $workbooks->assertGet('crm/organisations', $filter3);
$workbooks->log('Fetched objects using filter3', array($filter3, $response3['data']));

// Test that the responses are all the same.
$same = true; 
$same = $same && (count($response1['data']) == count($response2['data']));
$same = $same && (count($response1['data']) == count($response3['data']));
for ($i = 0; $i < count($response1['data']); $i++) {
  $diff2 = array_diff($response1['data'][$i], $response2['data'][$i]);
  $diff3 = array_diff($response1['data'][$i], $response3['data'][$i]);
  $same = $same && (empty($diff2) && empty($diff3));
}
if (!$same) 
{
  $workbooks->log('The results retrieved through different filter syntaxes differ!');
  testExit($workbooks, $exit_error);
}


testExit($workbooks);

?>

