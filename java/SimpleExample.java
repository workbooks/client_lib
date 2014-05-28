package workbooks_app.client_lib.java;

import java.util.ArrayList;
import java.util.HashMap;

import workbooks_app.client_lib.java.WorkbooksApi.WorkbooksApiResponse;

/**
 *  A demonstration of using the Workbooks API via a thin Java wrapper
 * 	
 *  License: www.workbooks.com/mit_license
 * 	Last commit $Id: SimpleExample.java 22080 2014-05-21 12:53:52Z bviroja $
 *
 */
public class SimpleExample {

	static WorkbooksApi  workbooks = null;
	static TestLoginHelper login = null;
	
	/**
	 * @param args
	 */
	public static void main(String[] args) {
		login = new TestLoginHelper();
		workbooks = login.testLogin();
		
		SimpleExample orgEx = new SimpleExample();
		orgEx.createOrganisations(true, true, true);
		orgEx.getOrganisations();
		
		login.testExit(workbooks, 0);
	}
	
	
	private void createOrganisations(boolean doUpdate, boolean doDelete, boolean doBatch) {
		ArrayList<HashMap<String, Object>> objectIdLockVersion = null;

		try{
			//*************Multiple Organisations
			HashMap<String, Object> org2 = new HashMap<String, Object>();
			HashMap<String, Object> org3 = new HashMap<String, Object>();
			HashMap<String, Object> org4 = new HashMap<String, Object>();
			ArrayList<HashMap<String, Object>> multipleOrganisations = new ArrayList<HashMap<String, Object>>();
			
      org2.put("name"                                 , "Freedom & Light Ltd");
      org2.put("created_through_reference"            , "12345");
      org2.put("industry"                             , "Media & Entertainment");
      org2.put("main_location[country]"               , "United Kingdom");
      org2.put("main_location[county_province_state]" , "Berkshire");
      org2.put("main_location[fax]"                   , "0234 567890");
      org2.put("main_location[postcode]"              , "RG99 9RG");
      org2.put("main_location[street_address]"        , "100 Main Street");
      org2.put("main_location[telephone]"             , "0123 456789");
      org2.put("main_location[town]"                  , "Beading");
      org2.put("no_phone_soliciting"                  , true);
      org2.put("no_post_soliciting"                   , true);
      org2.put("organisation_annual_revenue"          , "10000000");
      org2.put("organisation_category"                , "Marketing Agency");
      org2.put("organisation_company_number"          , "12345678");
      org2.put("organisation_num_employees"           , 250);
      org2.put("organisation_vat_number"              , "GB123456");
      org2.put("website"                              , "www.freedomandlight.com");    

      org3.put("name", "Freedom Power Tools Limited");
      org3.put("created_through_reference", "12346");
			
      org4.put("name", "Freedom o\" the Seas Recruitment");
      org4.put("created_through_reference", "12347");
      
      multipleOrganisations.add(org2);
      multipleOrganisations.add(org3);
      multipleOrganisations.add(org4);
      
      WorkbooksApiResponse response = workbooks.assertCreate("crm/organisations", multipleOrganisations, null, null);
			
			workbooks.log("createOrganisations Multiple: ", new Object[] {response.getFirstAffectedObject()});
			objectIdLockVersion = workbooks.idVersions(response);
			
		// **************** UPDATE THE CREATED ORGANISATION RECORDS
			if (doUpdate) {
			
			// Clear the hashmaps to populate with the details to update
			multipleOrganisations.clear();
			org2.clear();
			org3.clear();
			org4.clear();
			
			org2.put("id", ((HashMap<String, Object>)objectIdLockVersion.get(0)).get("id"));
			org2.put("lock_version", ((HashMap<String, Object>)objectIdLockVersion.get(0)).get("lock_version"));
			org2.put("name", "Freedom & Light Unlimited");
			org2.put("main_location[postcode]", "RG66 6RG");
			org2.put("main_location[street_address]", "199 High Street");
			
			org3.put("id", ((HashMap<String, Object>)objectIdLockVersion.get(1)).get("id"));
			org3.put("lock_version", ((HashMap<String, Object>)objectIdLockVersion.get(1)).get("lock_version"));
			org3.put("name", "Freedom Power");
			
			org4.put("id", ((HashMap<String, Object>)objectIdLockVersion.get(2)).get("id"));
			org4.put("lock_version", ((HashMap<String, Object>)objectIdLockVersion.get(2)).get("lock_version"));
			org4.put("name", "Sea Recruitment");
			
			multipleOrganisations.add(org2);
			multipleOrganisations.add(org3);
			multipleOrganisations.add(org4);
			
			response = workbooks.assertUpdate("crm/organisations", multipleOrganisations, null, null);
			workbooks.log("Updated organisations ", new Object[] {response.getFirstAffectedObject()});
			}
			objectIdLockVersion = workbooks.idVersions(response);		

			if (doBatch) {
			//************** BATCH ALL
			HashMap<String, Object> createAction = new HashMap<String, Object> ();
			HashMap<String, Object> updateAction =  new HashMap<String, Object> ();
			HashMap<String, Object> deleteAction =  new HashMap<String, Object> ();
			HashMap<String, Object> deleteAnotherAction =  new HashMap<String, Object> ();
			ArrayList<HashMap<String, Object>> batchActions = new ArrayList<HashMap<String, Object>>();
			
			
			createAction.put("method"														, "CREATE");
	    createAction.put("name"                                 , "Abercrombie Pies");
	    createAction.put("industry"                             , "Food");
	    createAction.put("main_location[country]"               , "United Kingdom");
	    createAction.put("main_location[county_province_state]" , "Berkshire");
	    createAction.put("main_location[town]"                  , "Beading");
	    
	    updateAction.put("method", "UPDATE");
	    updateAction.put("id", ((HashMap<String, Object>)objectIdLockVersion.get(0)).get("id"));
			updateAction.put("lock_version", ((HashMap<String, Object>)objectIdLockVersion.get(0)).get("lock_version"));
			updateAction.put("name", "Lights \'R Us");
			updateAction.put("main_location[postcode]", null);
	    
			deleteAction.put("method", "DELETE");
			deleteAction.put("id", ((HashMap<String, Object>)objectIdLockVersion.get(1)).get("id"));
			deleteAction.put("lock_version", ((HashMap<String, Object>)objectIdLockVersion.get(1)).get("lock_version"));
			
			deleteAnotherAction.put("id", ((HashMap<String, Object>)objectIdLockVersion.get(2)).get("id"));
			deleteAnotherAction.put("lock_version", ((HashMap<String, Object>)objectIdLockVersion.get(2)).get("lock_version"));
			deleteAnotherAction.put("method", "DELETE");

			batchActions.add(createAction);
			batchActions.add(updateAction);
			batchActions.add(deleteAction);
			batchActions.add(deleteAnotherAction);
			
			response = workbooks.assertBatch("crm/organisations", batchActions , null, null, null);
			workbooks.log("Batch Actions: ", new Object[] {response.getFirstAffectedObject()});
			}
			objectIdLockVersion = workbooks.idVersions(response);
			
			//************** CREATE A SINGLE ORGANISATION
			ArrayList<HashMap<String, Object>> singleOrganisation = new ArrayList<HashMap<String, Object>>();		
			HashMap<String, Object> org1 = new HashMap<String, Object>();
			
			org1.put("name", "Birkbeck Burgers");
			org1.put("industry", "Food");
			org1.put("main_location[country]", "United Kingdom");
			org1.put("main_location[county_province_state]", "Oxfordshire");
			org1.put("main_location[town]", "Oxford");
			
			singleOrganisation.add(org1);
			response = workbooks.assertCreate("crm/organisations", singleOrganisation, null, null);
			workbooks.log("createOrganisations Single: ", new Object[] {response.getFirstAffectedObject()});
			ArrayList<HashMap<String, Object>> createdObjectIdLockVersion = workbooks.idVersions(response);
			
			createdObjectIdLockVersion.add((HashMap<String, Object>)objectIdLockVersion.get(0));
			createdObjectIdLockVersion.add((HashMap<String, Object>)objectIdLockVersion.get(1));
			//***************** DELETE THE REMAIIG ORGANISATIONS CREATED IN THIS CLASS
			if (doDelete) {					
						WorkbooksApiResponse responseDelete = workbooks.assertDelete("crm/organisations", createdObjectIdLockVersion, null, null);
						workbooks.log("Delete Organisations ", new Object[] {responseDelete.getFirstAffectedObject()});
			}
		} catch(Exception e) {
			e.printStackTrace();
		}
		
	}
	
	private void getOrganisations() {
		String[] columns = {"id",
		    "lock_version",
		    "name",
		    "object_ref",
		    "main_location[town]",
		    "updated_at",
		    "updated_by_user[person_name]"};
		HashMap<String, Object>   filter_limit_select = new HashMap<String, Object> ();
		
		filter_limit_select.put("_start", "0");// Starting from the 'zeroth' record
		filter_limit_select.put("_limit", "100");//   fetch up to 100 records
		filter_limit_select.put("_sort", "id");// Sort by 'id'
		filter_limit_select.put("_dir", "ASC");//   in ascending order
		filter_limit_select.put("_ff[]", "main_location[county_province_state]");// Filter by this column
		filter_limit_select.put("_ft[]", "ct");//   containing
		filter_limit_select.put("_fc[]", "Berkshire");//   'Berkshire'
		filter_limit_select.put("_select_columns[]", columns);  // An array, of columns to select
		try {
		WorkbooksApiResponse response = workbooks.assertGet("crm/organisations", filter_limit_select, null);
		workbooks.log("Total organisations: " , new Object[] {response.getTotal()} );
		
		workbooks.log("Get Organisations : ", new Object[] {response.getFirstData()});
		} catch(Exception e) {
			e.printStackTrace();
		}
    
	}


}
