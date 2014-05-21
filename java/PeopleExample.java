package workbooks_app.client_lib.java;

import java.text.SimpleDateFormat;
import java.util.ArrayList;
import java.util.Date;
import java.util.HashMap;
import java.util.Random;

import javax.json.JsonArray;
import javax.json.JsonObject;

import workbooks_app.client_lib.java.WorkbooksApi.WorkbooksApiResponse;

/** 
 *	A demonstration of using the Workbooks API to operate on People via a thin Java wrapper.
 *  The created_through_reference and created_through attributes are used as if the caller
 *  were synchronising with an external service.
 * 
 * 	License: www.workbooks.com/mit_license
 * 	Last commit $Id: PeopleExample.java 22068 2014-05-20 11:54:15Z jkay $
 */

public class PeopleExample {
	static WorkbooksApi  workbooks = null;
	String[] columns = {"id",
		    "lock_version",
		    "name",
		    "object_ref",
		    "main_location[telephone]",
		    "main_location[town]",
		    "updated_at",
		    "updated_by_user[person_name]"};

	static TestLoginHelper login = null;
	
	public static void main(String[] args) {
		login = new TestLoginHelper();
		workbooks = login.testLogin();
		
		PeopleExample peopleEx = new PeopleExample();
		peopleEx.create_two_people(true, false);
		peopleEx.getAllPeopleInPastFewWeeks();
		peopleEx.deletePeople();
		
		peopleEx.getPeople();
		
		login.testExit(workbooks, 0);
	}

	/**
	 * Create two people, tagging with their identifiers in the external system. Up to 100 can be done in one batch.
	 * 
	 * @param doUpdate - whether to perform the update operation on the created people record
	 * @param doDelete - whether to perform the delete operation on the created people record
	 */
	private void create_two_people(boolean doUpdate, boolean doDelete) {
		
		ArrayList<HashMap<String, Object>> twoPeople = new ArrayList<HashMap<String, Object>>();
		
		HashMap<String, Object> person1 = new HashMap<String, Object>();
		ArrayList<HashMap<String, Object>> objectIdLockVersion = null;
		
		person1.put("name", "Richard Richards");                                
		person1.put("created_through_reference", "101");           
		person1.put("main_location[country]" , "UK");             
		person1.put("main_location[county_province_state]", "Berkshire");
		person1.put("main_location[fax]", "01234 54646");                  
		person1.put("main_location[postcode]" , "RG6 1AZ");            
		person1.put("main_location[street_address]"      , "100 Civvy Street"); 
		person1.put("main_location[telephone]"            , "011897656");
		person1.put("main_location[town]"                 , "Reading");
		person1.put("no_email_soliciting"                 , false);
		person1.put("no_phone_soliciting"                 , true);
		person1.put("no_post_soliciting"                  , true);
		person1.put("person_first_name"                   , "Richard");
		person1.put("person_middle_name"                  , "");
		person1.put("person_last_name"                    , "Richards");
		person1.put("person_personal_title"               , "Mr.");
		person1.put("website"                             , "www.richards.me.uk");
	
		HashMap<String, Object> person2 = new HashMap<String, Object>();
		
		//String[][] person2 = ... 
		person2.put("name", "Steve Stephens");                                
		person2.put("created_through_reference", "102");           
		person2.put("main_location[country]" , "UK");             
		person2.put("main_location[county_province_state]", "Berkshire");
		person2.put("main_location[fax]", "01234 54646");                  
		person2.put("main_location[postcode]" , "RG6 1AZ");            
		person2.put("main_location[street_address]"      , "102 Castle Street"); 
		person2.put("main_location[telephone]"            , "011897656");
		person2.put("main_location[town]"                 , "Reading");
		person2.put("no_email_soliciting"                 , false);
		person2.put("no_phone_soliciting"                 , true);
		person2.put("no_post_soliciting"                  , true);
		person2.put("person_first_name"                   , "Steve");
		person2.put("person_middle_name"                  , "");
		person2.put("person_last_name"                    , "Stephens");
		person2.put("person_personal_title"               , "Mr.");
		person2.put("website"                             , "www.steve.me.uk");
		
		
		twoPeople.add(person1);
		twoPeople.add(person2);
		try {
			WorkbooksApiResponse response = workbooks.assertCreate("crm/people", twoPeople, null, null);
			
			workbooks.log("create_two_people", new Object[] {response.getFirstAffectedObject()});	
	
			JsonArray allData = response.getAffectedObjects();
			
			for (int i = 0; i < allData.size(); i++) {
				JsonObject data = allData.getJsonObject(i);
				System.out.println("Ref: " +  data.getString("object_ref") + " Value :" + data.getString("created_at"));
			}
			
			// **************** UPDATE THE TWO CREATED PEOPLE RECORDS
			if (doUpdate) {
				objectIdLockVersion = workbooks.idVersions(response);
				
				// Clear the hashmaps to populate with the details to update
				twoPeople.clear();
				person1.clear();
				person2.clear();
				
				person1.put("id", ((HashMap)objectIdLockVersion.get(0)).get("id"));
				person1.put("lock_version", ((HashMap)objectIdLockVersion.get(0)).get("lock_version"));
				person1.put("main_location[email]", "richards@one.com");
				
				person2.put("id", ((HashMap)objectIdLockVersion.get(1)).get("id"));
				person2.put("lock_version", ((HashMap)objectIdLockVersion.get(1)).get("lock_version"));
				person2.put("main_location[email]", "steve@stevie.com");
				
				twoPeople.add(person1);
				twoPeople.add(person2);
				
				WorkbooksApiResponse responseUpdate = workbooks.assertUpdate("crm/people", twoPeople, null, null);
				workbooks.log("update_two_people", new Object[] {responseUpdate.getFirstAffectedObject()});
			}
			//***************** DELETE THE TWO CREATED PEOPLE
			if (doDelete) {
				objectIdLockVersion = workbooks.idVersions(response);
				
				WorkbooksApiResponse responseDelete = workbooks.assertDelete("crm/people", objectIdLockVersion, null, null);
				workbooks.log("delete_two_people", new Object[] {responseDelete.getFirstAffectedObject()});
			}
			
		} catch (Exception wbe) {
			System.out.println("Error while creating the people record: " + wbe.getMessage());
			wbe.printStackTrace();
			login.testExit(workbooks, 1);
		}
	
	}
	
	
	public void deletePeople() {
		// Get the people to delete
		WorkbooksApiResponse response = getPeople();
		
		JsonArray allData = response.getData();
		ArrayList retval = new ArrayList();
		
		if (allData.size() > 0) {
			for (int i = 0; i < allData.size(); i++) {
				JsonObject data = allData.getJsonObject(i);
				HashMap<String, Object> objectIdVersions = new HashMap<String, Object>();
				objectIdVersions.put("id", data.get("id"));
				objectIdVersions.put("lock_version", data.get("lock_version"));
				retval.add(objectIdVersions);
			}
			try {
				WorkbooksApiResponse responseDelete = workbooks.assertDelete("crm/people", retval, null, null);
				workbooks.log("delete_two_people", new Object[] {responseDelete.getFirstAffectedObject()});
			} catch (Exception wbe) {
				System.out.println("Error while deleting the people record: " + wbe.getMessage());
				wbe.printStackTrace();
				login.testExit(workbooks, 1);
			}
		}
	}

	/*
	 * List up to the first hundred people matching our 'created_through' attribute value, just selecting a few columns to retrieve
	 */

	public WorkbooksApiResponse getPeople() {
		
		HashMap<String, Object>   filter_limit_select = new HashMap<String, Object> ();
		
		filter_limit_select.put("_start", "0");
		filter_limit_select.put("_limit", "100");
		filter_limit_select.put("_sort", "id");
		filter_limit_select.put("_dir", "ASC");
		filter_limit_select.put("_ff[]", "name");
		filter_limit_select.put("_ft[]", "bg");
		filter_limit_select.put("_fc[]", "Alex");
		filter_limit_select.put("_select_columns[]", columns);
		try {
			WorkbooksApiResponse response = workbooks.assertGet("crm/people", filter_limit_select, null);
//			System.out.println("Response" + response.toString());
			JsonArray allData = response.getData();
			
			workbooks.log("getPeople", new Object[] {response.getFirstData()});
			
			for (int i = 0; i < allData.size(); i++) {
				JsonObject data = allData.getJsonObject(i);
				System.out.println("Person name: " + data.getString("name") + " Object Ref: " + data.getString("object_ref"));
			}
		
			return response;
		} catch (Exception wbe) {
			System.out.println("Error while getting the people record: " + wbe.getMessage());
			wbe.printStackTrace();
			login.testExit(workbooks, 1);
		}
		return null;
	}
	
	/*
	 * List every person in the system, in alphabetic name order, whether deleted or not, which 
	 * has been updated in the last three weeks. 
	 *
	 * If you are trying to have an external system replicate the contents of Workbooks, this is 
	 * the technique you would use, perhaps only requesting records which were updated since the
	 * time of the last fetch.
	 */

	public HashMap<String, Object> getAllPeopleInPastFewWeeks() {
		
		Date currentDate = new Date();
		SimpleDateFormat simpleDate = new SimpleDateFormat("EEE MMM dd HH:mm:ss z yyyy");
		
		long few_weeks_ago = currentDate.getTime() - (1 * 7 * 24 * 60 * 60);
		String updated_since = simpleDate.format(new Date(few_weeks_ago));
		
		ArrayList all_records = new ArrayList();
		int fetched = -1;
		int start = 0;
		while (fetched != 0) {
			HashMap<String, Object> fetch_chunk = new HashMap<String, Object> ();
		  
		    fetch_chunk.put("_start", Integer.toString(start));
		    fetch_chunk.put("_limit", Integer.toString(100));       // The maximum 
		    
		    fetch_chunk.put("_sort","id");      // Sort by 'id' to ensure every record is fetched
		    fetch_chunk.put("_dir","ASC");     //   in ascending order

		                                         // Filter 
		    fetch_chunk.put("_fm","1          AND (2 OR           3)");
		    fetch_chunk.put("_ff[]",new String[] {"updated_at",   "is_deleted",  "is_deleted"});
		    fetch_chunk.put("_ft[]",new String[] {"ge",           "true",        "false"     }); //   "ge" = "is on or after"
		    fetch_chunk.put("_fc[]",new String[] {updated_since, "",            ""          });

		    fetch_chunk.put("_select_columns[]",new String[]{     // An array, of columns to select. Fewer = faster
		      "id",                              //   Unique, incrementing, key for each record
		      "lock_version",                    //   Required if updating a record
		      "is_deleted",                      //   True or False
		      "object_ref",                      //   Also unique, more friendly key for each record

		      "name",                            //   Full name
		      "created_through",                 //   Source system
		      "created_through_reference",       //   Reference given by source system
		      "employer_link",                   //   Employer Organisation ID

		      "main_location[email]",
		      "main_location[fax]",
		      "main_location[mobile]",
		      "main_location[telephone]",
		      "main_location[street_address]", 
		      "main_location[town]",
		      "main_location[county_province_state]",
		      "main_location[postcode]",
		      "main_location[country]",
		      
		      "no_email_soliciting",

		      "person_first_name",
		      "person_job_role",
		      "person_job_title",
		      "person_last_name",
		      "person_middle_name",
		      "person_personal_title",
		      "person_salutation",
		      "person_suffix",

		      "created_at",
		      "created_by_user[person_name]",
		      "updated_at",
		      "updated_by_user[person_name]",
		    }
		  );
	    try {
	    	WorkbooksApiResponse response = workbooks.assertGet("crm/people", fetch_chunk, null);
	    		
	    		JsonArray allData = response.getData();
				for (int i = 0; i < allData.size(); i++) {
					JsonObject data = allData.getJsonObject(i);
					all_records.add(data);
				}
				
				fetched = allData.size();			  
				start += fetched;
			  
		  } catch(Exception e) {
			  workbooks.log("getAllWithinTwentyWeeks error", new Object[] {e}, "error", 4096);
			  e.printStackTrace();
			  login.testExit(workbooks, 1);
		  }
		}
		
		workbooks.log("getAllWithinTwentyWeeks", new Object[] {all_records, all_records.size()});
		
		
		return null;
	}
}
