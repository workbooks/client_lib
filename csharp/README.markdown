# C# language binding for the Workbooks API

See the C# code here in github for simple usage examples to explore the objects returned by the API. The comments in the `WorkbooksApi.cs` file contain additional information to that here.

Note that this software does not change often, because it does not need to.  The Workbooks API is designed so that it is based on metadata and although features are added to Workbooks with every release the API does not change.  As far as possible API changes are backwards compatible.  We are proud that software written to our original 1.0 API back in 2009 continues to be both supported and supportable.

To find out more about the many records and fields within your Workbooks database navigate within Workbooks to Configuration > Automation > API Reference.

## Usage

External classes can authenticate using an API Key or a username and password. Authentication is done automatically for process engine scripts. In the examples here authentication is done in `TestLoginHelper.cs`.

## External Script Usage

There are several ways for external classes to authenticate with Workbooks. Most API scripts should use API Keys to authenticate with Workbooks: Workbooks users can create API Keys in the Workbooks Desktop. Using API Keys there is no need to explicitly call `login()` or `logout()`.

### Using API Keys without a Session

Simply invoke `new()` and pass an API Key to create a Workbooks API object and then you can use any of the following methods: `get()`, `create()`, `update()`, `delete()`, `batch()`, or the assert versions. Using session-based authentication (as described below) is more efficient if you are going to issue multiple API calls.

### Using login() and logout()

An alternative is to establish a session with the Workbooks service: pass an API Key or a username and password to the `login()` call and your script receives back a Session ID in a cookie. Sessions can be reconnected using an existing session whose ID you have retained. When you are finished, it is polite to `logout()` or you may want to use `getSessionId()` to retain a session ID for future use.

Once you have a new `WorkbooksAPI` object you will typically `login()` to create a new session, although you might use `setSessionId()` to re-connect to an existing session whose ID you have retained. When you are finished, it is polite to `logout()` or you may want to use `getSessionId()` to retain a session ID for future use.

Having obtained a session you can use any of the following methods: `get()`, `create()`, `update()`, `delete()`, `batch()`, or the assert versions.

### new()

_Initialise the Workbooks API_

Example:
<pre><code>
  using WorkbooksApiApplication;
	Dictionary<string, object> loginParams = new Dictionary<string, object>();
  string application_name = "csharp_test_client";
  string user_agent = "csharp_test_client/0.1";
  string api_key ="xxxxx-xxxxx-xxxxx-xxxxx-xxxxx-xxxxx-xxxxx-xxxxx";
  bool verify_peer = false;
  public WorkbooksApi workbooks = null;

  public WorkbooksApi testLogin() {
  	loginParams.Add("application_name", application_name);
  	loginParams.Add("user_agent", user_agent);
  	loginParams.Add("verify_peer", verify_peer);
  	if (api_key != null && api_key != "") {
  	  loginParams.Add("api_key", api_key);
  	}
  	try {
  	  workbooks = new WorkbooksApi(loginParams);
			workbooks.log("Logged in with these loginParams: ", new Object[] {loginParams} );
  	} catch(Exception e) {
  		workbooks.log("Error while creating the Workbooks API object: ", new Object[] {e}, "error");
  		Console.WriteLine (e.StackTrace);
  	}
		return workbooks;
	}
</code></pre>

If you omit the api_key above you will instead need to use `login()` to establish a session and receive a cookie.

### login()

_Login to the service to set up a session_

This is not required if you have passed an api_key to the `new()` function. If you use a username and password to authenticate you may also need to pass a logical_database_id.

Example:
<pre><code>
public WorkbooksApi testLogin() {
	Dictionary<string, object> loginParams = new Dictionary<string, object>();
  loginParams.Add("username", username);
  loginParams.Add("password", password);

  if (System.Environment.GetEnvironmentVariable("DATABASE_ID") != null) {
    loginParams.Add("logical_database_id", System.Environment.GetEnvironmentVariable("DATABASE_ID"));
  }
  workbooks.log("Login commences", new Object[] {this.GetType().Name});
  Dictionary <string, object> response = null;
  try {
    response = workbooks.login(loginParams);
    int http_status = (int) response["http_status"];
    Dictionary<string, object> responseData = (Dictionary<string, object>) response["response"];
    if (response.ContainsKey("failure_reason")) {
      String failure_reason = response["failure_reason"].ToString();
      if (http_status == WorkbooksApi.HTTP_STATUS_FORBIDDEN && failure_reason.Equals("no_database_selection_made")) {
      /*
       * Multiple databases are available, and we must choose one.
       * A good UI might remember the previously-selected database or use $databases to present a list of databases for the user to choose from.
       */
	    String default_database_id = responseData["default_database_id"].ToString();
      loginParams.Add("logical_database_id", default_database_id);

/*
       * For this example we simply select the one which was the default when the user last logged in to the Workbooks user interface. This
       * would not be correct for most API clients since the user's choice on any particular session should not necessarily change their choice
       * for all of their API clients.
       */
	    response = workbooks.login(loginParams);
      http_status = (int)response["http_status"];
      if (http_status != WorkbooksApi.HTTP_STATUS_OK) {
        workbooks.log("Login has failed", new Object[] {response}, "error");
        System.Environment.Exit(0);
      }
    }
  }
  workbooks.log("Login complete", new Object[] {this.GetType().ToString()}, "info");
  } catch(Exception e) {
    workbooks.log("Exception: ", new Object[] {e}, "error");
  }
  return workbooks;
 }
</code></pre>

### logout()

_Logout from the service_

This is not required if you have passed an api_key to the `new()` function.

Example:
<pre><code>
    Dictionary<string, object> logout = workbooks.logout();
</code></pre>

## Interacting with Workbooks

### assertGet(), get()

_Get a list of objects, or show a single object_

Example:
<pre><code>
  String[] columns = {"id",
        "lock_version",
        "name",
        "object_ref",
        "main_location[town]",
        "updated_at",
        "updated_by_user[person_name]"};
  Dictionary<string, object>   filter_limit_select = new Dictionary<string, object> ();

  filter_limit_select.Add("_start", "0");// Starting from the 'zeroth' record
  filter_limit_select.Add("_limit", "100");//   fetch up to 100 records
  filter_limit_select.Add("_sort", "id");// Sort by 'id'
  filter_limit_select.Add("_dir", "ASC");//   in ascending order
  filter_limit_select.Add("_ff[]", "main_location[county_province_state]");// Filter by this column
  filter_limit_select.Add("_ft[]", "ct");//   containing
  filter_limit_select.Add("_fc[]", "Berkshire");//   'Berkshire'
  filter_limit_select.Add("_select_columns[]", columns);  // An array, of columns to select

	WorkbooksApiResponse response = workbooks.assertGet("crm/organisations", filter_limit_select, null);
OR
  WorkbooksApiResponse response = workbooks.get("crm/organisations", filter_limit_select, null);
  object[] allData = response.getData();
</code></pre>

### assertCreate(), create()

_Create one or more objects_

Example, creating a single organisation:
<pre><code>
  List<Dictionary<string, object>> singleOrganisation = new List<Dictionary<string, object>>();
  Dictionary<string, object> org1 = new Dictionary<string, object>();

  org1.Add("name", "Birkbeck Burgers");
  org1.Add("industry", "Food");
  org1.Add("main_location[country]", "United Kingdom");
  org1.Add("main_location[county_province_state]", "Oxfordshire");
  org1.Add("main_location[town]", "Oxford");

  singleOrganisation.Add(org1);
  WorkbooksApiResponse response = workbooks.assertCreate("crm/organisations", singleOrganisation, null, null);
OR
  WorkbooksApiResponse response = workbooks.create("crm/organisations", singleOrganisation, null, null);
</code></pre>

Or create several:
<pre><code>
//*************Multiple Organisations
  Dictionary<string, object> org2 = new Dictionary<string, object>();
  Dictionary<string, object> org3 = new Dictionary<string, object>();
  Dictionary<string, object> org4 = new Dictionary<string, object>();
  List<Dictionary<string, object>> multipleOrganisations = new List<Dictionary<string, object>>();

  org2.Add("name"                                 , "Freedom & Light Ltd");
  org2.Add("created_through_reference"            , "12345");
  org2.Add("industry"                             , "Media & Entertainment");
  org2.Add("main_location[country]"               , "United Kingdom");
  org2.Add("main_location[county_province_state]" , "Berkshire");
  org2.Add("main_location[fax]"                   , "0234 567890");
  org2.Add("main_location[postcode]"              , "RG99 9RG");
  org2.Add("main_location[street_address]"        , "100 Main Street");
  org2.Add("main_location[telephone]"             , "0123 456789");
  org2.Add("main_location[town]"                  , "Beading");
  org2.Add("no_phone_soliciting"                  , true);
  org2.Add("no_post_soliciting"                   , true);
  org2.Add("organisation_annual_revenue"          , "10000000");
  org2.Add("organisation_category"                , "Marketing Agency");
  org2.Add("organisation_company_number"          , "12345678");
  org2.Add("organisation_num_employees"           , 250);
  org2.Add("organisation_vat_number"              , "GB123456");
  org2.Add("website"                              , "www.freedomandlight.com");

  org3.Add("name", "Freedom Power Tools Limited");
  org3.Add("created_through_reference", "12346");

  org4.Add("name", "Freedom o\" the Seas Recruitment");
  org4.Add("created_through_reference", "12347");

  multipleOrganisations.Add(org2);
  multipleOrganisations.Add(org3);
  multipleOrganisations.Add(org4);

  WorkbooksApiResponse response = workbooks.assertCreate("crm/organisations", multipleOrganisations, null, null);

  workbooks.log("createOrganisations Multiple: ", new Object[] {response.print(response.getFirstAffectedObject())});
OR
  WorkbooksApiResponse response = workbooks.create("crm/organisations", multipleOrganisations, null, null);
</code></pre>

### assertUpdate(), update()

_Update one or more objects_

Example:
<pre><code>
  objectIdLockVersion = workbooks.idVersions(response);
  org2.Add("id", ((Dictionary<string, object>)objectIdLockVersion[0])["id"]);
  org2.Add("lock_version", ((Dictionary<string, object>)objectIdLockVersion[0])["lock_version"]);
  org2.Add("name", "Freedom & Light Unlimited");
  org2.Add("main_location[postcode]", "RG66 6RG");
  org2.Add("main_location[street_address]", "199 High Street");

  org3.Add("id", ((Dictionary<string, object>)objectIdLockVersion[1])["id"]);
  org3.Add("lock_version", ((Dictionary<string, object>)objectIdLockVersion[1])["lock_version"]);
  org3.Add("name", "Freedom Power");

  org4.Add("id", ((Dictionary<string, object>)objectIdLockVersion[2])["id"]);
  org4.Add("lock_version", ((Dictionary<string, object>)objectIdLockVersion[2])["lock_version"]);
  org4.Add("name", "Sea Recruitment");

  multipleOrganisations.Add(org2);
  multipleOrganisations.Add(org3);
  multipleOrganisations.Add(org4);

  WorkbooksApiResponse response = workbooks.assertUpdate("crm/organisations", multipleOrganisations, null, null);
OR
  WorkbooksApiResponse response = workbooks.update("crm/organisations", multipleOrganisations, null, null);
</code></pre>

### assertDelete(), delete()

_Delete one or more objects_

Example:
<pre><code>
  deleteAction.Add("id", ((Dictionary<string, object>)objectIdLockVersion[1])["id"]);
  deleteAction.Add("lock_version", ((Dictionary<string, object>)objectIdLockVersion[1])["lock_version"]);
  deleteOrganisations.add(deleteAction);
  WorkbooksApiResponse responseDelete = workbooks.assertDelete("crm/organisations", deleteOrganisations, null, null);
OR
  WorkbooksApiResponse responseDelete = workbooks.delete("crm/organisations", deleteOrganisations, null, null);
</code></pre>

### assertBatch(), batch()

_Create, update, and delete several objects together_

Example:
<pre><code>
  Dictionary<string, object> createAction = new Dictionary<string, object> ();
  Dictionary<string, object> updateAction =  new Dictionary<string, object> ();
  Dictionary<string, object> deleteAction =  new Dictionary<string, object> ();
  Dictionary<string, object> deleteAnotherAction =  new Dictionary<string, object> ();
  List<Dictionary<string, object>> batchActions = new List<Dictionary<string, object>>();


  createAction.Add("method"                           , "CREATE");
  createAction.Add("name"                                 , "Abercrombie Pies");
  createAction.Add("industry"                             , "Food");
  createAction.Add("main_location[country]"               , "United Kingdom");
  createAction.Add("main_location[county_province_state]" , "Berkshire");
  createAction.Add("main_location[town]"                  , "Beading");

  updateAction.Add("method", "UPDATE");
  updateAction.Add("id", ((Dictionary<string, object>)objectIdLockVersion[0])["id"]);
  updateAction.Add("lock_version", ((Dictionary<string, object>)objectIdLockVersion[0])["lock_version"]);
  updateAction.Add("name", "Lights \'R Us");
  updateAction.Add("main_location[postcode]", null);

  deleteAction.Add("method", "DELETE");
  deleteAction.Add("id", ((Dictionary<string, object>)objectIdLockVersion[1])["id"]);
  deleteAction.Add("lock_version", ((Dictionary<string, object>)objectIdLockVersion[1])["lock_version"]);

  deleteAnotherAction.Add("id", ((Dictionary<string, object>)objectIdLockVersion[2])["id"]);
  deleteAnotherAction.Add("lock_version", ((Dictionary<string, object>)objectIdLockVersion[2])["lock_version"]);
  deleteAnotherAction.Add("method", "DELETE");

  batchActions.Add(createAction);
  batchActions.Add(updateAction);
  batchActions.Add(deleteAction);
  batchActions.Add(deleteAnotherAction);

  WorkbooksApiResponse response = workbooks.assertBatch("crm/organisations", batchActions , null, null, null);
OR
  WorkbooksApiResponse response = workbooks.batch("crm/organisations", batchActions , null, null, null);
</code></pre>

### idVersion()

_Extract ID and LockVersion from response_

You need the ID and LockVersion in order to manipulate records. `idVersion()` is often done after an update or create operation.
Example:
<pre><code>
  // Apply some changes
  response = workbooks.assertUpdate("crm/organisations", multipleOrganisations, null, null);
  objectIdLockVersion = workbooks.idVersions(response);
  // Delete all those records!
  WorkbooksApiResponse responseDelete = workbooks.assertDelete("crm/organisations", objectIdLockVersion, null, null);
</code></pre>

### log()

_Write log records_

The `log()` method can be called with up to four parameters. All but the first are optional. The first parameter is a string to label the log record. The second parameter is data (e.g. an array, string or other data structure) which is dumped using `new Object[] {obj}`. The third parameter is a log level; log levels include 'error', 'warning', 'notice', 'info', 'debug' (the default), and 'output' (which is rarely used). The fourth parameter is the log size limit which Is defaulted to '4096'

Examples:
<pre><code>
  workbooks.log("Invoked", new Object[] {params, form_fields}, 'info');
  workbooks.log('Fetched a data item', new Object[] {responseDelete.getFirstData()});
  workbooks.log('Bad response for non-existent item', new Object[] {status, response}, 'error');
</code></pre>

## Further Information

The API is documented at <a href="http://www.workbooks.com/api" target="_blank">http://www.workbooks.com/api</a>.

## Requirements

This binding uses .Net framework 4.0 and JSON extensions. It should work on .Net framework 4.0 or later; it has been tested using Mono compiler 2.10.8.1 on Ubuntu 12.04.

## License

Licensed under the MIT License

> The MIT License
>
> Copyright (c) 2008-2014, Workbooks Online Limited.
>
> Permission is hereby granted, free of charge, to any person obtaining a copy
> of this software and associated documentation files (the "Software"), to deal
> in the Software without restriction, including without limitation the rights
> to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
> copies of the Software, and to permit persons to whom the Software is
> furnished to do so, subject to the following conditions:
>
> The above copyright notice and this permission notice shall be included in
> all copies or substantial portions of the Software.
>
> THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
> IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
> FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
> AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
> LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
> OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
> THE SOFTWARE.

## Support

Please contact <a href="mailto:support@workbooks.com">support@workbooks.com</a>. Enhancement suggestions should be logged on the Workbooks ideas forum at <a href="http://ideas.workbooks.com" target="_blank">http://ideas.workbooks.com</a>.