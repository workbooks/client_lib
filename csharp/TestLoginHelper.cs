 /**
  * 	License: www.workbooks.com/mit_license
  * 	Last commit $Id: TestLoginHelper.cs 63933 2024-09-03 14:06:12Z jmonahan $
  *
  * Login wrapper for Workbooks for API test purposes. This version uses an API Key to
  * authenticate which is the recommended approach unless you are running under the
  * Process Engine which will set up a session for you automatically without requiring
  * an API key.
  */
using System;
using System.IO;
using System.Net;
using System.Text;
using System.Collections.Generic;
using System.Collections;
using System.Runtime.Serialization.Json;
using System.Web;
using WorkbooksApiApplication;

namespace WorkbooksApiApplication
{
	public class TestLoginHelper
	{
		Dictionary<string, object> loginParams = new Dictionary<string, object>();
    string service = "http://localhost:3000";
		string application_name = "csharp_test_client";
		string user_agent = "csharp_test_client/0.1";
		string api_key ="01234-56789-01234-56789-01234-56789-01234-56789";
		bool verify_peer = false;

		public WorkbooksApi workbooks = null;

		public WorkbooksApi testLogin() {

      // allow the server and API key to be overridden with environment variables
      string service_env = Environment.GetEnvironmentVariable("WB_SERVICE");
      string api_key_env = Environment.GetEnvironmentVariable("WB_API_KEY");

      if (service_env != null) {
        service = service_env;
      }

      if (api_key_env != null) {
        api_key = api_key_env;
      }

			if (service != null) {
				loginParams.Add("service", service);
			}
			loginParams.Add("application_name", application_name);
			loginParams.Add("user_agent", user_agent);
			loginParams.Add("verify_peer", verify_peer);
			if (api_key != null && api_key != "") {
				loginParams.Add("api_key", api_key);
			}
			try {
				workbooks = new WorkbooksApi(loginParams);
				Console.WriteLine("Workbooks: " + workbooks.Service);
				//				workbooks.log("Logged in with these loginParams: ", new Object[] {loginParams} );
			} catch(Exception e) {
				workbooks.log("Error while creating the Workbooks API object: ", new Object[] {e}, "error", WorkbooksApi.DEFAULT_LOG_LIMIT);
				Console.WriteLine ("Stacktrace: " + e.StackTrace);
			}
			return workbooks;
		}

		public void testExit(WorkbooksApi workbooks, int exitcode) {

			if (exitcode == 0) {
        //Console.WriteLine ("Scripted exited OK");
        workbooks.log("Script exited", new Object[] {"OK"}, "info");
			} else {
        //Console.WriteLine ("Scripted exited with error");
        workbooks.log("Script exited with error", new Object[] {(int)exitcode}, "error");
			}
			System.Environment.Exit(exitcode);
		}

	}
}

