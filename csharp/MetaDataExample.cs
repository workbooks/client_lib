// License: www.workbooks.com/mit_license
// Last commit $Id$
//
// A demonstration of using the Workbooks API to fetch metadata via a thin C# wrapper


using System;
using WorkbooksApiApplication;
using System.Collections.Generic;

namespace ApiWrapper
{
  public class MetaDataExample
  {
    static WorkbooksApi  workbooks = null;
    static TestLoginHelper login = null;

    string[] columns = {"id",
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

    public static void Main() {
      login = new TestLoginHelper();
      workbooks = login.testLogin();

      MetaDataExample metaDataEx = new MetaDataExample();
      metaDataEx.fetchSummary();
      metaDataEx.fetchSomeMoreSummary();

      login.testExit(workbooks, 0);
    }

    public void fetchSummary() {
      string[] classNames = {
        "Private::Searchable",
        "Private::Crm::Person",
        "Private::Crm::Organisation",
        "Private::Crm::Case"};

      Dictionary<string, object> classMetaData = new Dictionary<string, object> ();

      classMetaData.Add("class_names[]", classNames);
      classMetaData.Add("_select_columns[]", columns);

      try {
        WorkbooksApiResponse response = workbooks.assertGet("metadata/types", classMetaData, null);

        workbooks.log("fetchSummary Total: ", new Object[] {response.getTotal()});  
        //      if (response.getTotal() != null && response.getTotal() > 0) {
        //        workbooks.log("fetchSummary First: ", new Object[] {response.getFirstData()});
        //      }     
      } catch (Exception e) {
        Console.WriteLine("Error while getting the metadata: " + e.Message);
        Console.WriteLine (e.StackTrace);
        login.testExit(workbooks, 1);
      }
    }

    public void fetchSomeMoreSummary() {
      string[] classNames = {
        "Private::Crm::Case"};

      string[] addColumns = {"id",
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


      Dictionary<string, object> classMetaData = new Dictionary<string, object> ();

      classMetaData.Add("class_names[]", classNames);
      classMetaData.Add("_select_columns[]", addColumns);

      try {
        WorkbooksApiResponse response = workbooks.assertGet("metadata/types", classMetaData, null);

        workbooks.log("fetchSomeMoreSummary Total: ", new Object[] {response.getTotal()});  
        //      if (response.getTotal() != null && response.getTotal() > 0) {
        //        workbooks.log("fetchSomeMoreSummary First: ", new Object[] {response.getFirstData()});
        //      }   
      } catch (Exception e) {
        Console.WriteLine("Error while getting the metadata: " + e.Message);
        Console.WriteLine ((e.StackTrace));
        login.testExit(workbooks, 1);
      }
    }

    public void fetchAll() {
      Dictionary<string, object> classMetaData = new Dictionary<string, object> ();
      try {
        WorkbooksApiResponse response = workbooks.assertGet("metadata/types", classMetaData, null);

        workbooks.log("fetchAll Total: ", new Object[] {response.getTotal()});  
        //      if (response.getTotal() > 0) {
        //        workbooks.log("fetchAll First: ", new Object[] {response.getFirstData()});
        //      }
      } catch (Exception e) {
        Console.WriteLine("Error while getting the metadata: " + e.Message);
        Console.WriteLine ((e.StackTrace));
        login.testExit(workbooks, 1);
      }
    }

  }
}

