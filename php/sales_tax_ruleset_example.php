<?php
  
/**
 *   Query Sales Tax Rules to select the best match Sales Tax Code.
 *
 *   Last commit $Id: sales_tax_ruleset_example.php 39576 2018-05-03 15:18:38Z jkay $
 *
 *       The MIT License
 *
 *       Copyright (c) 2008-2017, Workbooks Online Limited.
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

$workbooks->log('Running test script', __FILE__, 'info');

/* 
  A tax_regime_id must be specified.  
  Several rulesets can be evaluated in one call: the 'id' attribute selects a Sales Tax Ruleset and
  multiple values can be passed separated by a comma.
  The six location attributes are all optional.
*/

$options = [
  '_filters[]' => ['id', 'eq', '2,4'],
  'tax_regime_id' => 1,
  'delivery_country' => 'United States of America',
  'delivery_county_province_state' => 'California',
  'delivery_town' => 'San Andreas',
  'customer_country' => 'Brazil',
  'customer_county_province_state' => 'Rio de Janeiro',
  'customer_town' => 'Rio de Janeiro',
];
$response = $workbooks->assertGet('accounting/sales_tax_rulesets/resolve', $options);

if ($response['total'] <> 1) {
  $workbooks->log('Received an unexpected number of rows - expected 1 results and instead got ', $response);
  testExit($workbooks, $exit_error);
}
if ($response['data'][0]['resolved_sales_tax_code_id'] != '12') {
  $workbooks->log('Expected to select the local tax code', array ($response['data']));
  testExit($workbooks, $exit_error);
}

$workbooks->log('Tax Code', $response['data'][0]);

/* This should NOT match a record */
$options = [
  '_filters[]' => ['id', 'eq', '2'],
  'tax_regime_id' => 1,
  'delivery_country' => 'United States of America',
  'delivery_county_province_state' => 'California',
  'delivery_town' => 'San Taclause',
  'customer_country' => 'Brazil',
  'customer_county_province_state' => 'Rio de Janeiro',
  'customer_town' => 'Rio de Janeiro',
];
$response = $workbooks->assertGet('accounting/sales_tax_rulesets/resolve', $options);


if (isset($response['data'][0]['resolved_sales_tax_code_id'])) {
  $workbooks->log('Should not have had a tax code available', array ($response['data']));
  testExit($workbooks, $exit_error);
}

testExit($workbooks);

?>

