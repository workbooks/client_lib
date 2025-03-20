#
#  A demonstration of using the Workbooks API via a thin Ruby wrapper
#
#
#  Last commit $Id: note_example.rb 57318 2023-02-09 15:55:19Z kswift $
#  License: www.workbooks.com/mit_license
#

require './workbooks_api.rb'
require './test_login_helper.rb'

workbooks = WorkbooksApiTestLoginHelper.new.workbooks

#
# We now have a valid logged-in session. This script does a series of 'CRUD' (Create, Read, Update, Delete) operations.
#

# Create a single organisation
organisation = {
  'name' => 'Nogo Supermarkets',
  'industry' => 'Food',
  'main_location[country]' => 'United Kingdom',
  'main_location[county_province_state]' => 'Oxfordshire',
  'main_location[town]' => 'Oxford',
}

response = workbooks.assert_create('crm/organisations', organisation)
object_id_lock_versions = response.id_versions

created_organisation_id = object_id_lock_versions[0]['id']
created_organisation_lock_version = object_id_lock_versions[0]['lock_version'],


html = <<-EOF
<p>This is the body of note number 2<p/>
<a href="http://www.workbooks.com/" target="_blank">Workbooks.com</a>
EOF

# Create Notes associated with that organisation
#
notes = [{
  'resource_id'   => created_organisation_id,
  'resource_type' => 'Private::Crm::Organisation',
  'subject'   => 'Note number 1',
  'text'      => 'This is the body of note number 1'
},{
  'resource_id'   => created_organisation_id,
  'resource_type' => 'Private::Crm::Organisation',
  'subject'   => 'Note number 2',
  'text'      => html # Text on notes can render html
},{
  'resource_id'   => created_organisation_id,
  'resource_type' => 'Private::Crm::Organisation',
  'subject'   => 'Note number 3',
  'text'      => 'This is the body of note number 3',
},{
  'resource_id'   => created_organisation_id,
  'resource_type' => 'Private::Crm::Organisation',
  'subject'   => 'Note number 4 🎂 with cake',
  'text'      => 'This is the body of note number 4, it has cake 🎂, a tree 🌲 and a snowman ⛄'
}]

response = workbooks.assert_create('notes.api', notes)
note_1 = response.affected_objects[0]
note_2 = response.affected_objects[1]
note_3 = response.affected_objects[2]
note_4 = response.affected_objects[3]

# Update the first Note associated with that organisation
update_note_1 = {
  'id'      => note_1['id'],
  'lock_version'  => note_1['lock_version'],
  'text'      => note_1['text'] + "\n Here is a new line on note number 1",
  }

response = workbooks.assert_update('notes.api', update_note_1)

# Delete the 3rd Note associated with that organisation

delete_note = {
  'id' => note_3['id'],
  'lock_version' => note_3['lock_version']
  }

response = workbooks.assert_delete('notes.api', delete_note)


# List the Notes we have left
#
filter_limit_select = {
 :_start => '0',     # Starting from the 'zeroth' record
 :_limit => '100',   # fetch up to 100 records
 :_sort => 'id',     # Sort by 'id'
 :_dir => 'ASC',     # in ascending order
 '_filters[]'           => [ 'resource_id', 'eq', created_organisation_id ],
 '_select_columns[]'    => [ # An array, of columns to select
    'id',
    'lock_version',
    'subject',
    'text',
  ]
}

response = workbooks.assert_get('notes.api', filter_limit_select)
workbooks.log('Fetched objects', response.data)

# Delete the created organisation
# Doing this will also delete any associated notes

response = workbooks.assert_delete('crm/organisations', { 
 'id' => created_organisation_id,
 'lock_version' => created_organisation_lock_version
 })


workbooks.logout
exit(0)
