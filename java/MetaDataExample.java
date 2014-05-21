package workbooks_app.client_lib.java;

import java.util.HashMap;
import workbooks_app.client_lib.java.WorkbooksApi.WorkbooksApiResponse;

/** 
 *	A demonstration of using the Workbooks API to fetch metadata via a thin Java wrapper
 * 
 * 	License: www.workbooks.com/mit_license
 * 	Last commit $Id: MetaDataExample.java 22068 2014-05-20 11:54:15Z jkay $
 */

public class MetaDataExample {

	static WorkbooksApi workbooks = null;
	HashMap<String, Object> logObjects = new HashMap<String, Object>();
	static TestLoginHelper login = null;
	
	String[] columns = {"id",
	    "lock_version",
	    "class_name",
      "base_class_name",
      "human_class_name",
      "human_class_name_plural",
      "human_class_description",
      "instances_described_by",
      "icon",
      "help_url",
      "controller_path"};
	
	/**
	 * @param args
	 */
	public static void main(String[] args) {

		TestLoginHelper login = new TestLoginHelper();
		workbooks = login.testLogin();

		MetaDataExample metaDataEx = new MetaDataExample();
		metaDataEx.fetchSummary();
		metaDataEx.fetchSomeMoreSummary();
		metaDataEx.fetchAll();

		login.testExit(workbooks, 0);
	}
	
	public void fetchSummary() {
		String[] classNames = {
				"Private::Searchable",
	      "Private::Crm::Person",
	      "Private::Crm::Organisation",
	      "Private::Crm::Case"};
		
		HashMap<String, Object> classMetaData = new HashMap<String, Object> ();
		
		classMetaData.put("class_names[]", classNames);
		classMetaData.put("_select_columns[]", columns);
		
		try {
			WorkbooksApiResponse response = workbooks.assertGet("metadata/types", classMetaData, null);
			
			workbooks.log("fetchSummary Total: ", new Object[] {response.getTotal()});	
//			if (response.getTotal() != null && response.getTotal() > 0) {
//				workbooks.log("fetchSummary First: ", new Object[] {response.getFirstData()});
//			}			
		} catch (Exception e) {
			System.out.println("Error while getting the metadata: " + e.getMessage());
			e.printStackTrace();
			login.testExit(workbooks, 1);
		}
	}

	public void fetchSomeMoreSummary() {
		String[] classNames = {
	      "Private::Crm::Case"};

		String[] addColumns = {"id",
		    "lock_version",
		    "class_name",
	      "base_class_name",
	      "human_class_name",
	      "human_class_name_plural",
	      "human_class_description",
	      "instances_described_by",
	      "icon",
	      "help_url",
	      "controller_path",
	      "fields", 
	      "associations"};
		
		
		HashMap<String, Object> classMetaData = new HashMap<String, Object> ();
		
		classMetaData.put("class_names[]", classNames);
		classMetaData.put("_select_columns[]", addColumns);
		
		try {
			WorkbooksApiResponse response = workbooks.assertGet("metadata/types", classMetaData, null);
			
			workbooks.log("fetchSomeMoreSummary Total: ", new Object[] {response.getTotal()});	
//			if (response.getTotal() != null && response.getTotal() > 0) {
//				workbooks.log("fetchSomeMoreSummary First: ", new Object[] {response.getFirstData()});
//			}		
		} catch (Exception e) {
			System.out.println("Error while getting the metadata: " + e.getMessage());
			e.printStackTrace();
			login.testExit(workbooks, 1);
		}
	}
	
	public void fetchAll() {
		HashMap<String, Object> classMetaData = new HashMap<String, Object> ();
		
		classMetaData.clear();
		
		try {
			WorkbooksApiResponse response = workbooks.assertGet("metadata/types", classMetaData, null);
			
			workbooks.log("fetchAll Total: ", new Object[] {response.getTotal()});	
//			if (response.getTotal() > 0) {
//				workbooks.log("fetchAll First: ", new Object[] {response.getFirstData()});
//			}
		} catch (Exception e) {
			System.out.println("Error while getting the metadata: " + e.getMessage());
			e.printStackTrace();
			login.testExit(workbooks, 1);
		}
	}
}
