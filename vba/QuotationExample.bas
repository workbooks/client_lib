' Sample code to invoke WorkbooksApi
'
' Last commit $Id$
' License: www.workbooks.com/mit_license
'
' This example does the following:
'   1. Login to establish a session.
'   2. Fetch a Person (who will be the customer Party for the Quotation).
'   3. Create a Quotation record.
'   4. Create two Quotation line items within that Quotation.
'   5. Update both these Quotation line items.
'   6. Fetch the Quotation.
'   7. Update the Quotation.
'   8. Delete the Quotation (and all its line items).
'   9. Logout from the service.

Option Explicit

Sub RunQuotationExample()
    Dim Workbooks As WorkbooksApi
    Set Workbooks = New WorkbooksApi

    Workbooks.Log "Starting QuotationExample"


' Login
    Dim LoginResponse As WebResponse
    Set LoginResponse = Workbooks.Login(ApiKey:=Cells(1, "A").Value, ApplicationName:="VBA Test/0.1")


' Fetch a Person
    Dim GetPersonParams As New Dictionary
    ' Sort field and order ASC/DESC
    GetPersonParams.Add "_sort", "id"
    GetPersonParams.Add "_dir", "ASC" ' The earliest records first
    ' Paging
    GetPersonParams.Add "_start", "0"
    GetPersonParams.Add "_limit", "1"
    ' Recommended to avoid slow-down on large tables where many records may match
    GetPersonParams.Add "_skip_total_rows", "1"

    Dim GetPersonResponse As Dictionary
    Set GetPersonResponse = Workbooks.AssertGet(Endpoint:="crm/people", Params:=GetPersonParams)
    Workbooks.Log "GetPersonResponse", Array(GetPersonResponse), "msgbox"


' Fetch a Product (there may be none in the database)
    Dim GetProductParams As New Dictionary
    ' Sort field and order ASC/DESC
    GetProductParams.Add "_sort", "id"
    GetProductParams.Add "_dir", "ASC" ' The earliest records first
    ' Paging
    GetProductParams.Add "_start", "0"
    GetProductParams.Add "_limit", "1"
    ' Recommended to avoid slow-down on large tables where many records may match
    GetProductParams.Add "_skip_total_rows", "1"

    Dim GetProductResponse As Dictionary
    Set GetProductResponse = Workbooks.AssertGet(Endpoint:="pricebook/products", Params:=GetProductParams)
    Workbooks.Log "GetProductResponse", Array(GetProductResponse), "msgbox"


' Create a Quotation record
    Dim CreateRecords As New Collection

    Dim CreateRecord1 As New Dictionary
    CreateRecord1.Add "party_id", GetPersonResponse("data").Item(1)("id")
    CreateRecord1.Add "description", "New quotation as of " & Now()
    CreateRecord1.Add "document_date", "New quotation as of " & Format(Now(), "YYYY-MM-DD")
    CreateRecord1.Add "document_currency", "GBP"
    CreateRecords.Add CreateRecord1

    Dim CreateQuotationResponse As Dictionary
    Set CreateQuotationResponse = Workbooks.AssertCreate(Endpoint:="accounting/quotations", Records:=CreateRecords)
    Workbooks.Log "CreateQuotationResponse", Array(CreateQuotationResponse), "msgbox"
    Dim QuotationId As String
    
    ' Retrieve the id of the Quotation created just now.
    QuotationId = CreateQuotationResponse("affected_objects").Item(1)("id")

' Create two Quotation line items within that Quotation
    Dim CreateLineItems As New Collection

    Dim CreateLineItem1 As New Dictionary
    CreateLineItem1.Add "document_header_id", QuotationId
    CreateLineItem1.Add "unit_quantity", "10"
    CreateLineItem1.Add "document_currency_unit_price_value", "75.00 GBP 0" ' Currency format: "AMOUNT CURRENCY_CODE 0"
    CreateLineItem1.Add "description", "First line item"
    CreateLineItems.Add CreateLineItem1

    Dim CreateLineItem2 As New Dictionary
    CreateLineItem2.Add "document_header_id", QuotationId
    CreateLineItem2.Add "unit_quantity", "20"
    CreateLineItem2.Add "document_currency_unit_price_value", "42.00 GBP 0"
    If GetProductResponse("data").Count = 1 Then ' If we found a product, link it to this line item.
        CreateLineItem2.Add "product_id", GetProductResponse("data").Item(1)("id")
        CreateLineItem2.Add "description", GetProductResponse("data").Item(1)("description") & " - " & Format(Now(), "YYYY-MM-DD")
    Else
        CreateLineItem2.Add "description", "Second line item"
    End If
    CreateLineItems.Add CreateLineItem2

    Dim CreateLineItemsResponse As Dictionary
    Set CreateLineItemsResponse = Workbooks.AssertCreate(Endpoint:="accounting/quotation_line_items", Records:=CreateLineItems)
    Workbooks.Log "CreateLineItemsResponse", Array(CreateLineItemsResponse), "msgbox"


' Update both these Quotation line items
    Dim UpdateLineItems As New Collection
    Dim CreateLineItemResult As Dictionary
    For Each CreateLineItemResult In CreateLineItemsResponse("affected_objects")
        Dim UpdateLineItem As Dictionary
        Set UpdateLineItem = New Dictionary
        ' When updating a record you must supply both its "id" and its current "lock_version".
        UpdateLineItem.Add "id", CStr(CreateLineItemResult("id"))
        UpdateLineItem.Add "lock_version", CStr(CreateLineItemResult("lock_version"))
        ' For this example double each unit_quantity
        UpdateLineItem.Add "unit_quantity", CreateLineItemResult("unit_quantity") * 2
        UpdateLineItems.Add UpdateLineItem
    Next CreateLineItemResult

    Dim UpdateLineItemsResponse As Dictionary
    Set UpdateLineItemsResponse = Workbooks.AssertUpdate(Endpoint:="accounting/quotation_line_items", Records:=UpdateLineItems)
    Workbooks.Log "UpdateLineItemsResponse", Array(UpdateLineItemsResponse), "msgbox"

    
' Fetch the Quotation
    Dim GetQuotationParams As New Dictionary
    GetQuotationParams.Add "_ff[]", Array("id")
    GetQuotationParams.Add "_ft[]", Array("eq")
    GetQuotationParams.Add "_fc[]", Array(QuotationId)
    ' Only retrieve the field we need
    GetQuotationParams.Add "_select_columns[]", Array("lock_version")
    ' Recommended to avoid slow-down on large tables where many records may match
    GetQuotationParams.Add "_skip_total_rows", "1"
    
    Dim GetQuotationResponse As Dictionary
    Set GetQuotationResponse = Workbooks.AssertGet(Endpoint:="accounting/quotations", Params:=GetQuotationParams)
    Workbooks.Log "GetQuotationResponse", Array(GetQuotationResponse), "msgbox"


' Update the Quotation (had to fetch it first: updating its line items probably changed its lock_version)
    Dim UpdateQuotations As New Collection
    Dim UpdateQuotation As Dictionary
    Set UpdateQuotation = New Dictionary
    ' When updating a record you must supply both its "id" and its current "lock_version".
    UpdateQuotation.Add "id", QuotationId
    UpdateQuotation.Add "lock_version", GetQuotationResponse("data").Item(1)("lock_version")
    UpdateQuotation.Add "payment_due_date", Format(Now(), "YYYY-MM-DD")
    UpdateQuotations.Add UpdateQuotation

    Dim UpdateQuotationsResponse As Dictionary
    Set UpdateQuotationsResponse = Workbooks.AssertUpdate(Endpoint:="accounting/quotations", Records:=UpdateQuotations)
    Workbooks.Log "UpdateQuotationsResponse", Array(UpdateQuotationsResponse), "msgbox"


' Delete the Quotation (and all its line items).
    Dim DeleteRecords As New Collection
    Dim DeleteRecord As Dictionary
    Set DeleteRecord = New Dictionary
    ' When deleting a record you must supply both its "id" and its current "lock_version".
    DeleteRecord.Add "id", QuotationId
    DeleteRecord.Add "lock_version", CStr(UpdateQuotationsResponse("affected_objects").Item(1)("lock_version"))
    DeleteRecords.Add DeleteRecord

    Dim DeleteResponse As Dictionary
    Set DeleteResponse = Workbooks.AssertDelete(Endpoint:="accounting/quotations", Records:=DeleteRecords)
    Workbooks.Log "DeleteResponse", Array(DeleteResponse), "msgbox"

    Workbooks.Log "QuotationExample completed successfully", "", "msgbox"

    
' Logout from the service
    Workbooks.Logout


End Sub
