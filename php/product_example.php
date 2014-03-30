<?php
  
/**
 *   A demonstration of using the Workbooks API to operate on product via a thin PHP wrapper.
 *   The created_through_reference and created_through attributes are used as if the caller
 *   were synchronising with an external service.
 *
 *   Created by: bviroja at: 06/02/2014, 14:00 license: www.workbooks.com/mit_license
 */

require_once 'workbooks_api.php';

/* If not running under the Workbooks Process Engine create a session */
require 'test_login_helper.php';

/*
 * We now have a valid logged-in session. This script does a series of 'CRUD' (Create, Read, Update, Delete) operations.
 */


/*
 * Create two product, tagging with their identifiers in the external system. Up to 100 can be done in one batch.
 */
$create_two_product = array(
    array (
        'description'                           => 'Consulting Day - Training',
        'unit_type'                             => 'Day',
        'product_category'                      => 'Services',
        'nominal_price'                         =>  '1050.00 GBP',
        'refcode'                               => 'TRAINING',
),
    array (
        'description'                           => 'Consulting Day - Installation',
        'unit_type'                             => 'Day',
        'product_category'                      => 'Services',
        'nominal_price'                         =>  '1000.00 GBP',
        'refcode'                               => 'INSTALL',
    ),
);

$response = $workbooks->assertCreate('pricebook/products', $create_two_product);
$object_id_lock_versions = $workbooks->idVersions($response);

/*
 * Update those two product. You must specify the 'id' and 'lock_version' of records you want to update.
 */
$update_two_product = array(
  array (
    'id'                                   => $object_id_lock_versions[0]['id'],
    'lock_version'                         => $object_id_lock_versions[0]['lock_version'],
    'sales_tax_code_id'                    => 5,                                            // 5 is the Sales Tax Code for Standard VAT Rate UK
    'is_purchased'                         =>  true,
  ),
  array (
    'id'                                   => $object_id_lock_versions[1]['id'],
    'lock_version'                         => $object_id_lock_versions[1]['lock_version'],
    'nominal_cost'                         => '800.00 GBP',
  ),
);

$response = $workbooks->assertUpdate('pricebook/products', $update_two_product);
$object_id_lock_versions = $workbooks->idVersions($response);
/*
 * List up to the first hundred product matching our 'created_through' attribute value, just selecting a few columns to retrieve
 */
$filter_limit_select = array(
  '_start'               => '0',                                     // Starting from the 'zeroth' record
  '_limit'               => '100',                                   //   fetch up to 100 records
  '_sort'                => 'id',                                    // Sort by 'id'
  '_dir'                 => 'ASC',                                   //   in ascending order
  '_ff[]'                => 'created_through',                       // Filter by this column
  '_ft[]'                => 'eq',                                    //   equals
  '_fc[]'                => 'test_client',                           //   this script
  '_select_columns[]'    => array(                                   // An array, of columns to select
    'id',
    'lock_version',
    'description',
    'product_category',
    'unit_type',
  )
);
$response = $workbooks->assertGet('pricebook/products', $filter_limit_select);
$workbooks->log('Fetched objects', $response['data']);

/*
 * Delete the product which were created in this script
 */
$response = $workbooks->assertDelete('pricebook/products', $response['data']);

/*
 * List every product in the system, in alphabetic name order, whether deleted or not, which
 * has been updated in the last twenty weeks. 
 *
 * If you are trying to have an external system replicate the contents of Workbooks, this is 
 * the technique you would use, perhaps only requesting records which were updated since the
 * time of the last fetch.
 */

date_default_timezone_set('UTC');
$twenty_weeks_ago = time() - (20 * 7 * 24 * 60 * 60);
$updated_since = date('D M d H:i:s e Y', $twenty_weeks_ago);

$all_records = array();
$fetched = -1;
$start = 0;
while ($fetched <> 0) {
  $fetch_chunk = array(
    '_start'               => $start,
    '_limit'               => 100,       // The maximum 
    
    '_sort'                => 'id',      // Sort by 'id' to ensure every record is fetched
    '_dir'                 => 'ASC',     //   in ascending order

                                         // Filter 
    '_fm'                  =>       '1          AND (2 OR           3)',
    '_ff[]'                => array('updated_at',   'is_deleted',  'is_deleted'),
    '_ft[]'                => array('ge',           'true',        'false'     ), //   'ge' = "is on or after"
    '_fc[]'                => array($updated_since, '',            ''          ),

    '_select_columns[]'    => array(     // An array, of columns to select. Fewer = faster
      'id',                              //   Unique, incrementing, key for each record
      'lock_version',                    //   Required if updating a record
      'is_deleted',                      //   True or False
      'object_ref',                      //   Also unique, more friendly key for each record

      'refcode',
      'description',
      'sales_tax_code',
      'unit_type'      ,
      'product_category',
      'nominal_price'    ,

      'created_at',
      'created_by_user[person_name]',
      'updated_at',
      'updated_by_user[person_name]',
    )
  );
  $response = $workbooks->assertGet('pricebook/products', $fetch_chunk);
  // $workbooks->log('Records from ' . $start, $response);
  foreach ($response['data'] as $record) {
    $all_records[] = $record;
  }
  $fetched = count($response['data']);
  $workbooks->log('Fetched', $fetched);
  $start += $fetched;
}
$workbooks->log('All records fetched', $all_records);
$workbooks->log('Total fetched', count($all_records));

testExit($workbooks);

?>

