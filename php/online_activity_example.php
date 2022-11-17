<?php
  
/**
 *   A demonstration of using the Workbooks API via a thin PHP wrapper to create an online activity
 *
 *   Last commit $Id: online_activity_example.php 51151 2021-05-12 10:34:40Z kswift $
 *
 *       The MIT License
 *
 *       Copyright (c) 2008-2018, Workbooks Online Limited.
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
 * We now have a valid logged-in session. This script creates a number of online activity records.
 */

/*
 * Some online activity examples.
 */
$online_activity = array(
  0 => array(
    'online_activity_type'   => 'Download',
    'action_name'            => 'php example 0',
    'source'                 => 'Google',
    'medium'                 => 'Organic'
  ),
  1 => array(
    'online_activity_type'   => 'Click Through',
    'action_name'            => 'php example 1',
    'source'                 => 'Bing',
    'medium'                 => 'Organic'
  ),
  2 => array(
    'online_activity_type'   => 'Download',
    'action_name'            => 'php example 2',
    'source'                 => 'Fred',
    'medium'                 => 'Organic'
  ),
  3 => array(
    'online_activity_type'   => 'Download',
    'action_name'            => 'php example 3',
    'source'                 => 'DuckDuckGo',
    'medium'                 => 'Organic'
  ),
  4 => array(
    'online_activity_type'   => 'Download',
    'action_name'            => 'php example 4',
    'source'                 => 'Google',
  ),
);

$response = $workbooks->assertCreate('crm/online_activities', $online_activity);
$workbooks->log('Created Activities', $response);

$online_1 = $response['affected_objects'][0];
$online_2 = $response['affected_objects'][1];
$online_3 = $response['affected_objects'][2];
$online_4 = $response['affected_objects'][3];
$online_5 = $response['affected_objects'][4];


/*
* Update the activity with a missing medium
*/
$update_one_online_activity = [
  'id'           => (string)$response['affected_objects'][4]['id'],
  'lock_version' => (string)$response['affected_objects'][4]['lock_version'],
  'medium'       => 'Organic'
];
  
$update_one_online_activity_response = $workbooks->assertUpdate('crm/online_activities', $update_one_online_activity);
$workbooks->log('Updated online activity', $update_one_online_activity_response);

testExit($workbooks);
?>
