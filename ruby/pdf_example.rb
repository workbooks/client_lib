#
#  A demonstration of using the Workbooks API to fetch a PDF document via a thin Ruby wrapper
#
#
#  Last commit $Id: pdf_example.rb 57318 2023-02-09 15:55:19Z kswift $
#  License: www.workbooks.com/mit_license
#

require './workbooks_api.rb'
require './test_login_helper.rb'

workbooks = WorkbooksApiTestLoginHelper.new.workbooks

#
# In order to generate a PDF you need to know the ID of the transaction document (the 
# order, quotation, credit note etc) and the ID of a PDF template. You can find these out
# for a particular PDF by using the Workbooks Desktop, generating a PDF and examining the
# URL used to generate the PDF. You will see something like
# 
#    https://secure.workbooks.com/accounting/sales_orders/42.pdf?template=34
# 
# which implies a document ID of 42 and a template ID of 34. The 'sales_orders' part 
# indicates the type of transaction document you want to reference; see the Workbooks API 
# Reference for a list of the available API endpoints.
#

pdf_template_id = 34

# Find the ID of the first transaction document
response = workbooks.assert_get('accounting/sales_orders', {
  :_start => 0,
  :_limit => 1,
  :_sort => 'id',
  :_dir => 'ASC',
  '_select_columns[]' => ['id'],
})
if response.data.size != 1
  workbooks.log('Did not find any orders!', response)
  exit(1)
end

order_id = response.data[0]['id']

# Now generate the PDF
url = "accounting/sales_orders/#{order_id}.pdf?template=#{pdf_template_id}"
pdf = workbooks.get(url, nil, :decode_json => false)
# Ensure the response looks like a PDF
if !pdf.match(/^\%PDF\-1\.4\s/)
  workbooks.log('ERROR: Unexpected response: is it a PDF?', pdf, Logger::ERROR )
  exit(1)
end

workbooks.log('Fetched PDF', pdf)

workbooks.logout
exit(0)
