Option Explicit

' VBA wrapper for the Workbooks API documented at http://www.workbooks.com/api built
' using VBA-Web (https://github.com/VBA-tools/VBA-Web)
'
' Last commit $Id: WorkbooksApi.cls 57625 2023-03-10 12:45:15Z klawless $
' License: www.workbooks.com/mit_license
'
' Significant methods in the class WorkbooksApi:
'   Login               - authenticate and create session
'   Logout              - end session
'   AssertGet           - get a list of records, or show a record
'   AssertCreate        - create records
'   AssertUpdate        - update records
'   AssertDelete        - delete records
'   AssertBatch         - create, update, and delete records together
'   Log                 - a simple logger
'
' See the Examples supplied alongside this Class for usage instructions.
' 
' Tested on MS Excel for Mac 15.32 on macOS 10.12.4

Private Const ApiVersion As String = "1"
Private Const SessionCookieName As String = "Workbooks-Session"
Private Const ServiceBase As String = "https://secure.workbooks.com"
Private Const TimeoutMs As Integer = 20000 ' 20 seconds
Private Const EnableLogging As Boolean = True
Private Const DefaultErrorCode As Integer = 10000

Private pSessionCookie As String
Private pAuthenticityToken As String

' A logger, with the usual signature for a WorkbooksApi logger, wrapping the loggers from WebHelpers.
' Shows a MsgBox if the level is not "debug"
' Parameter expressions is either a single item or an array of items to be dumped after the message.
Public Sub Log(Message As String, Optional Expressions As Variant = "", Optional Level As String = "debug")
    WebHelpers.EnableLogging = EnableLogging

    If IsArray(Expressions) Then
        Dim Expression As Variant
        For Each Expression In Expressions
            Message = Message & " <<" & WebHelpers.ConvertToJson(Expression) & ">>"
        Next Expression
    ElseIf Expressions <> "" Then
        Message = Message & " <<" & CStr(Expressions) & ">>"
    End If

    Select Case Level
        Case "error"
            WebHelpers.LogError Message, "WorkbooksApi"
            MsgBox(Message)
        Case "warning"
            WebHelpers.LogWarning Message, "WorkbooksApi"
            MsgBox(Message)
        Case "info", "msgbox"
            WebHelpers.LogDebug Message, "WorkbooksApi"
            MsgBox(Message)
        Case Else
            WebHelpers.LogDebug Message, "WorkbooksApi"
    End Select
End Sub

' An error occurred. Report it.
Private Sub RaiseError(Code As String, Optional Description As String = "")
    Log Code, Description, "error"
    Err.Raise DefaultErrorCode, Code, Description
End Sub

' Login to Workbooks and store the Session Cookie value, or raise an error. Returns the login Response.
Public Function Login(ApiKey As String, ApplicationName As String) As WebResponse
    Log "Login", Array(ApiKey, ApplicationName)

    pSessionCookie = ""

    If ApiKey = "01234-01234-01234-01234-01234-01234-01234-01234" Then
        RaiseError "Invalid API Key", "Remember to create an API Key for your own Workbooks Database"
    End If

    Dim Client As WebClient
    Set Client = New WebClient
    Client.TimeoutMs = TimeoutMs

    Dim Body As Dictionary
    Set Body = New Dictionary
    Body.Add "api_key", ApiKey
    Body.Add "_application_name", ApplicationName
    Body.Add "api_version", ApiVersion

    Set Login = Client.PostJson(ServiceBase & "/login.api", Body)

    Select Case Login.StatusCode
        Case 301
            RaiseError "Authentication failed", "Workbooks login.api rejected the login credentials"
        Case 200
            'Log "Login successful"
        Case Else
            RaiseError "Login failed", "Workbooks login.api failed, code " & CStr(Login.StatusCode)
    End Select

    Dim SessionCookie As String
    SessionCookie = WebHelpers.FindInKeyValues(Login.Cookies, SessionCookieName)
    If Len(SessionCookie) < 32 Then
        RaiseError "Login failed", "Workbooks session cookie incomplete: " & SessionCookie
    End If

    pSessionCookie = SessionCookie
    pAuthenticityToken = Login.Data("authenticity_token")
    Log "Login successful", Array(pSessionCookie, pAuthenticityToken)
End Function

' End the Workbooks session
Public Sub Logout
    Log "Logout"

    Dim Client As WebClient
    Set Client = New WebClient
    Client.TimeoutMs = TimeoutMs
    Dim Request As New WebRequest
    Dim Response As WebResponse

    If pSessionCookie <> "" Then
        Request.Method = WebMethod.HttpPost
        Request.Resource = ServiceBase & "/logout"  '?_method=PUT
        Request.RequestFormat = WebFormat.FormUrlEncoded
        Request.AddCookie SessionCookieName, pSessionCookie
    
        Client.TimeoutMs = TimeoutMs
        Set Response = Client.Execute(Request)
    
        Select Case Response.StatusCode
            Case 302
                Log "Logout successful"
            Case Else
                Log "Logout failed", Response.StatusCode ' But ignore this warning
        End Select
    
        pSessionCookie = ""
        pAuthenticityToken = ""
    Else
        Log "Logout unnecessary, no session existed"
    End If
End Sub

' Make a request to an endpoint on the service to read or list objects. You must have logged in first
' Parameters:
'   * Endpoint - selects the portion of the API to use, e.g. "crm/organisations".
'   * Params - the parameters to the API call - filter, limit, column selection each element can have a
'              simple value or be a dictionary of values e.g. for column selection.
' Returns a Dictionary representing the received parsed JSON (i.e. including "data" and "total" elements)
' but will raise an error if the web request failed.
'
' As usual, check the API documentation for further information.
Public Function WbGet(Endpoint As String, Params As Dictionary) As Dictionary
    Log "WbGet", Array(Endpoint, Params)

    Dim Client As WebClient
    Set Client = New WebClient
    Dim Response As WebResponse

    If pSessionCookie = "" Then
        RaiseError "No session", "You must successfully Login before using this function"
    End If

    Dim Cookies As New Collection
    Cookies.Add WebHelpers.CreateKeyValue(SessionCookieName, pSessionCookie)
    Dim Options As New Dictionary
    Options.Add "Cookies", Cookies

    ' The set of Params includes array values and simple values. Array values have duplicate names so cannot simply
    ' go in a Dictionary. So assemble these into the query string manually.
    
    Dim QueryString As String: QueryString = ""
    Dim Param As Variant
    For Each Param in Params.Keys()
        If IsArray(Params(Param)) Then
            Dim ArrayElem As Variant
            For Each ArrayElem in Params(Param)
                QueryString = QueryString & "&" & WebHelpers.UrlEncode(Param) & "=" & WebHelpers.UrlEncode(ArrayElem)
            Next ArrayElem
        Else
            QueryString = QueryString & "&" & WebHelpers.UrlEncode(Param) & "=" & WebHelpers.UrlEncode(Params(Param))
        End If
    Next Param
    
    Dim Url As String
    Url = UrlForEndpoint(Endpoint) & QueryString

    Client.TimeoutMs = TimeoutMs
    Set Response = Client.GetJson(Url, Options)
    If Response.StatusCode <> 200 Then
        RaiseError "Request failed", "Workbooks API returned a failure StatusCode " & CStr(Response.StatusCode)
    End If

    Set WbGet = Response.Data
    Log "WbGet complete", Array(WbGet)
End Function

' As WbGet, but will raise an error if the request was not completely successful.
Public Function AssertGet(Endpoint As String, Params As Dictionary) As Dictionary
    Log "AssertGet", Array(Endpoint, Params)
    Set AssertGet = AssertResponse(WbGet(Endpoint, Params))
    Log "AssertGet successful", Array(AssertGet)
End Function

' Make a request to an endpoint on the service to operate on multiple objects. You must have logged in first.
' You can request a combination of CREATE, UPDATE and DELETE operations, to be batched together.
' This is the core method upon which other methods are implemented which perform a subset of these operations.
' Parameters:
'   * Endpoint - selects the portion of the API to use, e.g. 'crm/organisations'.
'   * Records - a Collection of records to create, update or delete.
'   * AddParamString - a set of additional parameters to send along with the data, for example
'                      "_per_object_transactions=1" to change the commit behaviour.
'   * Method - the method (CREATE, UPDATE or DELETE) which is to be used if not specified for a record.
' Returns a Dictionary representing the received parsed JSON response
' 
' As usual, check the API documentation for further information.
Public Function WbBatch(Endpoint As String, Records As Collection, Optional AddParamString As String = "", Optional Method As String = "Undefined") As Dictionary
    Log "WbBatch", Array(Endpoint, Records, AddParamString, Method)

    Dim Client As WebClient
    Set Client = New WebClient
    Dim Request As New WebRequest
    Dim Response As WebResponse

    If pSessionCookie = "" Then
        RaiseError "No session", "You must successfully Login before using this function"
    End If

    Dim FilterParamString as String
    FilterParamString = EncodeMethodParams(Records, Method)
    
    Dim PostParamString as String
    PostParamString = FullSquare(Records)

    Dim Url As String
    Url = UrlForEndpoint(Endpoint) & "&_method=PUT&_authenticity_token=" & pAuthenticityToken & FilterParamString
    If Len(AddParamString) > 0 Then
        Url = Url & "&" & AddParamString
    End If

    Request.Method = WebMethod.HttpPost
    Request.Resource = Url
    Request.RequestFormat = WebFormat.FormUrlEncoded
    Request.ResponseFormat = WebFormat.Json
    Request.AddCookie SessionCookieName, pSessionCookie
    Request.Body = PostParamString

    Client.TimeoutMs = TimeoutMs
    Set Response = Client.Execute(Request)

    If Response.StatusCode <> 200 Then
        RaiseError "Request failed", "Workbooks API returned a failure StatusCode " & CStr(Response.StatusCode)
    End If

    Set WbBatch = Response.Data
    Log "WbBatch complete", Array(WbBatch)
End Function

' As WbBatch, but will raise an error if the request was not completely successful.
Public Function AssertBatch(Endpoint As String, Records As Collection, Optional AddParamString As String = "", Optional Method As String = "Undefined") As Dictionary
    Log "AssertBatch", Array(Endpoint, Records, AddParamString, Method)
    Set AssertBatch = AssertResponse(WbBatch(Endpoint, Records, AddParamString, Method))
    Log "AssertBatch successful", Array(AssertBatch)
End Function

' Create records; parameters and response as per WbBatch, above. 
Public Function WbCreate(Endpoint As String, Records As Collection, Optional AddParamString As String = "") As Dictionary
    Log "WbCreate", Array(Endpoint, Records, AddParamString)
    Set WbCreate = WbBatch(Endpoint, Records, AddParamString, "CREATE")
    Log "WbCreate complete", Array(WbCreate)
End Function

' As WbCreate, but will raise an error if the request was not completely successful.
Public Function AssertCreate(Endpoint As String, Records As Collection, Optional AddParamString As String = "") As Dictionary
    Log "AssertCreate", Array(Endpoint, Records, AddParamString)
    Set AssertCreate = AssertResponse(WbCreate(Endpoint, Records, AddParamString))
    Log "AssertCreate successful", Array(AssertCreate)
End Function

' Update records; parameters and response as per WbBatch, above. 
Public Function WbUpdate(Endpoint As String, Records As Collection, Optional AddParamString As String = "") As Dictionary
    Log "WbUpdate", Array(Endpoint, Records, AddParamString)
    Set WbUpdate = WbBatch(Endpoint, Records, AddParamString, "UPDATE")
    Log "WbUpdate complete", Array(WbUpdate)
End Function

' As WbUpdate, but will raise an error if the request was not completely successful.
Public Function AssertUpdate(Endpoint As String, Records As Collection, Optional AddParamString As String = "") As Dictionary
    Log "AssertUpdate", Array(Endpoint, Records, AddParamString)
    Set AssertUpdate = AssertResponse(WbUpdate(Endpoint, Records, AddParamString))
    Log "AssertUpdate successful", Array(AssertUpdate)
End Function

' Delete records; parameters and response as per WbBatch, above. 
Public Function WbDelete(Endpoint As String, Records As Collection, Optional AddParamString As String = "") As Dictionary
    Log "WbDelete", Array(Endpoint, Records, AddParamString)
    Set WbDelete = WbBatch(Endpoint, Records, AddParamString, "DELETE")
    Log "WbDelete complete", Array(WbDelete)
End Function

' As WbDelete, but will raise an error if the request was not completely successful.
Public Function AssertDelete(Endpoint As String, Records As Collection, Optional AddParamString As String = "") As Dictionary
    Log "AssertDelete", Array(Endpoint, Records, AddParamString)
    Set AssertDelete = AssertResponse(WbDelete(Endpoint, Records, AddParamString))
    Log "AssertDelete successful", Array(AssertDelete)
End Function

' Evaluate the JSON response to determine how successful it was, raises an error if it represents failure, otherwise returns the response.
Public Function AssertResponse(Response As Dictionary) As Dictionary
    Log "AssertResponse Response", Array(Response)
    If Response.Exists("success") And Response("success") = "True" And Not Response.Exists("errors") Then
        If Response.Exists("affected_object_information") Then
            Dim Row As Dictionary
            For Each Row In Response("affected_object_information")
                If Not Row.Exists("success") Or Not Row("success") = "True" Then
                    RaiseError "Failed update", "One or more records could not be modified"
                End If
            Next Row    
        End If
        Set AssertResponse = Response
    Else
        RaiseError "Bad response", "Workbooks API returned a failure response"
    End If
End Function

' Return the url for an endpoint (before query parameters are added).
' Always adds a cache-buster ("_dc"); all subsequent parameters can be appended using an ampersand.
Private Function UrlForEndpoint(Endpoint As String) As String
    ' API calls are always to a ".api" endpoint; the caller does not have to include this.
    ' Including ANY extension will prevent ".api" from being appended.
    If InStr(Endpoint, ".") = 0 Then
        Endpoint = Endpoint & ".api"
    End If

    If Not Left(Endpoint, 1) = "/" Then
        Endpoint = "/" & Endpoint
    End If

    UrlForEndpoint = ServiceBase & Endpoint & _
      "?_dc=" & CStr(Int((9000 * Rnd()) + 1000)) & CStr(Int((9000 * Rnd()) + 1000)) & CStr(Int((9000 * Rnd()) + 1000))
End Function

' Depending on the method (CREATE/DELETE/UPDATE) the objects passed to Workbooks have certain minimum requirements.
' Callers may specify a method for each object or assume the same operation for all objects.
' Parameters:
'   * Records - a Collection of objects to be encoded, modified in place
'   * Method - the method (CREATE, UPDATE or DELETE) which is to be used if not specified for a record.
' Returns a String prefixed with an ampersand representing the filter which is required to define the working set of objects.
Private Function EncodeMethodParams(Records As Collection, Method As String) As String
    Log "EncodeMethodParams", Array(Records, Method)

    EncodeMethodParams = ""
    Dim ObjMethod As String
    Dim Record As Dictionary
    For Each Record In Records
        ObjMethod = Method
        If Record.Exists("method") Then
            ObjMethod = Record("method")
            Record.Remove "method"
        End If

        Select Case UCase(ObjMethod)
            Case "CREATE"
                Record.Add "__method", "POST"
                Record.Add "id", "0"
                Record.Add "lock_version", "0"
            Case "UPDATE"
                Record.Add "__method", "PUT"
                If Not Record.Exists("id") And Record.Exists("lock_version") Then
                    RaiseError "Missing attributes", "Both 'id' and 'lock_version' must be set to update an object"
                End If
            Case "DELETE"
                Record.Add "__method", "DELETE"
                If Not Record.Exists("id") And Record.Exists("lock_version") Then
                    RaiseError "Missing attributes", "Both 'id' and 'lock_version' must be set to delete an object"
                End If
            Case Else
                RaiseError "Unexpected method", "Unexpected method: " & ObjMethod
        End Select
        EncodeMethodParams = EncodeMethodParams & _
            "&" & WebHelpers.UrlEncode("_ff[]") & "=" & WebHelpers.UrlEncode("id") & _
            "&" & WebHelpers.UrlEncode("_ft[]") & "=" & WebHelpers.UrlEncode("eq") & _
            "&" & WebHelpers.UrlEncode("_fc[]") & "=" & WebHelpers.UrlEncode(Record("id"))
    Next Record
    EncodeMethodParams = EncodeMethodParams & _
        "&" & WebHelpers.UrlEncode("_fm") & "=" & WebHelpers.UrlEncode("or")
    
    Log "EncodeMethodParams", EncodeMethodParams
End Function

' The Workbooks wire protocol requires that each key which is used in any object be present in all objects, 
' and delivered in the right order. Callers of this binding library will omit keys from some objects and not 
' from others. Some special values are used in this encoding - :null_value: and :no_value:.
' Parameter: Records - a Collection of records to be encoded
' Returns a String which is the url-encoded set of objects suitable for passing to Workbooks.
Private Function FullSquare(Records As Collection) As String
    Log "FullSquare", Array(Records)

    FullSquare = ""

    ' Get the full set of field names for all of the Records. Believe it or not this is one line in ruby:
    '   unique_keys = records.map { |o| o.keys }.flatten.uniq
    Dim NumFields As Integer: NumFields = 0
    Dim MaxFields As Integer: MaxFields = -1
    Dim AllFields() As String
    Dim Record As Dictionary
    Dim Field As Variant
    For Each Record In Records
        For Each Field In Record.Keys()
            If NumFields > MaxFields Then
                MaxFields = 10 + MaxFields * 2
                ReDim Preserve AllFields(MaxFields)
            End If
            Dim Matched As Boolean: Matched = False
            Dim F As Variant
            For Each F In AllFields
                If F = Field Then
                    Matched = True
                End If
            Next F
            If Not Matched Then
                AllFields(NumFields) = Field
                NumFields = NumFields + 1
            End If
        Next Field
    Next Record
    ReDim Preserve AllFields(NumFields - 1)
    Log "AllFields", Array(AllFields, NumFields)
    
    ' The full square array is one with a value for every field in every record.
    Dim Value As String
    For Each Record In Records
        For Each Field In AllFields
            If Record.Exists(Field) Then
                If Record(Field) = "" Then
                    Value = ":null_value:"
                Else
                    Value = Record(Field)
                End If
            Else
                Value = ":no_value:"
            End If
            FullSquare = FullSquare & "&" & WebHelpers.UrlEncode(CStr(Field) & "[]") & "=" & WebHelpers.UrlEncode(Value)
        Next Field
    Next Record

    ' Lose the first ampersand
    If Len(FullSquare) > 0 Then
        FullSquare = Mid(FullSquare,2)
    End If

    Log "FullSquare", FullSquare
End Function
