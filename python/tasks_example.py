#!/usr/bin/env python3

# A demonstration of using the Workbooks API to operate on Meetings via a thin Python wrapper.

# Last commit $Id: tasks_example.py 60951 2024-01-03 18:13:10Z jkay $
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
from datetime import datetime

# Create a Workbooks API instance
workbooks = WorkbooksApiTestLogin().workbooks

# Creating a PowerPoint task
create_powerpoint_task = {
    'name': 'Make PowerPoint',
    'start': '2023-06-26',
    'finish': '2023-06-30',
    'due': '2023-07-03',
    'all_day': True
}
create_powerpoint_task_response = workbooks.assert_create('activity/tasks', create_powerpoint_task)
workbooks.log('Created the PowerPoint Task:', create_powerpoint_task_response)

# Creating a research task
create_research_task = {
    'name': 'Complete research',
    'start': '2023-06-26T10:00:00.000Z',
    'finish': '2023-06-26T12:00:00.000Z',
    'due': '2023-06-26T15:00:00.000Z'
}
create_research_task_response = workbooks.assert_create('activity/tasks', create_research_task)
workbooks.log('Created the research Task:', create_research_task_response)

# Updating the research task
research_task = create_research_task_response.id_versions()[0]
update_research_task = {
    'id': research_task['id'],
    'lock_version': research_task['lock_version'],
    'started': '2023-06-26T10:30:00.000Z',
    'completed': '2023-06-26T16:00:00.000Z',
    'activity_status': 'Complete'
}
update_research_task_response = workbooks.assert_update('activity/tasks', update_research_task)
workbooks.log('Updated the research Task:', update_research_task_response)

# Updating the PowerPoint task
powerpoint_task = create_powerpoint_task_response.id_versions()[0]
update_powerpoint_task = {
    'id': powerpoint_task['id'],
    'lock_version': powerpoint_task['lock_version'],
    'completed': '2023-06-30',
    'activity_status': 'Complete'
}
update_powerpoint_task_response = workbooks.assert_update('activity/tasks', update_powerpoint_task)
workbooks.log('Updated the PowerPoint Task:', update_powerpoint_task_response)

# Fetching tasks
filter_limit_select = {
    '_start': 0,
    '_limit': 100,
    '_sort': 'id',
    '_dir': 'ASC',
    '_ff[]': 'created_through',
    '_ft[]': 'eq',
    '_fc[]': 'python_test_client',
    '_select_columns[]': ['id', 'lock_version', 'name', 'start', 'finish', 'due', 'duration', 'started', 'completed', 'scheduled_start', 'scheduled_finish']
}
get_tasks_response = workbooks.assert_get('activity/tasks', filter_limit_select)
workbooks.log('Fetched Tasks:', get_tasks_response['data'])

# Deleting tasks
delete_tasks = [{'id': item['id'], 'lock_version': item['lock_version']} for item in get_tasks_response['data']]
delete_tasks_response = workbooks.assert_delete('activity/tasks', delete_tasks)
workbooks.log('Deleted Tasks:', delete_tasks_response)

workbooks.logout()

workbooks.log('Passed', __file__)
