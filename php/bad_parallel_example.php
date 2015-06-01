<?php
  
/**
 *   Check that failing requests are handled as expected.
 *
 *   Last commit $Id$
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

require 'client_lib/php/workbooks_api.php';

/* If not already authenticated or running under the Workbooks Process Engine create a session */
require 'client_lib/php/test_login_helper.php';

// Attempt to call an endpoint which does not exist.
$request_404 = $workbooks->get('cases', 
  array(
  ), array(
    'async' => true,
  )
);

// Create a record without specifying mandatory fields.
$create_no_name = array(
);
$create_zombie_request = $workbooks->create('crm/people', $create_no_name, array(),
  array(
    'async' => true,
  )
);


testExit($workbooks);

?>

