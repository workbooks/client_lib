#
#  A demonstration of using the Workbooks API to upload and download files via a thin Ruby wrapper
#
#
#  Last commit $Id: upload_file_example.rb 22501 2014-07-01 12:17:25Z jkay $
#  License: www.workbooks.com/mit_license
#

require './workbooks_api.rb'
require './test_login_helper.rb'
require 'base64'
require 'tempfile'

workbooks = WorkbooksApiTestLoginHelper.new.workbooks

#
# We now have a valid logged-in session. This script does a series of 'CRUD' (Create, Read, Update, Delete) operations.
#
# Note that creating attachments is done using the 'resource_upload_files' endpoint, so that the caller can specify what 
# the file should be attached to; updating and deleting files is via the 'upload_files' endpoint.
#

#
# Create a single organisation, to which we will attach a note.
#
create_one_organisation = { 'name' => 'Test Organisation' }
organisation_id_lock_versions = workbooks.id_versions(workbooks.assert_create('crm/organisations', create_one_organisation))

#
# Create a note associated with that organisation, to which we will attach files.
#
create_note = {
  'resource_id' => organisation_id_lock_versions[0]['id'],
  'resource_type' => 'Private::Crm::Organisation',
  'subject' => 'Test Note',
  'text' => 'This is the body of the test note. It is <i>HTML</i>.',
}
note_id_lock_versions = workbooks.id_versions(workbooks.assert_create('notes', create_note))
note_id = note_id_lock_versions[0]['id']

#
# A variety of simple test files which get uploaded. To keep this example simple we create a series of files
# on disk, then pass handles to those files to the Workbooks API binding.
#
files = [
  {
    :name => 'smallest.png',
    :type => 'image/png', 
    :data => Base64.decode64('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAACklEQVR4nGMAAQAABQABDQottAAAAABJRU5ErkJggg=='),
  }, {
    :name => 'four_nulls.txt',
    :type => 'text/plain',
    :data => "\x00\x00\x00\x00",
  }, {
    :name => 'file.htm',
    :type => 'text/html', 
    :data => '<b>A small fragment of HTML</b>',
  }, {
    :name => "<OK> O'Reilly & Partners",
    :type => 'text/plain',
    :data => "'象形字 指事字 会意字 / 會意字  假借字 形声字 / 形聲字 xíngshēngzì. By far.",
  }, {
    :name => 'Байкал Бизнес Центр',
    :type => 'text/plain',
    :data => "экологически чистом районе города Солнечный. Включает  ... whatever that means.",
  }, {
    :name => '2mbytes.txt',
    :type => 'text/plain', 
    :data => "A large text file\n" + ("123456789\n" * (0.2*1024*1024)) + "last line\n",
  },
]

create_uploads = []
files.each do |file|
  tmp_file = Tempfile.new('upload')
  file[:tmp] = tmp_file.path
  tmp_file.write(file[:data])
  tmp_file.close

  # The Create API requires you to pass an array of hashes such as these. The :'upload_file[data]' element is itself
  # a hash which includes an open file handle (:file), a :file_name and the :file_content_type.
  create_uploads << {
    :resource_id => note_id,
    :resource_type => 'Private::Note',
    :resource_attribute => 'upload_files',
    :'upload_file[data]' => {
      :file => File.open(file[:tmp]),
      :file_name => file[:name],
      :file_content_type => file[:type],
    }
  }
  File.unlink(file[:tmp]) # Clean up
end

# Always use the ('content_type' => multipart/form-data) option for uploading files: it is efficient.
response = workbooks.assert_create('resource_upload_files', create_uploads, {}, :content_type => 'multipart/form-data')

#
# Now list them all the 'upload_files' endpoint and the 'resource_upload_files' endpoint and compare the contents of 
# each with what was uploaded.
#
uploaded_files = []
filters = []
response.affected_objects.each do |r|
  uploaded_files << {
    'id' => r['upload_file[id]'],
    'lock_version' => r['upload_file[lock_version]'],
  }
  filters <<  "['id', 'eq', '#{r['upload_file[id]']}']"
end
file_filter = {
  :_sort => 'id',
  :_dir => 'ASC',
  :_fm => 'OR',
  :_filter_json => "[#{filters.join(',')}]",
  '_select_columns[]' => ['id', 'file_name', 'file_content_type', 'file_size'],
}
file_response = workbooks.assert_get('upload_files', file_filter)

resource_filter = {
  :_sort => 'id',
  :_dir => 'ASC',
  '_ff[]' => ['resource_id', 'resource_type', 'resource_attribute'],
  '_ft[]' => ['eq', 'eq', 'eq'],
  '_fc[]' => [note_id, 'Private::Note', 'upload_files'],
  '_select_columns[]' => ['upload_file[id]', 'upload_file[file_name]', 'upload_file[file_content_type]', 'upload_file[file_size]', 'file'],
}
resource_response = workbooks.assert_get('resource_upload_files', resource_filter);

if files.size != resource_response.total || files.size != resource_response.data.size
  workbooks.log('Get resource_upload_files: unexpected result size', {:files => files, :resource_response => resource_response}, :error)
  exit(1)
end
if files.size != file_response.total || files.size != file_response.data.size
  workbooks.log('Get upload_files: unexpected result size', {:files => files, :file_response => file_response}, :error)
  exit(1)
end

files.each_with_index do |file, i|
  data_len = file[:data].size
  r = resource_response.data[i]
  f = file_response.data[i]
  if file[:name] == r['upload_file[file_name]'] &&
     file[:name] == f['file_name'] &&
     file[:type] == r['upload_file[file_content_type]'] &&
     file[:type] == f['file_content_type'] &&
     r['upload_file[id]'] == f['id'] &&
     data_len == r['upload_file[file_size]'] &&
     data_len == f['file_size']
    # Everything OK; download the data, compare with the originally-uploaded data
    data = workbooks.get("upload_files/#{f['id']}/download", nil, :decode_json => false)
    if data.size != data_len
      workbooks.log('File download failed: bad data length', [data.size, data_len, f], :error)
      exit(1)
    end
    if data != file[:data]
      workbooks.log('File comparison failed', [data, file[:data]], :error)
      exit(1)
    end
    workbooks.log('Downloaded previously-uploaded file, comparisons OK', f);
  else
    workbooks.log('File retrieval failed: differences', [files, resource_response, file_response], :error)
    exit(1)
  end
end

#
# Delete all except the last of the files just uploaded.
#
first_file = uploaded_files.shift # leave a file behind, for the next test
response = workbooks.assert_delete('upload_files', uploaded_files)

#
# An update of a file.
#
file = {
  :name => 'alternate.txt',
  :type => 'text/plain',
  :data => 'alternate',
}

tmp_file = Tempfile.new('upload')
file[:tmp] = tmp_file.path
tmp_file.write(file[:data])
tmp_file.close

update = {
  'id' => first_file['id'],
  'lock_version' => first_file['lock_version'],
  'data' => {
    :file => File.open(file[:tmp]),
    :file_name => file[:name],
    :file_content_type => file[:type],
  },
}

File.unlink(file[:tmp]) # Clean up. We still have a handle to the file.

response = workbooks.assert_update('upload_files', update, {}, :content_type => 'multipart/form-data')

#
# Delete the created organisation; doing this will also delete any associated notes and files associated with those.
#
workbooks.assert_delete('crm/organisations', organisation_id_lock_versions)

workbooks.logout
exit(0)
