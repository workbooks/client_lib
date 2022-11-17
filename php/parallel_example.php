<?php
  
/**
 *   A demonstration of using the Workbooks API via a thin PHP wrapper to run concurrent requests.
 *
 *   Most Workbooks API requests - get, create, update, delete - can run concurrently resulting in
 *   faster script execution. Call the appropriate method and pass in option 'async' set to true:
 *   requests will be processed without blocking the calling script. The caller should later use
 *   asyncResponse() or assertAsyncResponse() to gather the response.
 *
 *   Last commit $Id: parallel_example.php 50752 2021-04-06 10:34:50Z kswift $
 *
 *       The MIT License
 *
 *       Copyright (c) 2008-2015, Workbooks Online Limited.
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
 * An array of requests which are run in parallel. Note that each call includes the 'async'
 * option set to true and that the 'assert' methods (assertGet() etc) must not be used. 
 */

$requests = array();

// Fetch four attributes of a randomly-selected open case. Speed things up by not counting all cases.
$requests[] = $workbooks->get('crm/cases', 
  array(
    '_select_columns[]' => array('id', 'lock_version', 'name', 'object_ref'),
    '_filters[]' => array(
       array('record_state', 'eq', 'open'),
    ),
    '_start' => 0,
    '_limit' => 1,
    '_sort' => 'id',
    '_dir' => 'DESC',
    '_skip_total_rows' => true,
  ), array(
    'async' => true,
  )
);

// Fetch three attributes of the three people with the highest id. Speed things up by not counting all people.
$requests[] = $workbooks->get('crm/people', 
  array(
    '_select_columns[]' => array('name', 'type', 'object_ref'),
    '_sort' => 'id',
    '_dir' => 'DESC',
    '_start' => 0, 
    '_limit' => 3,
    '_skip_total_rows' => true,
  ), array(
  'async' => true,
  )
);

// Create an API Data record
date_default_timezone_set('Europe/London');
$create = array(
    'key' => 'Test Create ' . date('dmy'),
    'value' => 'text',
);
$create_api_data_request = $workbooks->create('automation/api_data', $create, array(),
  array(
    'async' => true,
  )
);

// Fetch three attributes of the organisations with id 1 and 3. Speed things up by not counting all organisations.
$requests[] = $workbooks->get('crm/organisations', 
  array(
    '_select_columns[]' => array('name', 'type', 'object_ref'),
    '_filters[]' => array(
       array('id', 'eq', '1,3'),
    ),
    '_skip_total_rows' => true,
  ), 
  array(
    'async' => true,
  )
);

// Fetch three attributes of the three organisation with the highest id. Speed things up by not counting all organisations.
$requests[] = $workbooks->get('crm/organisations', 
  array(
    '_select_columns[]' => array('name', 'type', 'object_ref'),
    '_sort' => 'id',
    '_dir' => 'DESC',
    '_start' => 0, 
    '_limit' => 3,
    '_skip_total_rows' => true,
  ), 
  array(
    'async' => true,
  )
);

// The create must complete before we can issue an update to the created record
$create_api_data_response = $workbooks->assertAsyncResponse($create_api_data_request);
$workbooks->log('Api Data Create Response', $create_api_data_response);
$update = array(
  'key2' => 'Secondary key',
  'value' => 'test2',
  'id' => $create_api_data_response['affected_objects'][0]['id'],
  'lock_version' => $create_api_data_response['affected_objects'][0]['lock_version'],
);
$update_api_data_request = $workbooks->update('automation/api_data', $update, array(),
  array(
    'async' => true,
  )
);

/*
 * Now retrieve the outstanding responses. 
 * The assertAsyncResponse() call will block until the response is available unless the 'async' option is passed again and set to true.
 */
foreach ($requests as $request) {
  $response = $workbooks->assertAsyncResponse($request);
  $workbooks->log('Response', $response);
}

$workbooks->log('Received all outstanding requests');

// Delete the api_data record created then updated earlier:
// The update must complete before we can issue this
$update_api_data_response = $workbooks->assertAsyncResponse($update_api_data_request);
$workbooks->log('Api Data Update Response', $update_api_data_response);
$delete = array(
  'id' => $update_api_data_response['affected_objects'][0]['id'],
  'lock_version' => $update_api_data_response['affected_objects'][0]['lock_version'],
);
$delete_api_data_request = $workbooks->delete('automation/api_data', $delete, array(),
  array(
    'async' => true,
  )
);

// This is effectively synchronous since no requests are outstanding apart from the delete().
$delete_api_data_response = $workbooks->assertAsyncResponse($delete_api_data_request);
$workbooks->log('Api Data Delete Response', $delete_api_data_response);

// Now show how responses can also be gathered at our leisure.
// Send a set of requests
$endpoints = array('crm/organisations', 'crm/people', 'crm/cases', 'automation/api_data', 'pricebook/products',
                   'activity/activities', 'dashboard', 'reporting/reports', 'crm/sales_leads', 'activity/tasks');
$fetch_requests = array();
for ($i = 0; $i < 20; $i++) {
  $fetch_requests[] = $workbooks->get($endpoints[$i%5], 
    array(
      '_select_columns[]' => array('name', 'type', 'object_ref'),
      '_sort' => 'id',
      '_dir' => 'DESC',
      '_start' => $i, 
      '_limit' => 0,
      '_skip_total_rows' => true,
    ), 
    array(
      'async' => true,
    )
  );
}

// Demonstrate the use of async response handling to receive things in any order
$i = 0;
$received = 0;
while ($received < count($fetch_requests)) {
  echo "Waiting for all responses received=$received/",count($fetch_requests)," (looped " . ++$i . ") times.\n";
  foreach ($fetch_requests as &$fetch_request) {
    if ($fetch_request == NULL) { continue; } // Ignore already-completed requests

    // Passing 'async' set to true to asyncResponse() causes it to return NULL if a response has not yet been received.
    // Do NOT call assertAsyncResponse() because it will treat NULL as an error. Instead call assertResponse() later.
    $fetch_response = $workbooks->asyncResponse($fetch_request, array('async' => true));
    if ($fetch_response) {
      $workbooks->assertResponse($fetch_response); // Check for errors
      $workbooks->log('Fetch Response received ' . ++$received . ' responses', array('request' => $fetch_request, 'response' => $fetch_response));
      $fetch_request = NULL;
    }
  }
  usleep(50000); // 50 msec.  Do something useful while we wait?
}

testExit($workbooks);

?>

