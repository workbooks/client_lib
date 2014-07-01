// License: www.workbooks.com/mit_license
// Last commit $Id$
//
// A demonstration of using the Workbooks API to send emails via a thin C# wrapper.
// Emails can be based on templates (with substitution of values from records) or
// created in a raw form ("rfc822").
//

using System;
using WorkbooksApiApplication;
using System.Collections.Generic;

namespace EmailExampleApplication
{
  public class EmailSendExample
  {
    static WorkbooksApi  workbooks = null;
    static TestLoginHelper login = null;

    public static void Main() {

      login = new TestLoginHelper();
      workbooks = login.testLogin();

      sendTemplatedEmail();
      sendDraftTemplatedEmail();
      sendSimpleTestEmail();

      login.testExit(workbooks, 0);
    }

    /// <summary>
    /// Choose a template and a Case then use it to Send an email about the Case.
    /// You can pass additional placeholders as JSON; these are merged into the template.
    /// </summary>
    public static void sendTemplatedEmail() {

      List<Dictionary<string, object> > templatedEmailList = new List<Dictionary<string, object> >();

      Dictionary<string, object> templatedEmail = new Dictionary<string, object>();
      templatedEmail.Add("render_with_template_name", "Autotest Template");
      templatedEmail.Add("render_with_resource_type", "Private::Crm::Case");
      templatedEmail.Add("render_with_resource_id", 2);
      templatedEmail.Add("from_address", "support@workbooks.com");
      templatedEmail.Add("to_addresses", "to.address1@workbooks.com, to.address2@workbooks.com");
      templatedEmail.Add("cc_addresses", "cc.address1@workbooks.com, cc.address2@workbooks.com");
      templatedEmail.Add("bcc_addresses", "bcc.address@workbooks.com");
      templatedEmail.Add("render_with_placeholders", "{\"today_date\":\"10 Jun 2014\",\"contract_value\":\"&pound;420.42\",\"credits_used\":\"42\"}");
      templatedEmail.Add("status", "SEND");

      templatedEmailList.Add(templatedEmail);
      try {
        workbooks.assertCreate("email/emails", templatedEmailList, null, null);
        workbooks.log("sendTemplatedEmail() : Email is sent successfully");
      } catch (Exception e) {
        workbooks.log("Error while sending templated Email " + e.Message);
        Console.WriteLine (e.StackTrace);
      }
    }

    /// <summary>
    /// Alternatively, choose a template and a Case then use it to create a Draft email about the Case.
    /// </summary>
    public static void sendDraftTemplatedEmail() {

      List<Dictionary<string, object> > templatedEmailList = new List<Dictionary<string, object> >();

      Dictionary<string, object> templatedEmail = new Dictionary<string, object>();
      templatedEmail.Add("render_with_template_name", "Autotest Template");
      templatedEmail.Add("render_with_resource_type", "Private::Crm::Case");
      templatedEmail.Add("render_with_resource_id", 2);
      templatedEmail.Add("from_address", "support@workbooks.com");
      templatedEmail.Add("to_addresses", "to.address1@workbooks.com, to.address2@workbooks.com");
      templatedEmail.Add("cc_addresses", "cc.address1@workbooks.com, cc.address2@workbooks.com");
      templatedEmail.Add("bcc_addresses", "bcc.address@workbooks.com");
      templatedEmail.Add("status", "DRAFT");

      templatedEmailList.Add(templatedEmail);
      try {
        WorkbooksApiResponse response =  workbooks.assertCreate("email/emails", templatedEmailList, null, null);

        List<Dictionary<string, object> > idVersionObjects =  workbooks.idVersions(response);

        /*        
       * Now change the status to send it
       */
        Dictionary<string, object> updateEmail = new Dictionary<string, object> ();
        updateEmail.Add("id", ((Dictionary<string, object>)idVersionObjects[0])["id"]);
        updateEmail.Add("lock_version",((Dictionary<string, object>)idVersionObjects[0])["lock_version"]);
        updateEmail.Add("status", "SEND");

        List<Dictionary<string, object> > updateEmailList = new List<Dictionary<string,object>>();
        updateEmailList.Add(updateEmail);
        response = workbooks.assertUpdate("email/emails", updateEmailList, null, null);
        workbooks.log("sendDraftTemplatedEmail() : Email is sent successfully");
      } catch (Exception e) {
        workbooks.log("Error while sending templated Email " + e.Message);
        workbooks.log(e.StackTrace);
        login.testExit(workbooks, 1);
      }
    }

    /// <summary>
    /// Create an email without asking for anything clever. Just supply some text. It will be sent using the
    /// email configuration of the user account that we are logged in with.
    /// </summary>
    public static void sendSimpleTestEmail() {
      String rfc822 = "From:    \"Fred\" <fred@workbooks.com>\n" +
                    "To:      \"George\" <george@elsewhere.com>\n" +
                    "Subject: A simple message, in HTML with a plain text version\n" + 
                    "MIME-Version: 1.0\n" + 
                    "Content-Type: multipart/mixed; boundary=frontier\n\n" +

                    "This is a message with multiple parts in MIME format.\n" +
                    "--frontier\n" +
                    "Content-Type: text/plain\n\n" +

                    "This is the body of the message.\n" +
                    "--frontier\n" +
                    "Content-Type: text/html\n\n" +

                    "<html>\n" +
                    "<body>\n" +
                    "<p>\n" +
                    "This is the body of <em>the message</em>.\n" +
                    "</p>\n" +
                    "</body>\n" +
                    "</html>\n" +
                    "--frontier--\n" ;

      Dictionary<string, object> createEmail = new Dictionary<string, object>();
      createEmail.Add("rfc822",rfc822);
      createEmail.Add("status","SEND");

      List<Dictionary<string, object> > listOfEmails = new List<Dictionary<string,object>>();
      listOfEmails.Add(createEmail);
      try {
        workbooks.assertCreate("email/emails", listOfEmails, null, null);
        workbooks.log("sendSimpleTestEmail(): Email is sent successfully");
      } catch (Exception e) {
        workbooks.log("Error while sending simple text email" + e.Message);
        workbooks.log(e.StackTrace);
        login.testExit(workbooks, 1);
      }
    }


  }// class
}

