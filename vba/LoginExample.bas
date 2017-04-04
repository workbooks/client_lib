' Sample code to invoke WorkbooksApi
'
' Last commit $Id$
' License: www.workbooks.com/mit_license
'
' This example does the following:
'   1. Login to establish a session.
'   2. Logout from the service.

Option Explicit

Sub RunLoginExample()
    Dim Workbooks As WorkbooksApi
    Set Workbooks = New WorkbooksApi

    Workbooks.Log "Starting LoginExample"


' Login
    Dim LoginResponse As WebResponse
    Set LoginResponse = Workbooks.Login(ApiKey:=Cells(1, "A").Value, ApplicationName:="VBA Test/0.1")


' Logout from the service
    Workbooks.Logout


    Workbooks.Log "LoginExample completed successfully", "", "msgbox"
End Sub
