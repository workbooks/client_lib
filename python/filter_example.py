#!/usr/bin/env python3

# A demonstration of using the Workbooks API to to find records using "filters" via a thin Python wrapper.

# Last commit $Id: filter_example.py 60951 2024-01-03 18:13:10Z jkay $
# Copyright (c) 2008-2023, Workbooks Online Limited.

# The MIT License
#
# Permission is hereby granted, free of charge, to any person obtaining a copy
# of this software and associated documentation files (the "Software"), to deal
# in the Software without restriction, including without limitation the rights
# to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
# copies of the Software, and to permit persons to whom the Software is
# furnished to do so, subject to the following conditions:
#
# The above copyright notice and this permission notice shall be included in
# all copies or substantial portions of the Software.
# 
# THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
# IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
# FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
# AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
# LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
# OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
# THE SOFTWARE.

from workbooks_api import WorkbooksApi
from workbooks_test_login import WorkbooksApiTestLogin

# Create a Workbooks API instance
workbooks = WorkbooksApiTestLogin().workbooks

select_columns = [ 
    'id',
    'lock_version',
    'name',
    'object_ref',
    'updated_by_user[person_name]',
    'main_location[county_province_state]',
    'main_location[street_address]',
]

limit_select = {
    '_skip_total_rows': True,
    '_start': 0,
    '_limit': 5,
    '_sort': 'id',
    '_dir': 'ASC',
    '_select_columns[]': select_columns,
}

filter1 = {
    '_ff[]': ['main_location[county_province_state]', 'main_location[county_province_state]', 'main_location[street_address]'],
    '_ft[]': ['eq', 'ct', 'not_blank'],
    '_fc[]': ['Berkshire', 'Yorkshire', ''],
    '_fm': '(1 OR 2) AND 3',
}
response1 = workbooks.assert_get('crm/organisations', {**limit_select, **filter1})
workbooks.log('Fetched objects using filter1', [filter1, response1['data']])

filter2 = {
    '_filter_json': '[["main_location[county_province_state]", "eq", "Berkshire"],' +
                    '["main_location[county_province_state]", "ct", "Yorkshire"],' +
                    '["main_location[street_address]", "not_blank", ""]]',
    '_fm': '(1 OR 2) AND 3',
}
response2 = workbooks.assert_get('crm/organisations', {**limit_select, **filter2})
workbooks.log('Fetched objects using filter2', [filter2, response2['data']])

filter3 = {
    '_filters[]': [
        ['main_location[county_province_state]', 'eq', 'Berkshire'],
        ['main_location[county_province_state]', 'ct', 'Yorkshire'],
        ['main_location[street_address]', 'not_blank', ''],
    ],
    '_fm': '(1 OR 2) AND 3',
}
response3 = workbooks.assert_get('crm/organisations', {**limit_select, **filter3})
workbooks.log('Fetched objects using filter3', [filter3, response3['data']])

if (len(response1['data']) == len(response2['data']) == len(response3['data']) and
    response1['data'] == response2['data'] == response3['data']):
    workbooks.log('The results retrieved through different filter syntaxes are the same!')
else:
    workbooks.log('The results retrieved through different filter syntaxes differ!')
    workbooks.log('Failed', __file__)
    exit(1)

workbooks.logout()

workbooks.log('Passed', __file__)
