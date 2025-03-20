#!/usr/bin/env python3

#  Processes often need to store their state between runs. The 'API Data' facility provides
#  a simple way to do this.
#
# A demonstration of using the Workbooks API to manipulate API Data via a thin Python wrapper
#
# Last commit $Id: api_data_example.py 60951 2024-01-03 18:13:10Z jkay $
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

# Create API Data items
test_values = {
    'the answer': 42,
    'poppins': 'Supercalifragilisticexpealidocious',
    'null': None,
    'hundred thousand characters': '123456789 ' * 10000,
    'multibyte_characters': 'д е ё ж з и й к л  字 字',
}

create_api_data = [
    {'key': 'api_data_example: the answer', 'value': test_values['the answer']},
    {'key': 'api_data_example: poppins', 'value': test_values['poppins']},
    {'key': 'api_data_example: null', 'value': test_values['null']},
    {'key': 'api_data_example: hundred thousand characters', 'value': test_values['hundred thousand characters']},
    {'key': 'api_data_example: multibyte characters', 'value': test_values['multibyte_characters']},
]

# Make the request for creating API data items
response = workbooks.assert_create('automation/api_data', create_api_data)
object_id_lock_versions = response.id_versions()

# Update API Data items
update_api_data = [
    {'id': object_id_lock_versions[0]['id'], 'lock_version': object_id_lock_versions[0]['lock_version'], 'value': 43},
    {'id': object_id_lock_versions[2]['id'], 'lock_version': object_id_lock_versions[2]['lock_version'], 'value': 'null points'},
]

# Make the request for updating API data items
response = workbooks.assert_update('automation/api_data', update_api_data)
object_id_lock_versions = response.id_versions()

# Fetch API Data items
get_api_data = {
    '_sort': 'id',
    '_dir': 'ASC',
    '_fm': 'or',
    '_ff[]': ['key'] * 4,
    '_ft[]': ['eq'] * 4,
    '_fc[]': [
        'api_data_example: the answer',
        'api_data_example: null',
        'api_data_example: hundred thousand characters',
        'api_data_example: multibyte characters',
    ],
}

# Make the request for fetching API data items
response = workbooks.assert_get('automation/api_data', get_api_data)
workbooks.log('Fetched data', response['data'])

# Fetch a single item using the alternate filter syntax
response = workbooks.assert_get('automation/api_data', {'_filter_json': '[["key", "eq", "api_data_example: poppins"]]'})
workbooks.log('Fetched a data item', response['data'])

# Attempt to fetch an item which does not exist
get_non_existent_api_data = {
    '_ff[]': 'key',
    '_ft[]': 'eq',
    '_fc[]': 'api_data_example: no such record exists',
}

# Make the request for fetching a non-existent API data item
response = workbooks.get('automation/api_data', get_non_existent_api_data)
if not response['total'] == 0:
    workbooks.log('Bad response for non-existent item', response)
    exit(1)
workbooks.log('Response for non-existent item', response)

# Delete all data items visible to this user
response = workbooks.assert_get('automation/api_data', {'_select_columns[]': ['id', 'lock_version']})
workbooks.log('Items to delete', response['data'])

# Make the request for deleting API data items
response = workbooks.assert_delete('automation/api_data', response['data'])

# Logout from the session
workbooks.logout()

workbooks.log('Passed', __file__)
