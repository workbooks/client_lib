 /**
  * 	License: www.workbooks.com/mit_license
  * 	Last commit $Id: NotesExample.cs 57625 2023-03-10 12:45:15Z klawless $
  *
  */
using System;
using System.Collections.Generic;
using WorkbooksApiApplication;
using System.Collections;

namespace NotesExampleApplication
{
	public class NotesExample
	{
		public static WorkbooksApi workbooks = null;
		String[] columns = {
      "id",
			"lock_version",
			"name",
      "subject",
      "text"
      };

    string created_person_id;
		List<Dictionary<string, object>> personObjectIdLockVersion = null;

		static TestLoginHelper testLoginHelper = null;
    public static void Main() {
			testLoginHelper = new TestLoginHelper();
			workbooks = testLoginHelper.testLogin();
			NotesExample notesExample = new NotesExample();
      notesExample.createPerson();
      notesExample.createTwoNotes();
      notesExample.getNotes();
      notesExample.deletePerson();
	//		testLoginHelper.testExit (workbooks, 0);
		}

    /// <summary>
    /// Create a Person so that we can attach some notes to this person
    /// </summary>
    private void createPerson() {

			List<Dictionary<string, object>> notes = new List<Dictionary<string, object>>();

			Dictionary<string, object> note1 = new Dictionary<string, object>();

			note1.Add("name", "Csharp Rich Richards");
			note1.Add("created_through_reference", "101");
			note1.Add("main_location[country]" , "UK");
			note1.Add("main_location[county_province_state]", "Berkshire");
			note1.Add("main_location[fax]", "01234 54646");
			note1.Add("main_location[postcode]" , "RG6 1AZ");
			note1.Add("main_location[street_address]"      , "100 Civvy Street");
			note1.Add("main_location[telephone]"            , "011897656");
			note1.Add("main_location[town]"                 , "Reading");
			note1.Add("no_email_soliciting"                 , false);
			note1.Add("no_phone_soliciting"                 , true);
			note1.Add("no_post_soliciting"                  , true);
			note1.Add("person_first_name"                   , "Richard");
			note1.Add("person_middle_name"                  , "");
			note1.Add("person_last_name"                    , "Richards");
			note1.Add("person_personal_title"               , "Mr.");
			note1.Add("website"                             , "www.richards.me.uk");

			notes.Add(note1);

			try {
				WorkbooksApiResponse response = workbooks.assertCreate("crm/people", notes, null, null);
				personObjectIdLockVersion = workbooks.idVersions(response);

				object[] allData = response.getAffectedObjects();

        // There should only be one object
				for (int i = 0; i < allData.Length; i++) {
					Dictionary<string, object> data = (Dictionary<string, object>)allData[i];
					Console.WriteLine("Person name: " + data["name"] + " Object Ref: " + data["object_ref"] + " id: " + data["id"]);
          created_person_id = "" + data["id"];
				}
			} catch (Exception wbe) {
				Console.WriteLine("Error while creating the person record: " + wbe.Message);
				Console.WriteLine(wbe.StackTrace);
				testLoginHelper.testExit(workbooks, 1);
			}
    }

    /// <summary>
    /// Create two notes.
    /// </summary>
		private void createTwoNotes() {

			List<Dictionary<string, object>> twoNotes = new List<Dictionary<string, object>>();

			Dictionary<string, object> note1 = new Dictionary<string, object>();

			note1.Add("resource_id", created_person_id);
			note1.Add("resource_type", "Private::Crm::Person");
			note1.Add("subject", "Note number 1");
			note1.Add("text", "This is the body of note number 1");

			Dictionary<string, object> note2 = new Dictionary<string, object>();

			//String[][] note2 = ...
			note2.Add("resource_id", created_person_id);
			note2.Add("resource_type", "Private::Crm::Person");
			note2.Add("subject", "Note number 2 🎂 with cake");
			note2.Add("text", "This is the body of note number 2, it has cake 🎂, a tree 🌲 and a snowman ⛄");

			twoNotes.Add(note1);
			twoNotes.Add(note2);

			try {
				WorkbooksApiResponse response = workbooks.assertCreate("notes.api", twoNotes, null, null);

				object[] allData = response.getAffectedObjects();

				for (int i = 0; i < allData.Length; i++) {
					Dictionary<string, object> data = (Dictionary<string, object>)allData[i];
					Console.WriteLine("Created Note Subject: " + data["subject"] + " Text: " + data["text"]);
				}
			} catch (Exception wbe) {
				Console.WriteLine("Error while creating the note records: " + wbe.Message);
				Console.WriteLine(wbe.StackTrace);
				testLoginHelper.testExit(workbooks, 1);
			}
		}

    /// <summary>
    /// Delete the person.
    /// </summary>
		private void deletePerson() {
			try {
					WorkbooksApiResponse responseDelete = workbooks.assertDelete("crm/people", personObjectIdLockVersion, null, null);

				  Dictionary<string, object> data = responseDelete.getFirstAffectedObject();
          //foreach (var item in data) {
          //  Console.WriteLine("key = {0}", item.Key);
          //  }
          Console.WriteLine("Deleted Person id = " + data["id"]);
			} catch (Exception wbe) {
				Console.WriteLine("Error while deleting the people record: " + wbe.Message);
				Console.WriteLine(wbe.StackTrace);
				testLoginHelper.testExit(workbooks, 1);
			}
    }

    /// <summary>
    /// Gets the notes based on the filters given
    /// </summary>
		public void getNotes() {
			Dictionary<string, object>   filter_limit_select = new Dictionary<string, object> ();

			filter_limit_select.Add("_start", "0");
			filter_limit_select.Add("_limit", "100");
			filter_limit_select.Add("_sort", "id");
			filter_limit_select.Add("_dir", "ASC");
			filter_limit_select.Add("_ff[]", "resource_id");
			filter_limit_select.Add("_ft[]", "eq");
			filter_limit_select.Add("_fc[]", created_person_id);
			filter_limit_select.Add("_select_columns[]", columns);

			try {
				WorkbooksApiResponse response = workbooks.get("notes.api", filter_limit_select, null);

				object[] allData = response.getData();
				if (allData != null) {
				  Console.WriteLine("Response Total: " + response.getTotal());

				  for (int i = 0; i < allData.Length; i++) {
					  Dictionary<string, object> data = (Dictionary<string, object>)allData[i];
					  Console.WriteLine("Got Note Subject: " + data["subject"] + " Text: " + data["text"]);
				  }
				}
			} catch (Exception wbe) {
				Console.WriteLine("Error while getting the notes records: " + wbe.Message);
				Console.WriteLine ("Stacktrace: " + wbe);
				testLoginHelper.testExit(workbooks, 1);
			}
		}
  }
}
