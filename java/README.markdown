# JAVA language binding for the Workbooks API

See the other Java code here in github for simple usage examples to explore the objects returned by the API. The comments in the `WorkbooksApi.java` file contain additional information to that here.

Note that this software does not change often, because it does not need to.  The Workbooks API is designed so that it is based on metadata and although features are added to Workbooks with every release the API does not change.  As far as possible API changes are backwards compatible.  We are proud that software written to our original 1.0 API back in 2009 continues to be both supported and supportable.

To find out more about the many records and fields within your Workbooks database navigate within Workbooks to Configuration > Automation > API Reference.

## Usage

External classes can authenticate using an API Key or a username and password. Authentication is done automatically for process engine scripts. In the examples here authentication is done in `TestLoginHelper.java`.

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
  import workbooks_app.client_lib.java.WorkbooksApi;
  import workbooks_app.client_lib.java.WorkbooksApi.WorkbooksApiResponse;

	HashMap<String, Object> params = new HashMap<String,Object>();
	String application_name = "java_test_client";
	String user_agent = "java_test_client/0.1";
	String api_key ="xxxxx-xxxxx-xxxxx-xxxxx-xxxxx-xxxxx-xxxxx-xxxxx";
	boolean verify_peer = false;

	public WorkbooksApi workbooks = null;
	HashMap<String, Object> logObjects = new HashMap<String, Object>();

	public WorkbooksApi testLogin() {
		params.put("application_name", application_name);
		params.put("user_agent", user_agent);
		params.put("logger_callback", "logAllToStdout");
		params.put("verify_peer", verify_peer);
		if (api_key != null && api_key != "") {
			params.put("api_key", api_key);
		}
		try {
			workbooks = new WorkbooksApi(params);
			workbooks.log("Logged in with these params: ", new Object[] {params} );
		} catch(Exception e) {
			workbooks.log("Error while creating the Workbooks API object: ", new Object[] {e}, "error", WorkbooksApi.DEFAULT_LOG_LIMIT);
			e.printStackTrace();
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
	HashMap<String, Object> loginParams = new HashMap<String, Object>();
	loginParams.put("username", username);
	loginParams.put("password", password);

	if (System.getenv("DATABASE_ID") != null) {
	   loginParams.put("logical_database_id", System.getenv("DATABASE_ID"));
	}
	workbooks.log("Login commences", new Object[] {this.getClass().toString()});
	HashMap<String, Object> response = null;
	try {
		response = workbooks.login(loginParams);
		int http_status = (Integer) response.get("http_status");
		JsonObject responseObject = (JsonObject)response.get("response");
		if (response.containsKey("failure_reason")) {
		String failure_reason = response.get("failure_reason").toString();
		if (http_status == WorkbooksApi.HTTP_STATUS_FORBIDDEN && failure_reason.equals("no_database_selection_made")) {
/*
       * Multiple databases are available, and we must choose one.
       * A good UI might remember the previously-selected database or use $databases to present a list of databases for the user to choose from.
       */
	    String default_database_id = responseObject.getString("default_database_id");
/*
       * For this example we simply select the one which was the default when the user last logged in to the Workbooks user interface. This
       * would not be correct for most API clients since the user's choice on any particular session should not necessarily change their choice
       * for all of their API clients.
       */
	    loginParams.put("logical_database_id", default_database_id);

      response = workbooks.login(loginParams);
      http_status = (Integer)response.get("http_status");
      if (http_status != WorkbooksApi.HTTP_STATUS_OK) {
         workbooks.log("Login has failed", new Object[] {response}, "error", WorkbooksApi.DEFAULT_LOG_LIMIT);
                System.exit(0);
      }
	  }
	}
	workbooks.log("Login complete", new Object[] {this.getClass()}, "info", WorkbooksApi.DEFAULT_LOG_LIMIT);

	} catch(Exception e) {
	  workbooks.log("Exception: ", new Object[] {e}, "error", WorkbooksApi.DEFAULT_LOG_LIMIT);
	}
	return workbooks;
}

</code></pre>

### logout()

_Logout from the service_

This is not required if you have passed an api_key to the `new()` function.

Example:
<pre><code>
    HashMap<String, Object> logout = workbooks.logout();
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
  HashMap<String, Object>   filter_limit_select = new HashMap<String, Object> ();

  filter_limit_select.put("_start", "0");// Starting from the 'zeroth' record
	filter_limit_select.put("_limit", "100");//   fetch up to 100 records
	filter_limit_select.put("_sort", "id");// Sort by 'id'
	filter_limit_select.put("_dir", "ASC");//   in ascending order
	filter_limit_select.put("_ff[]", "main_location[county_province_state]");// Filter by this column
	filter_limit_select.put("_ft[]", "ct");//   containing
  filter_limit_select.put("_fc[]", "Berkshire");//   'Berkshire'
  filter_limit_select.put("_select_columns[]", columns);  // An array, of columns to select

  WorkbooksApiResponse response = workbooks.assertGet("crm/organisations", filter_limit_select, null);
OR
  WorkbooksApiResponse response = workbooks.get("crm/organisations", filter_limit_select, null);
  JsonArray allData = response.getData();
</code></pre>

### assertCreate(), create()

_Create one or more objects_

Example, creating a single organisation:
<pre><code>
  ArrayList<HashMap<String, Object>> singleOrganisation = new ArrayList<HashMap<String, Object>>();
  HashMap<String, Object> org1 = new HashMap<String, Object>();
  ArrayList<HashMap<String, Object>> objectIdLockVersion = null;

  org1.put("name", "Birkbeck Burgers");
  org1.put("industry", "Food");
  org1.put("main_location[country]", "United Kingdom");
  org1.put("main_location[county_province_state]", "Oxfordshire");
  org1.put("main_location[town]", "Oxford");
  singleOrganisation.add(org1);
  WorkbooksApiResponse response = workbooks.assertCreate("crm/organisations", singleOrganisation, null, null);
OR
  WorkbooksApiResponse response = workbooks.create("crm/organisations", singleOrganisation, null, null);
</code></pre>

Or create several:
<pre><code>
//*************Multiple Organisations
  HashMap<String, Object> org2 = new HashMap<String, Object>();
  HashMap<String, Object> org3 = new HashMap<String, Object>();
  HashMap<String, Object> org4 = new HashMap<String, Object>();
  ArrayList<HashMap<String, Object>> multipleOrganisations = new ArrayList<HashMap<String, Object>>();

  org2.put("name"                                 , "Freedom & Light Ltd");
  org2.put("created_through_reference"            , "12345");
  org2.put("industry"                             , "Media & Entertainment");
  org2.put("main_location[country]"               , "United Kingdom");
  org2.put("main_location[county_province_state]" , "Berkshire");
  org2.put("main_location[fax]"                   , "0234 567890");
  org2.put("main_location[postcode]"              , "RG99 9RG");
  org2.put("main_location[street_address]"        , "100 Main Street");
  org2.put("main_location[telephone]"             , "0123 456789");
  org2.put("main_location[town]"                  , "Beading");
  org2.put("no_phone_soliciting"                  , true);
  org2.put("no_post_soliciting"                   , true);
  org2.put("organisation_annual_revenue"          , "10000000");
  org2.put("organisation_category"                , "Marketing Agency");
  org2.put("organisation_company_number"          , "12345678");
  org2.put("organisation_num_employees"           , 250);
  org2.put("organisation_vat_number"              , "GB123456");
  org2.put("website"                              , "www.freedomandlight.com");

  org3.put("name", "Freedom Power Tools Limited");
  org3.put("created_through_reference", "12346");

  org4.put("name", "Freedom o\" the Seas Recruitment");
  org4.put("created_through_reference", "12347");

  multipleOrganisations.add(org2);
  multipleOrganisations.add(org3);
  multipleOrganisations.add(org4);

  response = workbooks.assertCreate("crm/organisations", multipleOrganisations, null, null);
OR
  response = workbooks.create("crm/organisations", multipleOrganisations, null, null);
</code></pre>

### assertUpdate(), update()

_Update one or more objects_

Example:
<pre><code>
  objectIdLockVersion = workbooks.idVersions(response);
  org2.put("id", ((HashMap)objectIdLockVersion.get(0)).get("id"));
  org2.put("lock_version", ((HashMap)objectIdLockVersion.get(0)).get("lock_version"));
  org2.put("name", "Freedom & Light Unlimited");
  org2.put("main_location[postcode]", "RG66 6RG");
  org2.put("main_location[street_address]", "199 High Street");

  org3.put("id", ((HashMap)objectIdLockVersion.get(1)).get("id"));
  org3.put("lock_version", ((HashMap)objectIdLockVersion.get(1)).get("lock_version"));
  org3.put("name", "Freedom Power");

  org4.put("id", ((HashMap)objectIdLockVersion.get(2)).get("id"));
  org4.put("lock_version", ((HashMap)objectIdLockVersion.get(2)).get("lock_version"));
  org4.put("name", "Sea Recruitment");

  multipleOrganisations.add(org2);
  multipleOrganisations.add(org3);
  multipleOrganisations.add(org4);

  response = workbooks.assertUpdate("crm/organisations", multipleOrganisations, null, null);
OR
  response = workbooks.update("crm/organisations", multipleOrganisations, null, null);
</code></pre>

### assertDelete(), delete()

_Delete one or more objects_

Example:
<pre><code>
  org3.put("id", ((HashMap)objectIdLockVersion.get(1)).get("id"));
  org3.put("lock_version", ((HashMap)objectIdLockVersion.get(1)).get("lock_version"));
  objectIdLockVersion.add(org3);
  WorkbooksApiResponse responseDelete = workbooks.assertDelete("crm/organisations", objectIdLockVersion, null, null);
OR
  WorkbooksApiResponse responseDelete = workbooks.delete("crm/organisations", objectIdLockVersion, null, null);
</code></pre>

### assertBatch(), batch()

_Create, update, and delete several objects together_

Example:
<pre><code>
  HashMap<String, Object> createAction = new HashMap<String, Object> ();
  HashMap<String, Object> updateAction =  new HashMap<String, Object> ();
  HashMap<String, Object> deleteAction =  new HashMap<String, Object> ();
  HashMap<String, Object> deleteAnotherAction =  new HashMap<String, Object> ();
  ArrayList<HashMap<String, Object>> batchActions = new ArrayList<HashMap<String, Object>>();
  createAction.put("method"														, "CREATE");
  createAction.put("name"                                 , "Abercrombie Pies");
  createAction.put("industry"                             , "Food");
  createAction.put("main_location[country]"               , "United Kingdom");
  createAction.put("main_location[county_province_state]" , "Berkshire");
  createAction.put("main_location[town]"                  , "Beading");

  updateAction.put("method", "UPDATE");
  updateAction.put("id", ((HashMap)objectIdLockVersion.get(0)).get("id"));
  updateAction.put("lock_version", ((HashMap)objectIdLockVersion.get(0)).get("lock_version"));
  updateAction.put("name", "Lights \'R Us");
  updateAction.put("main_location[postcode]", null);

  deleteAction.put("method", "DELETE");
  deleteAction.put("id", ((HashMap)objectIdLockVersion.get(1)).get("id"));
  deleteAction.put("lock_version", ((HashMap)objectIdLockVersion.get(1)).get("lock_version"));

  deleteAnotherAction.put("id", ((HashMap)objectIdLockVersion.get(2)).get("id"));
  deleteAnotherAction.put("lock_version", ((HashMap)objectIdLockVersion.get(2)).get("lock_version"));
  deleteAnotherAction.put("method", "DELETE");

  batchActions.add(createAction);
  batchActions.add(updateAction);
  batchActions.add(deleteAction);
  batchActions.add(deleteAnotherAction);

  WorkbooksApiResponse responseBatch = workbooks.assertBatch("crm/organisations", batchActions , null, null, null);
OR
  WorkbooksApiResponse responseBatch = workbooks.batch("crm/organisations", batchActions , null, null, null);
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

_write log records_

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

This binding uses Java with JSON extensions. It should work on Java 6 or later; it has been tested using Java on various versions of Ubuntu from 12.04 through 24.04.

## License

Licensed under the MIT License

> The MIT License
>
> Copyright (c) 2008-2025, Workbooks Online Limited.
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

We ensure backwards-compatability so that older versions of these bindings continue to work with the production Workbooks service.  *These bindings are provided "as-is" and without any commitment to support.* If you do find issues with the bindings published here we welcome the submission of patches which we will evaluate and may merge in due course.
