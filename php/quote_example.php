<?php
/**
 *   A demonstration of using the Workbooks API to ... via a thin PHP wrapper
 *
 *   Last commit $Id: quote_example.php 31149 2016-07-15 08:22:45Z swhitehouse $
 *
 *       The MIT License
 *
 *       Copyright (c) 2008-2013, Workbooks Online Limited.
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

if (!isset($workbooks)){
  
  require_once 'workbooks_api.php';
  
  require 'test_login_helper.php';

}

/*
 * We now have a valid logged-in session. This script does a series of 'CRUD' (Create, Read, Update, Delete) operations.
 */


/**
 * The structure of this script can be used for any Workbooks documents, including customer orders, supplier orders, invoices, opportunities etc
 */


/**
 * Get the ID of the first person record in the database
 */

$filter_limit_select = array(
  '_start' => '0', // Starting from the 'zeroth' record
  '_limit' => '1', // fetch a single record
  '_sort' => 'id', // Sort by 'id'
  '_dir' => 'ASC', // in ascending order
  '_select_columns[]' => array( // An array, of columns to select
    'id',
  )
);
$response = $workbooks->assertGet('crm/people.api', $filter_limit_select);

$party_id = $response['data'][0]['id'];
$today = date('Y-m-d');

/**
 * Build the create quote
 */
$create_quote = array(
  'party_id'      => $party_id, //Set the party ID (Customer)
  'description'   => 'New quotation',
  'document_date'   => $today,
  'document_currency' => 'GBP',
);

$creation_response = $workbooks->assertCreate('accounting/quotations.api', $create_quote);

/**
 * Get the quote ID from the creation response
 */
$workbooks->log('creation_response', $creation_response);
$created_quote_id = $creation_response['affected_objects'][0]['id'];
$quote_lock_version = $creation_response['affected_objects'][0]['lock_version']; //Lock version starts at 1 once it has been created

echo 'Quote created on '.$today;


/**
 * Get the ID of the first product record in the database
 */

$filter_limit_select = array(
  '_start' => '0', // Starting from the 'zeroth' record
  '_limit' => '1', // fetch a single record
  '_sort' => 'id', // Sort by 'id'
  '_dir' => 'ASC', // in ascending order
  '_select_columns[]' => array( // An array, of columns to select
    'id',
  'sales_tax_code_id',
  )
);
$response = $workbooks->assertGet('pricebook/products.api', $filter_limit_select);

$product = $response['data'][0];

/**
 * Create 3 line items on the created quote
 * - The quotation must be created first before line items can be added
 */

$quote_line_items = array(
  0 => array(
    'document_currency_unit_price_value'  => '75.00 GBP 0', //Set the price of the line item to be £75.00. Currencies must be in this format: 'AMOUNT CURRENCY_CODE 0'
    'unit_quantity'             => 1, //Set the unit quantity to 1
    'document_header_id'          => $created_quote_id,
    'description'             => 'New line item 1', //Set the description of the line item
  ),
  1 => array(
    'document_currency_unit_price_value'  => '25.00 GBP 0', //Set the price of the line item to be £25.00
    'unit_quantity'             => 1, //Set the unit quantity to 1
    'document_header_id'          => $created_quote_id,
    'description'             => 'New line item 2', //Set the description of the line item
    'product_id'              => $product['id'], //Line items on documents can refer to product records in the product book by providing their ID
    'sales_tax_code_id'           => $product['sales_tax_code_id'], //Set the sales tax code id from the product
  ), 
  2 => array(
    'document_currency_unit_price_value'  => '50.00 GBP 0', //Set the price of the line item to be £50.00
    'unit_quantity'             => 1, //Set the unit quantity to 1
    'document_header_id'          => $created_quote_id,
    'description'             => 'New line item 3', //Set the description of the line item
  ),
);

$created_line_items_response = $workbooks->assertCreate('accounting/quotation_line_items.api', $quote_line_items);
$line_item_2 = $created_line_items_response['affected_objects'][1];
$line_item_3 = $created_line_items_response['affected_objects'][2];

/**
 * Update second line item
 */

$update_line_item = array(
  'id'        => $line_item_2['id'],
  'lock_version'    => $line_item_2['lock_version'],
  'unit_quantity'   => 3, //Change the unit quantity to 3
);

$workbooks->assertUpdate('accounting/quotation_line_items.api', $update_line_item);

/**
 * Delete third line item
 */

$delete_line_item = array(
  'id'        => $line_item_3['id'],
  'lock_version'    => $line_item_3['lock_version'],
);

$workbooks->assertDelete('accounting/quotation_line_items.api', $delete_line_item);

/**
 * Retrieve remaining line items and log them
 */

$filter_limit_select = array(
  '_start' => '0', 
  '_limit' => '100', 
  '_sort' => 'id', 
  '_dir' => 'ASC', 
  '_ff[]' => 'document_header_id', 
  '_ft[]' => 'eq', 
  '_fc[]' => $created_quote_id, // ID of the created organisation
  '_select_columns[]' => array( // An array, of columns to select
    'id',
    'lock_version',
  'unit_quantity',
    'document_currency_unit_price_value',
  )
);

$response = $workbooks->assertGet('accounting/quotation_line_items.api', $filter_limit_select);

$remaining_line_items = $response['data'];

$workbooks->log('Line items', $remaining_line_items);

/**
 * Delete the quotation
 * This will also delete any associated line items
 * 
 * Get the queue again, in case its lock version has changed 
 */

$filter_quote = array(
  '_start' => '0', 
  '_limit' => '1',
  '_ff[]' => 'id', 
  '_ft[]' => 'eq', 
  '_fc[]' => $created_quote_id, // ID of the created quote
  '_select_columns[]' => array( // An array, of columns to select
    'id',
    'lock_version'
  )
);

$response = $workbooks->assertGet('accounting/quotations.api', $filter_quote);
$quote_lock_version = $response['data'][0]['lock_version'];
$workbooks->log('Quotation (fetched again to get lock_version)', $response);
    
$delete_quote = array(
  'id'            => $created_quote_id,
  'lock_version'  => $quote_lock_version,
);

$workbooks->assertDelete('accounting/quotations.api', $delete_quote);

if(function_exists('testExit')) {
  testExit($workbooks);
}

else {
  exit (0);
}

?>