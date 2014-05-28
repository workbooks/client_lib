package workbooks_app.client_lib.java;

import java.util.HashMap;

import javax.json.JsonArray;

import workbooks_app.client_lib.java.WorkbooksApi.WorkbooksApiResponse;

/** 
 *	A demonstration of using the Workbooks API to generate PDF files via a thin Java wrapper
 * 
 * 	License: www.workbooks.com/mit_license
 * 	Last commit $Id: PdfExample.java 22080 2014-05-21 12:53:52Z bviroja $
 */

public class PdfExample {
	static WorkbooksApi  workbooks = null;
	String pdfTemplateId = "34"; // Order document
	static TestLoginHelper login = null;
	
	public static void main(String[] args) {
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
		HashMap<String, Object>   filter_limit_select = new HashMap<String, Object> ();
		
		filter_limit_select.put("_start", "0");
		filter_limit_select.put("_limit", "1");
		filter_limit_select.put("_sort", "id");
		filter_limit_select.put("_dir", "ASC");
		filter_limit_select.put("_select_columns[]", new String[] {"id"});
		try {
			WorkbooksApiResponse response = workbooks.assertGet("accounting/sales_orders", filter_limit_select, null);
			JsonArray allData = response.getData();
			if (allData != null) {
				if(allData.size() != 1) {
					workbooks.log("generatePDFs: Did not find any orders: ", new Object[] {allData});
					login.testExit(workbooks, 1);
				} else {
					int orderId = allData.getJsonObject(0).getInt("id");
					// Now generate the PDF
					
					String url = "accounting/sales_orders/" + orderId + ".pdf" ;
					
					// Important to add the decode_json as false for PDFs
					HashMap<String, Object> options = new HashMap<String, Object> ();
					options.put("decode_json", false);
					
					HashMap<String, Object> templateParams = new HashMap<String, Object> ();
					templateParams.put("template", pdfTemplateId);

					WorkbooksApiResponse responsePDF = workbooks.get(url, templateParams, options);
					workbooks.log("generatePDFs", new Object[] {responsePDF.print()});
				}
			}
		
		} catch(Exception wbe) {
			workbooks.log("Exception while generating PDF", new Object[] {wbe}, "error", WorkbooksApi.DEFAULT_LOG_LIMIT);
			wbe.printStackTrace();
			login.testExit(workbooks, 1);
		}
		
		
	}
}
