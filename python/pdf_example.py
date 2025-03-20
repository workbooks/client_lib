#!/usr/bin/env python3

# A demonstration of using the Workbooks API to to fetch a PDF document via a thin Python wrapper.

# Last commit $Id: pdf_example.py 60951 2024-01-03 18:13:10Z jkay $
# Copyright (c) 2008-2023, Workbooks Online Limited.

# The MIT License
#
# Permission is hereby granted, free of charge, to any person obtaining a copy
# of this software and associated documentation files (the "Software"), to deal
# in the Software without restriction, including without limitation the rights
# to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
# copies of the Software, and to permit persons to whom the Software is
# furnished to do so, subject to the following conditions:
#
# The above copyright notice and this permission notice shall be included in
# all copies or substantial portions of the Software.
# 
# THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
# IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
# FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
# AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
# LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
# OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
# THE SOFTWARE.

from workbooks_api import WorkbooksApi
from workbooks_test_login import WorkbooksApiTestLogin

# Create a Workbooks API instance
workbooks = WorkbooksApiTestLogin().workbooks

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

pdf_template_id = 34

response = workbooks.assert_get('accounting/sales_orders', {
    '_start': 0,
    '_limit': 1,
    '_sort': 'id',
    '_dir': 'ASC',
    '_select_columns[]': ['id'],
})

if len(response['data']) != 1:
    workbooks.log('Did not find any orders!', response)
    exit(1)

order_id = response['data'][0]['id']

url = f"accounting/sales_orders/{order_id}.pdf?template={pdf_template_id}"
pdf = workbooks.get(url, None, {'decode_json':False})

if not pdf.startswith('%PDF-1.4'):
    workbooks.log('ERROR: Unexpected response: is it a PDF?', pdf, Logger.ERROR)
    workbooks.log('Failed', __file__)
    exit(1)

workbooks.log('Fetched PDF', pdf)

workbooks.logout()
workbooks.log('Passed', __file__)
