// License: www.workbooks.com/mit_license
// Last commit $Id: ApiDataExample.cs 65874 2025-02-28 14:11:26Z jmonahan $
//
/** Processes often need to store their state between runs. The 'API Data' facility provides
 *  a simple way to do this.
 *
 *  A demonstration of using the Workbooks API via a thin C# wrapper to store and retrieve
 *  state.
*/
using System;
using WorkbooksApiApplication;
using System.Collections.Generic;

namespace ApiWrapper
{
  public class ApiDataExample
  {
    static WorkbooksApi workbooks = null;
    static TestLoginHelper login = null;

    public static void Main() {

      login = new TestLoginHelper();
      workbooks = login.testLogin();

      ApiDataExample apiDataEx = new ApiDataExample();

      // apiDataEx.createAPIData();
      apiDataEx.getNonExistenceAPIData();

      login.testExit(workbooks, 0);
    }

    public void createAPIData() {
      Dictionary<string, object> testValues = new Dictionary<string, object> ();
      testValues.Add("the answer"             , 42);
      testValues.Add("poppins"                , "Supercalifragilisticexpealidocious");
      testValues.Add("null"                   , null);
      testValues.Add("ten thousand characters",  repeat("123456789", 1000));
      testValues.Add("multibyte_characters"  , " 'д е ё ж з и й к л  字 字");

      Dictionary<string, object> apiData = null;

      List<Dictionary<string, object> > apiDataList = new List<Dictionary<string, object>>();

      foreach (string key in testValues.Keys) {
        apiData = new Dictionary<string, object> ();
        apiData.Add("key", "api_data_example: " + key);
        apiData.Add("value", testValues[key]);

        apiDataList.Add(apiData);
      }

      WorkbooksApiResponse response;
      try {
        response = workbooks.assertCreate("automation/api_data", apiDataList, null, null);
        List<Dictionary<string, object> > idVersionObjects = workbooks.idVersions(response);

        workbooks.log("createAPIData: First Object- ", new Object[] {response.getFirstAffectedObject()});

        apiData.Clear();
        apiDataList.Clear();

        // Update two API Data entries
        apiData.Add("id", ((Dictionary<string, object>)idVersionObjects[0])["id"]);
        apiData.Add("lock_version", ((Dictionary<string, object>)idVersionObjects[0])["lock_version"]);
        apiData.Add("value", 17);
        apiDataList.Add(apiData);

        Dictionary<string, object> apiData1 = new Dictionary<string, object> ();
        apiData1.Add("id", ((Dictionary<string, object>)idVersionObjects[2])["id"]);
        apiData1.Add("lock_version", ((Dictionary<string, object>)idVersionObjects[2])["lock_version"]);
        apiData1.Add("value", "null points");
        apiDataList.Add(apiData1);

        response = workbooks.assertUpdate("automation/api_data", apiDataList, null, null);

        workbooks.log("Updated API Data: First Object- ", new Object[] {response.getFirstAffectedObject()});


      } catch (Exception e) {
        workbooks.log("Exception while creating apidata", new Object[] {e}, "error");
        Console.WriteLine (e.StackTrace);
        login.testExit(workbooks, 1);
      }   
    }

    /*
    * Fetch four of them back, all available fields
    */
    public void getAPIData() {
      Dictionary<string, object> filter3 = new Dictionary<string, object> ();

      //Merge the limit_select and then add array of arrays
      filter3.Add("_sort", "id");
      filter3.Add("_dir", "ASC");
      filter3.Add("_ff[]", new String[]{"key", "key", "key", "key"}); 
      filter3.Add("_ft[]", new String[]{"eq", "eq", "eq", "eq"});
      filter3.Add("_fc[]", new String[]{"api_data_example: the answer",               
        "api_data_example: null",                     
        "api_data_example: ten thousand characters",   
        "api_data_example: multibyte characters"});
      filter3.Add("_fm", "or");

      try {
        WorkbooksApiResponse response = workbooks.assertGet("automation/api_data", filter3, null);

        workbooks.log("getAPIData Total fetched", new Object[] {response.getTotal()});      
        workbooks.log("getAPIData First Data", new Object[] {response.getFirstData()});

      } catch (Exception e) {
        workbooks.log("Error while getting the apiData:", new Object[] {e});      
        Console.WriteLine (e.StackTrace);
        login.testExit(workbooks, 1);
      }

    }
    /*
     * Fetch a single item using the alternate filter syntax
    */
    public void getSingleAPIData() {
      Dictionary<string, object> filter3 = new Dictionary<string, object> ();

      filter3.Add("_filter_json", "[[\"key\", \"eq\", \"api_data_example: poppins\"]]");

      try {
        WorkbooksApiResponse response = workbooks.assertGet("automation/api_data", filter3, null);

        workbooks.log("getAPIData Total fetched", new Object[] {response.getTotal()});      
        workbooks.log("getAPIData First Data", new Object[] {response.getFirstData()});

      } catch (Exception e) {
        workbooks.log("Error while getting the apiData:", new Object[] {e});      
        Console.WriteLine (e.StackTrace);
        login.testExit(workbooks, 1);
      }

    }

    /*
     * Attempt to fetch an item which does not exist
     */
    public void getNonExistenceAPIData() {
      Dictionary<string, object> filter3 = new Dictionary<string, object> ();

      //Merge the limit_select and then add array of arrays
      filter3.Add("_sort", "id");
      filter3.Add("_dir", "ASC");
      filter3.Add("_ff[]", "key"); 
      filter3.Add("_ft[]", "eq");
      filter3.Add("_fc[]", "api_data_example: no such record exists");

      try {
        WorkbooksApiResponse response = workbooks.assertGet("automation/api_data", filter3, null);

        if (response.getTotal() != 0) {
          workbooks.log("Bad response for non-existent item");
          login.testExit(workbooks, 1);
        }

      } catch (Exception e) {
        workbooks.log("Error while getting the apiData:", new Object[] {e});      
        Console.WriteLine (e.StackTrace);
        login.testExit(workbooks, 1);
      }
    }

    // Method to get the long string 123456789 multiple times
    public static String repeat(String str, int times){
      return new String(new char[times]).Replace("\0", str);
    }

  }
}

