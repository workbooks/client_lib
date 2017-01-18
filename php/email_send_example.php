<?php
  
/**
 *   A demonstration of using the Workbooks API to send emails via a thin PHP wrapper.
 *   Emails can be based on templates (with substitution of values from records) or
 *   created in a raw form ("rfc822").
 *
 *   Last commit $Id: email_send_example.php 32815 2016-12-13 12:36:50Z swhitehouse $
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

/* If not running under the Workbooks Process Engine create a session */
if (!isset($workbooks)){
  require_once 'workbooks_api.php';
  require 'test_login_helper.php';
}

date_default_timezone_set('UTC');


function confirm_email_sent( $email_id, $testrfc822_content ) {

  global $workbooks;

  /* How long do we wait for an email to send, before giving up */
  $email_send_time_limit = 180;

  $secs_waited = 0;
  do {
   sleep(2);
   $secs_waited += 2;
   $email_filter_limit_select = array(
     '_ff[]'                => array('id'),
     '_ft[]'                => array('eq'),             
     '_fc[]'                => array($email_id), 
     '_select_columns[]'    => array('status', 'rfc822')
   );
   $sent_email = $workbooks->assertGet('email/emails', $email_filter_limit_select);
   $workbooks->log('Retrieved email status', $sent_email['data'][0]['status']);
  } while ($sent_email['data'][0]['status'] != 'SENT' && $secs_waited < $email_send_time_limit );

  if ( $secs_waited >= $email_send_time_limit ) {
    $workbooks->log('ERROR: Email Not Sent within expected time limit:', $email_send_time_limit);
    exit(1);
  } else {
    $workbooks->log('Retrieved sent email', $sent_email);
    if ( $sent_email['data'][0]['rfc822'] == NULL ) {
      $workbooks->log('ERROR: Missing rfc822 blob' );
      exit(1);
    }
    
    if ( $testrfc822_content == 1 ) {  
      preg_match_all('/^--mimepart(.*?\n){5}/m', $sent_email['data'][0]['rfc822'], $parts);
      $workbooks->log('Email parts', $parts[0]);
      $workbooks->log('Email parts count', count( $parts ));
      if ( count( $parts ) <> 2 ) {
         $workbooks->log('ERROR: Did not see two parts from rfc822 blob: got', count( $parts ));
         exit(1);
       }
     }
  }
}

/*
 * Create a note associated with a Case, to which we will attach files.
 */
$create_note = array(
  'resource_id'   => 2,
  'resource_type' => 'Private::Crm::Case',
  'subject'   => 'Test Note',
  'text'      => 'This is the body of the test note. It is <i>HTML</i>.'
);
$note_id_lock_versions = $workbooks->idVersions($workbooks->assertCreate('notes', $create_note));
$note_id = $note_id_lock_versions[0]['id'];

/*
 * Prepare a file to be uploaded and attached to the Note.
 */
$file = array(
  'name' => 'workbooks_logo.png',
  'type' => 'image/png', 
  'data' => base64_decode('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'),
);

$file['tmp_name'] = tempnam('', 'upload-');
$fp = fopen($file['tmp_name'], 'w');
$res = fwrite($fp, $file['data']);
fclose($fp);

$create_upload = array(
  'resource_id'   => $note_id,
  'resource_type' => 'Private::Note',
  'resource_attribute' => 'upload_files',
  'upload_file[data]' => array(
    'tmp_name' => '@' . $file['tmp_name'], // Mirror curl and '$_FILES[]' interfaces
    'file_name' => $file['name'],
    'file_content_type' => $file['type'],
  ),
);

// Always use the ('content_type' => multipart/form-data) option for uploading files: it is efficient.
$response = $workbooks->assertCreate('resource_upload_files', $create_upload, array(), array('content_type' => 'multipart/form-data'));

$workbooks->log('Uploaded file: ',$response, 'debug');
$uploaded_file_id = $response['affected_objects'][0]['upload_file_id'];

unlink($file['tmp_name']);

/*
 * Choose a template and a Case then use it to Send an email about the Case.
 * You can pass additional placeholders as JSON; these are merged into the template.
 */
$send_templated_email = array(
  'render_with_template_name' => 'Autotest Template',
  'render_with_resource_type' => 'Private::Crm::Case',
  'render_with_resource_id' => 2,
  'from_address' => 'from_address@workbooks.com',
  'to_addresses' => 'to.address1@workbooks.com, to.address2@workbooks.com',
  'cc_addresses' => 'cc.address1@workbooks.com, cc.address2@workbooks.com',
  'bcc_addresses' => 'bcc.address@workbooks.com',
  'render_with_placeholders' => json_encode(
    array(
      'today_date' => date('d M Y'),
      'contract_value' => '&pound;420.42',
      'credits_used' => 42,
    )
  ),
  'attach_uploaded_files' => array(
    $uploaded_file_id,
  ),
  'status' => 'SEND',
);
$create_response = $workbooks->assertCreate('email/emails', $send_templated_email);
$workbooks->log('Sent email', $create_response);

/*
 * Check that the email has been sent, then log the generated RFC822 data,
 * which should contain all of the relevant parts.
 */
$email_id_lock_versions = $workbooks->idVersions($create_response);
$email_id = $email_id_lock_versions[0]['id'];
confirm_email_sent( $email_id, 1  );

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
$workbooks->log('Created draft email', $create_email_response);

/*
 * Now change the status to send it
 */
$update_email = array (
    'id' => $email_id_lock_versions[0]['id'],
    'lock_version' => $email_id_lock_versions[0]['lock_version'],
    'status' => 'SEND',
);

$response = $workbooks->assertUpdate('email/emails', $update_email);
$workbooks->log('Updated (sent) email status', $response);

/*
 * Check that the email has been sent, then log the generated RFC822 data,
 * which should contain all of the relevant parts.
 */
$email_id_lock_versions = $workbooks->idVersions($response);
$email_id = $email_id_lock_versions[0]['id'];
confirm_email_sent( $email_id, 1 );



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
$email_id_lock_versions = $workbooks->idVersions($response);
$email_id = $email_id_lock_versions[0]['id'];
confirm_email_sent( $email_id, 0 );

testExit($workbooks);

?>

