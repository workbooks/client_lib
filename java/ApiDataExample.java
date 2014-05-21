package workbooks_app.client_lib.java;

import java.util.ArrayList;
import java.util.HashMap;

import workbooks_app.client_lib.java.WorkbooksApi.WorkbooksApiResponse;


/** Processes often need to store their state between runs. The 'API Data' facility provides
 *  a simple way to do this.
 *
 *  A demonstration of using the Workbooks API via a thin Java wrapper to store and retrieve
 *  state. 
 * 
 * 	License: www.workbooks.com/mit_license
 * 	Last commit $Id: ApiDataExample.java 22068 2014-05-20 11:54:15Z jkay $
 */

public class ApiDataExample {
	static WorkbooksApi workbooks = null;
	static TestLoginHelper login = null;
	
	
	/**
	 * @param args
	 */
	public static void main(String[] args) {

		login = new TestLoginHelper();
		workbooks = login.testLogin();

		ApiDataExample apiDataEx = new ApiDataExample();
		
		apiDataEx.createAPIData();
		apiDataEx.getNonExistenceAPIData();
		
		login.testExit(workbooks, 0);
	}

	public void createAPIData() {
		HashMap<String, Object> testValues = new HashMap<String, Object> ();
		testValues.put("the answer"             , 42);
		testValues.put("poppins"                , "Supercalifragilisticexpealidocious");
		testValues.put("null"                   , null);
		testValues.put("ten thousand characters",  repeat("123456789", 1000));
		testValues.put("multibyte_characters"  , " 'д е ё ж з и й к л  字 字");
		
		HashMap<String, Object> apiData = null;
		
		ArrayList<HashMap<String, Object> > apiDataList = new ArrayList<HashMap<String,Object>>();
		
		for (String key : testValues.keySet()) {
			apiData = new HashMap<String, Object> ();
			apiData.put("key", "api_data_example: " + key);
			apiData.put("value", testValues.get(key));
			
			apiDataList.add(apiData);
		}
			
		WorkbooksApiResponse response;
		try {
			response = workbooks.assertCreate("automation/api_data", apiDataList, null, null);
			ArrayList<HashMap<String, Object> > idVersionObjects = workbooks.idVersions(response);
			
			workbooks.log("createAPIData: First Object- ", new Object[] {response.getFirstAffectedObject()});

			apiData.clear();
			apiDataList.clear();
			
			// Update two API Data entries
			apiData.put("id", ((HashMap)idVersionObjects.get(0)).get("id"));
			apiData.put("lock_version", ((HashMap)idVersionObjects.get(0)).get("lock_version"));
			apiData.put("value", 17);
			apiDataList.add(apiData);
			
			HashMap<String, Object> apiData1 = new HashMap<String, Object> ();
			apiData1.put("id", ((HashMap)idVersionObjects.get(2)).get("id"));
			apiData1.put("lock_version", ((HashMap)idVersionObjects.get(2)).get("lock_version"));
			apiData1.put("value", "null points");
			apiDataList.add(apiData1);
			
			response = workbooks.assertUpdate("automation/api_data", apiDataList, null, null);
			
			workbooks.log("Updated API Data: First Object- ", new Object[] {response.getFirstAffectedObject()});
			
			
		} catch (Exception e) {
				workbooks.log("Exception while creating or updating API Data", new Object[] {e}, "error", WorkbooksApi.DEFAULT_LOG_LIMIT);
       	e.printStackTrace();
       	login.testExit(workbooks, 1);
		}		
	}
	
	public void getAPIData() {
		HashMap<String, Object> filter3 = new HashMap<String, Object> ();
		
		//Merge the limit_select and then add array of arrays
		filter3.put("_sort", "id");
		filter3.put("_dir", "ASC");
		filter3.put("_ff[]", new String[]{"key", "key", "key", "key"}); 
		filter3.put("_ft[]", new String[]{"eq", "eq", "eq", "eq"});
		filter3.put("_fc[]", new String[]{"api_data_example: the answer",               
                                    "api_data_example: null",                     
                                    "api_data_example: ten thousand characters",   
                                    "api_data_example: multibyte characters"});
		filter3.put("_fm", "or");
		
		try {
			WorkbooksApiResponse response = workbooks.assertGet("automation/api_data", filter3, null);
			
			workbooks.log("getAPIData Total fetched", new Object[] {response.getTotal()});			
			workbooks.log("getAPIData First Data", new Object[] {response.getFirstData()});
			
		} catch (Exception e) {
			workbooks.log("Error while getting the apiData:", new Object[] {e});			
			e.printStackTrace();
			login.testExit(workbooks, 1);
		}

	}
	
	public void getSingleAPIData() {
		HashMap<String, Object> filter3 = new HashMap<String, Object> ();
		
		filter3.put("_filter_json", "[['key', 'eq', 'api_data_example: poppins']]");
		
		try {
			WorkbooksApiResponse response = workbooks.assertGet("automation/api_data", filter3, null);
			
			workbooks.log("getAPIData Total fetched", new Object[] {response.getTotal()});			
			workbooks.log("getAPIData First Data", new Object[] {response.getFirstData()});
			
		} catch (Exception e) {
			workbooks.log("Error while getting the apiData:", new Object[] {e});			
			e.printStackTrace();
			login.testExit(workbooks, 1);
		}
		
	}
	
	public void getNonExistenceAPIData() {
		HashMap<String, Object> filter3 = new HashMap<String, Object> ();
		
		//Merge the limit_select and then add array of arrays
		filter3.put("_sort", "id");
		filter3.put("_dir", "ASC");
		filter3.put("_ff[]", "key"); 
		filter3.put("_ft[]", "eq");
		filter3.put("_fc[]", "api_data_example: no such record exists");
		
		try {
			WorkbooksApiResponse response = workbooks.assertGet("automation/api_data", filter3, null);
			
			if (response.getTotal() != 0) {
          workbooks.log("Bad response for non-existent item", new Object[] {response.print()});      
          login.testExit(workbooks, 1);
      }
			
		} catch (Exception e) {
			workbooks.log("Error while getting the apiData:", new Object[] {e});			
			e.printStackTrace();
			login.testExit(workbooks, 1);
		}
	}
	
	public static String repeat(String str, int times){
	   return new String(new char[times]).replace("\0", str);
	}
}
