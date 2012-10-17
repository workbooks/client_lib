<?php
  
/**
 *   A demonstration of using the Workbooks API to fetch a report via a thin PHP wrapper.
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

testExit($workbooks);

?>

