<?php
  
/**
 *   A demonstration of using the Workbooks API to fetch metadata via a thin PHP wrapper
 *
 *   Last commit $Id: metadata_example.php 16982 2012-07-31 11:28:14Z jkay $
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
 * List all the class names
 */
$fetch_summary = array(
  '_select_columns[]'    => array(                                   // An array, of columns to select
      'class_name',
  )
);
$response = $workbooks->assertGet('metadata/types', $fetch_summary);
$workbooks->log('Fetched objects', $response['data']);

/*
 * A set of data for a defined set of class names
 */
$fetch_some = array(
  'class_names[]' => array(                                          // An array, of class_names to fetch
      'Private::Searchable',
      'Private::Crm::Person',
      'Private::Crm::Organisation',
      'Private::Crm::Case',
    ),
  '_select_columns[]'    => array(                                   // An array, of columns to select
      'class_name',
      'base_class_name',
      'human_class_name',
      'human_class_name_plural',
      'human_class_description',
      'instances_described_by',
      'icon',
      'help_url',
      'controller_path',
  )
);
$response = $workbooks->assertGet('metadata/types', $fetch_some);
$workbooks->log('Fetched objects', $response['data']);

/*
 * Lots of data, including associations and fields, for a single class name
 */
$fetch_some_more = array(
  'class_names[]' => array(                                          // An array, of class_names to fetch
      'Private::Crm::Case',
    ),
  '_select_columns[]'    => array(                                   // An array, of columns to select
      'class_name',
      'base_class_name',
      'human_class_name',
      'human_class_name_plural',
      'human_class_description',
      'instances_described_by',
      'icon',
      'help_url',
      'controller_path',
      'fields',
      'associations',
  )
);
$response = $workbooks->assertGet('metadata/types', $fetch_some_more);
$workbooks->log('Fetched objects', $response['data']);

/*
 * Fetch everything
 */
$fetch_all = array();
$response = $workbooks->assertGet('metadata/types', $fetch_all);
$workbooks->log('Fetched objects', $response['data']);

testExit($workbooks);

?>

