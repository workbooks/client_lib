package workbooks_app.client_lib.java;

import java.util.HashMap;

/** 
 *	Login wrapper for Workbooks for API test purposes. This version uses an API Key to
 *  authenticate which is the recommended approach unless you are running under the 
 *  Process Engine which will set up a session for you automatically without requiring
 *  an API key.
 * 
 * 	License: www.workbooks.com/mit_license
 * 	Last commit $Id: TestLoginHelper.java 22080 2014-05-21 12:53:52Z bviroja $
 */

public class TestLoginHelper {
	
	HashMap<String, Object> params = new HashMap<String,Object>();
	String service = "http://localhost:3000";
	String application_name = "java_test_client";
	String user_agent = "java_test_client/0.1";
	String api_key ="01234-56789-01234-56789-01234-56789-01234-56789";
	boolean verify_peer = false;
	
	public WorkbooksApi workbooks = null;
	HashMap<String, Object> logObjects = new HashMap<String, Object>();
	
	public WorkbooksApi testLogin() {
		if (service != null) {
			params.put("service", service);
		}
		params.put("application_name", application_name);
		params.put("user_agent", user_agent);
		params.put("verify_peer", verify_peer);
		if (api_key != null && api_key != "") {
			params.put("api_key", api_key);
		}
		try {
			workbooks = new WorkbooksApi(params);
			workbooks.log("Logged in with these params: ", new Object[] {params} );
		} catch(Exception e) {
			workbooks.log("Error while creating the Workbooks API object: ", new Object[] {e}, "error", WorkbooksApi.DEFAULT_LOG_LIMIT);
			e.printStackTrace();
		}
		
		return workbooks;
	}
	
	public void testExit(WorkbooksApi workbooks, int exitcode) {
		
		if (exitcode == 0) {
			workbooks.log("Script exited", new Object[] {"OK"}, "info", WorkbooksApi.DEFAULT_LOG_LIMIT);
		} else {
			workbooks.log("Script exited with error", new Object[] {Integer.toString(exitcode)}, "error", WorkbooksApi.DEFAULT_LOG_LIMIT);
		}
		System.exit(exitcode);
		
	}
	
	public static void main(String[] args) {
		TestLoginHelper testLoginHelper = new TestLoginHelper();
		testLoginHelper.testLogin();
	}
}
