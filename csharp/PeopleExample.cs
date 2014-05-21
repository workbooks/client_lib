 /**
  * 	License: www.workbooks.com/mit_license
  * 	Last commit $Id$
  *
  */
using System;
using System.Collections.Generic;
using WorkbooksApiApplication;
using System.Collections;

namespace PeopleExampleApplication
{
	public class PeopleExample
	{
		public static WorkbooksApi workbooks = null;
		String[] columns = {"id",
			"lock_version",
			"name",
			"object_ref",
			"main_location[telephone]",
			"main_location[town]",
			"updated_at",
			"updated_by_user[person_name]"};

		static TestLoginHelper testLoginHelper = null;
    public static void Main() {
			testLoginHelper = new TestLoginHelper();
			workbooks = testLoginHelper.testLogin ();
			PeopleExample peopleExample = new PeopleExample ();
      peopleExample.createTwoPeople (true, false);
      peopleExample.getPeople ();
	//		testLoginHelper.testExit (workbooks, 0);
		}

    /// <summary>
    /// Create two people, tagging with their identifiers in the external system. Up to 100 can be done in one batch.
    /// </summary>
    /// <param name="doUpdate">If set to <c>true</c> do update on the created people record.</param>
    /// <param name="doDelete">If set to <c>true</c> do delete on the created people record.</param>
		private void createTwoPeople(bool doUpdate, bool doDelete) {

			List<Dictionary<string, object>> twoPeople = new List<Dictionary<string, object>>();

			Dictionary<string, object> person1 = new Dictionary<string, object>();
			List<Dictionary<string, object>> objectIdLockVersion = null;

			person1.Add("name", "Csharp Rich Richards");
			person1.Add("created_through_reference", "101");
			person1.Add("main_location[country]" , "UK");
			person1.Add("main_location[county_province_state]", "Berkshire");
			person1.Add("main_location[fax]", "01234 54646");
			person1.Add("main_location[postcode]" , "RG6 1AZ");
			person1.Add("main_location[street_address]"      , "100 Civvy Street");
			person1.Add("main_location[telephone]"            , "011897656");
			person1.Add("main_location[town]"                 , "Reading");
			person1.Add("no_email_soliciting"                 , false);
			person1.Add("no_phone_soliciting"                 , true);
			person1.Add("no_post_soliciting"                  , true);
			person1.Add("person_first_name"                   , "Richard");
			person1.Add("person_middle_name"                  , "");
			person1.Add("person_last_name"                    , "Richards");
			person1.Add("person_personal_title"               , "Mr.");
			person1.Add("website"                             , "www.richards.me.uk");

			Dictionary<string, object> person2 = new Dictionary<string, object>();

			//String[][] person2 = ...
			person2.Add("name", "Csharp Stevie Stephens");
			person2.Add("created_through_reference", "102");
			person2.Add("main_location[country]" , "UK");
			person2.Add("main_location[county_province_state]", "Berkshire");
			person2.Add("main_location[fax]", "01234 54646");
			person2.Add("main_location[postcode]" , "RG6 1AZ");
			person2.Add("main_location[street_address]"      , "102 Castle Street");
			person2.Add("main_location[telephone]"            , "011897656");
			person2.Add("main_location[town]"                 , "Reading");
			person2.Add("no_email_soliciting"                 , false);
			person2.Add("no_phone_soliciting"                 , true);
			person2.Add("no_post_soliciting"                  , true);
			person2.Add("person_first_name"                   , "Steve");
			person2.Add("person_middle_name"                  , "");
			person2.Add("person_last_name"                    , "Stephens");
			person2.Add("person_personal_title"               , "Mr.");
			person2.Add("website"                             , "www.steve.me.uk");


			twoPeople.Add(person1);
			twoPeople.Add(person2);
			try {
				WorkbooksApiResponse response = workbooks.assertCreate("crm/people", twoPeople, null, null);

				object[] allData = response.getAffectedObjects();

				for (int i = 0; i < allData.Length; i++) {
					Dictionary<string, object> data = (Dictionary<string, object>)allData[i];
					Console.WriteLine("Person name: " + data["name"] + " Object Ref: " + data["object_ref"]);
				}

				// **************** UPDATE THE TWO CREATED PEOPLE RECORDS
				if (doUpdate) {
					objectIdLockVersion = workbooks.idVersions(response);

					// Clear the hashmaps to populate with the details to update
					twoPeople.Clear();
					person1.Clear();
					person2.Clear();

					person1.Add("id", ((Dictionary<string, object>)objectIdLockVersion[0])["id"]);
					person1.Add("lock_version", ((Dictionary<string, object>)objectIdLockVersion[0])["lock_version"]);
					person1.Add("main_location[email]", "richards@one.com");

					person2.Add("id", ((Dictionary<string, object>)objectIdLockVersion[1])["id"]);
					person2.Add("lock_version", ((Dictionary<string, object>)objectIdLockVersion[1])["lock_version"]);
					person2.Add("main_location[email]", "steve@stevie.com");

					twoPeople.Add(person1);
					twoPeople.Add(person2);

					WorkbooksApiResponse responseUpdate = workbooks.assertUpdate("crm/people", twoPeople, null, null);
					Console.WriteLine("Updated people. " );
					//workbooks.log("update_two_people", new Object[] {responseUpdate.getFirstAffectedObjects()});
				}
				//***************** DELETE THE TWO CREATED PEOPLE
				if (doDelete) {
					objectIdLockVersion = workbooks.idVersions(response);

					WorkbooksApiResponse responseDelete = workbooks.assertDelete("crm/people", objectIdLockVersion, null, null);

					workbooks.log("delete_two_people", new Object[] {responseDelete.getFirstAffectedObject()});
				}

			} catch (Exception wbe) {
				Console.WriteLine("Error while creating the people record: " + wbe.Message);
				Console.WriteLine(wbe.StackTrace);
				testLoginHelper.testExit(workbooks, 1);
			}

		}

    /// <summary>
    /// Gets the people based on the filters given
    /// </summary>
		public void getPeople() {
			Dictionary<string, object>   filter_limit_select = new Dictionary<string, object> ();

			filter_limit_select.Add("_start", "0");
			filter_limit_select.Add("_limit", "3");
			filter_limit_select.Add("_sort", "id");
			filter_limit_select.Add("_dir", "ASC");
			filter_limit_select.Add("_ff[]", "name");
			filter_limit_select.Add("_ft[]", "bg");
			filter_limit_select.Add("_fc[]", "Alex");
			filter_limit_select.Add("_select_columns[]", columns);
			try {
				WorkbooksApiResponse response = workbooks.get("crm/people", filter_limit_select, null);
				object[] allData = response.getData();
				if (allData != null) {
				  Console.WriteLine("Response Total: " + response.getTotal());

				  for (int i = 0; i < allData.Length; i++) {
					  Dictionary<string, object> data = (Dictionary<string, object>)allData[i];
					  Console.WriteLine("Person name: " + data["name"] + " Object Ref: " + data["object_ref"]);
				  }
				}
			} catch (Exception wbe) {
				Console.WriteLine("Error while getting the people record: " + wbe.Message);
				Console.WriteLine ("Stacktrace: " + wbe);
				testLoginHelper.testExit(workbooks, 1);
			}

		}

	}
}
