//
// A demonstration of using the Workbooks API via a thin C# wrapper
//
// Last commit $Id: SimpleExample.cs 57625 2023-03-10 12:45:15Z klawless $
// License: www.workbooks.com/mit_license
//

using WorkbooksApiApplication;
using System;
using System.Collections.Generic;

public class SimpleExample
{
  
  public static WorkbooksApi workbooks = null;
  static TestLoginHelper login = null;


  public static void Main() {
    login = new TestLoginHelper();
    workbooks = login.testLogin();
    SimpleExample simpleEx = new SimpleExample ();
    simpleEx.createOrganisations (true, true, true);
    simpleEx.getOrganisations ();
    login.testExit(workbooks, 0);
  }

  private void createOrganisations(bool doUpdate, bool doDelete, bool doBatch) {
    List<Dictionary<string, object>> objectIdLockVersion = null;

    try{
      //*************Multiple Organisations
      Dictionary<string, object> org2 = new Dictionary<string, object>();
      Dictionary<string, object> org3 = new Dictionary<string, object>();
      Dictionary<string, object> org4 = new Dictionary<string, object>();
      List<Dictionary<string, object>> multipleOrganisations = new List<Dictionary<string, object>>();

      org2.Add("name"                                 , "Freedom & Light Ltd");
      org2.Add("created_through_reference"            , "12345");
      org2.Add("industry"                             , "Media & Entertainment");
      org2.Add("main_location[country]"               , "United Kingdom");
      org2.Add("main_location[county_province_state]" , "Berkshire");
      org2.Add("main_location[fax]"                   , "0234 567890");
      org2.Add("main_location[postcode]"              , "RG99 9RG");
      org2.Add("main_location[street_address]"        , "100 Main Street");
      org2.Add("main_location[telephone]"             , "0123 456789");
      org2.Add("main_location[town]"                  , "Beading");
      org2.Add("no_phone_soliciting"                  , true);
      org2.Add("no_post_soliciting"                   , true);
      org2.Add("organisation_annual_revenue"          , "10000000");
      org2.Add("organisation_category"                , "Marketing Agency");
      org2.Add("organisation_company_number"          , "12345678");
      org2.Add("organisation_num_employees"           , 250);
      org2.Add("organisation_vat_number"              , "GB123456");
      org2.Add("website"                              , "www.freedomandlight.com");    

      org3.Add("name", "Freedom Power Tools Limited");
      org3.Add("created_through_reference", "12346");

      org4.Add("name", "Freedom o\" the Seas Recruitment");
      org4.Add("created_through_reference", "12347");

      multipleOrganisations.Add(org2);
      multipleOrganisations.Add(org3);
      multipleOrganisations.Add(org4);

      WorkbooksApiResponse response = workbooks.assertCreate("crm/organisations", multipleOrganisations, null, null);

      workbooks.log("createOrganisations Multiple: ", new Object[] {response.print(response.getFirstAffectedObject())});
      objectIdLockVersion = workbooks.idVersions(response);

      // **************** UPDATE THE CREATED ORGANISATION RECORDS
      if (doUpdate) {

        // Clear the hashmaps to populate with the details to update
        multipleOrganisations.Clear();
        org2.Clear();
        org3.Clear();
        org4.Clear();

        org2.Add("id", ((Dictionary<string, object>)objectIdLockVersion[0])["id"]);
        org2.Add("lock_version", ((Dictionary<string, object>)objectIdLockVersion[0])["lock_version"]);
        org2.Add("name", "Freedom & Light Unlimited");
        org2.Add("main_location[postcode]", "RG66 6RG");
        org2.Add("main_location[street_address]", "199 High Street");

        org3.Add("id", ((Dictionary<string, object>)objectIdLockVersion[1])["id"]);
        org3.Add("lock_version", ((Dictionary<string, object>)objectIdLockVersion[1])["lock_version"]);
        org3.Add("name", "Freedom Power");

        org4.Add("id", ((Dictionary<string, object>)objectIdLockVersion[2])["id"]);
        org4.Add("lock_version", ((Dictionary<string, object>)objectIdLockVersion[2])["lock_version"]);
        org4.Add("name", "Sea Recruitment");

        multipleOrganisations.Add(org2);
        multipleOrganisations.Add(org3);
        multipleOrganisations.Add(org4);

        response = workbooks.assertUpdate("crm/organisations", multipleOrganisations, null, null);
        workbooks.log("Updated organisations ", new Object[] {response.print(response.getFirstAffectedObject())});
      }
      objectIdLockVersion = workbooks.idVersions(response);   

      if (doBatch) {
        //************** BATCH ALL
        Dictionary<string, object> createAction = new Dictionary<string, object> ();
        Dictionary<string, object> updateAction =  new Dictionary<string, object> ();
        Dictionary<string, object> deleteAction =  new Dictionary<string, object> ();
        Dictionary<string, object> deleteAnotherAction =  new Dictionary<string, object> ();
        List<Dictionary<string, object>> batchActions = new List<Dictionary<string, object>>();


        createAction.Add("method"                           , "CREATE");
        createAction.Add("name"                                 , "Abercrombie Pies");
        createAction.Add("industry"                             , "Food");
        createAction.Add("main_location[country]"               , "United Kingdom");
        createAction.Add("main_location[county_province_state]" , "Berkshire");
        createAction.Add("main_location[town]"                  , "Beading");

        updateAction.Add("method", "UPDATE");
        updateAction.Add("id", ((Dictionary<string, object>)objectIdLockVersion[0])["id"]);
        updateAction.Add("lock_version", ((Dictionary<string, object>)objectIdLockVersion[0])["lock_version"]);
        updateAction.Add("name", "Lights \'R Us");
        updateAction.Add("main_location[postcode]", null);

        deleteAction.Add("method", "DELETE");
        deleteAction.Add("id", ((Dictionary<string, object>)objectIdLockVersion[1])["id"]);
        deleteAction.Add("lock_version", ((Dictionary<string, object>)objectIdLockVersion[1])["lock_version"]);

        deleteAnotherAction.Add("id", ((Dictionary<string, object>)objectIdLockVersion[2])["id"]);
        deleteAnotherAction.Add("lock_version", ((Dictionary<string, object>)objectIdLockVersion[2])["lock_version"]);
        deleteAnotherAction.Add("method", "DELETE");

        batchActions.Add(createAction);
        batchActions.Add(updateAction);
        batchActions.Add(deleteAction);
        batchActions.Add(deleteAnotherAction);

        response = workbooks.assertBatch("crm/organisations", batchActions , null, null, null);
        workbooks.log("Batch Actions: ", new Object[] {response.print(response.getFirstAffectedObject())});
      }
      objectIdLockVersion = workbooks.idVersions(response);

      //************** CREATE A SINGLE ORGANISATION
      List<Dictionary<string, object>> singleOrganisation = new List<Dictionary<string, object>>();   
      Dictionary<string, object> org1 = new Dictionary<string, object>();

      org1.Add("name", "Birkbeck Burgers");
      org1.Add("industry", "Food");
      org1.Add("main_location[country]", "United Kingdom");
      org1.Add("main_location[county_province_state]", "Oxfordshire");
      org1.Add("main_location[town]", "Oxford");

      singleOrganisation.Add(org1);
      response = workbooks.assertCreate("crm/organisations", singleOrganisation, null, null);
      workbooks.log("createOrganisations Single: ", new Object[] {response.getFirstAffectedObject()});
      List<Dictionary<string, object>> createdObjectIdLockVersion = workbooks.idVersions(response);

      createdObjectIdLockVersion.Add((Dictionary<string, object>)objectIdLockVersion[0]);
      createdObjectIdLockVersion.Add((Dictionary<string, object>)objectIdLockVersion[1]);
      //***************** DELETE THE REMAIIG ORGANISATIONS CREATED IN THIS CLASS
      if (doDelete) {         
        workbooks.assertDelete("crm/organisations", createdObjectIdLockVersion, null, null);
        workbooks.log("Delete Organisations ");
      }
    } catch(Exception e) {
      Console.WriteLine (e.StackTrace);
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
    Dictionary<string, object>   filter_limit_select = new Dictionary<string, object> ();

    filter_limit_select.Add("_start", "0");// Starting from the 'zeroth' record
    filter_limit_select.Add("_limit", "100");//   fetch up to 100 records
    filter_limit_select.Add("_sort", "id");// Sort by 'id'
    filter_limit_select.Add("_dir", "ASC");//   in ascending order
    filter_limit_select.Add("_ff[]", "main_location[county_province_state]");// Filter by this column
    filter_limit_select.Add("_ft[]", "ct");//   containing
    filter_limit_select.Add("_fc[]", "Berkshire");//   'Berkshire'
    filter_limit_select.Add("_select_columns[]", columns);  // An array, of columns to select
    try {
      WorkbooksApiResponse response = workbooks.assertGet("crm/organisations", filter_limit_select, null);
      workbooks.log("Total organisations: " , new Object[] {response.getTotal()} );
      workbooks.log ("First Organisation: ", new object[] {response.print (response.getFirstData ())});
         } catch(Exception e) {
      Console.WriteLine (e.StackTrace);
    }

  }

}
