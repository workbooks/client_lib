<?php
  
/**
 *   A demonstration of using the Workbooks API to operate on Meetings via a thin PHP wrapper.
 *
 *   Last commit $Id: meetings_example.php 63936 2024-09-03 21:33:12Z jmonahan $
 *
 *       The MIT License
 *
 *       Copyright (c) 2008-2023, Workbooks Online Limited.
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
 * Let's say that today is Monday morning (26 Jun 2023) and we are planning on having a meeting with a colleague on
 * Wednesday (28 Jun 2023) at 10:30 AM.
 * 
 * We can create the Meeting 'Discussion with colleague' that starts on 28/06/2023 at 10:30 AM and finishes one hour
 * later at 11:30 AM.
 * 
 * With the keys 'start' and 'finish', we must include a date and we can optionally include a time in the 24 hour format
 * if we have one. Alternatively, we could have used the keys 'start_datetime' or 'finish_datetime' instead as they only
 * accept values in a datetime format. Similarly, we can use the keys 'start_date' and 'finish_date' if we don't have a
 * time to specify. This pattern extends to the keys:
 *   - 'due',
 *   - 'started', and
 *   - 'completed'.
 * 
 * There are a couple of things to note in the logged response:
 *   - The attributes 'start' and 'finish' are displayed in the format '2023-06-28T09:30:00.000Z,1,Europe/London'. If
 *     they did not specify a time, they would be displayed slightly differently - as shown in the next example.
 *   - There are other attributes that have also been set; 'scheduled_start', 'scheduled_finish', and their '*_date' and
 *     '*_datetime' counterparts. The 'scheduled_start*' attributes should be the same as the 'start*' attributes and
 *     the 'scheduled_finish*' attributes should be the same as the 'finish*' attributes. If we had not set the 'start'
 *     and 'finish' attributes, the 'scheduled_start*' and 'scheduled_finish*' attributes would match the 'due*'
 *     attributes.
 *   - The response should also document the 'duration' of the Meeting; the amount of time in seconds that the Meeting
 *     is planned to take.
 *   - The API handles all dates and times in UTC, but a user will see the times in their local timezone.
 */
$create_meeting = [
  'name' => 'Discussion with colleague',
  'start' => '2023-06-28 10:30',
  'finish' => '2023-06-28 11:30'
];

$create_meeting_response = $workbooks->assertCreate('activity/meetings', $create_meeting);
$meeting_object_id_lock_versions = $workbooks->idVersions($create_meeting_response);
$workbooks->log('Created the Meeting', $create_meeting_response);

/*
 * It is now Tuesday afternoon. Our colleague informs us that they will not be able to attend the meeting and would like
 * to reschedule. They propose to spend all of Thursday for the discussion as the issue at hand is a lot more complex
 * than it was made out to be. This works for us so we accept.
 * 
 * We can update the Meeting 'Discussion with colleague' so that it starts and finishes on 29/06/2023.
 * 
 * As this Meeting is an all-day event, we must specify this flag. Additionally, as we have no longer specified a time
 * for 'start' and 'finish', the logged response will display them in the format '2023-06-29,0,Europe/London'.
 */
$update_meeting = [
  'id' => $meeting_object_id_lock_versions[0]['id'],
  'lock_version' => $meeting_object_id_lock_versions[0]['lock_version'],
  'start' => '2023-06-29',
  'finish' => '2023-06-29',
  'all_day' => true
];

$update_meeting_response = $workbooks->assertUpdate('activity/meetings', $update_meeting);
$meeting_object_id_lock_versions = $workbooks->idVersions($update_meeting_response);
$workbooks->log('Updated the Meeting', $update_meeting_response);

/*
 * List up to the first 100 Meetings that match our 'created_through' value. Select a few columns for retrieval.
 */
$filter_limit_select = array(
  '_start'               => '0',                                     // Starting from the 'zeroth' record
  '_limit'               => '100',                                   //   fetch up to 100 records
  '_sort'                => 'id',                                    // Sort by 'id'
  '_dir'                 => 'ASC',                                   //   in ascending order
  '_ff[]'                => 'created_through',                       // Filter by this column
  '_ft[]'                => 'eq',                                    //   equals
  '_fc[]'                => 'php_test_client',                       //   this script
  '_select_columns[]'    => array(                                   // An array, of columns to select
    'id',
    'lock_version',
    'name',
    'start',
    'finish',
    'due',
    'duration',
    'scheduled_start',
    'scheduled_finish'
  )
);

$get_meetings_response = $workbooks->assertGet('activity/meetings', $filter_limit_select);
$meetings_object_id_lock_versions = $workbooks->idVersions($get_meetings_response);
$workbooks->log('Fetched Meetings', $get_meetings_response['data']);

/*
 * Delete the Meeting.
 */
$delete_meeting = [
  'id' => $meeting_object_id_lock_versions[0]['id'],
  'lock_version' => $meeting_object_id_lock_versions[0]['lock_version']
];

$delete_meeting_response = $workbooks->assertDelete('activity/meetings', $delete_meeting);
$workbooks->log('Deleted the Meeting', $delete_meeting_response);

testExit($workbooks);

?>
