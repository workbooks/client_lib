<?php

/**
 *  Last commit $Id: generate_custom_records.php 57625 2023-03-10 12:45:15Z klawless $
 *  Copyright (c) 2008-2022, Workbooks Online Limited.
 *
 *  This is a simple script that will generate a specified number of custom records. Useful if you need to run
 *  performance tests on large numbers of records. Takes three arguments:
 *    * The number of records you want to create.
 *    * The batch size you want to create them in.
 *    * The plural name of the custom record, lower-case, underscored, as appears in the API reference.
 *  It will number the records in sequence.
*/

require_once 'workbooks_api.php';
require 'test_login_helper.php';

$loops_needed = $argv[1] / $argv[2];

for ($i = 1; $i <= $loops_needed; $i++) {
  $create_records = array();
  for ($j = 1; $j <= $argv[2]; $j++) {
    $record = ['name' => $j + (($i - 1) * $argv[2])];
    $create_records[] = $record;
  }
  $workbooks->log("Creating batch of {$argv[2]} custom records");
  $workbooks->assertCreate("custom_record/{$argv[3]}", $create_records);
  $workbooks->log("Created batch number {$i} of {$loops_needed}");
}

testExit($workbooks);
