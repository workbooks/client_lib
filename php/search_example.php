<?php
  
/**
 *   A demonstration of using the Workbooks API to search for records using
 *   their most significant fields via a thin PHP wrapper
 *
 *   Last commit $Id: search_example.php 46578 2020-02-20 11:16:18Z jmonahan $
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
 * Do a full search
 */
$response = $workbooks->assertGet('searchables.api', 
  array(
    'search' => 'James', 
    '_sort' => 'relevance', 
    '_dir' => 'DESC' 
  )
);

$workbooks->log('Fetched objects', $response['data']);
if ( $response['total'] <= 0 ||  $response['total'] >= 100 ) {
  $workbooks->log('Received an unexpected number of rows - expected between 1 and 100 and got ', array ($response['total']));
  testExit($workbooks, $exit_error);
}

/*
 * Perform a quick search
 */
$response = $workbooks->assertGet('searchables.api',
  array(
    'search' => 'J.K.',
    'quick_search' => 'true',
    '_sort' => 'relevance',
    '_dir' => 'DESC'
  )
);

$workbooks->log('Fetched objects', $response['data']);
if ( $response['total'] <= 0 ||  $response['total'] >= 100 ) {
  $workbooks->log('Received an unexpected number of rows - expected between 1 and 100 and got ', array ($response['total']));
  testExit($workbooks, $exit_error);
}

/*
 * Perform a full serach that would return > 100 rows (only the first 100 will be returned)
 */
$response = $workbooks->assertGet('searchables.api',
  array(
    'search' => 'Ltd',
    '_sort' => 'relevance',
    '_dir' => 'DESC'
  )
);

$workbooks->log('Fetched objects', $response['data']);
if ($response['total'] != 100 ) {
  $workbooks->log('Received an unexpected number of rows - expected 100 and got', $response['total']);
  testExit($workbooks, $exit_error);
}

testExit($workbooks);

?>

