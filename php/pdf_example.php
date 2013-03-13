<?php
  
/**
 *   A demonstration of using the Workbooks API to fetch a PDF document via a thin PHP wrapper.
 *
 *   Last commit $Id: pdf_example.php 18524 2013-03-06 11:15:59Z jkay $
 *
 *       The MIT License
 *
 *       Copyright (c) 2008-2012, Workbooks Online Limited.
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
 * In order to generate a PDF you need to know the ID of the transaction document (the 
 * order, quotation, credit note etc) and the ID of a PDF template. You can find these out
 * for a particular PDF by using the Workbooks Desktop, generating a PDF and examining the
 * URL used to generate the PDF. You will see something like
 *
 *    https://secure.workbooks.com/accounting/sales_orders/42.pdf?template=34
 *
 * which implies a document ID of 42 and a template ID of 34. The 'sales_orders' part 
 * indicates the type of transaction document you want to reference; see the Workbooks API 
 * Reference for a list of the available API endpoints.
 */

$pdf_template_id = 34;

/* Find the ID of the first transaction document */
$response = $workbooks->assertGet('accounting/sales_orders', array(
  '_start' => 0,
  '_limit' => 1,    
  '_sort' => 'id', 
  '_dir' => 'ASC',
  '_select_columns[]' => array('id'),
));
if (count($response['data']) <> 1) {
  $workbooks->log('Did not find any orders!', $response);
  testExit($workbooks, $exit_error);
}
$order_id = $response['data'][0]['id'];

/* Now generate the PDF */
$url = "accounting/sales_orders/{$order_id}.pdf?template={$pdf_template_id}";
$pdf = $workbooks->get($url, array(), FALSE);
// Ensure the response looks like a PDF
if (!preg_match('/^\%PDF\-1\.4\s/', $pdf)) {
  $workbooks->log('ERROR: Unexpected response: is it a PDF?', $pdf, 'error');
  testExit($workbooks, $exit_error);
}

$workbooks->log('Fetched PDF', $pdf);

testExit($workbooks);

?>

