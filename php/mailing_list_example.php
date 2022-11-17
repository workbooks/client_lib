<?php
/**
 *   A demonstration of using the Workbooks API to operate on mailing lists via a thin PHP wrapper.
 *
 *       Last commit $Id: mailing_list_example.php 35081 2017-06-05 09:47:23Z rharrison $
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


/*
 * Create a mailing list.
 */
$create_mailing_list = array(
  'name'        => 'example_mailing_list',
  'description' => 'This is a mailing list creating using the API.',
);
$response = $workbooks->assertCreate('email/mailing_lists.api', $create_mailing_list);
$workbooks->log('Mailing list created.', $response);


/*
 * Get the ID of the mailing list we just created.
 */
$id_versions = $workbooks->idVersions($response);
$workbooks->log('id versions array.', $id_versions);
$mailing_list_id = $id_versions[0]['id'];


/*
 * Get 5 ids from workbooks that we can add to the mailing list.
 */
$people_id_select = array(
  '_start'            => 0,
  '_limit'            => 5,
  '_select_columns[]' => array(
    'id',
  ),
);
$response = $workbooks->assertGet('crm/people', $people_id_select);
$workbooks->log('IDs gotten.', $response);


/*
 * Ensure that we retrieved 5 people.
 */
if (count($response['data']) != 5) {
  $workbooks->log('ERROR: Couldn\'t retrieve 5 people form the api', $response, 'ERROR');
  exit(2);
}


/*
 * Create 5 unsubscribed members.
 */
$create_new_mailing_list_members = array(
  ['mailing_list_id' => $mailing_list_id, 'party_or_lead_id' => $response['data'][0]['id'], 'party_or_lead_type' => 'Private::Crm::Person', 'subscribed' => 'false'],
  ['mailing_list_id' => $mailing_list_id, 'party_or_lead_id' => $response['data'][1]['id'], 'party_or_lead_type' => 'Private::Crm::Person', 'subscribed' => 'false'],
  ['mailing_list_id' => $mailing_list_id, 'party_or_lead_id' => $response['data'][2]['id'], 'party_or_lead_type' => 'Private::Crm::Person', 'subscribed' => 'false'],
  ['mailing_list_id' => $mailing_list_id, 'party_or_lead_id' => $response['data'][3]['id'], 'party_or_lead_type' => 'Private::Crm::Person', 'subscribed' => 'false'],
  ['mailing_list_id' => $mailing_list_id, 'party_or_lead_id' => $response['data'][4]['id'], 'party_or_lead_type' => 'Private::Crm::Person', 'subscribed' => 'false'],
);
$response = $workbooks->assertCreate('email/mailing_list_entries.api', $create_new_mailing_list_members);
$workbooks->log('Mailing list entries created.', $response);
$mailing_list_members = $response['affected_objects'];


/*
 * Change subscription status of a member.
 */
$update_mailing_list_member = [
  'id'           => $mailing_list_members[0]['id'],
  'lock_version' => $mailing_list_members[0]['lock_version'],
  'subscribed'   => 'true',
];
$response = $workbooks->assertUpdate('email/mailing_list_entries.api', $update_mailing_list_member);
$workbooks->log('Subscription status changed to subscribed.', $response);


/*
 * Delete a member from the mailing list.
 */
$delete_mailing_list_member = [
  'mailing_list_id' => $mailing_list_id,
  'id'              => $mailing_list_members[1]['id'],
  'lock_version'    => $mailing_list_members[1]['lock_version'],
];
$response = $workbooks->assertDelete('email/mailing_list_entries.api', $delete_mailing_list_member);
$workbooks->log('Member deleted.', $response);


/*
 * Get all the members from the mailing list.
 */
$mailing_list_members_select = [
  '_filters[]'        => ['mailing_list_id', 'eq', $mailing_list_id],
  '_select_columns[]' => ['id', 'member_name', 'lock_version', 'mailing_list_id', 'subscribed'],
];
$response = $workbooks->assertGet('email/mailing_list_entries.api', $mailing_list_members_select);
$workbooks->log('All mailing list entries returned', $response);


/*
 * Check if there are 4 total members.
 */
if ($response['total'] != 4) {
  $workbooks->log('ERROR: Total number of people on mailing list not 4', $response, 'Error');
  exit(2);
}


/*
 * Get all members that are subscribed in this mailing list.
 */
$subscribed_mailing_list_members_select = [
  '_filters[]'        => [
    ['mailing_list_id', 'eq', $mailing_list_id],
    ['subscribed', 'eq', 'true'],
  ],
  '_select_columns[]' => ['id', 'member_name', 'lock_version', 'mailing_list_id', 'subscribed'],
];
$response = $workbooks->assertGet('email/mailing_list_entries.api', $subscribed_mailing_list_members_select);
$workbooks->log('Mailing list entries returned for subscription filter', $response);


/*
 * Check there is one member subscribed.
 */
if ($response['total'] != 1) {
  $workbooks->log('ERROR: Total number of people subscribed on this mailing list not 1', $response, 'ERROR');
  exit(2);
}


/*
 * Delete the mailing list.
 */
$delete_mailing_list = [
  'id'           => $id_versions[0]['id'],
  'lock_version' => $id_versions[0]["lock_version"],
];
$response = $workbooks->assertDelete('email/mailing_lists.api', $delete_mailing_list);
$workbooks->log("Mailing list deleted", $response);


testExit($workbooks);