' Sample code to invoke WorkbooksApi
'
' Last commit $Id$
' License: www.workbooks.com/mit_license
'
' This example does the following:
'   1. Login to establish a session.
'   2. Create two ApiData records.
'   3. Update both these records.
'   4. Fetch both of them.
'   5. Delete both created ApiData records.
'   6. Logout from the service.

Option Explicit

Sub RunApiDataExample()
    Dim Workbooks As WorkbooksApi
    Set Workbooks = New WorkbooksApi

    Workbooks.Log "Starting ApiDataExample"


' Login
    Dim LoginResponse As WebResponse
    Set LoginResponse = Workbooks.Login(ApiKey:=Cells(1, "A").Value, ApplicationName:="VBA Test/0.1")


' Create (two records)
    Dim CreateRecords As New Collection

    Dim CreateRecord1 As New Dictionary
    CreateRecord1.Add "key", "FirstRecordKey"
    CreateRecord1.Add "value", "FirstRecordValue"
    CreateRecords.Add CreateRecord1

    Dim CreateRecord2 As New Dictionary
    CreateRecord2.Add "key", "SecondRecordKey"
    CreateRecord2.Add "key2", "SecondRecord2"
    CreateRecord2.Add "value", "SecondRecordValue"
    CreateRecords.Add CreateRecord2

    Dim CreateResponse As Dictionary
    Set CreateResponse = Workbooks.AssertCreate(Endpoint:="automation/api_data", Records:=CreateRecords)
    Workbooks.Log "CreateResponse", Array(CreateResponse), "msgbox"


' Update (two records)
    Dim UpdateRecords As New Collection
    Dim CreateResult As Dictionary
    For Each CreateResult In CreateResponse("affected_objects")
        Dim UpdateRecord As Dictionary
        Set UpdateRecord = New Dictionary
        ' When updating a record you must supply both its "id" and its current "lock_version".
        UpdateRecord.Add "id", CStr(CreateResult("id"))
        UpdateRecord.Add "lock_version", CStr(CreateResult("lock_version"))
        ' For this example up-shift the "key" and "value" fields.
        UpdateRecord.Add "key", UCase(CreateResult("key"))
        UpdateRecord.Add "value", UCase(CreateResult("value"))
        UpdateRecords.Add UpdateRecord
    Next CreateResult

    Dim UpdateResponse As Dictionary
    Set UpdateResponse = Workbooks.AssertUpdate(Endpoint:="automation/api_data", Records:=UpdateRecords)
    Workbooks.Log "UpdateResponse", Array(UpdateResponse), "msgbox"
    

' Fetch (two records)
    Dim GetParams As New Dictionary
    ' Sort field and order ASC/DESC
    GetParams.Add "_sort", "id"
    GetParams.Add "_dir", "DESC" ' The latest records first
    ' Paging
    GetParams.Add "_start", "0"
    GetParams.Add "_limit", "2"
    ' Filter: two tests, both of which must be met (_fm is "and").
    GetParams.Add "_ff[]", Array("key", "key")
    GetParams.Add "_ft[]", Array("eq", "eq")
    GetParams.Add "_fc[]", Array("FIRSTRECORDKEY", "SECONDRECORDKEY")
    GetParams.Add "_fm", "or"  ' "and" is the default
    ' Column selection
    GetParams.Add "_select_columns[]", Array("id", "lock_version", "key", "value")
    ' Recommended to avoid slow-down on large tables where many records may match
    GetParams.Add "_skip_total_rows", "1"

    Dim GetResponse As Dictionary
    Set GetResponse = Workbooks.AssertGet(Endpoint:="automation/api_data", Params:=GetParams)
    Workbooks.Log "GetResponse", Array(GetResponse), "msgbox"


' Delete (two records)
    Dim DeleteRecords As New Collection
    Dim GetResult As Dictionary
    For Each GetResult In GetResponse("data")
        Dim DeleteRecord As Dictionary
        Set DeleteRecord = New Dictionary
        ' When deleting a record you must supply both its "id" and its current "lock_version".
        DeleteRecord.Add "id", CStr(GetResult("id"))
        DeleteRecord.Add "lock_version", CStr(GetResult("lock_version"))
        DeleteRecords.Add DeleteRecord
    Next GetResult

    Dim DeleteResponse As Dictionary
    Set DeleteResponse = Workbooks.AssertDelete(Endpoint:="automation/api_data", Records:=DeleteRecords)
    Workbooks.Log "DeleteResponse", Array(DeleteResponse), "msgbox"


' Logout from the service
    Workbooks.Logout


    Workbooks.Log "ApiDataExample completed successfully", "", "msgbox"
End Sub
