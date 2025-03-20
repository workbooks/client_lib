<?php
  
/**
* A demonstration of using the Workbooks API via a thin PHP wrapper to CRUD comments
*
* Last commit $Id: comments_example.php 58221 2023-04-28 09:55:28Z kswift $
*
* The MIT License
*
* Copyright (c) 2008-2012, Workbooks Online Limited.
*
* Permission is hereby granted, free of charge, to any person obtaining a copy
* of this software and associated documentation files (the "Software"), to deal
* in the Software without restriction, including without limitation the rights
* to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
* copies of the Software, and to permit persons to whom the Software is
* furnished to do so, subject to the following conditions:
*
* The above copyright notice and this permission notice shall be included in
* all copies or substantial portions of the Software.
*
* THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
* IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
* FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
* AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
* LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
* OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
* THE SOFTWARE.
*/

/* If not running under the Workbooks Process Engine create a session */

if (!isset($workbooks)){
  
  require_once 'workbooks_api.php';
  
  require 'test_login_helper.php';

}

/*
* We now have a valid logged-in session. This script does a series of 'CRUD' (Create, Read, Update, Delete) operations.
*/

/*
* Create a single organisation
*/
$create_one_organisation = array(
  'name' => 'Super comment Supermarkets',
  'industry' => 'Food',
  'main_location[country]' => 'United Kingdom',
  'main_location[county_province_state]' => 'Berkshire',
  'main_location[town]' => 'Reading',
);
$response = $workbooks->assertCreate('crm/organisations', $create_one_organisation);
$created_organisation_id = $response['affected_objects'][0]['id'];
$created_organisation_lock_version = $response['affected_objects'][0]['lock_version'];

/*
* Create three hundred comments associated with that organisation
*/

$html = <<<EOF
&nbsp;<strong>Bold</strong> <em>Italic </em><u>Underlined&nbsp;</u> <span style="color:#e74c3c" class="">Red Text</span>
EOF;

$create_comments = array();

for ($x = 0; $x <= 100; $x++) {
  array_push($create_comments, 
    array(
    'resource_id'   => $created_organisation_id,
    'resource_type' => 'Private::Crm::Organisation',
    'text'      => $x*3 . ' This is the body of a comment'
    ),
    array(
      'resource_id'   => $created_organisation_id,
      'resource_type' => 'Private::Crm::Organisation',
      'text'      => $x*3 + 1 . $html
    ),
    array(
      'resource_id'   => $created_organisation_id,
      'resource_type' => 'Private::Crm::Organisation',
      'text'      => $x*3 + 2 . ' This is the body of a comment, it has cake 🎂, a tree 🌲 and a snowman ⛄'
    )
  );
}

$response = $workbooks->assertCreate('comments.api', $create_comments);
#$workbooks->log('created objects', $response);
$comment_1 = $response['affected_objects'][0];
$comment_2 = $response['affected_objects'][1];
$comment_3 = $response['affected_objects'][2];
$comment_4 = $response['affected_objects'][3];

/*
* Update the first comment associated with that organisation
*/

$update_comment_1 = array(
  'id'      => $comment_1['id'],
  'lock_version'  => $comment_1['lock_version'],
  'text'      => $comment_1['text']."\n Here is a new line on comment number 1",
);

$workbooks->assertUpdate('comments.api', $update_comment_1);

/*
* Delete the 3rd comment associated with that organisation
*/

$deletion_array = array('id' => $comment_3['id'], 'lock_version' => $comment_3['lock_version']);
$workbooks->assertDelete('comments.api', $deletion_array);

/* TODO add reactions and mentions */
 
/*
* List the Notes we have left
*/
$filter_limit_select = array(
  '_start' => '0', // Starting from the 'zeroth' record
  '_limit' => '200', // fetch up to 200 records
  '_sort' => 'id', // Sort by 'id'
  '_dir' => 'ASC', // in ascending order
  '_ff[]' => 'resource_id', // Filter by this column
  '_ft[]' => 'eq', // Equals
  '_fc[]' => $created_organisation_id, // ID of the created organisation
  '_select_columns[]' => array( // An array, of columns to select
    'id',
    'lock_version',
    'subject',
    'text',
  )
);
$response = $workbooks->assertGet('comments.api', $filter_limit_select);

$response_data = $response['data'];
$response_record_count = count($response_data);
$response_total = $response['total'];

$workbooks->log('Fetched object count', $response_record_count);
$workbooks->log('total comment count', $response_total);
//$workbooks->log('Fetched objects', $response);

if ($response_total != (count($create_comments) - 1)) {
  throw new Exception("Unexpected total comment count");
}

if ($response_record_count != 200) {
  throw new Exception("Unexpected response comment count");
}

/**
* Delete the created organisation
* - Doing this will also delete any associated comments
*/

$delete_organisation = array('id' => $created_organisation_id, 'lock_version' => $created_organisation_lock_version);
$workbooks->assertDelete('crm/organisations', $delete_organisation);

if(function_exists('testExit')) {
  testExit($workbooks);
} else {
  exit (0);
}

?>
