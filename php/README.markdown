# PHP language binding for the Workbooks API

See `simple_example.php` for some simple usage examples and explore the objects returned by the API. The comments in the `workbooks_api.php` file contain additional information to that here.

## Usage

The Workbooks API is session-based. 
Workbooks API scripts are either hosted externally to the Workbooks service ("external scripts"), or are hosted by the Workbooks Process Engine ("process engine scripts").  

External scripts need to establish a session manually whereas this is done automatically for process engine scripts.

## Running under the Workbooks Process Engine

The Process Engine will automatically create a login session for your script so you can skip the description of `new()`, `login()` and `logout()` below.
Your script will be invoked from time to time by the service, typically through a user action or according to a schedule.
Your script is passed the variable `$wb` which is an object representing a valid Workbooks session. 
Using $wb you can call methods such as `get()`, `create()`, `update()`, `delete()`, `batch()`.

## External Script Usage

Session IDs are transferred in cookies. Once you have a new `WorkbooksAPI` object you will typically `login()` to create a new session, although you might use `setSessionId()` to re-connect to an existing session whose ID you have retained. When you are finished, it is polite to `logout()` or you may want to use `getSessionId()` to retain a session ID for future use.

Having obtained a session you can use any of the following methods: `get()`, `create()`, `update()`, `delete()`, `batch()`.

### new()

_Initialise the Workbooks API_

Example:
<code>
    require 'workbooks_api.php';
    
    $wb = new WorkbooksApi(array(
      'application_name'   => 'PHP test client',
      'user_agent'         => 'php_test_client/0.1'
    )
</code>

### login()

_Login to the service to set up a session_

Example:
<code>
    $login_params = array(
      'username' => 'user@example.com',
      'password' => 'passw0rd',
    );
    
    $login = $wb->login($login_params);
    
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
      $login = $wb->login(array_merge($login_params, array('logical_database_id' => $default_database_id)));
    }
    
    if ($login['http_status'] <> WorkbooksApi::HTTP_STATUS_OK) {
      handle_login_failure();
    }
</code>

### logout()

_Logout from the service_

Example:
<code>
    $logout = $wb->logout();
</code>

## Interacting with Workbooks

### get()

_Get a list of objects, or show a single object_

Example:
<code>
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
    $response = $wb->get('crm/organisations', $filter_limit_select);
</code>

### create()

_Create one or more objects_

Example, creating a single organisation:
<code>
    $create_one_organisation = array(
      'name'                                 => 'Birkbeck Burgers',
      'industry'                             => 'Food',
      'main_location[country]'               => 'United Kingdom',
      'main_location[county_province_state]' => 'Oxfordshire',
      'main_location[town]'                  => 'Oxford',
    );
    $response = $wb->create('crm/organisations', $create_one_organisation);
</code>

Or create several:
<code>
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

    $response = $wb->create('crm/organisations', $create_three_organisations);
</code>

### update()

_Update one or more objects_

Example:
<code>
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

    $response = $wb->update('crm/organisations', $update_three_organisations);
</code>

### delete()

_Delete one or more objects_

Example:
<code>
    $object_id_lock_versions = array(
      array (
        'id'                                   => $object_id_lock_versions[0]['id'],
        'lock_version'                         => $object_id_lock_versions[0]['lock_version'],
      )
    );
    $response = $wb->delete('crm/organisations', $object_id_lock_versions);
</code>

### batch()

_Create, update, and delete several objects together_

Example:
<code>
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

    $response = $wb->batch('crm/organisations', $batch_organisations);
</code>

## Further Information

The API is documented at <a href="http://www.workbooks.com/api" target="_blank">http://www.workbooks.com/api</a>.

## Requirements

This binding uses CURL and JSON PHP extensions. It should work on PHP 5.2 or later; it has been tested using PHP 5.2.4 on Ubuntu 8.04 and PHP 5.3.2 on Mac OS X 10.6.4 and Ubuntu 10.04.

## License

Licensed under the MIT License

> The MIT License
> 
> Copyright (c) 2008-2011, Workbooks Online Limited.
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
