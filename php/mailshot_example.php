<?php
/**
 *   A demonstration of using the Workbooks API to operate on GatorMail mail shots via a thin PHP wrapper.
 *
 *       Last commit $Id: mailshot_example.php 35081 2017-06-05 09:47:23Z rharrison $
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

/* If not running under the Workbooks Process Engine create a session */
if (!isset($workbooks)) {
  require_once 'workbooks_api.php';
  require 'test_login_helper.php';
}


function add_people_to_mailing_list($mail_list_id, $number_of_people)
{
  global $workbooks;

  /*
   * Get ids from workbooks.
   */
  $people_select = array(
    '_start'            => 0,
    '_limit'            => $number_of_people,
    '_select_columns[]' => array(
      'id',
    ),
  );
  $response = $workbooks->assertGet('crm/people', $people_select);
  $workbooks->log('IDs retrieved', $response);


  /*
   * Check we retrieved the correct number of people
   */
  if (count($response['data']) != $number_of_people) {
    $workbooks->log('ERROR: Wrong amount of people retrieved', $response, 'Error');
    exit(2);
  }


  /*
   * Create an array of fields for each of the members retrieved
   */
  $array_of_people = array();
  foreach ($response['data'] as $value) {
    $array_of_people[] = ['mailing_list_id' => $mail_list_id, 'party_or_lead_id' => $value['id'], 'party_or_lead_type' => 'Private::Crm::Person', 'subscribed' => 'false'];
  }
  $response = $workbooks->assertCreate('email/mailing_list_entries.api', $array_of_people);
  $workbooks->log('Created members in mailing list', $response);

  return $response;
}


/*
 * Create a new mailshot
 */
$create_mailshot = array(
  'from_address'  => 'example@email.com',
  'subject'       => 'Example subject',
  'mailshot_type' => 'Refresh Non-Recurring',
  'status'        => 'Draft',
  'sync_with'     => 'GatorMail',                  // This is needed, or the mailshot will be created but not usable.
  'name'          => 'Example api mailshot',
);


$response = $workbooks->assertCreate('email/integrated_mailshots.api', $create_mailshot);
$workbooks->log('Created a new mailshot', $response);

$mailshot_mailing_list_id = $response['affected_objects'][0]['mailing_list_id'];
$workbooks->log('Mailing list id', $mailshot_mailing_list_id);

$id_versions = $workbooks->idVersions($response);
$workbooks->log('id versions array.', $id_versions);


/*
 * Add 10 people to the mailshot's mailing list
 */
add_people_to_mailing_list($mailshot_mailing_list_id, 10);


/*
 * Change the refresh period and description.
 */
$update_mailshot = [
  'id'             => $id_versions[0]['id'],
  'lock_version'   => $id_versions[0]['lock_version'],
  'description'    => 'Description for the example mailshot.',
  'refresh_period' => 60,
];
$response = $workbooks->assertUpdate('email/integrated_mailshots.api', $update_mailshot);
$workbooks->log('Mailshot updated', $response);


/*
 * Get the mailshot we created.
 */
$mailshot_by_id = array(
  '_filters[]' => ['id', 'eq', $id_versions[0]['id']],
);
$response = $workbooks->assertGet('email/integrated_mailshots.api', $mailshot_by_id);
$workbooks->log('Example mailshot returned', $response);


/*
 * Check that refresh_period updated correctly.
 */
if ($response['data'][0]['refresh_period'] != 60) {
  $workbooks->log('ERROR: refresh_period not updated', $response['data'][0]['refresh_period']);
  exit(2);
}


/*
 * Delete the mailshot we created.
 */
$delete_mailshot = array(
  'id'           => $id_versions[0]['id'],
  'lock_version' => $id_versions[0]['lock_version'] + 1,
);
$response = $workbooks->assertDelete('email/integrated_mailshots.api', $delete_mailshot);
$workbooks->log('Mailshot deleted', $response);


testExit($workbooks);