#
#  A demonstration of using the Workbooks API to send emails via a thin Ruby wrapper.
#  Emails can be based on templates (with substitution of values from records) or
#  created in a raw form ("rfc822").
#
#
#  Last commit $Id: email_send_example.rb 22501 2014-07-01 12:17:25Z jkay $
#  License: www.workbooks.com/mit_license
#

require './workbooks_api.rb'
require './test_login_helper.rb'

workbooks = WorkbooksApiTestLoginHelper.new.workbooks

#
# Choose a template and a Case then use it to Send an email about the Case.
# You can pass additional placeholders as JSON; these are merged into the template.
#
send_templated_email = {
  'render_with_template_name' => 'Autotest Template',
  'render_with_resource_type' => 'Private::Crm::Case',
  'render_with_resource_id' => 2,
  'from_address' => 'from_address@workbooks.com',
  'to_addresses' => 'to.address1@workbooks.com, to.address2@workbooks.com',
  'cc_addresses' => 'cc.address1@workbooks.com, cc.address2@workbooks.com',
  'bcc_addresses' => 'bcc.address@workbooks.com',
  'render_with_placeholders' => JSON.generate({
    'today_date' => Time.now.to_s,
    'contract_value' => '&pound;420.42',
    'credits_used' => 42,
  }),
  'status' => 'SEND',
}
create_response = workbooks.create('email/emails', send_templated_email)
workbooks.log('Sent email', create_response)

#
# Alternatively, choose a template and a Case then use it to create a Draft email about the Case.
#
create_templated_email = {
  'render_with_template_name' => 'Autotest Template',
  'render_with_resource_type' => 'Private::Crm::Case',
  'render_with_resource_id' => 2,
  'from_address' => 'from_address@workbooks.com',
  'to_addresses' => 'to.address1@workbooks.com, to.address2@workbooks.com',
  'cc_addresses' => 'cc.address1@workbooks.com, cc.address2@workbooks.com',
  'bcc_addresses' => 'bcc.address@workbooks.com',
  'status' => 'DRAFT',
}
create_email_response = workbooks.assert_create('email/emails', create_templated_email)
email_id_lock_versions = workbooks.id_versions(create_email_response)
workbooks.log('Created draft email', create_email_response)

#
# Create an email without asking for anything clever. Just supply some text. It will be sent using the
# email configuration of the user account that we are logged in with.
#
rfc822 = %{
From:    "Fred" <fred@workbooks.com>
To:      "George" <george@elsewhere.com>
Subject: A simple message, in HTML with a plain text version
MIME-Version: 1.0
Content-Type: multipart/mixed; boundary=frontier

This is a message with multiple parts in MIME format.
--frontier
Content-Type: text/plain

This is the body of the message.
--frontier
Content-Type: text/html

<html>
  <body>
    <p>
      This is the body of <em>the message</em>.
    </p>
  </body>
</html>
--frontier--

}

create_rfc822_email = {
  'rfc822' => rfc822,
  'status' => 'SEND',
}

response = workbooks.assert_create('email/emails', create_rfc822_email)

workbooks.logout
exit(0)
