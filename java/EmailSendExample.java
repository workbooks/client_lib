package workbooks_app.client_lib.java;

import java.util.ArrayList;
import java.util.HashMap;

import workbooks_app.client_lib.java.WorkbooksApi.WorkbooksApiResponse;

/** 
 *	A demonstration of using the Workbooks API to send emails via a thin Java wrapper.
 *  Emails can be based on templates (with substitution of values from records) or
 *  created in a raw form ("rfc822").
 * 
 * 	License: www.workbooks.com/mit_license
 * 	Last commit $Id: EmailSendExample.java 22442 2014-06-25 08:40:07Z jkay $
 */
public class EmailSendExample {

	static WorkbooksApi  workbooks = null;
	static HashMap<String, Object> logObjects = new HashMap<String, Object>();
  static TestLoginHelper login = null;
	/**
	 * @param args
	 */
	public static void main(String[] args) {

    login = new TestLoginHelper();
    workbooks = login.testLogin();
    
    sendTemplatedEmail();
    sendDraftTemplatedEmail();
    sendSimpleTestEmail();
    
    login.testExit(workbooks, 0);
	}
	
	/*
	 * Choose a template and a Case then use it to Send an email about the Case.
	 * You can pass additional placeholders as JSON; these are merged into the template.
	 */

	public static void sendTemplatedEmail() {
		
		ArrayList<HashMap<String, Object> > templatedEmailList = new ArrayList<HashMap<String, Object> >();
		
		HashMap<String, Object> templatedEmail = new HashMap<String, Object>();
		
		
		 templatedEmail.put("render_with_template_name", "Autotest Template");
		 templatedEmail.put("render_with_resource_type", "Private::Crm::Case");
		 templatedEmail.put("render_with_resource_id", 2);
		 templatedEmail.put("from_address", "support@workbooks.com");
		 templatedEmail.put("to_addresses", "beena@codextechnologies.co.uk");
		 templatedEmail.put("cc_addresses", "cc.address1@workbooks.com, cc.address2@workbooks.com");
		 templatedEmail.put("bcc_addresses", "bv@workbooks.com");
		 templatedEmail.put("render_with_placeholders", "{\"today_date\":\"10 Jun 2014\",\"contract_value\":\"&pound;420.42\",\"credits_used\":\"42\"}");
		 templatedEmail.put("status", "SEND");
		
		 templatedEmailList.add(templatedEmail);
		 
		
		 try {
			WorkbooksApiResponse response =  workbooks.assertCreate("email/emails", templatedEmailList, null, null);
			System.out.println("sendTemplatedEmail() : Email is sent successfully");
		} catch (Exception e) {
			System.out.println("Error while sending templated Email " + e.getMessage());
			e.printStackTrace();
		}
	}

	
	public static void sendDraftTemplatedEmail() {
		
		ArrayList<HashMap<String, Object> > templatedEmailList = new ArrayList<HashMap<String, Object> >();
		
		HashMap<String, Object> templatedEmail = new HashMap<String, Object>();
		
		
		 templatedEmail.put("render_with_template_name", "Autotest Template");
		 templatedEmail.put("render_with_resource_type", "Private::Crm::Case");
		 templatedEmail.put("render_with_resource_id", 2);
		 templatedEmail.put("from_address", "support@workbooks.com");
		 templatedEmail.put("to_addresses", "beena@codextechnologies.co.uk");
		 templatedEmail.put("cc_addresses", "cc.address1@workbooks.com, cc.address2@workbooks.com");
		 templatedEmail.put("bcc_addresses", "bv@workbooks.com");
		 templatedEmail.put("status", "DRAFT");
		
		 templatedEmailList.add(templatedEmail);
		 
		
		 try {
			WorkbooksApiResponse response =  workbooks.assertCreate("email/emails", templatedEmailList, null, null);
			
			ArrayList<HashMap<String, Object> > idVersionObjects =  workbooks.idVersions(response);
			
			/*
			 * Now change the status to send it
			 */
			HashMap<String, Object> updateEmail = new HashMap<String, Object> ();
			updateEmail.put("id", ((HashMap<String, Object>)idVersionObjects.get(0)).get("id"));
			updateEmail.put("lock_version",((HashMap<String, Object>)idVersionObjects.get(0)).get("lock_version"));
			updateEmail.put("status", "SEND");

			ArrayList<HashMap<String, Object> > updateEmailList = new ArrayList<HashMap<String,Object>>();
			updateEmailList.add(updateEmail);
			
			
			response = workbooks.assertUpdate("email/emails", updateEmailList, null, null);
			
			workbooks.log("sendDraftTemplatedEmail", new Object[] {response.print()});
			
			System.out.println("sendDraftTemplatedEmail() : Email is sent successfully");
		} catch (Exception e) {
			System.out.println("Error while sending templated Email " + e.getMessage());
			e.printStackTrace();
			login.testExit(workbooks, 1);
		}
	}
	
	/*
	 * Create an email without asking for anything clever. Just supply some text. It will be sent using the
	 * email configuration of the user account that we are logged in with.
	 */
	
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

		HashMap<String, Object> createEmail = new HashMap<String, Object>();
		
										
		createEmail.put("rfc822",rfc822);
		createEmail.put("status","SEND");
		
		ArrayList<HashMap<String, Object> > listOfEmails = new ArrayList<HashMap<String,Object>>();
		listOfEmails.add(createEmail);

		try {
			WorkbooksApiResponse response = workbooks.assertCreate("email/emails", listOfEmails, null, null);
			System.out.println("sendSimpleTestEmail(): Email is sent successfully");
		} catch (Exception e) {
			System.out.println("Error while sending simple text email" + e.getMessage());
			e.printStackTrace();
			login.testExit(workbooks, 1);
		}
	}
}
