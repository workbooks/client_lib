<?php
  
/**
 *   A demonstration of using the Workbooks API to fetch a report via a thin PHP wrapper.
 *
 *   Last commit $Id: report_example.php 18524 2013-03-06 11:15:59Z jkay $
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
 * Assumes you have a report with ID 1. 
 * You can find the ID of a report by exporting its contents as CSV then examining the 
 * URL which was used by your browser from its Downloads page. The number at the end 
 * is the ID, e.g. https://secure.workbooks.com/data_views/1.csv
 */
$response = $workbooks->get('data_views/1.csv', array(), FALSE);
$workbooks->log('Fetched CSV', $response);

if (!preg_match('/Amount,Close date,Opportunity stage name,Amount Amount,Amount Currency/', $response)) {
  $workbooks->log('ERROR: Unexpected response', $response, 'error');
  testExit($workbooks, $exit_error);
}




/* 
 * Assumes you have a Product Report called 'Product Lookup' with ID 3 and a calculated column called
 * fcode___description_6_amount.
 * 
 * Reports can be accessed via the API in two different ways, either by Name or by Id.
 * 
 * To access a Report by name simply use the path data_view/[Report Name].api - note that the name of the report must
 * be escaped according to RFC 3986 (in PHP use rawurlencode - basically encode the spaces in the name as %20 instead
 * of +), for example:
 *   $escaped_data_view_name = rawurlencode('My Report Name');
 *   $response = $workbooks->get("data_view/{$escaped_data_view_name}.api", $filter);
 * ...or could be writeen directly as...
 *   $response = $workbooks->get("data_view/My%20Report%20Name.api", $filter);
 * 
 * To access a Report by Id use the path data_view/[Report Id]/data.api, for example:
 *   $response = $workbooks->get("data_view/9999/data.api", $filter);
 * 
 * This example obtains the total number of rows for the Report and pages through the Report rows (in the page size
 * specified below) counting the number of specified words within the Code & Description column and then finally
 * outputs the results.
 */
$data_view_id = 3;
$data_view_name = 'Product Lookup';
$name_column = 'fcode___description_6_amount';
$page_size = 10;

$words_to_count = array(
  'Development',
  'Consultancy',
  'Course',
  'Bespoke'
);


function get_report_for_filter($filter) {
  global $workbooks, $data_view_name;
  // Note: We use rawurlencode as this converts spaces to %20 (not +) which is what the server expects
  $escaped_data_view_name = rawurlencode($data_view_name);
  $response = $workbooks->get("data_view/{$escaped_data_view_name}.api", $filter);
  return $response;
}

function get_report_row_count() {
  $response = get_report_for_filter(array(
    '_start' => '0',
    '_limit' => '0'
  ));
  return (int)$response['total'];
}

function get_report_data_for_page($page) {
  global $page_size;
  $start = $page * $page_size;
  $response = get_report_for_filter(array(
    '_start' => "{$start}",
    '_limit' => "{$page_size}",
    '__skip_total_rows' => 'true',
  ));
  return $response['data'];
}



// Get the total number of report rows
$report_row_count = get_report_row_count();
$workbooks->log('Report Total', $report_row_count);

// Work out how many pages to page through
$pages = ceil($report_row_count / $page_size);

// Build the regex to match words
$words_regex = '/\b' . implode('|', $words_to_count) . '\b/';

// Page through the Report counting the specified words
$results = array();
for ($page = 0; $page < $pages; $page++) {
  $page_data = get_report_data_for_page($page);
  foreach($page_data as $report_row) {
    $matches = array();
    if (preg_match_all($words_regex, $report_row[$name_column], $matches)) {
      if (is_array($matches[0])) {
        foreach ($matches[0] as $matched_name) {
          $results[$matched_name] = (array_key_exists($matched_name, $results)) ? $results[$matched_name] + 1 : 1;
        }
      }
    }
  }
}

$workbooks->log('Word Counts', $results);

testExit($workbooks);

?>
