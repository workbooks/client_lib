<?php

/** Report examples documentation
 * 
 * Reports are structured in different views which represent data. 
 * 
 * Both reports and views have an id.
 * One report can have many data views while a data view belongs to exactly one report (referenced by report_id).
 * Views can be very similar and just structuring information differently or they can be very different in which case a report is more of a "category for different reporting views".
 * 
 * The core functionality of the API reporting is executing a data view by sending a request to the endpoint: 
 * ENDPOINT: '/data_view/42/data.api'
 * where 42 is the id of the view. Those examples are at the bottom section named "Executing DATAVIEWS"
 *
 * There are two further types of queries for reporting with the purpose to help identifying the id for a view.
 *
 * (1) Getting all/some reports or displaying their metadata
 * (2) Getting all/some views or displaying their metadata
 *
 * Example task: If a report named "Opportunities Amount vs Date" should be automatically displayed using a php script, one would identify the view id like this:
 * Get one report by name (see below)
 * Note the (hopefully only 1) id
 * Get all views for report by id (see below)
 * Note the possibly several ids
 * For each of these ids do: Execute view by id (see below)
 *
 * For convenience there's also a complete code block for executing all views for a known report id. (This would include future new data views of that report)
 * There's even a code block for executing all views belonging to a report matching a certain name, but this is not recommended, as it will break once the name is changed.
 *
 * There are some other convenience tasks, for the complete list see the one line comments below
 * For more information on filtering (this example: eq for equals) please see the API reference.
 *
 * USING THIS FILE: 
 * For trying out one example, just remove the comments (// for one line examples, /* for multiline examples). 
 * The logging and the testexit at the very end of the file should always be executed
 * The first code line of an example is always the line that ends with the comment ("EXAMPLE: Get ...") 
 * For getting a good overview, it is recommended to use an editor/ide that allows to collapse/fold all /* parts.
 *
 * Last commit $Id:$
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
**/

require_once 'workbooks_api.php';
// If not already authenticated or running under the Workbooks Process Engine create a session 
require 'test_login_helper.php';

// Section 1: All elements (without filtering) and meta data
function get_metadata_for_reports(){ // EXAMPLE: Get metadata for REPORTS , for more details on metadata, see reference and metadata_example.php
	global $workbooks;
	$result = $workbooks->assertGet('/reporting/reports/metadata.api');
  $workbooks->log('Result', $result, 'debug', 200000); //log 200000 characters of the result. This is more than the default logging, may be too noisy.
	return $result;
}
function get_reports(){ // EXAMPLE: Get all REPORTS
	global $workbooks;
	$result = $workbooks->assertGet('/reporting/reports.api');
  $workbooks->log('Result', $result, 'debug', 200000); //log 200000 characters of the result. This is more than the default logging, may be too noisy.
	return $result;
}
function get_metadata_for_data_views(){ // EXAMPLE: Get metadata for DATAVIEWS, for more details on metadata, see reference and metadata_example.php
	global $workbooks;
	$result = $workbooks->assertGet('/reporting/data_views/metadata.api');
  $workbooks->log('Result', $result, 'debug', 200000); //log 200000 characters of the result. This is more than the default logging, may be too noisy.
	return $result;
}
function get_data_views(){ // EXAMPLE: Get all DATAVIEWS
	global $workbooks;
	$result = $workbooks->assertGet('/reporting/data_views.api');
  $workbooks->log('Result', $result, 'debug', 200000); //log 200000 characters of the result. This is more than the default logging, may be too noisy.
	return $result;
}

// Sectionend
 
// Section 2: Restricting a result to certain columns
function get_reports_ids_names_lock_versions(){ // EXAMPLE: Get only id, name and lock_version for all reports
	global $workbooks;
	$result = $workbooks->assertGet('/reporting/reports.api', array( 
		'_select_columns[]'    => array(                                   
		  'id',
		  'lock_version',
		  'name'
	)));
  $workbooks->log('Result', $result, 'debug', 200000); //log 200000 characters of the result. This is more than the default logging, may be too noisy.
	return $result;
}
function get_data_views_creators_changers_executors(){ // EXAMPLE: Find out who created, last changed, and who ran data views
	global $workbooks;
	$result = $workbooks->assertGet('/reporting/data_views.api', array(
		'_select_columns[]'    => array(                                   
		  'id',
		  'lock_version',
		  'name',
			'last_run_by_user[login_name]',
			'last_run_at',
			'updated_by_user[person_name]',
			'created_by_user[person_name]'
	)));
  $workbooks->log('Result', $result, 'debug', 200000); //log 200000 characters of the result. This is more than the default logging, may be too noisy.
	return $result;
}

// Sectionend
 
// Section 3: REPORT queries with filters 
function get_report_by_id($id){// EXAMPLE: Get one REPORT by id and select the only result //default1
	global $workbooks;
	$response = $workbooks->assertGet('/reporting/reports.api', array( 
	'_ff[]' => 'id',
	'_ft[]' => 'eq',
	'_fc[]' => $id));
	$result = $response['data'][0];
  $workbooks->log('Result', $result, 'debug', 200000); //log 200000 characters of the result. This is more than the default logging, may be too noisy.
	return $result;
}
function get_report_1_using_short_syntax(){ // EXAMPLE: Get one REPORT by id, SHORT QUERY SYNTAX
  global $workbooks;
  $result = $workbooks->assertGet('/reporting/reports.api?_ff[]=id&_ft[]=eq&_fc[]=1')['data'][0];
  $workbooks->log('Result', $result, 'debug', 200000); //log 200000 characters of the result. This is more than the default logging, may be too noisy.
  return $result;
}
function get_report_by_id_using_short_syntax($id){ // EXAMPLE: Get one REPORT by id, SHORT QUERY SYNTAX
	global $workbooks;
	$result = $workbooks->assertGet('/reporting/reports.api?_ff[]=id&_ft[]=eq&_fc[]='.$id)['data'][0];
  $workbooks->log('Result', $result, 'debug', 200000); //log 200000 characters of the result. This is more than the default logging, may be too noisy.
	return $result;
}
function get_reports_for_which_name_begins_with($begin_of_name){ // EXAMPLE: Get REPORTS for which name begins with Orders
	global $workbooks;
	$result = $workbooks->assertGet('/reporting/reports.api', array( 
		'_ff[]' => 'name',
		'_ft[]' => 'bg',
		'_fc[]' => $begin_of_name));
  $workbooks->log('Result', $result, 'debug', 200000); //log 200000 characters of the result. This is more than the default logging, may be too noisy.
	return $result;
}
function get_report_by_name($name){// EXAMPLE: Get one REPORT by name (breaks if users change name), returns only one if many results
	global $workbooks;
	$response = $workbooks->assertGet('/reporting/reports.api', array( 
		'_ff[]' => 'name',
		'_ft[]' => 'eq',
		'_fc[]' => $name));
	$result = $response['data'][0];
  $workbooks->log('Result', $result, 'debug', 200000); //log 200000 characters of the result. This is more than the default logging, may be too noisy.
	return $result;
}

// Sectionend

// Section 4: DATAVIEW queries with filters
function get_data_view_by_id($id){ // EXAMPLE: Get one DATAVIEW by id and select the only result
	global $workbooks;
	$response = $workbooks->assertGet('/reporting/data_views.api', array( 
		'_ff[]' => 'id',
		'_ft[]' => 'eq',
		'_fc[]' => $id));
	$result = $response['data'][0];
  $workbooks->log('Result', $result, 'debug', 200000); //log 200000 characters of the result. This is more than the default logging, may be too noisy.
	return $result;
}
function get_data_view_by_id_using_short_syntax($id){ // EXAMPLE: Get one DATAVIEW by id, SHORT QUERY SYNTAX
	global $workbooks;
	$result = $workbooks->assertGet('/reporting/data_views.api?_ff[]=id&_ft[]=eq&_fc[]='.$id)['data'][0];
  $workbooks->log('Result', $result, 'debug', 200000); //log 200000 characters of the result. This is more than the default logging, may be too noisy.
	return $result;
}
function get_data_views_for_report_by_id_using_short_syntax($id){ // EXAMPLE: Get all DATAVIEWS for REPORT by id (here: 1)
	global $workbooks;
	$result = $workbooks->assertGet('/reporting/data_views.api?_ff[]=report_id&_ft[]=eq&_fc[]='.$id);
  $workbooks->log('Result', $result, 'debug', 200000); //log 200000 characters of the result. This is more than the default logging, may be too noisy.
	return $result;
}
function get_data_views_for_report_by_id($id){ // EXAMPLE: Get all DATAVIEWS for report by id
	global $workbooks;
	$result = $workbooks->assertGet('/reporting/data_views.api', array( 
	'_ff[]' => 'report_id',
	'_ft[]' => 'eq',
	'_fc[]' => $id));
  $workbooks->log('Result', $result, 'debug', 200000); //log 200000 characters of the result. This is more than the default logging, may be too noisy.
	return $result;
}
function get_data_views_for_reports_by_name($name){ // EXAMPLE: Get DATAVIEWS for REPORTS for which name equals 'Test reports with multiple views' (better to use id instead)
	global $workbooks;
	$result=array();
  $reports = $workbooks->assertGet('/reporting/reports.api', array(
		'_ff[]' => 'name',
		'_ft[]' => 'eq',
		'_fc[]' => $name))
			['data']; 
	foreach($reports as $report) { 
		$data_views_for_report = $workbooks->get('/reporting/data_views.api?_ff[]=report_id&_ft[]=eq&_fc[]='.$report['id'])['data']; //fetch dataview with current report id
		foreach($data_views_for_report as $data_view) {
		  $result[] = $data_view; //append dataview to results
		} 
	}
  $workbooks->log('Result', $result, 'debug', 200000); //log 200000 characters of the result. This is more than the default logging, may be too noisy.
	return $result;
}

// Sectionend

// Section 5: Executing DATAVIEWS
function execute_data_view_by_id($id){ // EXAMPLE: Execute DATAVIEW by id
	global $workbooks;
	$result = $workbooks->assertGet('/data_view/'.$id.'/data.api');
  $workbooks->log('Result', $result, 'debug', 200000); //log 200000 characters of the result. This is more than the default logging, may be too noisy.
	return $result; 
}

function execute_data_view_by_name($name){ 
	global $workbooks;
	$result = $workbooks->assertGet('/data_view/'.$name.'.api');
  $workbooks->log('Result', $result, 'debug', 200000); //log 200000 characters of the result. This is more than the default logging, may be too noisy.
	return $result; 
}

function execute_data_view_1(){ // EXAMPLE: Execute DATAVIEW 1
  global $workbooks;
  $result = $workbooks->assertGet('/data_view/1/data.api');
  $workbooks->log('Result', $result, 'debug', 200000); //log 200000 characters of the result. This is more than the default logging, may be too noisy.
  return $result;
}
function execute_data_views_by_name_begins_with($begin_of_name){ // EXAMPLE: Execute DATAVIEWS for which the name begins (bg) with average (not recommended unless names are used very consistently and not changed)
	global $workbooks;
	$data_views = $workbooks->assertGet('/reporting/data_views.api', array( 
		'_ff[]' => 'name',
		'_ft[]' => 'bg',
		'_fc[]' => $begin_of_name
	))['data'];
	$result=array();
	foreach($data_views as $data_view){
		$result[]= $workbooks->assertGet('/data_view/'.$data_view['id'].'/data.api');
	}
  $workbooks->log('Result', $result, 'debug', 200000); //log 200000 characters of the result. This is more than the default logging, may be too noisy.
	return $result;
}
function execute_data_views_for_report_by_id($id){ // EXAMPLE: Execute all DATAVIEWS for REPORTS by id (here: 8)
	global $workbooks;
	$data_views = $workbooks->assertGet('/reporting/data_views.api?_ff[]=report_id&_ft[]=eq&_fc[]='.$id)['data'];
	$result = array();
	foreach ($data_views as $data_view ){
		$result[] = $workbooks->assertGet('/data_view/'.$data_view['id'].'/data.api'); // Append current data view to results
    $workbooks->log('Result', $result, 'debug', 200000); //log 200000 characters of the result. This is more than the default logging, may be too noisy.
	}
  $workbooks->log('Result', $result, 'debug', 200000); //log 200000 characters of the result. This is more than the default logging, may be too noisy.
	return $result;
}
function execute_data_views_for_report_name_begins_with($begin_of_name){ // EXAMPLE: Execute all DATAVIEWS for all REPORTS beginning by 'Order' (breaks if users change names, not recommended)
	global $workbooks;
	$reports = $workbooks->assertGet('/reporting/reports.api', array( 
    '_ff[]' => 'name',
    '_ft[]' => 'bg',
    '_fc[]' => $begin_of_name))
      ['data'];
  $result=array();
  foreach($reports as $report){
    $data_views = $workbooks->assertGet('/reporting/data_views.api?_ff[]=report_id&_ft[]=eq&_fc[]='.$report['id'])['data']; 
    foreach($data_views as $data_view){
      $result[]=$workbooks->assertGet('/data_view/'.$data_view['id'].'/data.api'); // Append current data view execution to result
    }
  }
  $workbooks->log('Result', $result, 'debug', 200000); //log 200000 characters of the result. This is more than the default logging, may be too noisy.
	return $result;
}


function get_metadata_for_data_view_execution_by_id($id){ //EXAMPLE: Get the metadata for one DATAVIEW by id
  global $workbooks;
  $result = $workbooks->assertGet('/data_view/'.$id.'/data/metadata.api');
  $workbooks->log('Result', $result, 'debug', 200000); //log 200000 characters of the result. This is more than the default logging, may be too noisy.
  return $result;
}


// Sectionend


function execute_all_at_once(){

  global $workbooks;
  $workbooks->log('Run get_metadata_for_reports'); get_metadata_for_reports();
  $workbooks->log('Run get_reports'); get_reports();
  $workbooks->log('Run get_metadata_for_data_views'); get_metadata_for_data_views();
  $workbooks->log('Run get_data_views'); get_data_views();
  $workbooks->log('Run get_reports_ids_names_lock_versions'); get_reports_ids_names_lock_versions();
  $workbooks->log('Run get_data_views_creators_changers_executors'); get_data_views_creators_changers_executors();
  $workbooks->log('Run get_report_by_id 1'); get_report_by_id(1);
  $workbooks->log('Run get_report_by_id 2'); get_report_by_id(2);
  $workbooks->log('Run get_reports_for_which_name_begins_with'); get_reports_for_which_name_begins_with('Order');
  $workbooks->log('Run get_report_by_name'); get_report_by_name('Orders per month');
  $workbooks->log('Run get_data_view_by_id 1'); get_data_view_by_id(1);
  $workbooks->log('Run get_data_view_by_id_using_short_syntax 2'); get_data_view_by_id_using_short_syntax(2);
  $workbooks->log('Run get_data_views_for_report_by_id_using_short_syntax 1'); get_data_views_for_report_by_id_using_short_syntax(1);
  $workbooks->log('Run get_data_views_for_report_by_id 2'); get_data_views_for_report_by_id(2);
  $workbooks->log('Run get_data_views_for_reports_by_name tr'); get_data_views_for_reports_by_name('Test reports with multiple views');
  $workbooks->log('Run execute_data_view_1'); execute_data_view_1();
  $workbooks->log('Run execute_data_view_by_id 2'); execute_data_view_by_id(2);
  $workbooks->log('Run execute_data_views_by_name_begins_with Average'); execute_data_views_by_name_begins_with('Average');
  $workbooks->log('Run execute_data_view_by_name viewname'); execute_data_view_by_name('Opportunities%20Amount%20vs%20Date%20v2:%20Summary');
  $workbooks->log('Run execute_data_views_for_report_by_id 2'); execute_data_views_for_report_by_id(2);
  $workbooks->log('Run execute_data_views_for_report_name_begins_with Orders'); execute_data_views_for_report_name_begins_with('Orders');
  $workbooks->log('Run get_metadata_for_data_view_execution_by_id 1'); get_metadata_for_data_view_execution_by_id(1);
}

global $workbooks;


execute_all_at_once(); // FOR TRYING OUT ONLY ONE EXAMPLE COMMENT THIS LINE AND MAKE ONE SINGLE FUNCTION CALL BELOW (SEE LINE BELOW)
//execute_data_views_for_report_by_id(1);

testExit($workbooks);