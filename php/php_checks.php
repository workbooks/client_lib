<?php
  
/**
 *   General confidence test for a version of PHP, to ensure the required
 *   extensions are present.
 *
 *   Last commit $Id: php_checks.php 65192 2024-12-20 17:11:06Z jkay $
 */


// (I hate the inconsistency in capitalisation here!)
$expected_extensions = [
  "Core", "date", "libxml", "openssl", "pcre", "zlib", "filter", "hash", "pcntl",
  "Reflection", "SPL", "session", "sodium", "standard", "mysqlnd", "PDO", "xml",
  "bcmath", "bz2", "calendar", "ctype", "curl", "dba", "dom", "mbstring", "fileinfo",
  "ftp", "gd", "gettext", "iconv", "imap", "intl", "json", "ldap", "exif", "mysqli",
  "odbc", "pdo_dblib", "pdo_mysql", "PDO_ODBC", "pdo_pgsql", "pdo_sqlite", "pgsql",
  "Phar", "posix", "readline", "shmop", "SimpleXML", "soap", "sockets", "sqlite3",
  "ssh2", "sysvmsg", "sysvsem", "sysvshm", "tokenizer", "xmlreader",
  "xmlwriter", "xsl", "zip", "Zend OPcache",
];
if (phpversion() < 8.2) {
  # Deprecated in #41076, not added to php8.2 and upwards.
  $expected_extensions[] = "pdo_sqlsrv";
  $expected_extensions[] = "sqlsrv";
}

$expected_shared_modules = [
  "AuthorizeNet", "PayPal-PHP-SDK", "PhpSpreadsheet", "QBO_SDK_260", 
  "aws", "geopal-client", "msgraph-sdk-php",
];

$errors = [];

//$params['_workbooks_service'] = 'https://secure.workbooks.com';
//$workbooks->setService('https://secure.workbooks.com');

$workbooks->log('Inputs', [
    '$params' => $params,
    'REMOTE_ADDR' => $_SERVER['REMOTE_ADDR'],
    '$_GET' => $_GET,
    '$_POST' => $_POST,
    '$_SERVER' => $_SERVER,
    'service' => $workbooks->getService(),
  ],
  'debug',
  100000
);

// Check WB API works.
$person_get_response = $workbooks->assertGet('crm/people', [
  '_start' => 0, '_limit' => 1, '_sort' => 'id', '_filters[]' => ['id', 'eq', '2'], '_select_columns[]' => ['id', 'name']
]);
$workbooks->log('Fetched person from Workbooks API', $person_get_response);

$extensions = get_loaded_extensions();
foreach ($expected_extensions as $expected) {
  if (!in_array($expected, $extensions)) {
    $workbooks->log('Missing extension!', $expected, 'error');
    $errors[]= ['Missing extension', $expected];
  }
}
foreach ($expected_shared_modules as $module) {
  $dir = '/usr/share/php/' . $module;
  if (!file_exists($dir)) {
    $workbooks->log('Missing module!', $dir, 'error');
    $errors[]= ['Missing module', $module];
  }
}

// Finally, the details
phpinfo();

if (!empty($errors)) {
  $workbooks->log('Errors reported', $errors, 'error');
  exit(2);
}
exit(0);
