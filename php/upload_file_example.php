<?php
  
/**
* A demonstration of using the Workbooks API via a thin PHP wrapper to upload and download files
*
* Last commit $Id: upload_file_example.php 18721 2013-04-19 21:41:28Z jkay $
*
* The MIT License
*
* Copyright (c) 2008-2013, Workbooks Online Limited.
*
* Permission is hereby granted, free of charge, to any person obtaining a copy
* of this software and associated documentation files (the "Software"), to deal
* in the Software without restriction, including without limitation the rights
* to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
* copies of the Software, and to permit persons to whom the Software is
* furnished to do so, subject to the following conditions:
*
* The above copyright notice and this permission notice shall be included in
* all copies or substantial portions of the Software.
*
* THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
* IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
* FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
* AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
* LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
* OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
* THE SOFTWARE.
*/

/* If not running under the Workbooks Process Engine create a session */

if (!isset($workbooks)){
  require_once 'workbooks_api.php';
  require 'test_login_helper.php';
}
/*
 * We now have a valid logged-in session. This script does a series of 'CRUD' (Create, Read, Update, Delete) operations.
 *
 * Note that creating attachments is done using the 'resource_upload_files' endpoint, so that the caller can specify what 
 * the file should be attached to; updating and deleting files is via the 'upload_files' endpoint.
 */

/*
 * Create a single organisation, to which we will attach a note.
 */
$create_one_organisation = array('name' => 'Test Organisation');
$organisation_id_lock_versions = $workbooks->idVersions($workbooks->assertCreate('crm/organisations', $create_one_organisation));

/*
 * Create a note associated with that organisation, to which we will attach files.
 */
$create_note = array(
  'resource_id'   => $organisation_id_lock_versions[0]['id'],
  'resource_type' => 'Private::Crm::Organisation',
  'subject'   => 'Test Note',
  'text'      => 'This is the body of the test note. It is <i>HTML</i>.'
);
$note_id_lock_versions = $workbooks->idVersions($workbooks->assertCreate('notes', $create_note));
$note_id = $note_id_lock_versions[0]['id'];

/*
 * A variety of simple test files which get uploaded
 */
$files = array(
  array(
    'name' => 'smallest.png',
    'type' => 'image/png', 
    'data' => base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAACklEQVR4nGMAAQAABQABDQottAAAAABJRU5ErkJggg=='),
  ),
  array(
    'name' => 'four_nulls.txt',
    'type' => 'text/plain',
    'data' => "\x00\x00\x00\x00",
  ),
  array(
    'name' => 'file.htm',
    'type' => 'text/html', 
    'data' => '<b>A small fragment of HTML</b>',
  ),
  array(
    'name' => "<OK> O'Reilly & Partners",
    'type' => 'text/plain',
    'data' => "'象形字 指事字 会意字 / 會意字  假借字 形声字 / 形聲字 xíngshēngzì. By far.",
  ),
  array(
    'name' => 'Байкал Бизнес Центр',
    'type' => 'text/plain',
    'data' => "экологически чистом районе города Солнечный. Включает  ... whatever that means.",
  ),
  array(
    'name' => '2mbytes.txt',
    'type' => 'text/plain', 
    'data' => "A large text file\n" . str_repeat ("123456789\n", 0.2*1024*1024) . "last line\n",
  ),
);

$create_uploads = array();
foreach ($files as &$file) {
  $file['tmp_name'] = tempnam('', 'upload-');
  $fp = fopen($file['tmp_name'], 'w');
  $res = fwrite($fp, $file['data']);
  fclose($fp);

  $create_uploads[] = array(
    'resource_id'   => $note_id,
    'resource_type' => 'Private::Note',
    'resource_attribute' => 'upload_files',
    'upload_file[data]' => array(
      'tmp_name' => '@' . $file['tmp_name'], // Mirror curl and '$_FILES[]' interfaces
      'file_name' => $file['name'],
      'file_content_type' => $file['type'],
    ),
  );
}

// Always use the ('content_type' => multipart/form-data) option for uploading files: it is efficient.
$response = $workbooks->assertCreate('resource_upload_files', $create_uploads, array(), array('content_type' => 'multipart/form-data'));

foreach ($files as &$file) { unlink($file['tmp_name']); }

/*
 * Now list them all via both the 'upload_files' endpoint and the 'resource_upload_files' endpoint and compare the contents of each with
 * what was uploaded.
 */
$uploaded_files = array();
$filters = array();
foreach ($response['affected_objects'] as $r) {
  $uploaded_files[] = array(
    'id' => $r['upload_file[id]'],
    'lock_version' => $r['upload_file[lock_version]'],
  );
  $filters[] = "['id', 'eq', '" . $r['upload_file[id]'] . "']";
}
$file_filter = array(
  '_sort' => 'id',
  '_dir' => 'ASC',
  '_fm' => 'OR',
  '_filter_json' => '[' . join(',', $filters) . ']',
  '_select_columns[]' => array('id', 'file_name', 'file_content_type', 'file_size'),
);
$file_response = $workbooks->get('upload_files', $file_filter);

$resource_filter = array(
  '_sort' => 'id',
  '_dir' => 'ASC',
  '_ff[]' => array('resource_id', 'resource_type', 'resource_attribute'),
  '_ft[]' => array('eq', 'eq', 'eq'),
  '_fc[]' => array($note_id, 'Private::Note', 'upload_files'),
  '_select_columns[]' => array('upload_file[id]', 'upload_file[file_name]', 'upload_file[file_content_type]', 'upload_file[file_size]', 'file'),
);
$resource_response = $workbooks->get('resource_upload_files', $resource_filter);

if (count($files) != $resource_response['total'] || count($files) != count($resource_response['data'])) {
  $workbooks->log('Get resource_upload_files: unexpected result size',array($files, $resource_response), 'error');
  testExit($workbooks, $exit_error);
}
if (count($files) != $file_response['total'] || count($files) != count($file_response['data'])) {
  $workbooks->log('Get upload_files: unexpected result size',array($files, $file_response), 'error');
  testExit($workbooks, $exit_error);
}

for ($i = 0; $i < count($files); $i++) {
  $data_len = strlen($files[$i]['data']);
  $r = $resource_response['data'][$i];
  $f = $file_response['data'][$i];
  if ($files[$i]['name'] == $r['upload_file[file_name]'] &&
      $files[$i]['name'] == $f['file_name'] &&
      $files[$i]['type'] == $r['upload_file[file_content_type]'] &&
      $files[$i]['type'] == $f['file_content_type'] &&
      $r['upload_file[id]'] == $f['id'] &&
      $data_len == $r['upload_file[file_size]'] &&
      $data_len == $f['file_size']
  ) { // Everything OK; download the data, compare with the originally-uploaded data

    $data = $workbooks->get("upload_files/{$f['id']}/download", array(), FALSE);

    if (strlen($data) != $data_len) {
      $workbooks->log('File download failed: bad data length', array(strlen($data), $data_len, $f), 'error');
      testExit($workbooks, $exit_error);
    }
    if ($data != $files[$i]['data']) {
      $workbooks->log('File comparison failed', array(strlen($data), $data_len, $f), 'error');
      testExit($workbooks, $exit_error);
    }
    $workbooks->log('Downloaded previously-uploaded file, comparisons OK', $f);
  }
  else {
    $workbooks->log('File retrieval failed: differences', array($files, $resource_response, $file_response), 'error');
    testExit($workbooks, $exit_error);
  }
}

/*
 * Delete all except the last of the files just uploaded.
 */
$first_file = array_pop($uploaded_files); // leave a file behind, for the next test
$response = $workbooks->assertDelete('upload_files', $uploaded_files);

/*
 * An update of a file.
 */
$file = array(
  'name' => 'alternate.txt',
  'type' => 'text/plain',
  'data' => 'alternate',
);

$file['tmp_name'] = tempnam('', 'upload-');
$fp = fopen($file['tmp_name'], 'w');
$res = fwrite($fp, $file['data']);
fclose($fp);

$update = array(
  array(
    'id' => $first_file['id'],
    'lock_version' => $first_file['lock_version'],
    'data' => array(
      'tmp_name' => '@' . $file['tmp_name'], // Mirror curl and '$_FILES[]' interfaces
      'file_name' => $file['name'],
      'file_content_type' => $file['type'],
    ),
  ),
);
$response = $workbooks->assertUpdate('upload_files', $update);
unlink($file['tmp_name']);

/*
 * Delete the created organisation; doing this would also delete any associated notes and files associated with those.
 */
$workbooks->assertDelete('crm/organisations', $organisation_id_lock_versions);

if(function_exists('testExit')) {
  testExit($workbooks);
}

else {
  exit (0);
}

?>
