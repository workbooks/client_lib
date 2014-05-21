 /**
  * 	License: www.workbooks.com/mit_license
  * 	Last commit $Id$
  *
  */
using System;
using System.Collections.Generic;
using WorkbooksApiApplication;

namespace WorkbooksApiApplication
{
  public class TestUsernamePasswordSessionHelper
  {
    Dictionary<string, object> loginParams = new Dictionary<string, object>();
    string service = "http://localhost:3000";
    string application_name = "csharp_test_client";
    string user_agent = "csharp_test_client/0.1";
    string username = "demo@workbooks.com";
    string password = "demo1234";
    bool verify_peer = false;

    public WorkbooksApi workbooks = null;

    public TestUsernamePasswordSessionHelper ()
    {
      if (service != null) {
        loginParams.Add("service", service);
      }
      loginParams.Add("application_name", application_name);
      loginParams.Add("user_agent", user_agent);
      loginParams.Add("verify_peer", verify_peer);
      try {
        workbooks = new WorkbooksApi(loginParams);
      } catch(Exception e) {
        Console.WriteLine ("Exception while getting the workbooks object" + e.Message);
      }
    }

    public WorkbooksApi testLogin() {
      loginParams = new Dictionary<string, object>();
      loginParams.Add("username", username);
      loginParams.Add("password", password);
      if (System.Environment.GetEnvironmentVariable("DATABASE_ID") != null) {
        loginParams.Add("logical_database_id", System.Environment.GetEnvironmentVariable("DATABASE_ID"));
      }
      workbooks.log("Login commences", new Object[] {this.GetType().Name});
      Dictionary <string, object> response = null;
      try {
        response = workbooks.login(loginParams);
        int http_status = (int) response["http_status"];
        Dictionary<string, object> responseData = (Dictionary<string, object>) response["response"];
        if (response.ContainsKey("failure_reason")) {
          String failure_reason = response["failure_reason"].ToString();
          if (http_status == WorkbooksApi.HTTP_STATUS_FORBIDDEN && failure_reason.Equals("no_database_selection_made")) {
            String default_database_id = responseData["default_database_id"].ToString();
            loginParams.Add("logical_database_id", default_database_id);

            response = workbooks.login(loginParams);
            http_status = (int)response["http_status"];
            if (http_status != WorkbooksApi.HTTP_STATUS_OK) {
              workbooks.log("Login has failed", new Object[] {response}, "error");
              System.Environment.Exit(0);
            }
          }
        }
        workbooks.log("Login complete", new Object[] {this.GetType().ToString()}, "info");
      } catch(Exception e) {      
        workbooks.log("Exception: ", new Object[] {e}, "error");
      }
      return workbooks;
    }

    public void testExit(WorkbooksApi workbooks, int exitcode) {
      try {
        Dictionary<string, object> logout = workbooks.logout();
        if (!logout.ContainsKey("success") ) {
          workbooks.log("Logout failed", new Object[] {logout}, "error");
          System.Environment.Exit(0);
        }
        workbooks.log("Logout Complete", new Object[] {this.GetType()}, "info");
      } catch(Exception e) {
        workbooks.log("Errors while logging out: ", new Object[] {e}, "error");
      }
      if (exitcode == 0) {
        workbooks.log("Script exited", new Object[] {"OK"}, "info");
      } else {
        workbooks.log("Script exited with error", new Object[] {exitcode}, "error");
      }
      System.Environment.Exit(exitcode);
    }

  } // class
}

