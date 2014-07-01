#
#  Processes often need to store their state between runs. The 'API Data' facility provides
#  a simple way to do this.
#
#  A demonstration of using the Workbooks API to manipulate API Data via a thin Ruby wrapper
#
#
#  Last commit $Id: api_data_example.rb 22501 2014-07-01 12:17:25Z jkay $
#  License: www.workbooks.com/mit_license
#

require './workbooks_api.rb'
require './test_login_helper.rb'

workbooks = WorkbooksApiTestLoginHelper.new.workbooks

#
# We now have a valid logged-in session. This script does a series of 'CRUD' (Create, Read, Update, Delete) operations.
#

#
# Create API Data items
#
test_values = {
  'the answer'              => 42,
  'poppins'                 => 'Supercalifragilisticexpealidocious',
  'null'                    => nil,
  'hundred thousand characters' => '123456789 ' * 10000,
  'multibyte_characters'    => 'д е ё ж з и й к л  字 字',
}

create_api_data = [
  { 'key' => 'api_data_example: the answer',                  'value' => test_values['the answer'] },
  { 'key' => 'api_data_example: poppins',                     'value' => test_values['poppins'] },
  { 'key' => 'api_data_example: null',                        'value' => test_values['null'] },
  { 'key' => 'api_data_example: hundred thousand characters', 'value' => test_values['hundred thousand characters'] },
  { 'key' => 'api_data_example: multibyte characters',        'value' => test_values['multibyte_characters'] },
]
response = workbooks.assert_create('automation/api_data', create_api_data)
object_id_lock_versions = workbooks.id_versions(response)

#
# Update a couple of those API Data items
#
update_api_data = [
  {
    'id'                                   => object_id_lock_versions[0]['id'],
    'lock_version'                         => object_id_lock_versions[0]['lock_version'],
    'value'                                => 43,
  },{
    'id'                                   => object_id_lock_versions[2]['id'],
    'lock_version'                         => object_id_lock_versions[2]['lock_version'],
    'value'                                => 'null points',
  },
]
response = workbooks.assert_update('automation/api_data', update_api_data)
object_id_lock_versions = workbooks.id_versions(response)

#
# Fetch four of them back, all available fields
#
get_api_data = {
  :_sort                => 'id',                                    # Sort by 'id'
  :_dir                 => 'ASC',                                   #   in ascending order
  :_fm                  => 'or',
  '_ff[]'               => ['key'] * 4,
  '_ft[]'               => ['eq'] * 4,
  '_fc[]'               => [
    'api_data_example: the answer',               
    'api_data_example: null',                     
    'api_data_example: hundred thousand characters',   
    'api_data_example: multibyte characters',     
  ],
}
response = workbooks.assert_get('automation/api_data', get_api_data)
workbooks.log('Fetched data', response['data'])

#
# Fetch a single item using the alternate filter syntax
#
response = workbooks.assert_get('automation/api_data', { :_filter_json => '[["key", "eq", "api_data_example: poppins"]]' })
workbooks.log('Fetched a data item', response['data'])

#
# Attempt to fetch an item which does not exist
#
get_non_existent_api_data = {
  '_ff[]'                => 'key',
  '_ft[]'                => 'eq',
  '_fc[]'                => 'api_data_example: no such record exists',
}
response = workbooks.get('automation/api_data', get_non_existent_api_data)
if !(response['total'].to_i == 0)
  workbooks.log('Bad response for non-existent item', response)
  exit(1)
end
workbooks.log('Response for non-existent item', response)

#
# Delete all data items which are visible to this user. 
#
response = workbooks.assert_get('automation/api_data', { '_select_columns[]' => ['id', 'lock_version'] })
workbooks.log('Items to delete', response['data'])

response = workbooks.assert_delete('automation/api_data', response['data'])

workbooks.logout
exit(0)
