# A demonstration of using the Workbooks API to operate on Tasks via a thin Ruby wrapper.
# Last commit $Id: tasks_example.rb 58935 2023-07-04 09:14:05Z hsurendralal $
#
#     The MIT License
#     Copyright (c) 2008-2023, Workbooks Online Limited.
#
#     Permission is hereby granted, free of charge, to any person obtaining a copy
#     of this software and associated documentation files (the "Software"), to deal
#     in the Software without restriction, including without limitation the rights
#     to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
#     copies of the Software, and to permit persons to whom the Software is
#     furnished to do so, subject to the following conditions:
#
#     The above copyright notice and this permission notice shall be included in
#     all copies or substantial portions of the Software.
#
#     THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
#     IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
#     FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
#     AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
#     LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
#     OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
#     THE SOFTWARE.

# If not running under the Workbooks Process Engine create a session
require './workbooks_api.rb'
require './test_login_helper.rb'

workbooks = WorkbooksApiTestLoginHelper.new.workbooks

# We now have a valid logged-in session. This script does a series of 'CRUD' (Create, Read, Update, Delete) operations.

# Let's say that today is 9:00 AM on Monday (26 Jun 2023) and we are delivering a presentation to our team next Monday
# (3 Jul 2023) for which we will need to make a PowerPoint. At the latest, we will need to finish making the PowerPoint
# by Friday (30 Jun 2023) but we're unsure of which days we are available to start making it.
#
# We can create the Task 'Make PowerPoint' that is due on 03/07/2023.
#
# As we are unsure of the exact times at which we can start and finish the Task, we have not included them. For this
# reason, the Task is considered an all-day Task, hence we must also pass this flag. Conversely, if we have inlcuded
# exact times, this flag must be false (or omitted as it is false by default). Otherwise, the time will be lost.
#
# With the key 'due', we must include a date and we can optionally include a time in the 24 hour format if we have one.
# Alternatively, we could have used the key 'due_date' instead of 'due' as it only accepts values in a date format.
# Similarly, we can use the key 'due_datetime' if we have a time to specify as well. This pattern extends to the keys:
#   - 'start',
#   - 'finish',
#   - 'started', and
#   - 'completed'.
#
# There are a couple of things to note in the logged response:
#   - The attributes 'start', 'finish', and 'due' are displayed in the format '2023-06-26,0,Europe/London'. If they
#     also specified a time, they would be displayed slightly differently - as shown in the next example.
#   - There are other attributes that have also been set; 'scheduled_start', 'scheduled_finish', and their '*_date' and
#     '*_datetime' counterparts. The 'scheduled_start*' attributes should be the same as the 'start*' attributes and
#     the 'scheduled_finish*' attributes should be the same as the 'finish*' attributes. If we had not set the 'start'
#     and 'finish' attributes, the 'scheduled_start*' and 'scheduled_finish*' attributes would match the 'due*'
#     attributes.
#   - The response should also document the 'duration' of the Task; the amount of time in seconds that the Task is
#     planned to take.
#   - The API handles all dates and times in UTC, but a user will see the times in their local timezone.
create_powerpoint_task = {
  'name' => 'Make PowerPoint',
  'start' => '2023-06-26',
  'finish' => '2023-06-30',
  'due' => '2023-07-03',
  'all_day' => true
}

create_powerpoint_task_response = workbooks.assert_create('activity/tasks', create_powerpoint_task)
powerpoint_task_object_id_lock_versions = create_powerpoint_task_response.id_versions
workbooks.log('Created the PowerPoint Task', create_powerpoint_task_response.data)

# Part of making the PowerPoint requires some research to be done. Our colleague is willing to review the research and
# provide feedback on topics they feel are missing or not required. They have informed us that they can start reviewing
# at 3:00 PM. Today, we have a meeting at 9:30 AM that usually finishes at 10:00 AM; so we are free to start the
# research after this meeting. Given that it is expected to take at least a couple of hours, we can expect the research
# to be completed by 12:00 PM.
# 
# We can create the Task 'Complete research' that is scheduled to start at 10:00 AM, finish at 12:00 PM, and is due at
# 3:00 PM.
# 
# As we have specified a time for 'start', 'finish', and 'due', the logged response will display them in the format
# '2023-06-26T09:00:00.000Z,1,Europe/London'.
create_research_task = {
  'name' => 'Complete research',
  'start' => '2023-06-26 10:00',
  'finish' => '2023-06-26 12:00',
  'due' => '2023-06-26 15:00'
}

create_research_task_response = workbooks.assert_create('activity/tasks', create_research_task)
research_task_object_id_lock_versions = create_research_task_response.id_versions
workbooks.log('Created the research Task', create_research_task_response.data)

# It is now 4:00 PM. Unfortunately, the meeting we had in the morning overran by half an hour so we started the
# research late at 10:30 AM. On top of this, we had an interruption which meant that we did not complete the research
# until 4:00 PM, meaning the task was overdue.
# 
# We can update the Task 'Complete research' to say that it started at 10:30 AM and finished at 4:00 PM.
update_research_task = {
  'id' => research_task_object_id_lock_versions[0]['id'],
  'lock_version' => research_task_object_id_lock_versions[0]['lock_version'],
  'started' => '2023-06-26 10:30',
  'completed' => '2023-06-26 16:00',
  'activity_status' => 'Complete'
}

update_research_task_response = workbooks.assert_update('activity/tasks', update_research_task)
research_task_object_id_lock_versions = update_research_task_response.id_versions
workbooks.log('Updated the research Task', update_research_task_response.data)

# Fast forward to Friday (30 Jun 2023). All of the pre-requisites for the PowerPoint and the PowerPoint itself have
# been completed.
# 
# We can update the Task 'Make PowerPoint' to say that it was completed on 30/06/2023.
update_powerpoint_task = {
  'id' => powerpoint_task_object_id_lock_versions[0]['id'],
  'lock_version' => powerpoint_task_object_id_lock_versions[0]['lock_version'],
  'completed' => '2023-06-30',
  'activity_status' => 'Complete'
}

update_powerpoint_task_response = workbooks.assert_update('activity/tasks', update_powerpoint_task)
powerpoint_task_object_id_lock_versions = update_powerpoint_task_response.id_versions
workbooks.log('Updated the PowerPoint Task', update_powerpoint_task_response.data)

# List up to the first 100 Tasks that match our 'created_through' value. Select a few columns for retrieval.
select_columns = [
  'id',
  'lock_version',
  'name',
  'start',
  'finish',
  'due',
  'duration',
  'started',
  'completed',
  'scheduled_start',
  'scheduled_finish'
]

filter_limit_select = {
  :_start                => 0,                                       # Starting from the 'zeroth' record
  :_limit                => 100,                                     #   fetch up to 100 records
  :_sort                 => 'id',                                    # Sort by 'id'
  :_dir                  => 'ASC',                                   #   in ascending order
  :'_ff[]'               => 'created_through',                       # Filter by this column
  :'_ft[]'               => 'eq',                                    #   equals
  :'_fc[]'               => 'ruby_test_client',                      #   this script
  :'_select_columns[]'   => select_columns                           # An array, of columns to select
}

get_tasks_response = workbooks.assert_get('activity/tasks', filter_limit_select)
workbooks.log('Fetched Tasks', get_tasks_response.data)

# Delete both Tasks.
delete_tasks = [
  {
    'id' => research_task_object_id_lock_versions[0]['id'],
    'lock_version' => research_task_object_id_lock_versions[0]['lock_version']
  },
  {
    'id' => powerpoint_task_object_id_lock_versions[0]['id'],
    'lock_version' => powerpoint_task_object_id_lock_versions[0]['lock_version']
  }
]

delete_tasks_response = workbooks.assert_delete('activity/tasks', delete_tasks)
workbooks.log('Deleted both Tasks', delete_tasks_response.data)

workbooks.logout
exit(0)

?>
