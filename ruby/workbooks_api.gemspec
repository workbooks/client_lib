Gem::Specification.new do |s|
  s.name        = 'workbooks_api'
  s.version     = '1.0.1'
  s.date        = '2023-05-05'
  s.summary     = "A ruby wrapper for the Workbooks API"
  s.authors     = ["Workbooks"]
  s.email       = 'support@workbooks.com'
  s.files       = ["workbooks_api.rb"]
  s.homepage    =
    'https://github.com/workbooks/client_lib/tree/master/ruby'
  s.license       = 'MIT'
  s.description = %{
# Ruby language binding for the Workbooks API

See the ruby code here in github for simple usage examples to explore the objects returned by the API. The comments in the `workbooks_api.rb` file contain additional information to that here.

## Usage

External scripts can authenticate using an API Key or a username and password. In the examples included here authentication is done in `test_login_helper.rb` using an API Key: just pass the `:api_key` parameter when you create a new `WorkbooksApi` object.

### Using login() and logout()

An alternative is to use a username and password to establish a session with the Workbooks service: pass them to the `login()` call. Sessions can be reconnected using an existing session whose ID you have retained. When you are finished, it is polite to `logout()` or you may want to retain a session ID for future use.

Having obtained a session you can use any of the following methods: `get()`, `create()`, `update()`, `delete()`, `batch()`, or the assert versions.

### new()

_Initialise the Workbooks API_

Example:
<pre><code>
    require 'workbooks_api.rb'
    
    logger = Logger.new(STDOUT)

    workbooks = WorkbooksApi.new(
      :application_name => 'ruby_test_client',                      # Please give your application a useful name
      :user_agent => 'ruby_test_client/0.1',                        # Please give your application a useful label
      :api_key => '01234-56789-01234-56789-01234-56789-01234-56789',
      :logger => logger                                             # Omit this for silence from the binding
   )
</code></pre>

If you omit the api_key above you will instead need to use `login()` to establish a session.

### login()

_Login to the service to set up a session_

This is not required if you have passed an api_key to the `new()` function. If you use a username and password to authenticate you may also need to pass a logical_database_id. If you omit a logical_database_id and one is required an error is returned which you can detect and use to prompt the user to select a database.

Example:
<pre><code>
  workbooks = WorkbooksApiTestLoginHelper.new.workbooks

  login_params = {
    :username => 'user@example.com',
    :password => 'passw0rd',
  # :logical_database_id => 3,
  }
</code></pre>

### logout()

_Logout from the service_

Example:
<pre><code>
    workbooks.logout
</code></pre>

## Interacting with Workbooks

Options are passed as hash elements with the key being a symbol, and objects are passed as arrays of hashes. The ruby API will normally accept either a symbol or a string as a hash key. Exceptions are raised as instances of `WorkbooksApiException`, or responses are returned as instances of `WorkbooksApiResponse`.

### assert_get(), get()

_Get a list of objects, or show a single object_

Example:
<pre><code>
  filter_limit_select = {
    :_start               => '0',                                     # Starting from the 'zeroth' record
    :_limit               => '100',                                   #   fetch up to 100 records
    :_sort                => 'id',                                    # Sort by 'id'
    :_dir                 => 'ASC',                                   #   in ascending order
    '_filters[]'           => ['main_location[county_province_state]', 'bg', 'Berkshire'], # 'begins with'
    '_select_columns[]'    => [                                       # An array, of columns to select
      'id',
      'lock_version',
      'name',
      'main_location[town]',
      'updated_by_user[person_name]',
    ]
  }
  response = workbooks.assert_get('crm/organisations', filter_limit_select)
  workbooks.log('Fetched objects', response.data)
</code></pre>

### assert_create(), create()

_Create one or more objects_

Example, creating a single organisation:
<pre><code>
  create_one_organisation = {
    :method                                => :create,
    'name'                                 => 'Birkbeck Burgers',
    'industry'                             => 'Food',
    'main_location[country]'               => 'United Kingdom',
    'main_location[county_province_state]' => 'Oxfordshire',
    'main_location[town]'                  => 'Oxford',
  }
  response = workbooks.assert_create('crm/organisations', create_one_organisation)
  created_id_lock_versions = response.id_versions
</code></pre>

Or create several:
<pre><code>
  create_three_organisations = [{
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
  },{
    'name'                                 => 'Freedom Power Tools Limited',
    'created_through_reference'            => '12346',
  },{
    'name'                                 => 'Freedom o\' the Seas Recruitment',
    'created_through_reference'            => '12347',
  }]

  response = workbooks.assert_create('crm/organisations', create_three_organisations)
  object_id_lock_versions = response.id_versions
</code></pre>

### assert_update(), update()

_Update one or more objects_

Example:
<pre><code>
  update_three_organisations = [{
    'id'                                   => object_id_lock_versions[0]['id'],
    'lock_version'                         => object_id_lock_versions[0]['lock_version'],
    'name'                                 => 'Freedom & Light Unlimited',
    'main_location[postcode]'              => 'RG66 6RG',
    'main_location[street_address]'        => '199 High Street',
  },{
    'id'                                   => object_id_lock_versions[1]['id'],
    'lock_version'                         => object_id_lock_versions[1]['lock_version'],
    'name'                                 => 'Freedom Power',
  },{
    'id'                                   => object_id_lock_versions[2]['id'],
    'lock_version'                         => object_id_lock_versions[2]['lock_version'],
    'name'                                 => 'Sea Recruitment',
  }]

  response = workbooks.assert_update('crm/organisations', update_three_organisations)
  object_id_lock_versions = response.id_versions
</code></pre>

### assert_delete(), delete()

_Delete one or more objects_

Example:
<pre><code>
  response = workbooks.assert_get('automation/api_data', { '_select_columns[]' => ['id', 'lock_version'] })
  workbooks.log('Items to delete', response['data'])

  response = workbooks.assert_delete('automation/api_data', response['data'])
</code></pre>

### assert_batch(), batch()

_Create, update, and delete several objects together_

Example:
<pre><code>
  batch_organisations = [{
    :method                                => :create,
    'name'                                 => 'Abercrombie Pies',
    'industry'                             => 'Food',
    'main_location[country]'               => 'United Kingdom',
    'main_location[county_province_state]' => 'Berkshire',
    'main_location[town]'                  => 'Beading',
  },{
    :method                                => :update,
    'id'                                   => object_id_lock_versions[0]['id'],
    'lock_version'                         => object_id_lock_versions[0]['lock_version'],
    'name'                                 => 'Lights \'R Us',
    'main_location[postcode]'              => nil,   # Clear the postcode.
  },{
    :method                                => :delete,
    'id'                                   => object_id_lock_versions[1]['id'],
    'lock_version'                         => object_id_lock_versions[1]['lock_version'],
  },{
    :method                                => :delete,
    'id'                                   => object_id_lock_versions[2]['id'],
    'lock_version'                         => object_id_lock_versions[2]['lock_version'],
  }]

  response = workbooks.assert_batch('crm/organisations', batch_organisations)
  object_id_lock_versions = response.id_versions
</code></pre>

### id_versions()

_Extract ID and LockVersion from response_

You need the ID and LockVersion in order to manipulate records. `id_versions()` is often done after an update or create operation. See the 'delete' example above.

### log()

_Write log records_

This is a simple wrapper around `Logger`.

The `log()` method can be called with up to four parameters. All but the first are optional. The first parameter is a string to label the log record. The second parameter is data (e.g. an array, string or other data structure) which is dumped. The third parameter is a log level; log levels include `:error`, `:warning`, `:notice`, `:info` and `:debug` (the default).

Examples:
<pre><code>
    workbooks.log('Got here')
    workbooks.log("Invoked", [params, form_fields], :info)
    workbooks.log('Fetched a data item', response['data'])
    workbooks.log('Bad response for non-existent item', [status, response], :error)
</code></pre>

## Further Information

The API is documented at <a href="http://www.workbooks.com/api" target="_blank">http://www.workbooks.com/api</a>.

## Requirements

For most systems the requirements will already be present with a standard Ruby installation. You may need to obtain a JSON 
parser if you are running Ruby prior to version 1.9.

This binding has been tested on ruby 1.8.7 (Ubuntu 12.04 LTS), ruby 2.0.0 (Mac OS X 10.9.3) and ruby 3.2.0 (Mac OS 13.2.1)

## Support

Please contact <a href="mailto:support@workbooks.com">support@workbooks.com</a>. Enhancement suggestions should be logged on the Workbooks ideas forum at <a href="http://ideas.workbooks.com" target="_blank">http://ideas.workbooks.com</a>.

}
end
