#!/usr/bin/env python3

# A demonstration of using the Workbooks API via a thin Python wrapper to CRUD notes
#
# Last commit $Id: note_example.py 60951 2024-01-03 18:13:10Z jkay $
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

# Create a single organisation
create_one_organisation = {
    'name': 'Nogo Supermarkets',
    'industry': 'Food',
    'main_location[country]': 'United Kingdom',
    'main_location[county_province_state]': 'Oxfordshire',
    'main_location[town]': 'Oxford',
}
response = workbooks.assert_create('crm/organisations', create_one_organisation)
created_organisation_id = response.affected_objects()[0]['id']
created_organisation_lock_version = response['affected_objects'][0]['lock_version']

# Create three Notes associated with that organisation
html = """
<p>This is the body of note number 2<p/>
<a href="http://www.workbooks.com/" target="_blank">Workbooks.com</a>
"""

utf8md4_subject = 'Note number 4 🎂 with cake'
utf8md4_text = 'This is the body of note number 4, it has cake 🎂, a tree 🌲 and a snowman ⛄'

create_notes = [
    {
        'resource_id': created_organisation_id,
        'resource_type': 'Private::Crm::Organisation',
        'subject': 'Note number 1',
        'text': 'This is the body of note number 1'
    },
    {
        'resource_id': created_organisation_id,
        'resource_type': 'Private::Crm::Organisation',
        'subject': 'Note number 2',
        'text': html  # Text on notes can render html
    },
    {
        'resource_id': created_organisation_id,
        'resource_type': 'Private::Crm::Organisation',
        'subject': 'Note number 3',
        'text': 'This is the body of note number 3'
    },
    {
        'resource_id': created_organisation_id,
        'resource_type': 'Private::Crm::Organisation',
        'subject': utf8md4_subject,
        'text': utf8md4_text
    },
]
response = workbooks.assert_create('notes.api', create_notes)
note_1 = response['affected_objects'][0]
note_2 = response['affected_objects'][1]
note_3 = response['affected_objects'][2]
note_4 = response['affected_objects'][3]

# Update the first Note associated with that organisation
update_note_1 = {
    'id': note_1['id'],
    'lock_version': note_1['lock_version'],
    'text': note_1['text'] + "\n Here is a new line on note number 1",
}
workbooks.assert_update('notes.api', update_note_1)

# Delete the 3rd Note associated with that organisation
deletion_array = {'id': note_3['id'], 'lock_version': note_3['lock_version']}
workbooks.assert_delete('notes.api', deletion_array)

# List the Notes we have left
filter_limit_select = {
    '_start': '0',
    '_limit': '100',
    '_sort': 'id',
    '_dir': 'ASC',
    '_ff[]': 'resource_id',
    '_ft[]': 'eq',
    '_fc[]': created_organisation_id,
    '_select_columns[]': [
        'id',
        'lock_version',
        'subject',
        'text',
    ]
}
response = workbooks.assert_get('notes.api', filter_limit_select)
# print('Fetched objects', response['data'])

# Delete the created organisation
delete_organisation = {'id': created_organisation_id, 'lock_version': created_organisation_lock_version}
workbooks.assert_delete('crm/organisations', delete_organisation)

workbooks.log('Passed', __file__)
