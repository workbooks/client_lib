package workbooks_app.client_lib.java;
import java.util.HashMap;
import javax.json.JsonObject;

/** 
 *	Login wrapper for Workbooks for API test purposes. This version uses a 'traditional' username and 
 *  password. As an alternative, consider using an API Key without explicit Login/Logout requests: 
 *  see TestLoginHelper.java
 * 
 * 	License: www.workbooks.com/mit_license
 * 	Last commit $Id: TestUsernamePasswordSessionHelper.java 22080 2014-05-21 12:53:52Z bviroja $
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
