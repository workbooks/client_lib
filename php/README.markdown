# PHP language binding for the Workbooks API

See the Script Library within Workbooks itself and the other PHP code here in github for simple usage examples to explore the objects returned by the API. The comments in the `workbooks_api.php` file contain additional information to that here.

## Usage

Workbooks API scripts are either hosted externally to the Workbooks service ("external scripts"), or are hosted by the Workbooks Process Engine ("process engine scripts"). Process engine scripts can be invoked in several ways, including under a scheduler ('Scheduled Processes'), when a button is pressed ('Button Processes') or when a URL is invoked ('Web Processes').

External scripts can authenticate using an API Key or a username and password. Authentication is done automatically for process engine scripts. In the examples here authentication is done in `test_login_helper.php` so that they can run outside the process engine.

## Running under the Workbooks Process Engine

The Process Engine will automatically create a login session for your script so you can skip the description of `new()`, `login()` and `logout()` below.
Your script will be invoked from time to time by the service, typically through a user action or according to a schedule.
Alongside its parameters your script is passed the variable `$workbooks` which is an object representing a valid Workbooks session. 
Using $workbooks you can call methods such as `get()`, `create()`, `update()`, `delete()`, `batch()`. You should normally call equivalent methods which check the response and raise an exception if it is not expected: these are `assertGet()`, `assertCreate()`, `assertUpdate()`, `assertDelete()`, `assertBatch()`.

## External Script Usage

There are several ways for external scripts to authenticate with Workbooks. Most API scripts should use API Keys to authenticate with Workbooks: Workbooks system administrators can create API Keys in the Workbooks Desktop. Using API Keys there is no need to explicitly call `login()` or `logout()`.

### Using API Keys without a Session

Simply invoke `new()` and pass an API Key to create a Workbooks API object and then you can use any of the following methods: `get()`, `create()`, `update()`, `delete()`, `batch()`, or the assert versions.

### Using login() and logout()

An alternative is to establish a session with the Workbooks service: pass an API Key or a username and password to the `login()` call and your script receives back a Session ID in a cookie. Sessions can be reconnected using an existing session whose ID you have retained. When you are finished, it is polite to `logout()` or you may want to use `getSessionId()` to retain a session ID for future use.

Once you have a new `WorkbooksAPI` object you will typically `login()` to create a new session, although you might use `setSessionId()` to re-connect to an existing session whose ID you have retained. When you are finished, it is polite to `logout()` or you may want to use `getSessionId()` to retain a session ID for future use.

Having obtained a session you can use any of the following methods: `get()`, `create()`, `update()`, `delete()`, `batch()`, or the assert versions.

### new()

_Initialise the Workbooks API_

Example:
<pre><code>
    require_once 'workbooks_api.php';
    
    $workbooks = new WorkbooksApi(array(
      'application_name'   => 'PHP test client',
      'user_agent'         => 'php_test_client/0.1',
      'api_key'            => 'xxxxx-xxxxx-xxxxx-xxxxx-xxxxx-xxxxx-xxxxx-xxxxx',
    )
</code></pre>

If you omit the api_key above you will instead need to use `login()` to establish a session and receive a cookie.

### login()

_Login to the service to set up a session_

This is not required if you have passed an api_key to the `new()` function. If you use a username and password to authenticate you may also need to pass a logical_database_id.

Example:
<pre><code>
    $login_params = array(
      'username' => 'user@example.com',
      'password' => 'passw0rd',
    );
    
    $login = $workbooks->login($login_params);
    
    if ($login['http_status'] == WorkbooksApi::HTTP_STATUS_FORBIDDEN && $login['response']['failure_reason'] == 'no_database_selection_made') {
      /*
       * Multiple databases are available, and we must choose one. 
       * A good UI might remember the previously-selected database or use $databases to present a list of databases for the user to choose from. 
       */
      $default_database_id = $login['response']['default_database_id'];
      $databases = $login['response']['databases'];

      /*
       * For this example we simply select the one which was the default when the user last logged in to the Workbooks user interface. This 
       * would not be correct for most API clients since the user's choice on any particular session should not necessarily change their choice 
       * for all of their API clients.
       */
      $login = $workbooks->login(array_merge($login_params, array('logical_database_id' => $default_database_id)));
    }
    
    if ($login['http_status'] <> WorkbooksApi::HTTP_STATUS_OK) {
      handle_login_failure();
    }
</code></pre>

### logout()

_Logout from the service_

This is not required if you have passed an api_key to the `new()` function. 

Example:
<pre><code>
    $logout = $workbooks->logout();
</code></pre>

## Interacting with Workbooks

### assertGet(), get()

_Get a list of objects, or show a single object_

Example:
<pre><code>
    $filter_limit_select = array(
      '_start'               => '0',                                     // Starting from the 'zeroth' record
      '_limit'               => '100',                                   //   fetch up to 100 records
      '_sort'                => 'id',                                    // Sort by 'id'
      '_dir'                 => 'ASC',                                   //   in ascending order
      '_ff[]'                => 'main_location[county_province_state]',  // Filter by this column
      '_ft[]'                => 'ct',                                    //   containing
      '_fc[]'                => 'Berkshire',                             //   'Berkshire'
      '_select_columns[]'    => array(                                   // An array, of columns to select
        'id',
        'lock_version',
        'name',
        'main_location[town]',
        'updated_by_user[person_name]',
      )
    );
    $response = $workbooks->assertGet('crm/organisations', $filter_limit_select);
    // or: $response = $workbooks->get('crm/organisations', $filter_limit_select);
</code></pre>

### assertCreate(), create()

_Create one or more objects_

Example, creating a single organisation:
<pre><code>
    $create_one_organisation = array(
      'name'                                 => 'Birkbeck Burgers',
      'industry'                             => 'Food',
      'main_location[country]'               => 'United Kingdom',
      'main_location[county_province_state]' => 'Oxfordshire',
      'main_location[town]'                  => 'Oxford',
    );
    $response = $workbooks->assertCreate('crm/organisations', $create_one_organisation);
    // or: $response = $workbooks->create('crm/organisations', $create_one_organisation);
</code></pre>

Or create several:
<pre><code>
    $create_three_organisations = array(
      array (
        'name'                                 => 'Freedom & Light Ltd',
        'created_through_reference'            => '12345',
        'industry'                             => 'Media & Entertainment',
        'main_location[country]'               => 'United Kingdom',
        'main_location[county_province_state]' => 'Berkshire',
        'main_location[fax]'                   => '0234 567890',
        'main_location[postcode]'              => 'RG99 9RG',
        'main_location[street_address]'        => '100 Main Street',
        'main_location[telephone]'             => '0123 456789',
        'main_location[town]'                  => 'Beading',
        'no_phone_soliciting'                  => true,
        'no_post_soliciting'                   => true,
        'organisation_annual_revenue'          => '10000000',
        'organisation_category'                => 'Marketing Agency',
        'organisation_company_number'          => '12345678',
        'organisation_num_employees'           => 250,
        'organisation_vat_number'              => 'GB123456',
        'website'                              => 'www.freedomandlight.com',    
      ),
      array (
        'name'                                 => 'Freedom Power Tools Limited',
        'created_through_reference'            => '12346',
      ),
      array (
        'name'                                 => 'Freedom o\' the Seas Recruitment',
        'created_through_reference'            => '12347',
      ),
    );

    $response = $workbooks->assertCreate('crm/organisations', $create_three_organisations);
    // or: $response = $workbooks->create('crm/organisations', $create_three_organisations);
</code></pre>

### assertUpdate(), update()

_Update one or more objects_

Example:
<pre><code>
    $update_three_organisations = array(
      array (
        'id'                                   => $object_id_lock_versions[0]['id'],
        'lock_version'                         => $object_id_lock_versions[0]['lock_version'],
        'name'                                 => 'Freedom & Light Unlimited',
        'main_location[postcode]'              => 'RG66 6RG',
        'main_location[street_address]'        => '199 High Street',
      ),
      array (
        'id'                                   => $object_id_lock_versions[1]['id'],
        'lock_version'                         => $object_id_lock_versions[1]['lock_version'],
        'name'                                 => 'Freedom Power',
      ),
      array (
        'id'                                   => $object_id_lock_versions[2]['id'],
        'lock_version'                         => $object_id_lock_versions[2]['lock_version'],
        'name'                                 => 'Sea Recruitment',
      ),
    );

    $response = $workbooks->assertUpdate('crm/organisations', $update_three_organisations);
    // or: $response = $workbooks->update('crm/organisations', $update_three_organisations);
</code></pre>

### assertDelete(), delete()

_Delete one or more objects_

Example:
<pre><code>
    $object_id_lock_versions = array(
      array (
        'id'                                   => $object_id_lock_versions[0]['id'],
        'lock_version'                         => $object_id_lock_versions[0]['lock_version'],
      )
    );
    $response = $workbooks->assertDelete('crm/organisations', $object_id_lock_versions);
    // or: $response = $workbooks->delete('crm/organisations', $object_id_lock_versions);
</code></pre>

### assertBatch(), batch()

_Create, update, and delete several objects together_

Example:
<pre><code>
    $batch_organisations = array(
      array (
        'method'                               => 'CREATE',
        'name'                                 => 'Abercrombie Pies',
        'industry'                             => 'Food',
        'main_location[country]'               => 'United Kingdom',
        'main_location[county_province_state]' => 'Berkshire',
        'main_location[town]'                  => 'Beading',
      ),
      array (
        'method'                               => 'UPDATE',
        'id'                                   => $object_id_lock_versions[0]['id'],
        'lock_version'                         => $object_id_lock_versions[0]['lock_version'],
        'name'                                 => 'Lights \'R Us',
        'main_location[postcode]'              => NULL,   # Clear the postcode.
      ),
      array (
        'method'                               => 'DELETE',
        'id'                                   => $object_id_lock_versions[1]['id'],
        'lock_version'                         => $object_id_lock_versions[1]['lock_version'],
      ),
      array (
        'method'                               => 'DELETE',
        'id'                                   => $object_id_lock_versions[2]['id'],
        'lock_version'                         => $object_id_lock_versions[2]['lock_version'],
      ),
    );

    $response = $workbooks->assertBatch('crm/organisations', $batch_organisations);
    // or: $response = $workbooks->batch('crm/organisations', $batch_organisations);
</code></pre>

### idVersion()

_Extract ID and LockVersion from response_

You need the ID and LockVersion in order to manipulate records. `idVersion()` is often done after an update or create operation.
Example:
<pre><code>
    // Apply some changes
    $response = $workbooks->assertBatch('crm/organisations', $batch_organisations);
    $object_id_lock_versions = $workbooks->idVersions($response);
    // Delete all those records!
    $response = $workbooks->assertDelete('crm/organisations', $object_id_lock_versions);
</code></pre>

### log()

_Write log records_

Workbooks has a comprehensive logging facility. API requests to the service and responses from the service are automatically logged for scripts running under the process engine.

The `log()` method can be called with up to three parameters. All but the first are optional. The first parameter is a string to label the log record. The second parameter is data (e.g. an array, string or other data structure) which is dumped using `var_export()`. The third parameter is a log level; log levels include 'error', 'warning', 'notice', 'info', 'debug' (the default), and 'output' (which is rarely used).

The last item that a Process logs or outputs is used as the summary of a process within the Automation section of the Workbooks Desktop.
Examples:
<pre><code>
    $workbooks->log(__FUNCTION__);
    $workbooks->log("Invoked", array($params, $form_fields), 'info');
    $workbooks->log('Fetched a data item', $response['data']);
    $workbooks->log('Bad response for non-existent item', array($status, $response), 'error');
</code></pre>

### header()

_Send a header_

If your script is running under the Workbooks Process Engine as a Web Process then you can send headers before the start of output (which becomes the HTTP body). To send a header, use the `header()` method; be careful to send _nothing_ before sending a header - examine your use of php tags and whitespace carefully.
Examples:
<pre><code>
    $workbooks->header('Set-Cookie:wibble=wobble; path=/');
    $workbooks->header('X-Customer-Defined-Header: 0123456789');
</code></pre>

### output()

_Send output_

This is typically used if your script is running under the Workbooks Process Engine as a Web Process. Use it to write to the HTTP body of your web response.
Examples:
<pre><code>
    $workbooks->output('Hello ' . $params['_workbooks_username']);
</code></pre>


## Further Information

The API is documented at <a href="http://www.workbooks.com/api" target="_blank">http://www.workbooks.com/api</a>.

## Requirements

This binding uses CURL and JSON PHP extensions. It should work on PHP 5.2 or later; it has been tested using PHP 5.2.4 on Ubuntu 8.04 and PHP 5.3.2 on Mac OS X 10.6.4 and Ubuntu 10.04.

## License

Licensed under the MIT License

> The MIT License
> 
> Copyright (c) 2008-2012, Workbooks Online Limited.
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
