Workbooks October 2012 release

The examples have been cleaned up and common code moved to login helpers.

Login helpers have been added demonstrating three ways to authenticate to the service. Most
examples use API Keys to authenticate in sessionless mode; two examples demonstrate other 
techniques:
  * username/password (username_login_example.php)
  * api_key session mode (api_key_login_example.php)

New examples added:
  * generating a CSV (report_example.php)
  * generating a PDF
  * sync process (added to people_example.php)
  * demonstrate the use of the new "API Data" api to hold state (api_data_example.php)

The Workbooks API remains backwards-compatible and has been extended to add additional methods.

Improvements to documentation including documenting a set of new methods in workbooks_api.php
including idVersion(), header(), and output().

