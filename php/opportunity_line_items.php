<?php

/**
 *   A demonstration of using the Workbooks API to operate on Opportunities with line items
 *
 *   Last commit $Id: opportunity_line_items.php 18524 2013-03-06 11:15:59Z gphillips $
 *
 *       The MIT License
 *
 *       Copyright (c) 2008-2024, Workbooks Online Limited.
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

/**
 * Create the opportunity
 */
$create_opportunity = array(
   array(
      'description'                          => 'Example Opportunity',
      'created_through_reference'            => '12345',
      'document_date'                        => '01 Jan 2024',
      'probability'                          => 0
   )
);

$response = $workbooks->assertCreate('crm/opportunities.api', $create_opportunity);
$workbooks->log("Created Opportunity", $response);
$opportunity_id_lock = $workbooks->idVersions($response)[0];

/**
 * Create some line items
 */
$line_items_to_create = array(
  0 => array(
    'document_currency_unit_price_value'  => '100 GBP 0',
    'unit_quantity'                       => 1,
    'description'                         => 'Example Line Item',
    'document_currency'                   => 'GBP',
    'home_currency'                       => 'GBP',
    'document_header_id'                  => $opportunity_id_lock['id']
  )
);

$created_line_item_response = $workbooks->assertCreate('crm/opportunity_line_items.api', $line_items_to_create);
$workbooks->log('Line Item Response', $created_line_item_response);

/**
 * See the value and weighted values from the line item.
 */
$opportunity_filter = array(
   '_start'               => '0',                                     // Starting from the 'zeroth' record
   '_limit'               => '1',                                     // Fetch one record
   '_sort'                => 'id',                                    // Sort by 'id'
   '_dir'                 => 'DESC',                                  // In descending order
   '_ff[]'                => 'id',
   '_ft[]'                => 'eq',
   '_fc[]'                => $opportunity_id_lock['id'],
   '_select_columns[]'    => array(                                   // An array, of columns to select
     'id',
     'lock_version',
     'probability',
     'document_currency_net_value',
     'home_currency_net_value',
     'document_currency_weighted_value',
     'home_currency_weighted_value'
   )
);

$response = $workbooks->assertGet('crm/opportunities.api', $opportunity_filter);
$opportunity_data = $response["data"][0];
$workbooks->log("Opportunity", $opportunity_data);

/**
 * Update the opportunity
 */
$update_opportunity = array(
   array (
      'id'                => $opportunity_data['id'],
      'lock_version'      => $opportunity_data['lock_version'],
      'probability'       => 100,
      'comment'           => 'Updating an opportunity...',
   ),
);

$response = $workbooks->assertUpdate('crm/opportunities.api', $update_opportunity);
$workbooks->log("Updated Opportunity - Affected Objects", $response["affected_objects"]);
$opportunity_id_lock = $workbooks->idVersions($response)[0];

/**
 * Fetch the weighted values, net values and probability for the opportunity.
 */
$opportunity_filter = array(
   '_start'               => '0',                                     // Starting from the 'zeroth' record
   '_limit'               => '1',                                     // Fetch one record
   '_sort'                => 'id',                                    // Sort by 'id'
   '_dir'                 => 'DESC',                                  // In descending order
   '_ff[]'                => 'id',
   '_ft[]'                => 'eq',
   '_fc[]'                => $opportunity_id_lock['id'],
   '_select_columns[]'    => array(                                   // An array, of columns to select
     'id',
     'lock_version',
     'probability',
     'document_currency_net_value',
     'home_currency_net_value',
     'document_currency_weighted_value',
     'home_currency_weighted_value'
   )
);

$response = $workbooks->assertGet('crm/opportunities.api', $opportunity_filter);
$opportunity_data = $response["data"][0];
$workbooks->log("Opportunity", $opportunity_data);

/*
 * Delete the Opportunity
 */
$opportunity_to_delete = array(
   array(
     'id'           => $opportunity_data['id'],
     'lock_version' => $opportunity_data['lock_version'],
   ),
);

$response = $workbooks->assertDelete('crm/opportunities.api', $opportunity_to_delete);

testExit($workbooks);

?>
