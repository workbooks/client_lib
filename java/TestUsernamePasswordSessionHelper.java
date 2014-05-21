package workbooks_app.client_lib.java;
import java.io.StringReader;
import java.util.HashMap;

import javax.json.Json;
import javax.json.JsonObject;
import javax.json.JsonReader;

/** 
 *	Login wrapper for Workbooks for API test purposes. This version uses a 'traditional' username and 
 *  password. As an alternative, consider using an API Key without explicit Login/Logout requests: 
 *  see TestLoginHelper.java
 * 
 * 	License: www.workbooks.com/mit_license
 * 	Last commit $Id: TestUsernamePasswordSessionHelper.java 22068 2014-05-20 11:54:15Z jkay $
 */
 
public class TestUsernamePasswordSessionHelper {

	
	HashMap<String, Object> params = new HashMap<String,Object>();
	String service = "http://localhost:3000";
	String application_name = "java_test_client";
	String user_agent = "java_test_client/0.1";
	String username = "demo@workbooks.com";
	String password = "demo1234";
	boolean verify_peer = false;
	
	WorkbooksApi workbooks = null;
	
	public TestUsernamePasswordSessionHelper() {
		if (service != null) {
			params.put("service", service);
		}
		params.put("application_name", application_name);
		params.put("user_agent", user_agent);
		params.put("logger_callback", "logAllToStdout");
		params.put("verify_peer", verify_peer);

		try {
			workbooks = new WorkbooksApi(params);
		} catch(Exception e) {
			
		}
	}
	
	public WorkbooksApi testLogin() {
		HashMap<String, Object> loginParams = new HashMap<String, Object>();
		loginParams.put("username", username);
		loginParams.put("password", password);
		
		if (System.getenv("DATABASE_ID") != null) {
			loginParams.put("logical_database_id", System.getenv("DATABASE_ID"));
		}
		workbooks.log("Login commences", new Object[] {this.getClass().toString()});
		HashMap<String, Object> response = null;
		try {
			response = workbooks.login(loginParams);
			int http_status = (Integer) response.get("http_status");
			//int http_status = Integer.parseInt((String) response.get("http_status"));
	
			JsonObject responseObject = (JsonObject)response.get("response"); 
			if (response.containsKey("failure_reason")) {
				String failure_reason = response.get("failure_reason").toString();
				if (http_status == WorkbooksApi.HTTP_STATUS_FORBIDDEN && failure_reason.equals("no_database_selection_made")) {
					String default_database_id = responseObject.getString("default_database_id");
					loginParams.put("logical_database_id", default_database_id);
					
					response = workbooks.login(loginParams);
					http_status = (Integer)response.get("http_status");
					if (http_status != WorkbooksApi.HTTP_STATUS_OK) {
						workbooks.log("Login has failed", new Object[] {response}, "error", WorkbooksApi.DEFAULT_LOG_LIMIT);
						System.exit(0);
					}
				}
			}
			
			workbooks.log("Login complete", new Object[] {this.getClass()}, "info", WorkbooksApi.DEFAULT_LOG_LIMIT);
			
		} catch(Exception e) {			
			workbooks.log("Exception: ", new Object[] {e}, "error", WorkbooksApi.DEFAULT_LOG_LIMIT);
		}
		
		return workbooks;
	}
	
	public void testJsonConversion() {
		String str = "{\"person_name\": \"Beena Viroja\", \"my_queues\": {\"Private::Crm::MarketingCampaignQueue\": 6, \"Private::Accounting::SalesOrderQueue\": 6, \"Private::Crm::PersonLeadPartyQueue\": 7, \"Private::Crm::PersonQueue\": 6, \"Private::Crm::OpportunityQueue\": 6, \"Private::Accounting::CreditNoteQueue\": 6, \"Private::Crm::SalesLeadQueue\": 7, \"Private::Crm::OrganisationQueue\": 6, \"Private::Accounting::InvoiceQueue\": 6, \"Private::Crm::CaseQueue\": 10, \"Private::Accounting::QuotationQueue\": 6, \"Private::Activity::ActivityQueue\": 7, \"Private::Accounting::ContractQueue\": 6, \"Private::Accounting::PurchaseOrderQueue\": 6}, \"timezone\": \"London (GMT+00:00)\", \"logical_database_id\": 13330, \"default_database_id\": 13330, \"user_id\": 29198, \"version\": \"A.40.45 (build 20476)\", \"database_instance_id\": 47119, \"authenticity_token\": \"4b23f905a0f8f9e3217173d96c2dc278f3481ad6\", \"session_id\": \"e836089802e3f2d185d8d73f8fb56332\", \"databases\": [{\"name\": \"Demo\", \"created_at\": \"Wed Feb 05 11:29:51 UTC 2014\", \"id\": 13350}, {\"name\": \"Main\", \"created_at\": \"Tue Feb 04 10:25:56 UTC 2014\", \"id\": 13330}], \"api_version\": 1, \"database_name\": \"Main\", \"login_name\": \"beena@codextechnologies.co.uk\"}";
		JsonReader jsonReader = Json.createReader(new StringReader(str));
		JsonObject object = jsonReader.readObject();
		String pName = object.getString("person_name");
		JsonObject queueObj =  object.getJsonObject("my_queues");
		System.out.println("Person name:" + queueObj.getInt("Private::Crm::MarketingCampaignQueue"));
		jsonReader.close();
	}
	
	public void testExit(WorkbooksApi workbooks, int exitcode) {
		try {
			HashMap<String, Object> logout = workbooks.logout();
			if (!logout.containsKey("success") ) {
				workbooks.log("Logout failed", new Object[] {logout}, "error", WorkbooksApi.DEFAULT_LOG_LIMIT);
				System.exit(0);
			}
			workbooks.log("Logout Complete", new Object[] {this.getClass()}, "info", WorkbooksApi.DEFAULT_LOG_LIMIT);
		} catch(Exception e) {
			workbooks.log("Errors while logging out: ", new Object[] {e}, "error", WorkbooksApi.DEFAULT_LOG_LIMIT);
		}
		
		if (exitcode == 0) {
			workbooks.log("Script exited", new Object[] {"OK"}, "info", WorkbooksApi.DEFAULT_LOG_LIMIT);
		} else {
			workbooks.log("Script exited with error", new Object[] {Integer.toString(exitcode)}, "error", WorkbooksApi.DEFAULT_LOG_LIMIT);
		}
		System.exit(exitcode);
		
	}
	
	public static void main(String[] args) {
		TestUsernamePasswordSessionHelper testApi = new TestUsernamePasswordSessionHelper();
		//testApi.testLogin();
		testApi.testJsonConversion();
	}
		
}
