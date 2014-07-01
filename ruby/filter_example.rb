#
#  A demonstration of using the Workbooks API tto find records using "filters" via a thin Ruby wrapper
#
#
#  Last commit $Id: filter_example.rb 22501 2014-07-01 12:17:25Z jkay $
#  License: www.workbooks.com/mit_license
#

require './workbooks_api.rb'
require './test_login_helper.rb'

workbooks = WorkbooksApiTestLoginHelper.new.workbooks

#
# You can choose range (_start, _limit), sort order (_sort, _dir), column selection (_select_columns) and 
# a filter which can include boolean logic (and/or/not). The filter can have several possible structures
# which are equivalent.
#
 
# 
# Some basic options which are used in the following examples.
# 
# An array, of columns to select. Discover these in the API 'meta data' document, here:
#    http://www.workbooks.com/api-reference
# 
select_columns = [ 
  'id',
  'lock_version',
  'name',
  'object_ref',
  'updated_by_user[person_name]',
  'main_location[county_province_state]',
  'main_location[street_address]',
]

#
# Start/Limit, Sort/Direction, Column selection
#
limit_select = {
  :_skip_total_rows     => true,  # Omit the 'total number of qualifying entries' from the response: *significantly* faster.
  :_start               => 0,     # Starting from the 'zeroth' record (this is an offset)
  :_limit               => 5,     #   fetch up to 5 records
  :_sort                => 'id',  # Sort by 'id'
  :_dir                 => 'ASC', #   in ascending order (=> oldest record first)
  :'_select_columns[]'  => select_columns,
}

# First filter structure: specify arrays for Fields ('_ff[]'), comparaTors ('_ft[]'), Contents ('_fc[]').
# Note that 'ct' (contains) is MUCH slower than equals. 'not_blank' requires Contents to compare with, but this is ignored.
filter1 = limit_select.merge({
  '_ff[]' => ['main_location[county_province_state]', 'main_location[county_province_state]', 'main_location[street_address]'],
  '_ft[]' => ['eq',                                   'ct',                                   'not_blank'],
  '_fc[]' => ['Berkshire',                            'Yorkshire',                             ''],
  :_fm => '(1 OR 2) AND 3',                          # How to combine the above clauses; without this will do an 'AND'.
})
response1 = workbooks.assert_get('crm/organisations', filter1)
workbooks.log('Fetched objects using filter1', [$filter1, response1.data])

# The equivalent using a second filter structure: a JSON-formatted string  array of arrays containg 'field, comparator, contents'
filter2 = limit_select.merge({
  :_filter_json => '[' +
    '["main_location[county_province_state]", "eq", "Berkshire"],' +
    '["main_location[county_province_state]", "ct", "Yorkshire"],' +
    '["main_location[street_address]", "not_blank", ""]' +
    ']',
  :_fm => '(1 OR 2) AND 3',                            # How to combine the above clauses; without this will do an 'AND'.
})
response2 = workbooks.assert_get('crm/organisations', filter2)
workbooks.log('Fetched objects using filter2', [filter2, response2.data])

# The equivalent using a third filter structure: an array of filters, each containg 'field, comparator, contents'.
filter3 = limit_select.merge({
  '_filters[]'     => [
     ['main_location[county_province_state]', 'eq', 'Berkshire'],
     ['main_location[county_province_state]', 'ct', 'Yorkshire'],
     ['main_location[street_address]', 'not_blank', ''],
  ],
  :_fm => '(1 OR 2) AND 3',                            # How to combine the above clauses; without this will do an 'AND'.
})
response3 = workbooks.assert_get('crm/organisations', filter3)
workbooks.log('Fetched objects using filter3', [filter3, response3.data])

# Test that the responses are all the same.
unless response1.data.size == response2.data.size &&
       response1.data.size == response3.data.size &&
       (response1.data - response2.data).size == 0 &&
       (response1.data - response3.data).size == 0
  workbooks.log('The results retrieved through different filter syntaxes differ!')
  exit(1)
end

workbooks.logout
exit(0)

