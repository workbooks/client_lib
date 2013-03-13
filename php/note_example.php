<?php
  
/**
* A demonstration of using the Workbooks API via a thin PHP wrapper to CRUD notes
*
* Last commit $Id: note_example.php 18524 2013-03-06 11:15:59Z jkay $
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
  'name' => 'Nogo Supermarkets',
  'industry' => 'Food',
  'main_location[country]' => 'United Kingdom',
  'main_location[county_province_state]' => 'Oxfordshire',
  'main_location[town]' => 'Oxford',
);
$response = $workbooks->assertCreate('crm/organisations', $create_one_organisation);
$created_organisation_id = $response['affected_objects'][0]['id'];
$created_organisation_lock_version = $response['affected_objects'][0]['lock_version'];

/*
* Create three Notes associated with that organisation
*/

$html = <<<EOF
<p>This is the body of note number 2<p/>
<a href="http://www.workbooks.com/" target="_blank">Workbooks.com</a>
EOF;

$create_notes = array(
  0 => array(
    'resource_id'   => $created_organisation_id,
    'resource_type' => 'Private::Crm::Organisation',
    'subject'   => 'Note number 1',
    'text'      => 'This is the body of note number 1'
  ),
  1 => array(
    'resource_id'   => $created_organisation_id,
    'resource_type' => 'Private::Crm::Organisation',
    'subject'   => 'Note number 2',
    'text'      => $html //Text on notes can render html
    
  ),
  2 => array(
    'resource_id'   => $created_organisation_id,
    'resource_type' => 'Private::Crm::Organisation',
    'subject'   => 'Note number 3',
    'text'      => 'This is the body of note number 3'
    
  ),
);

$response = $workbooks->assertCreate('notes.api', $create_notes);
$note_1 = $response['affected_objects'][0];
$note_2 = $response['affected_objects'][1];
$note_3 = $response['affected_objects'][2];

/*
* Update the first Note associated with that organisation
*/

$update_note_1 = array(
  'id'      => $note_1['id'],
  'lock_version'  => $note_1['lock_version'],
  'text'      => $note_1['text']."\n Here is a new line on note number 1",
);

$workbooks->assertUpdate('notes.api', $update_note_1);


/*
* Delete the last Note associated with that organisation
*/

$deletion_array = array('id' => $note_3['id'], 'lock_version' => $note_3['lock_version']);
$workbooks->assertDelete('notes.api', $deletion_array);
 
/*
* List the two Notes we have left
*/
$filter_limit_select = array(
  '_start' => '0', // Starting from the 'zeroth' record
  '_limit' => '100', // fetch up to 100 records
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
$response = $workbooks->assertGet('notes.api', $filter_limit_select);
$workbooks->log('Fetched objects', $response['data']);

/**
* Delete the created organisation
* - Doing this will also delete any associated notes
*/

$delete_organisation = array('id' => $created_organisation_id, 'lock_version' => $created_organisation_lock_version);
$workbooks->assertDelete('crm/organisations', $delete_organisation);

if(function_exists('testExit')) {
  testExit($workbooks);
}

else {
  exit (0);
}

?>