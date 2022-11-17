<?php
  
/**
 *   A demonstration of using the Workbooks API via a thin PHP wrapper to manipulate Sales Leads
 *
 *   Last commit $Id: saleslead_example.php 39761 2018-05-14 13:14:20Z jkay $
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
 * We now have a valid logged-in session. This script does a series of 'CRUD' (Create, Read, Update, Delete) operations.
 */

/*
 * Create a Lead
 */
$create_one_sales_lead = [
  'org_lead_party[name]'                     => 'Smiley People',
  'org_lead_party[main_location[telephone]]' => '0123456789',
  'org_lead_party[main_location[email]]'     => 'george@smileypeople.com',
  'org_lead_party[main_location[country]]'   => 'United Kingdom',
  'person_lead_party[name]'                  => 'George Smiley',
  'person_lead_party[biography]'             => 'Tinker, tailor, soldier and spy',
  'person_lead_party[skype_name]'            => 'georgersmiley',
  'person_lead_party[twitter_url]'           => 'https://twitter.com/Workbooks',
  'org_lead_party[logo_url]'                 => 'https://www.workbooks.com/themes/workbooks/images/content/workbooks-logo.svg'
];

$create_one_sales_lead_response = $workbooks->assertCreate('crm/sales_leads', $create_one_sales_lead);
$workbooks->log('Created sales lead', $create_one_sales_lead_response);

/*
* Update the Lead
*/
$update_one_sales_lead = [
  'id' => (string)$create_one_sales_lead_response['affected_objects'][0]['id'],
  'lock_version' => (string)$create_one_sales_lead_response['affected_objects'][0]['lock_version'],
  'person_lead_party[twitter_url]' => 'https://twitter.com/georgetinkersmiley',
  'person_lead_party[biography]' => 'Formerly a tinker, tailor, soldier and spy',
];
  
$update_one_sales_lead_response = $workbooks->assertUpdate('crm/sales_leads', $update_one_sales_lead);
$workbooks->log('Updated sales lead', $update_one_sales_lead_response);
$sales_lead_object_id_lock_versions = $workbooks->idVersions($update_one_sales_lead_response);

/*
* Delete the Lead
*/
$delete_one_sales_lead = [
  'id' => (string)$update_one_sales_lead_response['affected_objects'][0]['id'],
  'lock_version' => (string)$update_one_sales_lead_response['affected_objects'][0]['lock_version']
];
  
$delete_one_sales_lead_response = $workbooks->assertDelete('crm/sales_leads', $delete_one_sales_lead);
$workbooks->log('Deleted sales lead', $delete_one_sales_lead_response);
  
testExit($workbooks);

?>
