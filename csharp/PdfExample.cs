// License: www.workbooks.com/mit_license
// Last commit $Id: PdfExample.cs 57625 2023-03-10 12:45:15Z klawless $
//
using System;
using WorkbooksApiApplication;
using System.Collections.Generic;
using System.Collections;

namespace ApiWrapper
{
  public class PdfExample
  {
    static WorkbooksApi  workbooks = null;
    int pdfTemplateId = 34;
    static TestLoginHelper login = null;

    public static void Main() {
      login = new TestLoginHelper();
      workbooks = login.testLogin();

      PdfExample pdfExample = new PdfExample();
      pdfExample.generatePDFs();
      login.testExit(workbooks, 0);

    }

    /* 
   * In order to generate a PDF you need to know the ID of the transaction document (the 
   * order, quotation, credit note etc) and the ID of a PDF template. You can find these out
   * for a particular PDF by using the Workbooks Desktop, generating a PDF and examining the
   * URL used to generate the PDF. You will see something like
   *
   *    https://secure.workbooks.com/accounting/sales_orders/11941.pdf?template=232
   *
   * which implies a document ID of 11941 and a template ID of 232. The 'sales_orders' part 
   * indicates the type of transaction document you want to reference; see the Workbooks API 
   * Reference for a list of the available API endpoints.
   */


    public void generatePDFs() {
      Dictionary<string, object>   filter_limit_select = new Dictionary<string, object> ();

      filter_limit_select.Add("_start", "0");
      filter_limit_select.Add("_limit", "1");
      filter_limit_select.Add("_sort", "id");
      filter_limit_select.Add("_dir", "ASC");
      filter_limit_select.Add("_select_columns[]", new String[] {"id"});
      try {
        WorkbooksApiResponse response = workbooks.assertGet("accounting/sales_orders", filter_limit_select, null);
        object[] allData = response.getData();
        if (allData != null) {
          if(allData.Length != 1) {
            workbooks.log("generatePDFs: Did not find any orders: ", new Object[] {allData});
            login.testExit(workbooks, 1);
          } else {
            int orderId = (int) ((Dictionary<string, object>)allData[0])["id"];
            // Now generate the PDF

            String url = "accounting/sales_orders/" + orderId + ".pdf";

            // Important to add the decode_json as false for PDFs
            Dictionary<string, object> options = new Dictionary<string, object> ();
            options.Add("decode_json", false);

            Dictionary<string, object> templateParams = new Dictionary<string, object> ();
            templateParams.Add("template", pdfTemplateId);

            workbooks.get(url, templateParams, options);
            workbooks.log("generatePDFs finished");
          }
        }

      } catch(Exception wbe) {
        workbooks.log("Exception while generating PDF", new Object[] {wbe}, "error");
        Console.WriteLine(wbe.StackTrace);
        login.testExit(workbooks, 1);
      }


    }

  }
}

