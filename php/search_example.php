<?php
  
/**
 *   A demonstration of using the Workbooks API to search for records using
 *   their most significant fields via a thin PHP wrapper
 *
 *   Last commit $Id: search_example.php 18524 2013-03-06 11:15:59Z jkay $
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
 * Do the search
 */
$response = $workbooks->assertGet('searchables.api', 
	array(
		'search' => 'James', 
		'_sort' => 'relevance', 
		'_dir' => 'DESC' 
	)
);
$workbooks->log('Fetched objects', $response['data']);

testExit($workbooks);

?>

