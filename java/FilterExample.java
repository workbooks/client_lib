package workbooks_app.client_lib.java;

import java.util.HashMap;

import javax.json.JsonArray;
import javax.json.JsonObject;

import workbooks_app.client_lib.java.WorkbooksApi.WorkbooksApiResponse;

/** 
 *	A demonstration of using the Workbooks API to find records using "filters" via a thin Java wrapper
 * 
 * 	License: www.workbooks.com/mit_license
 * 	Last commit $Id: FilterExample.java 22068 2014-05-20 11:54:15Z jkay $
 */

public class FilterExample {
	static WorkbooksApi  workbooks = null;
	HashMap<String, Object> logObjects = new HashMap<String, Object>();
	static TestLoginHelper login = null;
	/*
	 * Some basic options which are used in the following examples.
	 * 
	 * An array, of columns to select. Discover these in the API 'meta data' document, here:
	 *    http://www.workbooks.com/api-reference
	 */
	String[] columns = {"id",
		    "lock_version",
		    "name",
		    "main_location[telephone]",
		    "main_location[town]",
		    "updated_at",
		    "updated_by_user[person_name]"};
	HashMap<String, Object> limit_select = new  HashMap<String, Object>();
	
	/*
	 * Start/Limit, Sort/Direction, Column selection
	 */
	private void populateSelectLimit() {

		limit_select.put("_skip_total_rows", "true");
		limit_select.put("_start", "0");
		//limit_select.put("_limit", "5");
		limit_select.put("_sort", "id");
		limit_select.put("_dir", "ASC");
		limit_select.put("_select_columns[]", columns);
		
	}
	
	
	/**
	 * @param args
	 */
	public static void main(String[] args) {
		boolean same = true;

		login = new TestLoginHelper();
		workbooks = login.testLogin();
		
		FilterExample filterEx = new FilterExample();
//		WorkbooksApiResponse response2 = filterEx.getOrganisationsViaFilterJson();

		WorkbooksApiResponse response1 = filterEx.getOrganisationsViaFilter();
		WorkbooksApiResponse response2 = filterEx.getOrganisationsViaFilterJson();
		WorkbooksApiResponse response3 = filterEx.getOrganisationsViaFilterArray();

		JsonArray response1Data = null;
		JsonArray response2Data = null;
		JsonArray response3Data = null;
		
		if (response1 != null && response2 != null) {
			response1Data = response1.getData();
			
			response2Data = response2.getData();
			
			same = same && (response1Data.size() == response2Data.size());			
		}
		if (response1 != null && response3 != null) {
			response1Data = response1.getData();
			
			response3Data = response3.getData();
			
			same = same && (response1Data.size() == response3Data.size());			
		}
		
		System.out.println("Size of each get: Filter: " + response1Data.size() + " FilterJson: " + response2Data.size() + " FilterArray: " + response3Data.size());
		
		if (!same){
			System.out.println("The results retrieved through different filter syntaxes differ");
			login.testExit(workbooks, 1);
		}
		login.testExit(workbooks, 0);
	}
	
	
	// First filter structure: specify arrays for Fields ('_ff[]'), comparaTors ('_ft[]'), Contents ('_fc[]').
	// Note that 'ct' (contains) is MUCH slower than equals. 'not_blank' requires Contents to compare with, but this is ignored.

	public WorkbooksApiResponse  getOrganisationsViaFilter() {
		
		populateSelectLimit();
		
		HashMap<String, Object> filter3 = new HashMap<String, Object> ();
		
		//Merge the limit_select and then add array of arrays
		filter3.putAll(limit_select);
		filter3.put("_ff[]", new String[]{"main_location[county_province_state]", "main_location[county_province_state]", "main_location[street_address]"}); 
		filter3.put("_ft[]", new String[]{"eq", "ct", "not_blank"});
		filter3.put("_fc[]", new String[]{"Berkshire", "Yorkshire", ""});
			
		filter3.put("_fm", "(1 OR 2) AND 3");    // How to combine the above clauses, without this: 'AND'.
		try {
			WorkbooksApiResponse response3 = workbooks.assertGet("crm/organisations", filter3, null);
			if (response3.getTotal() > 0) {
				workbooks.log("getOrganisationsViaFilter First ", new Object[] {response3.getFirstData()});		
			}
			JsonArray allData = response3.getData();
			for (int i = 0; i < allData.size(); i++) {
				JsonObject data = allData.getJsonObject(i);
				//System.out.println("Value Name:" + data.getString("name"));
			}	
			return response3;
		} catch (Exception wbe) {
			System.out.println("Error while getting the Organisations record: " + wbe);
			wbe.printStackTrace();
			login.testExit(workbooks, 1);
		}
		return null;
	}
	

	// The equivalent using a third filter structure: an array of filters, each containg 'field, comparator, contents'.
	
	public WorkbooksApiResponse  getOrganisationsViaFilterArray() {
		
		populateSelectLimit();
		
		HashMap<String, Object> filter3 = new HashMap<String, Object> ();
		
		/* Merge the limit_select and then add array of arrays
		 * Syntax to create array of arrays is 
		 * 1) [[a, b, c], [x,y,z], [1,2,3]]; Note the two square brackets See the below method for this syntax
		 * 2) new String[][] {{a,b,c}, {x,y,z}, {1,2,3}} 
		 */
		filter3.putAll(limit_select);
		filter3.put("_filters[]", new String[][] {
				{"main_location[county_province_state]", "eq", "Berkshire"}, 
				{"main_location[county_province_state]", "ct", "Yorkshire"},
				{"main_location[street_address]", "not_blank", ""}
			});
		filter3.put("_fm", "(1 OR 2) AND 3");    // How to combine the above clauses, without this: 'AND'.
		try {
			WorkbooksApiResponse response3 = workbooks.assertGet("crm/organisations", filter3, null);
			if (response3.getTotal() > 0) {
				workbooks.log("getOrganisationsViaFilterArray First: ", new Object[] {response3.getFirstData()});		
			}	
			JsonArray allData = response3.getData();
			if (allData!=null) {
				for (int i = 0; i < allData.size(); i++) {
					JsonObject data = allData.getJsonObject(i);
					//System.out.println("Value Name:" + data.getString("name"));
				}	
			}
			return response3;
		} catch (Exception wbe) {
			System.out.println("Error while getting the organisations record: " + wbe.getMessage());
			wbe.printStackTrace();
			login.testExit(workbooks, 1);
		}
		return null;
	}

	// The equivalent using a second filter structure: a JSON-formatted string  array of arrays containg 'field, comparator, contents'
	public WorkbooksApiResponse  getOrganisationsViaFilterJson() {
		
		populateSelectLimit();
		
		HashMap<String, Object> filter3 = new HashMap<String, Object> ();
		
		/* Merge the limit_select and then add array of arrays
		 * Syntax to create array of arrays is 
		 * 1) [[a, b, c], [x,y,z], [1,2,3]]; Note the two square brackets
		 * 2) new String[][] {{a,b,c}, {x,y,z}, {1,2,3}} See the above method for this syntax
		 */
		filter3.putAll(limit_select);
		filter3.put("_filter_json", "[" +
				"['main_location[county_province_state]', 'eq', 'Berkshire']," +
				"['main_location[county_province_state]', 'ct', 'Yorkshire']," +
				"['main_location[street_address]', 'not_blank', '']" +
				"]");
		filter3.put("_fm", "(1 OR 2) AND 3");
		
		try{
			WorkbooksApiResponse response3 = workbooks.assertGet("crm/organisations", filter3, null);
			if (response3.getTotal() > 0) {
				workbooks.log("getOrganisationsViaFilterJson First: ", new Object[] {response3.getFirstData()});		
			}		
			JsonArray allData = response3.getData();
			if (allData != null) {
				for (int i = 0; i < allData.size(); i++) {
					JsonObject data = allData.getJsonObject(i);
					System.out.println("Value Name:" + data.getString("name"));
				}	
			}
			return response3;
		} catch (Exception wbe) {
			System.out.println("Error while getting the organisations record: " + wbe.getMessage());
			wbe.printStackTrace();
			login.testExit(workbooks, 1);
		}
		return null;
	}
}
