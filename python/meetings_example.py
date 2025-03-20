#!/usr/bin/env python3

# A demonstration of using the Workbooks API to operate on Meetings via a thin Python wrapper.

# Last commit $Id: meetings_example.py 60951 2024-01-03 18:13:10Z jkay $
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

create_meeting = {
    'name': 'Discussion with colleague',
    'start': '2023-06-28 10:30',
    'finish': '2023-06-28 11:30'
}

create_meeting_response = workbooks.assert_create('activity/meetings', create_meeting)
meeting_object_id_lock_versions = create_meeting_response.id_versions()
workbooks.log('Created the Meeting', create_meeting_response['data'])

update_meeting = {
    'id': meeting_object_id_lock_versions[0]['id'],
    'lock_version': meeting_object_id_lock_versions[0]['lock_version'],
    'start': '2023-06-29',
    'finish': '2023-06-29',
    'all_day': True
}

update_meeting_response = workbooks.assert_update('activity/meetings', update_meeting)
meeting_object_id_lock_versions = update_meeting_response.id_versions()
workbooks.log('Updated the Meeting', update_meeting_response['data'])

select_columns = [
    'id',
    'lock_version',
    'name',
    'start',
    'finish',
    'due',
    'duration',
    'scheduled_start',
    'scheduled_finish'
]

filter_limit_select = {
    '_start': 0,
    '_limit': 100,
    '_sort': 'id',
    '_dir': 'ASC',
    '_ff[]': 'created_through',
    '_ft[]': 'eq',
    '_fc[]': 'python_test_client',
    '_select_columns[]': select_columns
}

get_meetings_response = workbooks.assert_get('activity/meetings', filter_limit_select)
workbooks.log('Fetched Meetings', get_meetings_response['data'])

delete_meeting = [
    {
        'id': meeting_object_id_lock_versions[0]['id'],
        'lock_version': meeting_object_id_lock_versions[0]['lock_version']
    }
]

delete_meeting_response = workbooks.assert_delete('activity/meetings', delete_meeting)
workbooks.log('Deleted the Meeting', delete_meeting_response['data'])

workbooks.logout()

workbooks.log('Passed', __file__)
