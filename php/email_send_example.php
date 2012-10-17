<?php
  
/**
 *   A demonstration of using the Workbooks API to send emails via a thin PHP wrapper.
 *   Emails can be based on templates (with substitution of values from records) or
 *   created in a raw form ("rfc822").
 *
 *   Last commit $Id$
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
 * Choose a template and a Case then use it to Send an email about the Case.
 */
$send_templated_email = array(
  'render_with_template_name' => 'Autotest Template',
  'render_with_resource_type' => 'Private::Crm::Case',
  'render_with_resource_id' => 2,
  'from_address' => 'from_address@workbooks.com',
  'to_addresses' => 'to.address1@workbooks.com, to.address2@workbooks.com',
  'cc_addresses' => 'cc.address1@workbooks.com, cc.address2@workbooks.com',
  'bcc_addresses' => 'bcc.address@workbooks.com',
  'status' => 'SEND',
);
$workbooks->assertCreate('email/emails', $send_templated_email);

/*
 * Alternatively, choose a template and a Case then use it to create a Draft email about the Case.
 */
$create_templated_email = array(
  'render_with_template_name' => 'Autotest Template',
  'render_with_resource_type' => 'Private::Crm::Case',
  'render_with_resource_id' => 2,
  'from_address' => 'from_address@workbooks.com',
  'to_addresses' => 'to.address1@workbooks.com, to.address2@workbooks.com',
  'cc_addresses' => 'cc.address1@workbooks.com, cc.address2@workbooks.com',
  'bcc_addresses' => 'bcc.address@workbooks.com',
  'status' => 'DRAFT',
);
$create_email_response = $workbooks->assertCreate('email/emails', $create_templated_email);
$email_id_lock_versions = $workbooks->idVersions($create_email_response);

/*
 * Now change the status to send it
 */
$update_email = array (
    'id' => $email_id_lock_versions[0]['id'],
    'lock_version' => $email_id_lock_versions[0]['lock_version'],
    'status' => 'SEND',
);

$response = $workbooks->assertUpdate('email/emails', $update_email);
$workbooks->log('Updated email status', $response);

/*
 * Create an email without asking for anything clever. Just supply some text. It will be sent using the
 * email configuration of the user account that we are logged in with.
 */
$rfc822 = <<<EOD
From:    "Fred" <fred@workbooks.com>
To:      "George" <george@elsewhere.com>
Subject: A simple message, in HTML with a plain text version
MIME-Version: 1.0
Content-Type: multipart/mixed; boundary=frontier

This is a message with multiple parts in MIME format.
--frontier
Content-Type: text/plain

This is the body of the message.
--frontier
Content-Type: text/html

<html>
  <body>
    <p>
      This is the body of <em>the message</em>.
    </p>
  </body>
</html>
--frontier--

EOD;

$create_rfc822_email = array (
  'rfc822' => $rfc822,
  'status' => 'SEND',
);

$response = $workbooks->assertCreate('email/emails', $create_rfc822_email);
testExit($workbooks);

?>

