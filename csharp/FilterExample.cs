 /**
  * 	License: www.workbooks.com/mit_license
  * 	Last commit $Id: FilterExample.cs 65874 2025-02-28 14:11:26Z jmonahan $
  *
  *   A demonstration of using the Workbooks API to find records using "filters" via a thin C# wrapper
  */
using System;
using WorkbooksApiApplication;
using System.Collections.Generic;
using System.Collections;

namespace ApiWrapper
{
	public class FilterExample
	{
		static WorkbooksApi  workbooks = null;
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
			"main_location[county_province_state]",
			"updated_at",
			"updated_by_user[person_name]"};
		Dictionary<string, object> limit_select = new Dictionary<string, object>();

		/*
	 * Start/Limit, Sort/Direction, Column selection
	 */
		private void populateSelectLimit() {

			limit_select.Add("_skip_total_rows", "true");
			limit_select.Add("_start", "0");
			limit_select.Add("_limit", "20");
			limit_select.Add("_sort", "id");
			limit_select.Add("_dir", "ASC");
			limit_select.Add("_select_columns[]", columns);
		}

		public static void Main() {
			login = new TestLoginHelper ();
			workbooks = login.testLogin ();

			FilterExample filterEx = new FilterExample ();
			filterEx.populateSelectLimit();

			filterEx.getOrganisationsViaFilter ();
			filterEx.getOrganisationsViaFilterArray ();
			filterEx.getOrganisationsViaFilterJson ();
		}

		// First filter structure: specify arrays for Fields ('_ff[]'), comparaTors ('_ft[]'), Contents ('_fc[]').
		// Note that 'ct' (contains) is MUCH slower than equals. 'not_blank' requires Contents to compare with, but this is ignored.

		public WorkbooksApiResponse  getOrganisationsViaFilter() {


			Dictionary<string, object> filter3 = new Dictionary<string, object> ();

			//Merge the limit_select and then add array of arrays
			foreach(string limitKey in limit_select.Keys) {
				filter3.Add (limitKey, limit_select [limitKey]);
			}
			filter3.Add("_ff[]", new String[]{"main_location[county_province_state]", "main_location[county_province_state]", "main_location[street_address]"});
			filter3.Add("_ft[]", new String[]{"eq", "ct", "not_blank"});
			filter3.Add("_fc[]", new String[]{"Berkshire", "Yorkshire", ""});

			filter3.Add("_fm", "(1 OR 2) AND 3");    // How to combine the above clauses, without this: 'AND'.
			try {
				WorkbooksApiResponse response3 = workbooks.assertGet("crm/organisations", filter3, null);

				//workbooks.log("getOrganisationsViaFilter First ", new Object[] {response3.getFirstData()});
				Console.WriteLine("Total: " + response3.getTotal());
        object[] allData = response3.getData();
        for (int i = 0; i < allData.Length; i++) {
					Dictionary<string, object> data = (Dictionary<string, object>)allData[i];
					Console.WriteLine(i + ") " +data["name"] + " - " +  data["main_location[county_province_state]"]);
				}
				return response3;
			} catch (Exception wbe) {
				Console.WriteLine("Error while getting the Organisations record: " + wbe);
				Console.WriteLine(wbe.StackTrace);
				login.testExit(workbooks, 1);
			}
			return null;
		}

		// The equivalent using a third filter structure: an array of filters, each containg 'field, comparator, contents'.

		public WorkbooksApiResponse  getOrganisationsViaFilterArray() {
			Dictionary<string, object> filter3 = new Dictionary<string, object> ();
		/*			 Merge the limit_select and then add array of arrays
		 * Syntax to create array of arrays is
		 * 1) [[a, b, c], [x,y,z], [1,2,3]]; Note the two square brackets . See below method for example
		 * 2) new String[][] {new string[]{a,b,c}, new string[] {x,y,z},new string[] {1,2,3}}
		 */
      //Merge the limit_select and then add array of arrays
      foreach(string limitKey in limit_select.Keys) {
        filter3.Add (limitKey, limit_select [limitKey]);
      }
			filter3.Add("_filters[]", new String[][] {
				new string[] {"main_location[county_province_state]", "eq", "Berkshire"},
				new string[] {"main_location[county_province_state]", "ct", "Yorkshire"},
				new string[] {"main_location[street_address]", "not_blank", ""}
			});
			filter3.Add("_fm", "(1 OR 2) AND 3");    // How to combine the above clauses, without this: 'AND'.
			try {
				WorkbooksApiResponse response3 = workbooks.assertGet("crm/organisations", filter3, null);
				Console.WriteLine("Total: " + response3.getTotal());
				//workbooks.log("getOrganisationsViaFilterArray First: ", new Object[] {response3.getFirstData()});

        object[] allData = response3.getData();
        for (int i = 0; i < allData.Length; i++) {
					Dictionary<string, object> data = (Dictionary<string, object>)allData[i];
					Console.WriteLine(i + ") " +data["name"] + " - " +  data["main_location[county_province_state]"]);
				}
				return response3;
			} catch (Exception wbe) {
				Console.WriteLine("Error while getting the organisations record: " + wbe.Message);
				Console.WriteLine (wbe.StackTrace);
				login.testExit(workbooks, 1);
			}
			return null;
		}

		// The equivalent using a second filter structure: a JSON-formatted string  array of arrays containg 'field, comparator, contents'
		public WorkbooksApiResponse getOrganisationsViaFilterJson() {

			Dictionary<string, object> filter3 = new Dictionary<string, object> ();
			/*
		 * Syntax to create array of arrays is
		 * 1) [[a, b, c], [x,y,z], [1,2,3]]; Note the two square brackets
		 * 2) new String[][] {new string[]{a,b,c}, new string[] {x,y,z},new string[] {1,2,3}} see the above method for this syntax
		 */
			//Merge the limit_select and then add array of arrays
			foreach(string limitKey in limit_select.Keys) {
				filter3.Add (limitKey, limit_select [limitKey]);
			}
			filter3.Add("_filter_json", "[" +
				"[\"main_location[county_province_state]\", \"eq\", \"Berkshire\"]," +
				"[\"main_location[county_province_state]\", \"ct\", \"Yorkshire\"]," +
				"[\"main_location[street_address]\", \"not_blank\", \"\"]" +
				"]");
			filter3.Add("_fm", "(1 OR 2) AND 3");

			try{
				WorkbooksApiResponse response3 = workbooks.assertGet("crm/organisations", filter3, null);

				//workbooks.log("getOrganisationsViaFilterJson First: ", new Object[] {response3.getFirstData()});
				Console.WriteLine("Total: " + response3.getTotal());
        object[] allData = response3.getData();
        for (int i = 0; i < allData.Length; i++) {
					Dictionary<string, object> data = (Dictionary<string, object>)allData[i];
					Console.WriteLine(i + ") " +data["name"] + " - " +  data["main_location[county_province_state]"]);
				}
				return response3;
			} catch (Exception wbe) {
				Console.WriteLine("Error while getting the organisations record: " + wbe.Message);
				Console.WriteLine(wbe.StackTrace);
				login.testExit(workbooks, 1);
			}
			return null;
		}

	}
}
