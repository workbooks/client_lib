#
#  A demonstration of using the Workbooks API via a thin Ruby wrapper
#
#
#  Last commit $Id: simple_example.rb 22501 2014-07-01 12:17:25Z jkay $
#  License: www.workbooks.com/mit_license
#

require './workbooks_api.rb'
require './test_login_helper.rb'

workbooks = WorkbooksApiTestLoginHelper.new.workbooks

#
# We now have a valid logged-in session. This script does a series of 'CRUD' (Create, Read, Update, Delete) operations.
#

#
# Create three organisations. Note that keys can be symbols or strings.
#
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

#
# Update those organisations
#
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

#
# Combined call to Create, Update and Delete several organisations
#
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

#
# Create a single organisation
#
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
object_id_lock_versions = object_id_lock_versions[0..1] + created_id_lock_versions

#
# List the first hundred organisations in Berkshire, just selecting a few columns to retrieve
#
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

#
# Delete the remaining organisations which were created in this script
#
response = workbooks.assert_delete('crm/organisations', object_id_lock_versions)

workbooks.logout
exit(0)
