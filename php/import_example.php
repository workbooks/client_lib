<?php
  
/**
 *   A demonstration of using the Workbooks Import API via a thin PHP wrapper
 *
 *   Last commit $Id: import_example.php 23186 2014-09-15 15:34:37Z jkay $
 *   License: www.workbooks.com/mit_license
 *
 */

require_once 'workbooks_api.php';

/* If not already authenticated or running under the Workbooks Process Engine create a session */
require 'test_login_helper.php';


$tmp_files = array();

#
# Invoking Import via the API requires several API calls:
#   1. Find a pre-existing Import Job which will act as your template.
#   2. Create an Import Job object and upload and attach your CSV to it.
#   3. Set the Import Job to run soon and auto-approve the import when done.
#

# Product import
$previous_product_import_job = fetch_import_job_matching(array('descriptor', 'bg', 'Products '));

$product_file_tmp_name = create_test_csv_file(  
  array(
    'Product Name,Product Generic Name,Part Number,Mgt Part Number,Web Site Customer Price,Category Name',
    'Small Scale Fusion Reactor,Fusion Reactor,FUSION1985,1985-FOX-FILM-1,$1000000,Energy Products',
    'Flux Capacitor,Capacitor,FLUX1985,1985-FOX-FILM-2,$10000,Travel Goods',
    'Hover Board,Kids Toys,BOARD1985,1985-FOX-FILM-3,$1985,Travel Goods',
  )
);
$tmp_files[] = $product_file_tmp_name;

$product_import_job_id = create_import_job($previous_product_import_job, $product_file_tmp_name);

schedule_import_job($product_import_job_id); # Can run without waiting for any other import job

$product_import_job = fetch_import_job_matching(array('id', 'eq', $product_import_job_id));
$workbooks->log('Successfully scheduled a product import.', $product_import_job);

#
# Now schedule another couple of jobs, to follow after the first one completes. 
#

# Person import
$previous_person_import_job = fetch_import_job_matching(array('descriptor', 'bg', 'People '));

$person_file_tmp_name = create_test_csv_file(  
  array(
    'Person Name,Email',
    'Fred Bloggs,fred@bloggs.com',
    'Zaphod Beeblebrox,zaphod@president.galactic.gov',
  )
);
$tmp_files[] = $person_file_tmp_name;

$person_import_job_id = create_import_job($previous_person_import_job, $person_file_tmp_name);

# Organisation import
$previous_organisation_import_job = fetch_import_job_matching(array('descriptor', 'bg', 'Organisations '));

$organisation_file_tmp_name = create_test_csv_file(  
  array(
    'Name,Industry',
    'Mauve,Telecoms',
  )
);
$tmp_files[] = $organisation_file_tmp_name;

$organisation_import_job_id = create_import_job($previous_organisation_import_job, $organisation_file_tmp_name);

# Schedule these jobs in reverse order to demonstrate setting the run_after_imp_job_id. The import will only run
# when its predecessor job completes.
schedule_import_job($organisation_import_job_id, $product_import_job_id);
schedule_import_job($person_import_job_id, $organisation_import_job_id);

$workbooks->log("Import Jobs should run in order: {$product_import_job_id}, {$organisation_import_job_id}, {$person_import_job_id}");

# Note that you cannot delete an import job whilst it is scheduled, but you can once it is has completed execution.
$dummy_organisation_import_job_id = create_import_job($previous_organisation_import_job, $organisation_file_tmp_name);
$dummy_organisation_import_job = fetch_import_job_matching(array('id', 'eq', $dummy_organisation_import_job_id));
delete_import_job($dummy_organisation_import_job);

foreach ($tmp_files as $f) {
  unlink($f);
}
testExit($workbooks);


/* Create a simple CSV file from an array of lines, returning its name on disk. This defaults to Windows line endings */
function create_test_csv_file($import_csv_rows, $line_separator = "\r\n") {
  global $workbooks;

  $file_tmp_name = tempnam('', 'import-');
  $fp = fopen($file_tmp_name, 'w');
  $res = fwrite($fp, join($line_separator, $import_csv_rows));
  fclose($fp);
  #$workbooks->log("create_test_csv_file() returns file_tmp_name {$file_tmp_name}");
  return $file_tmp_name;
}

/* Find a pre-existing import job, returning its attributes */
function fetch_import_job_matching($filters) {
  global $workbooks;

  $fetch_import_jobs = array(
    '_sort' => 'id',
    '_dir' => 'ASC',
    '_filters[]' => $filters,
    '_start' => 0,
    '_limit' => 1,
  );
  $fetch_import_jobs_response = $workbooks->assertGet('import/imp_jobs', $fetch_import_jobs);
  $imp_job = @$fetch_import_jobs_response['data'][0];
  if (empty($imp_job)) {
    $workbooks->log('Failed to fetch a matching import job', $fetch_import_jobs_response);
    testExit($workbooks, 1);
  }
  #$workbooks->log('Fetched a matching import job', $fetch_import_jobs_response);
  return $imp_job;
}

/* Create an import job (based on a pre-existing Job), returning its ID */
function create_import_job($template_import_job, $filename, $upload_filename = 'import_data.csv') {
  global $workbooks;
        
  $create_import_job = array(
    'imp_file[file]' => array(
      'tmp_name' => '@' . $filename, // Mirror curl and '$_FILES[]' interfaces
      'file_name' => $upload_filename,
      'file_content_type' => 'text/csv',
    ),
    'create_with_import_type' =>  $template_import_job['imp_profile[imp_type]'],
    'based_on_previous_import' => true,
    'imp_profile_id' => $template_import_job['imp_profile_id'],
  );

  // Always use the ('content_type' => multipart/form-data) option for uploading files: it is efficient.
  $create_import_job_response = $workbooks->assertCreate('import/imp_jobs', 
    $create_import_job, array(), array('content_type' => 'multipart/form-data'));
  $import_job_id = @$create_import_job_response['affected_objects'][0]['id'];
  
  if (empty($import_job_id)) {
    $workbooks->log('Failed to create a new Import Job', $create_import_job_response);
    testExit($workbooks, 1);
  }
  $workbooks->log("create_import_job() returns id:{$import_job_id}; create_import_job_response", $create_import_job_response);
  return $import_job_id;
}

/* Update an import job to set it running with auto-approval. Optionally specify another job which must complete first */
function schedule_import_job($import_job_id, $run_after_imp_job_id=null) {
  global $workbooks;
  
  /* The lock_version can change unexpectedly, so fetch it now */
  $import_job = fetch_import_job_matching(array('id', 'eq', $import_job_id));
  
  $update_import_job = array(
    'id' => $import_job['id'],
    'lock_version' => "{$import_job['lock_version']}",
    'status' => 'API_QUEUED',
    'auto_approve' => true,
    'suppress_notification' => true,
    'run_after_imp_job_id' => $run_after_imp_job_id,
  );
  
  $update_import_job_response = $workbooks->assertUpdate('import/imp_jobs', $update_import_job);
  $import_job_status = $update_import_job_response['affected_objects'][0]['status'];
  if ($import_job_status != 'QUEUED') {
    $workbooks->log('Failed to schedule the Import Job', $update_import_job_response);
    testExit($workbooks, 1);
  }
  $workbooks->log("schedule_import_job(): update_import_job_response", $update_import_job_response);
}

/* Remove an import job. Normally this is not recommended: you cannot then 'undo' it */
/* Note that you cannot delete an import job whilst it is scheduled, but you can once it is has completed execution. */
function delete_import_job($import_job) {
  global $workbooks;

  $delete_import_job = array(
    'id' => $import_job['id'],
    'lock_version' => "{$import_job['lock_version']}",
  );
  
  $delete_import_job_response = $workbooks->assertDelete('import/imp_jobs', $delete_import_job);
  $workbooks->log("delete_import_job(): delete_import_job_response", $delete_import_job_response);
}

?>
