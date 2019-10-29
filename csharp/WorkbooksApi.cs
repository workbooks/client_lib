/** A C# wrapper for the Workbooks API
 *
 *  License: www.workbooks.com/mit_license
 *  Last commit $Id$
 *
 *
 * <summary> Significant methods in the class Workbooks:
 *     constructor               - create an API object, specifying various options
 *     login                     - authenticate
 *     logout                    - terminate logged-in session (automatic when object destroyed)
 *     get                       - get a list of objects, or show an object
 *     create                    - create objects
 *     update                    - update objects
 *     delete                    - delete objects
 *     batch                     - create, update, and delete objects together
 *     SessionId                 - use these to connect to an existing session
 *     condensedStatus           - use this to quickly check the response
 * </summary>
 */
namespace WorkbooksApiApplication
{
  using System;
  using System.Diagnostics;
  using System.IO;
  using System.Net;
  using System.Text;
  using System.Collections.Generic;
  using System.Collections;
  using System.Runtime.Serialization.Json;
  using System.Text.RegularExpressions;
  using System.Web;
  using System.Web.Script.Serialization;
  using System.Threading;


  /// <summary> Exception class - Thrown when an API call returns an exception. </summary>

  public class WorkbooksApiException : System.Exception
  {
    /// <summary> Make a new API Exception with the given result. </summary>
    ///
    /// <param> Dictionary </param>
    /// <returns> - the exception from the API server
    public WorkbooksApiException (Dictionary<string, object> response)
    {
      int code = 0;
      WorkbooksApi workbooks_api = null;

      Dictionary<string, object> errorObject = (Dictionary<string, object>) response["error"];

      String msg = errorObject["message"].ToString();

      if (response.ContainsKey("error_code")) {
        code = (int) response["error_code"];
      }
      // If we have access to the Workbooks API object, log all that we can
      if (response.ContainsKey("workbooks_api")) {
        workbooks_api = (WorkbooksApi) response["workbooks_api"];
        workbooks_api.log("new WorkbooksApiException", new Object[] {msg, code}, "error");
      }
    }
  } // end of WorkbooksApiException class

  /// <summary>The wrapper class to handle the response from Workbooks. It has methods which return the data and the affected objects from the Workbooks Response
  ///</summary>
  public class WorkbooksApiResponse
  {
    public enum CondensedStatus
    {
      Failed,
      NotOK,
      OK
    }
    Dictionary<string, object> response = null;
    private int? total = null;

    public WorkbooksApiResponse (Dictionary<string, object> response) {
      this.response = response;
    }

    ///<returns>The response Dictionary object</returns>
    public Dictionary<string, object> getResponse() {
          return response;
    }
    /// <summary>The total number of records returned from the response </summary>
    /// <returns>The total </returns>
    public int? getTotal() {
      if (response != null) {
        Dictionary<string, object> responseData = (Dictionary<string, object>) response["response"];
        total = (int?)responseData["total"];
      }
      return total;
    }

    ///<returns> - returns the response object as String</returns>
    public string print(Dictionary<string, object> dictionaryToString) {
      return WorkbooksApi.convertToString (dictionaryToString);
    }

    ///  <summary>Method to return the data object from the Workbooks Response </summary>
    /// <returns> array of the data object </returns>
    public object[] getData() {
      if (response != null) {
        Dictionary<string, object> responseData = (Dictionary<string, object>) response["response"];
        object[] allData = (object[])responseData["data"];
        return allData;
      }
      return null;
    }

    /// <summary>Method to return the affected objects from the Workbooks Response after an operation </summary>
    /// <returns> array of the affected objects </returns>
    public object[] getAffectedObjects() {
      if (response != null) {
        Dictionary<string, object> responseData = (Dictionary<string, object>) response["response"];
        object[] allData = (object[])responseData["affected_objects"];
        return allData;
      }
      return null;
    }

    /// <summary>Method to return the First element in the affected object in the Workbooks Response</summary>
    /// <returns> first affected object </returns>
    public Dictionary<string, object> getFirstAffectedObject() {
      object[] allData = getAffectedObjects();
      if (allData != null) {
        return (Dictionary<string, object>) allData[0];
      } else {
        return null;
      }
    }

    /// <summary>Method to return the first element in the data object in the Workbooks Response </summary>
    /// <returns> the first data object </returns>
    public Dictionary<string, object> getFirstData() {
      object[] allData = getData();
      if (allData != null) {
        return (Dictionary<string, object>)allData[0];
      } else {
        return null;
      }
    }
    /// <summary>Helper function which evaluates a response to determine how successful it was </summary>
    ///
    /// <returns> Enum value which is one of: 'failed', 'ok', 'not-ok' 'failed' - this is unexpected. 'not-ok' - something in the request could not be satisfied; you should
    ///         check the errors and warnings. 'ok' - completely successful.
    /// </returns>
    public CondensedStatus CondenseStatus ()
    {
      CondensedStatus status = CondensedStatus.OK;
      if (response != null) {
        Dictionary<string, object> responseData = (Dictionary<string, object>) response["response"];

        if (!responseData.ContainsKey("success")) {
          return CondensedStatus.Failed; // Unexpected failure - there should always be a "success" element
        } else if (!Boolean.Parse(responseData["success"].ToString())) {
          return CondensedStatus.Failed; // Something was quite wrong, not just a validation failure
        } else if (responseData.ContainsKey("errors")) { // TODO: // check what type the errors object is
          status = CondensedStatus.NotOK;
        } else if (!responseData.ContainsKey("affected_object_information") || !responseData["affected_object_information"].GetType().IsArray) {
          return CondensedStatus.OK;
        } else {
          object[] affected_objects = (object[])responseData["affected_object_information"];
          for (int i = 0; i < affected_objects.Length; i++) {
            Dictionary<string, object> affected = (Dictionary<string, object>)affected_objects[i];
            if (!affected.ContainsKey("success")) {
              status = CondensedStatus.Failed; // Again, this is unexpected.
            }
            if (!Boolean.Parse(affected["success"].ToString())) {
              status = CondensedStatus.NotOK; // There will be warnings or errors indicated which prevented complete success.
            }
          }
        }
      } else {
        status = CondensedStatus.NotOK;
      }
      return status;
    }

    /// <summary>Check responses are expected. Raises an exception if the response is not. </summary>
    ///
    /// <param name="expected"> the expected type of response, defaults to 'ok'. </param>
    /// <param name="raise_on_error"> the exception to raise if the response is not as expected.="Unexpected response from Workbooks API" </param>

    public void assertResponse (CondensedStatus expected=CondensedStatus.OK, string raise_on_error="Unexpected response from Workbooks API")
    {
      CondensedStatus condensed_status = this.CondenseStatus ();
      if (!condensed_status.Equals(expected)) {
        Console.WriteLine("Received an unxpected response", new Object[] {condensed_status, response, expected});
        throw new Exception(raise_on_error);
      }
    }
  } // End of WorkbooksApiResponse class

  //****************** Beginning of the WorkbooksApi class
  #pragma warning disable 0219, 0168  // Disable the warnings about variables never used
  public class WorkbooksApi
  {
    public const int API_VERSION = 1;

    protected string SessionId { get; set; }

    protected string ApiKey { get; set; }
    protected string Username { get; set; }
    protected string LogicalDatabaseId { get; set; }
    protected string DatabaseInstanceId { get; set; }
    protected string AuthenticityToken { get; set; }
    protected int apiVersion = API_VERSION;
    protected bool loggedIn = false;
    protected bool autoLogout = true;   // true => call logout() in destroy hook
    protected string ApplicationName { get; set; }
    protected string UserAgent { get; set; }
    protected int connectTimeout = 120000;
    protected bool verifyPeer = true;   // false is not correct for Production use.
    protected bool fastLogin = true; // speed up the login by not returning my_queues and some other details during login.
    protected string service = "https://secure.workbooks.com";
    protected decimal LastRequestDuration { get; set; }
    protected string UserQueues { get; set; }   // when logged in contains an array of user queues
    protected string jsonPretty = "pretty";// have json print pretty
    protected Dictionary<string, object> LoginResponse { get; set; }

    // Exit codes which mean something to the Workbooks Process Engine.
    const int EXIT_OK = 0;
    const int EXIT_RETRY = 1;
    const int EXIT_DISABLE = 2;

    /**
   * Those HTTP status codes of particular significance to the API.
   */
    public const int HTTP_STATUS_OK = 200;
    public const int HTTP_STATUS_FOUND = 302;
    public const int HTTP_STATUS_FORBIDDEN = 403;

    /**
   * The Workbooks session cookie
   */
    const string SESSION_COOKIE = "Workbooks-Session";

    /**
   * The content_type governs the encoding used for data transfer to the Service. Two forms are supported in this binding; use FORM_DATA for file uploads.
   */
    const string FORM_URL_ENCODED = "application/x-www-form-urlencoded";
    const string FORM_DATA = "multipart/form-data";

    /**
   * Define a hard limit of 1 MegaByte to limit the size of a log message logged with the default logger.
   */
    public const int HARD_LOG_LIMIT = 1048576;
    public const int DEFAULT_LOG_LIMIT = 4096;

    TraceSource source1 = new TraceSource("ApiWrapper");
    SourceSwitch sourceSwitch1 = new SourceSwitch("", "All");


    //************* Beginning of Getter/Setter methods ***************

    public int ApiVersion {
      get {
        return apiVersion;
      }
      set {
        apiVersion = value;
      }
    }

    public bool isLoggedIn {
      get {
        return loggedIn;
      }
      set {
        loggedIn = value;
      }
    }

    public bool isAutoLogout {
      get {
        return autoLogout;
      }
      set {
        autoLogout = value;
      }
    }

    public int ConnectTimeout {
      get {
        return connectTimeout;
      }
      set {
        connectTimeout = value;
      }
    }

    public bool VerifyPeer {
      get {
        return verifyPeer;
      }
      set {
        verifyPeer = value;
      }
    }

    public bool FastLogin {
      get {
        return fastLogin;
      }
      set {
        fastLogin = value;
      }
    }

    public string Service {
      get {
        return service;
      }
      set {
        service = value;
      }
    }

    public string JsonPretty {
      get {
        return jsonPretty;
      }
      set {
        jsonPretty = value;
      }
    }

    /// <summary>Get the session cookie </summary>
    /// <returns> String - the session cookie </returns>
    public String getSessionCookie() {
      String session_cookie = null;
      if (SessionId != null) {
        session_cookie = WorkbooksApi.SESSION_COOKIE + "=" +  this.SessionId;
      }
      return session_cookie;
    }

    //************* End of Getter/Setter methods ***************

    public string getDatabaseInstanceRef() {
      return System.Convert.ToBase64String (Encoding.UTF8.GetBytes (this.DatabaseInstanceId + "17"));
    }

    /// <summary>Constructor to build the WorkbooksApi object with the passed parameters</summary>
    /// <param name="loginParams">  parameters to be set in the object </param>

    public WorkbooksApi(Dictionary<string, object> loginParams)  {
      // A File can be created to write the logs using the TextWriterTraceListener
      //      TextWriterTraceListener twtl = new TextWriterTraceListener ("/Users/Home/Documents/thinCsharpLogger.log");
      //      twtl.Name = "TextLogger";
      //      twtl.TraceOutputOptions = TraceOptions.ThreadId | TraceOptions.DateTime;
      //      source1.Listeners.Add (twtl);

      // Using the consoleTraceListner to output all the log messages to the Console
      ConsoleTraceListener ctl = new ConsoleTraceListener(false);
      //ctl.TraceOutputOptions = TraceOptions.DateTime ;
      source1.Listeners.Add (ctl);
      source1.Switch = sourceSwitch1;

      if (loginParams.ContainsKey("application_name")) {
        ApplicationName = (string)loginParams ["application_name"];
      } else {
        throw new Exception("An application name must be supplied");
      }
      if (loginParams.ContainsKey("user_agent")) {
        UserAgent = (string) loginParams["user_agent"];
      } else {
        throw new Exception("A user agent must be supplied");
      }
      if (loginParams.ContainsKey("service")) {
        Service = (string) loginParams["service"];
      }
      if (loginParams.ContainsKey("connect_timeout")) {
        ConnectTimeout = (int) loginParams["connect_timeout"];
      }
      if (loginParams.ContainsKey("api_key")) {
        ApiKey = (string) loginParams["api_key"];
      }
      if (loginParams.ContainsKey("username")) {
        Username = (string) loginParams["username"];
      }
      if (loginParams.ContainsKey("session_id")) {
        SessionId = (string) loginParams["session_id"];
      }
      if (loginParams.ContainsKey("logical_database_id")) {
        LogicalDatabaseId = (string)loginParams["logical_database_id"];
      }
      if (loginParams.ContainsKey("verify_peer")) {
        VerifyPeer = (bool) loginParams["verify_peer"];
      }
      if (loginParams.ContainsKey("jsonPretty")) {
        JsonPretty = (string)loginParams["jsonPretty"];
      }
      if (loginParams.ContainsKey("api_version")) {
        ApiVersion = (int) loginParams["api_version"];
      }
    }

    public void log(String msg) {
      log(msg, new object[]{});
    }

    public void log(String msg, Object[] messageObjects) {
      log(msg, messageObjects, "debug", DEFAULT_LOG_LIMIT);
    }

    public void log(String msg, Object[] messageObjects, String level) {
      log(msg, messageObjects, level, DEFAULT_LOG_LIMIT);
    }

    /// <summary>
    /// Log the specified msg, messageObjects.
    /// </summary>
    /// <param name="msg">Message - msg a string to be logged.</param>
    /// <param name="messageObjects">Message objects - any object values to output with the message.</param>
    /// <param name="level">Level - level optional: one of 'error', 'warning', 'notice', 'info', 'debug' (the default), or 'output'.</param>
    /// <param name="log_size_limit">Log_size_limit -  log_size_limit the maximum size msg that will be logged. Logs the first and last parts of longer msgs and indicates the number of bytes that have
    ///         not been logged..</param>
    public void log(String msg, Object[] messageObjects, String level, int log_size_limit ) {
      msg += " «";
      if (messageObjects != null) {
        for (int i = 0; i < messageObjects.Length; i++) {
          msg += "{" + i + "}, ";
        }
      }
      msg += "»";

      int msg_size = msg.Length;

      if (msg_size > log_size_limit) {
        // Apply a hard limit to limit the load on the Workbooks service.
        log_size_limit = log_size_limit > HARD_LOG_LIMIT ? HARD_LOG_LIMIT : log_size_limit;
        msg = msg.Substring(0, log_size_limit / 2) + "... (" + (msg_size - log_size_limit) + " bytes) ..." + msg.Substring(msg_size - log_size_limit / 2);
      }

      // According to the level passed in, log the level for the logger
      if (level.Equals("debug")) {
        source1.TraceEvent(TraceEventType.Information, 2, msg, messageObjects);
      } else if (level.Equals("warning")) {
        source1.TraceEvent(TraceEventType.Warning, 3, msg, messageObjects);
      } else if (level.Equals("error")) {
        source1.TraceEvent(TraceEventType.Error, 1, msg, messageObjects);
      } else {
        source1.TraceEvent(TraceEventType.Information, 2, msg, messageObjects);
      }
      source1.Flush();
    }

    /// <summary>Extract ids and lock_versions from the 'affected_objects' of the response and return them as an Array of Arrays. </summary>
    /// <param name="response"> -  a response from the service API. </param>
    /// <returns> List - a set of id and lock_version values, one per affected object.</returns>
    public List<Dictionary<string, object>> idVersions(WorkbooksApiResponse response) {
      List<Dictionary<string, object>> retval = new List<Dictionary<string, object>>();

      object[] affected_objects = response.getAffectedObjects();
      //log("Affected Objects in idVersions: ", new Object[]{affected_objects});
      for (int i = 0; i < affected_objects.Length; i++) {
        Dictionary<string, object> affected = (Dictionary<string, object>) affected_objects[i];
        Dictionary<string, object> objectIdVersions = new Dictionary<string, object>();
        objectIdVersions.Add("id", affected["id"]);
        objectIdVersions.Add("lock_version", affected["lock_version"]);
        retval.Add(objectIdVersions);
      }
      return retval;
    }

    /// <summary>Builds and sends an HTTP request.
    ///
    /// Exceptions are raised if there is an error, for example a failure to resolve the service name, or an inability to make a connection to the service.
    /// Assuming the service can be contacted errors and warnings are passed back so the caller can capture the http_status of the response.
    /// </summary>
    /// <param name="endpoint">
    ///           selects the portion of the API to use, e.g. 'crm/organisations'.
    /// </param>
    /// <param name="method">
    ///           the restful method - one of 'GET', 'PUT', 'POST', 'DELETE'.
    /// </param>
    /// <param name="post_params">
    ///           A hash of uniquely-named parameters to add to the POST body.
    /// </param>
    /// <param name="ordered_post_params">
    ///           A simple array of additional parameters, to use for the POST body (may have duplicate keys e.g. 'id[]')
    /// </param>
    /// <param name="options">
    ///           Optional options, currently only 'content_type' is supported which defaults to 'application/x-www-form-urlencoded'
    /// </param>
    /// <returns> HashMap (Integer the http status, String the response text) </returns>
    /// <exception [cref="WorkbooksApiException"] />
    public Dictionary<string , object> makeRequest(string endpoint, string method, Dictionary<string, object> post_params, List <object> ordered_post_params,
      Dictionary<string, object> options) {
      string content_type = FORM_URL_ENCODED;
      string post_fields = null;
      Stream dataStream = null;
      WebRequest request = null;
      string responseFromServer = null;
      int? status = null;
      WebHeaderCollection responseHeaders = null;
      WebResponse response = null;
      string LINE_FEED = "\r\n";
      Stream dataOutputStream = null;

      if (options != null && options.ContainsKey ("content_type")) {
        content_type = (string)options ["content_type"];
      }
      int start_time = System.DateTime.Now.Millisecond;
      Dictionary<string, object> url_params = new Dictionary<string, object> ();
      url_params.Add ("_dc", System.Math.Round (new decimal (start_time * 1000)));   // cache-buster

      String url = this.getUrl (endpoint, url_params);
      if (post_params == null) {
        post_params = new Dictionary<string, object> ();
      }
      // post_params.Add("_method", method.toUpperCase());
      post_params.Add ("client", "api");

      if (!method.Equals ("GET") && AuthenticityToken != null) {
        post_params.Add ("_authenticity_token", AuthenticityToken);
      }
      long requestStartTime = System.DateTime.Now.Ticks;

      //************** content type is application/x-www-form-urlencoded *****************
      if (content_type != null && content_type.Equals (FORM_URL_ENCODED)) {
        post_fields = build_queryString (post_params);
        if (ordered_post_params != null) {
          foreach (object object_value in ordered_post_params) {
            post_fields += "&" + object_value.ToString ();
          }
        }
        try {
          if (method.Equals ("GET")) {
            url += "&" + post_fields;
          }
          request = createHttpConnectionObject (url, method, post_fields, content_type);
          //this.log("post_fields", new Object[] {post_fields});

          if (!method.Equals ("GET")) {
            byte[] byteArray = Encoding.UTF8.GetBytes (post_fields);
            dataStream = request.GetRequestStream ();
            // Write the data to the request stream.
            dataStream.Write (byteArray, 0, byteArray.Length);
            // Close the Stream object.
            dataStream.Close ();
          }
        } catch (Exception ioEx) {
          //this.log("Error occured while getting the I/O stream from the connection.");
          Dictionary<string, object> exceptionObj = new Dictionary<string, object> ();
          Dictionary<string, object> errorObj = new Dictionary<string, object> ();
          errorObj.Add ("message", "Error occured while getting the request stream from the connection: " + ioEx.Message);
          errorObj.Add ("type", "IOException");

          exceptionObj.Add ("workbooks_api", this);
          exceptionObj.Add ("error", errorObj);
          WorkbooksApiException e = new WorkbooksApiException (exceptionObj);
          throw e;
        }
      } else { // ***************** if the content type is multipart/form-data  **************************
        List<Dictionary<string, object>> fields = new List<Dictionary<string, object>> ();

        foreach (String key in post_params.Keys) {
          object value = post_params [key];
          if (value.GetType ().FullName.StartsWith ("System.Collections.Generic.Dictionary")) {
            Dictionary<string, object> fieldParts = (Dictionary<string, object>)value;
            foreach (String fieldKey in fieldParts.Keys) {
              Dictionary<string, object> part = new Dictionary<string, object> ();
              part.Add (fieldKey, fieldParts [fieldKey]);
              fields.Add (part);
            }
          } else if (value != null) {
            Dictionary<string, object> part = new Dictionary<string, object> ();
            part.Add (key, value);
            fields.Add (part);
          }
        }

        foreach (Object orderedParam in ordered_post_params) {
          if (orderedParam.GetType ().FullName.StartsWith ("System.Collections.Generic.Dictionary")) {
            fields.Add ((Dictionary<string, object>)orderedParam);
          } else {
            String[] keyValue = ((String)orderedParam).Split ("=".ToCharArray ());
            Dictionary<string, object> part = new Dictionary<string, object> ();
            part.Add (keyValue [0], keyValue [1]);
            fields.Add (part);
          }
        }

        try {
          Random rnd = new System.Random ();
          var boundaryFormat = rnd.Next ().ToString ("x8") + System.DateTime.Now.Ticks.ToString ("x8") + rnd.Next ().ToString ("x8");

          String boundary = "-----------------------form-data-" + boundaryFormat;
          content_type = FORM_DATA + "; boundary=" + boundary;
          request = createHttpConnectionObject (url, method, fields.ToString (), content_type);
          dataOutputStream = new System.IO.MemoryStream ();
          dataStream = request.GetRequestStream ();

          foreach (Dictionary<string, object> field in fields) {
            foreach (String fieldKey in field.Keys) {
              Object fieldValue = field [fieldKey];
              if (fieldValue.GetType ().FullName.StartsWith ("System.Collections.Generic.Dictionary")) {
                Dictionary<string, object> uploadFileDetails = (Dictionary<string, object>)fieldValue;
                String fileName = (String)uploadFileDetails ["file_name"];
                String fileContentType = (String)uploadFileDetails ["file_content_type"];
                String tmpFile = uploadFileDetails ["tmp_name"].ToString ();
                String fileClassName = uploadFileDetails ["tmp_name"].GetType ().FullName;

                if (fileName != null && fileContentType != null && fileClassName.Equals ("System.IO.FileInfo")) {

                  // send multipart form data (required) for file
                  string fileHeaders = "--" + boundary
                    + LINE_FEED
                    + "Content-Disposition: form-data; name=\"" + fieldKey + "\"; filename=\"" + fileName + "\""
                    + LINE_FEED
                    + "Content-Type: " + fileContentType
                    + LINE_FEED
                    + "Content-Transfer-Encoding: binary"
                    + LINE_FEED
                    + LINE_FEED;
                  byte[] fileHeaderBytes = Encoding.UTF8.GetBytes (fileHeaders);
                  try {
                    dataStream.Write (fileHeaderBytes, 0, fileHeaderBytes.Length);

                    FileStream fileInputStream = new FileStream (tmpFile, FileMode.Open);
                    int bytesRead = 0;
                    byte[] buffer = new byte[1024];
                    while ((bytesRead = fileInputStream.Read (buffer, 0, buffer.Length)) != 0) {
                      dataStream.Write (buffer, 0, bytesRead);
                    }
                    fileInputStream.Close ();
                    dataStream.Write (Encoding.UTF8.GetBytes (LINE_FEED), 0, LINE_FEED.Length);

                  } catch (IOException ioEx) {
                    Dictionary<string, object> exceptionObj = new Dictionary<string, object> ();
                    Dictionary<string, object> errorObj = new Dictionary<string, object> ();
                    errorObj.Add ("message", "Error while reading the file: " + ioEx.Message);
                    errorObj.Add ("type", "IOException");

                    exceptionObj.Add ("workbooks_api", this);
                    exceptionObj.Add ("error", errorObj);
                    WorkbooksApiException e = new WorkbooksApiException (exceptionObj);
                    throw e;
                  }
                }
              } else {
                string plainHeaders = "--" + boundary
                  + LINE_FEED
                  + "Content-Disposition: form-data; name=\"" + fieldKey + "\""
                  + LINE_FEED
                  + LINE_FEED
                  + fieldValue.ToString ()
                  + LINE_FEED;
                byte[] plainHeadersBytes = Encoding.UTF8.GetBytes (plainHeaders);
                dataStream.Write (plainHeadersBytes, 0, plainHeadersBytes.Length);
                dataStream.Flush ();
              }
            }
          } // end of for
          string endBoundary = "--" + boundary + "--" + LINE_FEED;
          byte[] endBoundaryBytes = Encoding.UTF8.GetBytes (endBoundary);
          dataStream.Write (endBoundaryBytes, 0, endBoundaryBytes.Length);

        } catch (Exception ioEx) {
          Dictionary<string, object> exceptionObj = new Dictionary<string, object> ();
          Dictionary<string, object> errorObj = new Dictionary<string, object> ();
          errorObj.Add ("message", "Error while writing to the output stream of connection: " + ioEx.Message);
          errorObj.Add ("type", "IOException");

          exceptionObj.Add ("workbooks_api", this);
          exceptionObj.Add ("error", errorObj);
          WorkbooksApiException e = new WorkbooksApiException (exceptionObj);
          throw e;
        }
        finally {
          dataOutputStream.Close ();
          dataStream.Close ();
        }
      }
      // ************** END of content type is multipart/form-data ********************
      try {
        ((HttpWebRequest)request).AllowAutoRedirect=true;
        // Get the response.
        response = request.GetResponse ();
        status = (int)((HttpWebResponse)response).StatusCode;

        responseHeaders = response.Headers;
        //       Console.WriteLine ("Response Headers are: " + responseHeaders);

        if (status != HTTP_STATUS_OK) {
          Dictionary<string, object> exceptionObj = new Dictionary<string, object> ();
          Dictionary<string, object> errorObj = new Dictionary<string, object> ();
          errorObj.Add ("message", "HTTP status not found: bad request?");
          errorObj.Add ("type", "ConnectionException");

          exceptionObj.Add ("workbooks_api", this);
          exceptionObj.Add ("error_code", status);
          exceptionObj.Add ("error", errorObj);

          WorkbooksApiException e = new WorkbooksApiException (exceptionObj);
          throw e;
        } else {
          // Get the stream containing content returned by the server.
          dataStream = response.GetResponseStream ();
          // Open the stream using a StreamReader for easy access.
          StreamReader reader = new StreamReader (dataStream);
          // Read the content.
          responseFromServer = reader.ReadToEnd ();
          // Clean up the streams.
          reader.Close ();
          dataStream.Close ();
          response.Close ();
        }

      } catch (Exception ioe) {
        //        this.log("Exception in makeRequest while making request to connection: ");
        Dictionary<string, object> exceptionObj = new Dictionary<string, object> ();
        Dictionary<string, object> errorObj = new Dictionary<string, object> ();
        errorObj.Add ("message", "Error while getting input stream from connection in multipart: " + ioe.Message);
        errorObj.Add ("type", "IOException");

        exceptionObj.Add ("workbooks_api", this);
        exceptionObj.Add ("error", errorObj);
        WorkbooksApiException e = new WorkbooksApiException (exceptionObj);
        throw e;
      }

      string decodedString = responseFromServer;
      string body = decodedString;
//      log("Body:", new Object[] {body});

      if (status == 0) {
        Dictionary<string, object> exceptionObj = new Dictionary<string, object> ();
        Dictionary<string, object> errorObj = new Dictionary<string, object> ();
        errorObj.Add ("message", "HTTP status not found: bad request?");
        errorObj.Add ("type", "BadRequest");
        errorObj.Add ("response", responseHeaders);

        exceptionObj.Add ("workbooks_api", this);
        exceptionObj.Add ("error_code", 0);
        exceptionObj.Add ("error", errorObj);

        WorkbooksApiException e = new WorkbooksApiException (exceptionObj);
        throw e;
      }

      long requestEndtime = System.DateTime.Now.Ticks;
      this.LastRequestDuration = requestEndtime - requestStartTime;
      // Extract the session_id from the response and retain it for future requests. This
      // may be a different ID from the one the client may have just sent.
      //   Set-Cookie: Workbooks-Session=7c67eba894177c768b4f0b84090704b7; path=/; secure; HttpOnly
      string cookieHeader = null;
      int i = 0;
      foreach (string resKey in responseHeaders.Keys) {
        if (resKey.Equals ("Set-Cookie")) {
          cookieHeader = resKey;
          break;
        } else {
          i++;
        }
      }
      if (cookieHeader != null) {
        Match session_cookies = Regex.Match (responseHeaders.Get (i).ToString (), @"^.*Workbooks-Session=(.*?);");
        SessionId = (session_cookies.Length > 0) ? session_cookies.Groups [1].Value : "";
      }

      Dictionary<string, object> retval = new Dictionary<string, object>();
      retval.Add("http_status", status);
      retval.Add("http_body", body);

      return retval;
    } // End of makeRequest()


    /// <summary>
    /// Make a call to an endpoint on the service, reconnecting to the session first if necessary if running beneath the Process Engine.
    /// </summary>
    /// <returns>WorkbooksApiResponse - the decoded json response if decode_json is true (default), or the raw response if not.</returns>
    /// <param name="endpoint">selects the portion of the API to use, e.g. 'crm/organisations'.</param>
    /// <param name="method">method the restful method - one of 'GET', 'Add', 'POST', 'DELETE'.</param>
    /// <param name="post_params">post_params A hash of uniquely-named parameters to add to the POST body.</param>
    /// <param name="ordered_post_params">ordered_post_params A simple array of additional parameters, to use for the POST body (may have duplicate keys e.g. 'id[]')</param>
    /// <param name="options">Optional options to pass through to makeRequest(). For backwards-compatability, setting this instead to 'true' or 'false' toggles the
    ///          decoding of JSON.</param>
    /// <exception cref="WorkbooksApiException"></exception>

    public WorkbooksApiResponse apiCall(String endpoint, String method, Dictionary<string, object> post_params, List<Object> ordered_post_params,
      Dictionary<string, object> options) {

      //this.log("apiCall() called with params", new Object[] {endpoint, method, post_params, ordered_post_params, options});
      Dictionary<string, object> response = null;
      if (post_params == null) {
        post_params = new Dictionary<string, object>();
      }
      // NOTE: Client needs to pass decode_json=false if parsing as json is not required
      if (options == null) {
        options = new Dictionary<string, object>();
        options.Add("decode_json", true);
      } else if (!options.ContainsKey("decode_json")) {
        options.Add("decode_json", true);
      }

      // Clients using API Keys normally pass those on each request; otherwise
      // establish a session to span multiple requests.
      if (ApiKey != null) {
        post_params.Add("api_key", ApiKey);
        post_params.Add("_api_version", ApiVersion);
      } else {
        this.ensureLogin();
      }

      // API calls are always to a ".api" endpoint; the caller does not have to include this.
      // Including ANY extension will prevent ".api" from being appended.
      if(!System.Text.RegularExpressions.Regex.IsMatch(endpoint, "\\.\\w{3,4}")) {
        endpoint += ".api";
      }
      Dictionary<string, object> serviceResponse = this.makeRequest(endpoint, method, post_params, ordered_post_params, options);

      int http_status = 0;
      Object http_body = null;
      if (serviceResponse != null) {
        http_status = (int) serviceResponse["http_status"];
        http_body = serviceResponse["http_body"];
      }

      if (http_status != HTTP_STATUS_OK) {
        Dictionary<string, object> exceptionObj = new Dictionary<string, object>();
        Dictionary<string, object> errorObj = new Dictionary<string, object>();
        errorObj.Add("message", "Non-OK response (" + http_status + ")");
        errorObj.Add("type", "WorkbooksServiceException");
        errorObj.Add("response", http_body);

        exceptionObj.Add("workbooks_api", this);
        exceptionObj.Add("error_code", http_status);
        exceptionObj.Add("error", errorObj);

        WorkbooksApiException e = new WorkbooksApiException(exceptionObj);
        throw e;
      }
      response = new Dictionary<string, object>();
      bool doDecodeJson = false;
      Boolean.TryParse(options["decode_json"].ToString(), out doDecodeJson);

      if (options.ContainsKey("decode_json") && doDecodeJson) {
        object responseObject = decodeJson((String) http_body);
        response.Add("response", responseObject);
      } else {
        response.Add("response", http_body);
      }
      //this.log("apiCall() returns", new Object[]{response}, "info", DEFAULT_LOG_LIMIT);
      WorkbooksApiResponse wbResponse = new WorkbooksApiResponse(response);
      return wbResponse;
    }

    /// <summary>
    /// Decodes the json string.
    /// </summary>
    /// <returns>The json object as Dictionary</returns>
    /// <param name="jsonString">Json string.</param>
    private Dictionary<string, object> decodeJson(string jsonString) {
      try {
        var javascriptSerializer = new JavaScriptSerializer ();
        javascriptSerializer.MaxJsonLength = int.MaxValue;
        var dict = javascriptSerializer.DeserializeObject (jsonString);
        return (Dictionary<string, object>) dict;
      } catch(Exception e) {
        Console.WriteLine ("Exception in decodeJson: " + e);
        return null;
      }
    }

    /// <summary>
    /// Method which converts the Dictionary to plain string format
    /// </summary>
    /// <returns>The dictionary represented as string.</returns>
    /// <param name="dictionary">Dictionary object.</param>
    public static string convertToString(Dictionary<string, object> dictionary)
    {
      StringBuilder strBuilder = new StringBuilder ();
      if(dictionary == null) {
        return "";
      } else {
        foreach(KeyValuePair<string, object> pair in dictionary) {
          strBuilder.Append (pair.Key + ": " + pair.Value + ", ");
        }
      }
      return strBuilder.ToString();
    }

    /// <summary>
    /// Construct a URL for the current Workbooks service including path and parameters.
    /// </summary>
    /// <returns>the URL for the given parameters</returns>
    /// <param name="path">Path for the url</param>
    /// <param name="query_params">Array optional query params to append</param>

    protected string getUrl(string path, Dictionary<string, object> query_params) {
      string url = this.Service;
      if (!path.StartsWith("/")) {
        url += "/";
      }
      url += path;
      if (query_params != null) {
        url += "?" + build_queryString(query_params);
      }
      return url;
    }

    /// <summary>
    /// Method which builds the queryString with the format key=value&key=value
    /// </summary>
    /// <returns>the String in the format key=value&key=value</returns>
    /// <param name="data">dictionary of Data.</param>
    public string build_queryString(Dictionary<string, object> data) {
      System.Text.StringBuilder queryString = new System.Text.StringBuilder();
      foreach (Object pair in data.Keys) {
        //queryString.Append(Encoding.UTF8.GetBytes(pair.ToString()).ToString() + "=");
        queryString.Append(pair.ToString() + "=");
        if (data[(string)pair] == null) {
          queryString.Append("null&");
        } else {
          queryString.Append((data[(string)pair]).ToString() + "&");
        }
      }
      if (queryString.Length > 0) {
        queryString.Remove(queryString.Length - 1, 1);
      }
      return queryString.ToString();
    }

    /// <summary>
    /// Creates a HttpsURLConnection using the url and other parameters passed
    /// </summary>
    /// <returns>HttpsURLConnection - the connection to the url provided with the request parameters set</returns>
    /// <param name="url">the url to connect.</param>
    /// <param name="method">Method - GET/POST.</param>
    /// <param name="post_fields">Post_fields - fields to be sent to connection.</param>
    /// <param name="content_type">Content_type.</param>

    private WebRequest createHttpConnectionObject(String url, String method, String post_fields, String content_type) {

      //string cookie = this.getSessionCookie ();
      // Create a request using a URL that can receive a post.
      Uri uriTarget = new Uri (url);
      WebRequest request =  WebRequest.Create (uriTarget);
      this.log("URl to connect:" , new object[] {url});
      if (!VerifyPeer) {
        ServicePointManager.ServerCertificateValidationCallback += (sender, certificate, chain, sslPolicyErrors) => {
          return true;
        };
      }
      // Set the Method property of the request to POST.
      request.Method = method;
      ((HttpWebRequest)request).UserAgent = UserAgent;
			((HttpWebRequest)request).Timeout = ConnectTimeout;
      ((HttpWebRequest)request).CookieContainer = new CookieContainer ();
			((HttpWebRequest)request).Expect = "";
      // Set the ContentType property of the WebRequest.
      request.ContentType = content_type;
      return request;
    }

    /// <summary>Login to the service to set up a session.
    ///   <para><remarks>Optional settings </remarks></para>
    ///   <para>- api_key: An API key (this is preferred over username/password). </para>
    ///   <para>- username: The user's login name (required if not set using setUsername) or using an API key. </para>
    ///   <para>- password: The user's login password. Either this or a session_id must be specified. </para>
    ///   <para>- session_id: The ID of a session to reconnect to. Either this or a password must be specified.</para>
    ///   <para>- logical_database_id: The ID of a database to select - not required when the user has access to exactly one.</para>
    ///   <para>- others as defined in the API documentation (e.g. _time_zone, _strict_attribute_checking, _per_object_transactions).</para>
    /// </summary>
    /// <param name="paramsObj"> parameters with credentials and other options to the login API endpoint.</param>
    /// <returns> dictionary object  (Integer the http status, String any failure reason, Array the decoded json) </return>
    ///
    /// A successful login returns an http status of 200 (WorkbooksApi::HTTP_STATUS_OK).
    /// If more than one database is available the http status is 403 (WorkbooksApi::HTTP_STATUS_FORBIDDEN), the failure reason
    ///   is 'no_database_selection_made' and the set of databases to choose from are in the decoded json beneath the 'databases'
    ///   key. Repeat the login() call, passing in a logical_database_id: you might use the 'default_database_id' value which
    ///   was returned in the previous login attempt.
    /// Otherwise the login has failed outright: see the Workbooks API documentation for a list of the possible http statuses.

    public Dictionary<string, object> login(Dictionary<string, object> paramsObj) {
      Dictionary<string, object> retval = null;

      // this->log('login() called with params', params);
      if (!paramsObj.ContainsKey("api_key")) {
        paramsObj.Add("api_key", ApiKey);
      }
      if (!paramsObj.ContainsKey("username")) {
        paramsObj.Add("username", Username);
      }
      if (!paramsObj.ContainsKey("api_key") && !paramsObj.ContainsKey("username")) {
        throw new Exception("An API key or a username must be supplied");
      }
      if (!paramsObj.ContainsKey("password") && !paramsObj.ContainsKey("session_id")) {
        paramsObj.Add("session_id", SessionId);
      }
      if (!paramsObj.ContainsKey("api_key") && !paramsObj.ContainsKey("password") && !paramsObj.ContainsKey("session_id")) {
        throw new Exception("A password or session_id must be supplied unless using an API Key");
      }
      if (!paramsObj.ContainsKey("logical_database_id")) {
        paramsObj.Add("logical_database_id", LogicalDatabaseId);
      }
      if (!paramsObj.ContainsKey("logical_database_id") && !paramsObj.ContainsKey("password") && !paramsObj.ContainsKey("session_id")) {
        throw new Exception("A logical database ID must be supplied when trying to re-connect to a session");
      }

      // These default settings can be overridden by the caller.
      paramsObj.Add("_application_name", ApplicationName);
      paramsObj.Add("json", JsonPretty);
      paramsObj.Add("_strict_attribute_checking", Boolean.TrueString);
      paramsObj.Add("api_version", ApiVersion);
      paramsObj.Add("_fast_login", FastLogin);

      Dictionary<string, object> serviceResponse = makeRequest("login.api", "POST", paramsObj, null, null);
      int http_status = 0;
      String response = null;
      if (serviceResponse != null) {
        http_status = (int) serviceResponse["http_status"];
        response = (string) serviceResponse["http_body"];
      }

      // Get the JsonObject structure from JsonReader.
      Dictionary<string, object> responseJsonObject = decodeJson(response);
      // The authenticity_token is valid for a specific session and is required when any modifications are attempted.
      if (http_status == HTTP_STATUS_OK) {
        this.isLoggedIn = true;
        if (responseJsonObject != null) {
          if (responseJsonObject["my_queues"].GetType().ToString().StartsWith("System.Collections.Generic.Dictionary") ) {
            UserQueues = convertToString((Dictionary<string, object>)responseJsonObject["my_queues"]);
          }
          AuthenticityToken = (string)responseJsonObject ["authenticity_token"];
          DatabaseInstanceId = ((int)responseJsonObject ["database_instance_id"]).ToString();
          LoginResponse = responseJsonObject;
        }
      }
      retval = new Dictionary<string, object>();
      retval.Add("http_status", http_status);
      if (responseJsonObject != null) {
        retval.Add("failure_message", responseJsonObject.ContainsKey("failure_message") ? responseJsonObject["failure_message"] : "");
        retval.Add("response", responseJsonObject);
      }
      return retval;
    }

    /// <summary>
    /// Logout from the service
    /// </summary>
    /// <returns>Dictionary : 'success' - whether it succeeded, 'http_status', 'response' - the response body</returns>
    // A successful logout will return a 'success' value of true
    public Dictionary<string, object> logout() {
      Dictionary<string, object> retval = new Dictionary<string, object>();

      Dictionary<string, object> serviceResponse = this.makeRequest("logout", "POST", null, null, null);

      this.isLoggedIn = false; // force a login regardless of the
      // server-side state
      this.AuthenticityToken = null;
      int http_status = (int) serviceResponse["http_status"];
      String response = (String) serviceResponse["http_body"];
      // Get the JsonObject structure from JsonReader.
      //      Dictionary<string, object> responseObject = decodeJson(response);
      bool success = (http_status == WorkbooksApi.HTTP_STATUS_FOUND) ? true : false;

      retval.Add("http_status", http_status);
      retval.Add("success", success);
      retval.Add("response", response);
      source1.Close ();
      //this.log("logout() returns", new Object[] {retval}, "info", 4096);
      return retval;
    }

    /// <summary>
    /// Make a request to an endpoint on the service to read or list objects. You must have logged in first
    /// <para>As usual, check the API documentation for further information.</para>
    /// </summary>
    /// <returns>WorkbooksApiResponse the decoded json response if decode_json is true (default), or the raw response if not</returns>
    /// <param name="endpoint">String endpoint selects the portion of the API to use, e.g. 'crm/organisations'</param>
    /// <param name="paramsObj">Dictionary params the parameters to the API call - filter, limit, column selection as an array of hashes;
    /// each hash element can have a simple value or be an array of values e.g. for column selection.
    /// </param>
    /// <param name="options">HashMap options Optional options to pass through to makeRequest() potentially including 'content_type'.
    ///   For backwards-compatibility, setting this instead to 'true' or 'false' toggles the decoding of JSON..
    /// </param>
    ///
    ///
    public WorkbooksApiResponse get(String endpoint, Dictionary<string, object> paramsObj, Dictionary<string, object> options) {
      bool url_encode = false;
      if (options == null) {
        options = new Dictionary<string, object>();
        options.Add("decode_json", true);
      }
      if (options != null && options.ContainsKey("content_type")) {
        options.Add("content_type", FORM_URL_ENCODED);
      } else {
        url_encode = true;
      }
      ArrayList keysToRemove = new ArrayList ();
      List<Object> array_params = new List<Object>();
      if (paramsObj != null) {
        foreach (string key in paramsObj.Keys) {
          object paramValue = paramsObj [key];
          if (paramValue.GetType().IsArray) {
            if (key.Equals("_filters[]")) {
              try {
                string[] fil = (string[]) paramValue;
                string[][] make_2d_filter = new string[1][];
                Array.Copy(fil, make_2d_filter[0], fil.Length);
                paramsObj[key] = make_2d_filter;
              } catch (Exception ce) {
                string[][] fil = (string[][]) paramValue;
              }
              string[][] filter_params = (string[][]) paramValue;
              foreach (string[] filter in filter_params) {
                array_params.Add("_ff[]=" + (url_encode ? HttpUtility.UrlEncode (filter[0]) : filter[0]));
                array_params.Add("_ft[]=" + (url_encode ? HttpUtility.UrlEncode (filter[1]) : filter[1]));
                array_params.Add("_fc[]=" + (url_encode ? HttpUtility.UrlEncode (filter[2]) : filter[2]));
              }
            } else {
              String[] paramValues = (String[]) paramValue;
              foreach (string stringKey in paramValues) {
                array_params.Add (key + "=" + (url_encode ? HttpUtility.UrlEncode (stringKey) : stringKey));
              }
            }
            // add the keys to remove from the object to a list
            keysToRemove.Add (key);
          }
        }
        // Remove the key from the map . There must be a better way to remove an item from dictionary
        foreach(string keyRemove in keysToRemove) {
          paramsObj.Remove (keyRemove);
        }
      }
      return this.apiCall(endpoint, "GET", paramsObj, array_params, options);
    }

    /// <summary>
    /// Interface as per get() but if the response is not 'ok' it also logs an error and raises an exception.
    /// </summary>
    /// <returns>WorkbooksApiResponse the decoded json response if decode_json is true (default), or the raw response if not</returns>
    /// <param name="endpoint">String endpoint selects the portion of the API to use, e.g. 'crm/organisations'</param>
    /// <param name="paramsObj">Dictionary params the parameters to the API call - filter, limit, column selection as an array of hashes;
    /// each hash element can have a simple value or be an array of values e.g. for column selection.
    /// </param>
    /// <param name="options">HashMap options Optional options to pass through to makeRequest() potentially including 'content_type'.
    ///   For backwards-compatibility, setting this instead to 'true' or 'false' toggles the decoding of JSON..
    /// </param>
    public WorkbooksApiResponse assertGet(String endpoint, Dictionary<string, object> paramsObj, Dictionary<string, object> options) {
      if (options == null) {
        options = new Dictionary<string, object>();
        options.Add("decode_json", true);
      }
      WorkbooksApiResponse response = this.get(endpoint, paramsObj, options);
      response.assertResponse();
      return response;
    }


    /// <summary>
    /// Make a request to an endpoint on the service to operate on multiple objects. You must have logged in first. You can request a combination of CREATE, UPDATE
    /// and DELETE operations, to be batched together. This is the core method upon which other methods are implemented which perform a subset of these operations.
    /// As usual, check the API documentation for further information.
    /// </summary>
    /// <returns> WorkbooksApiResponse -  the decoded response.
    /// <param name="endpoint">Endpoint - endpoint selects the portion of the API to use, e.g. 'crm/organisations'..</param>
    /// <param name="objs">Objects - objs an array of objects to create, update or delete.</param>
    /// <param name="paramsObj">Parameters object-  a set of additional parameters to send along with the data, for example array('_per_object_transactions' => true)
    /// to change the commit behaviour.</param>
    /// <param name="method">Method - The method (CREATE/UPDATE/DELETE) which is to be used if not specified for an object.</param>
    /// <param name="options">Options -  Optional options to pass through to makeRequest() potentially including 'content_type'.</param>
    public WorkbooksApiResponse batch(String endpoint, ref List<Dictionary<string, object>> objs, Dictionary<string, object> paramsObj, String method, Dictionary<string, object> options)
    {
      // this->log('batch() called with paramsObj', array(endpoint, objs));
      List<object> filter_params = this.encodeMethodParams(ref objs, method);
      bool url_encode = true;
      if (options != null && options.ContainsKey("content_type")) {
        url_encode = !(options["content_type"].ToString().Equals(WorkbooksApi.FORM_DATA));
      }
      List<object> ordered_post_params = this.fullSquare(objs, url_encode);
      filter_params.AddRange(ordered_post_params);
      WorkbooksApiResponse response = this.apiCall(endpoint, "PUT", paramsObj, filter_params, options);
      //    this.log("batch returns", new Object[] {response}, "info", DEFAULT_LOG_LIMIT);
      return response;
    }


    /// <summary>
    /// Interface as per batch() but if the response is not 'ok' it also logs an error and raises an exception.
    /// </summary>
    /// <returns> WorkbooksApiResponse -  the decoded response.
    /// <param name="endpoint">Endpoint - endpoint selects the portion of the API to use, e.g. 'crm/organisations'..</param>
    /// <param name="objs">Objects - objs an array of objects to create, update or delete.</param>
    /// <param name="paramsObj">Parameters object-  a set of additional parameters to send along with the data, for example array('_per_object_transactions' => true)
    /// to change the commit behaviour.</param>
    /// <param name="method">Method - The method (CREATE/UPDATE/DELETE) which is to be used if not specified for an object.</param>
    /// <param name="options">Options -  Optional options to pass through to makeRequest() potentially including 'content_type'.</param>

    public WorkbooksApiResponse assertBatch(String endpoint, List<Dictionary<string, object>> objs, Dictionary<string, object> paramsObj, String method, Dictionary<string, object> options)
    {
      WorkbooksApiResponse response = this.batch(endpoint, ref objs, paramsObj, method, options);
      response.assertResponse();
      return response;
    }

    /// <summary>
    /// Make a request to an endpoint on the service to create objects. You must have logged in first.
    /// As usual, check the API documentation for further information.
    /// </summary>
    /// <returns> WorkbooksApiResponse -  the decoded response.
    /// <param name="endpoint">Endpoint - endpoint selects the portion of the API to use, e.g. 'crm/organisations'..</param>
    /// <param name="objs">Objects - objs an array of objects to create, update or delete.</param>
    /// <param name="paramsObj">Parameters object-  a set of additional parameters to send along with the data, for example array('_per_object_transactions' => true)
    /// to change the commit behaviour.</param>
    /// <param name="method">Method - The method (CREATE/UPDATE/DELETE) which is to be used if not specified for an object.</param>
    /// <param name="options">Options -  Optional options to pass through to makeRequest() potentially including 'content_type'.</param>

    public WorkbooksApiResponse create(String endpoint, List<Dictionary<string, object>> objs, Dictionary<string, object> paramsObj, Dictionary<string, object> options)
    {
      return this.batch(endpoint, ref objs, paramsObj, "CREATE", options);
    }


    /// <summary>
    /// Interface as per create() but if the response is not 'ok' it also logs an error and raises an exception.
    /// </summary>
    /// <returns> WorkbooksApiResponse -  the decoded response.
    /// <param name="endpoint">Endpoint - endpoint selects the portion of the API to use, e.g. 'crm/organisations'..</param>
    /// <param name="objs">Objects - objs an array of objects to create, update or delete.</param>
    /// <param name="paramsObj">Parameters object-  a set of additional parameters to send along with the data, for example array('_per_object_transactions' => true)
    /// to change the commit behaviour.</param>
    /// <param name="method">Method - The method (CREATE/UPDATE/DELETE) which is to be used if not specified for an object.</param>
    /// <param name="options">Options -  Optional options to pass through to makeRequest() potentially including 'content_type'.</param>

    public WorkbooksApiResponse assertCreate(String endpoint, List<Dictionary<string, object>> objs, Dictionary<string, object> paramsObj, Dictionary<string, object> options) {
      WorkbooksApiResponse response = this.create(endpoint, objs, paramsObj, options);
      response.assertResponse();
      return response;
    }

    /// <summary>
    /// Make a request to an endpoint on the service to update objects. You must have logged in first.
    /// As usual, check the API documentation for further information.
    /// </summary>
    /// <returns> WorkbooksApiResponse -  the decoded response.
    /// <param name="endpoint">Endpoint - endpoint selects the portion of the API to use, e.g. 'crm/organisations'..</param>
    /// <param name="objs">Objects - objs an array of objects to create, update or delete.</param>
    /// <param name="paramsObj">Parameters object-  a set of additional parameters to send along with the data, for example array('_per_object_transactions' => true)
    /// to change the commit behaviour.</param>
    /// <param name="method">Method - The method (CREATE/UPDATE/DELETE) which is to be used if not specified for an object.</param>
    /// <param name="options">Options -  Optional options to pass through to makeRequest() potentially including 'content_type'.</param>

    public WorkbooksApiResponse update(String endpoint, List<Dictionary<string, object>> objs, Dictionary<string, object> paramsObj, Dictionary<string, object> options)
    {
      return this.batch(endpoint, ref objs, paramsObj, "UPDATE", options);
    }

    /// <summary>
    /// Interface as per update() but if the response is not 'ok' it also logs an error and raises an exception.
    /// </summary>
    /// <returns> WorkbooksApiResponse -  the decoded response.
    /// <param name="endpoint">Endpoint - endpoint selects the portion of the API to use, e.g. 'crm/organisations'..</param>
    /// <param name="objs">Objects - objs an array of objects to create, update or delete.</param>
    /// <param name="paramsObj">Parameters object-  a set of additional parameters to send along with the data, for example array('_per_object_transactions' => true)
    /// to change the commit behaviour.</param>
    /// <param name="method">Method - The method (CREATE/UPDATE/DELETE) which is to be used if not specified for an object.</param>
    /// <param name="options">Options -  Optional options to pass through to makeRequest() potentially including 'content_type'.</param>

    public WorkbooksApiResponse assertUpdate(String endpoint, List<Dictionary<string, object>> objs, Dictionary<string, object> paramsObj, Dictionary<string, object> options) {
      WorkbooksApiResponse response = this.update(endpoint, objs, paramsObj, options);
      response.assertResponse();
      return response;
    }

    /// <summary>
    /// Make a request to an endpoint on the service to delete objects. You must have logged in first.
    /// As usual, check the API documentation for further information.
    /// </summary>
    /// <returns> WorkbooksApiResponse -  the decoded response.
    /// <param name="endpoint">Endpoint - endpoint selects the portion of the API to use, e.g. 'crm/organisations'..</param>
    /// <param name="objs">Objects - objs an array of objects to create, update or delete.</param>
    /// <param name="paramsObj">Parameters object-  a set of additional parameters to send along with the data, for example array('_per_object_transactions' => true)
    /// to change the commit behaviour.</param>
    /// <param name="method">Method - The method (CREATE/UPDATE/DELETE) which is to be used if not specified for an object.</param>
    /// <param name="options">Options -  Optional options to pass through to makeRequest() potentially including 'content_type'.</param>

    public WorkbooksApiResponse delete(String endpoint, List<Dictionary<string, object>> objs, Dictionary<string, object> paramsObj, Dictionary<string, object> options)
    {
      return this.batch(endpoint, ref objs, paramsObj, "DELETE", options);
    }

    /// <summary>
    /// Interface as per delete() but if the response is not 'ok' it also logs an error and raises an exception.
    /// </summary>
    /// <returns> WorkbooksApiResponse -  the decoded response.
    /// <param name="endpoint">Endpoint - endpoint selects the portion of the API to use, e.g. 'crm/organisations'..</param>
    /// <param name="objs">Objects - objs an array of objects to create, update or delete.</param>
    /// <param name="paramsObj">Parameters object-  a set of additional parameters to send along with the data, for example array('_per_object_transactions' => true)
    /// to change the commit behaviour.</param>
    /// <param name="method">Method - The method (CREATE/UPDATE/DELETE) which is to be used if not specified for an object.</param>
    /// <param name="options">Options -  Optional options to pass through to makeRequest() potentially including 'content_type'.</param>

    public WorkbooksApiResponse assertDelete(String endpoint, List<Dictionary<string, object>> objs, Dictionary<string, object> paramsObj, Dictionary<string, object> options) {
      WorkbooksApiResponse response = this.delete(endpoint, objs, paramsObj, options);
      response.assertResponse();
      return response;
    }

    /// <summary>
    /// Depending on the method (Create/Update/Delete) the objects passed to Workbooks have certain minimum requirements. Callers may specify a method for each
    /// object or assume the same operation for all objects.
    /// </summary>
    /// <returns>Array of the list which has the correct method name and the lock_version, Id if required.</returns>
    /// <param name="obj_array">Obj_array - Array Objects to be encoded, *modified in place* .</param>
    /// <param name="method">Method - The method (CREATE/UPDATE/DELETE) which is to be used if not specified for an object. </param>
    protected List<object> encodeMethodParams(ref List<Dictionary<string, object>> obj_array, String method)  {
      //    this.log("encodeMethodParams called with params ", new Object[] {obj_array});
      string method_key = "__method";
      string obj_method = method;
      List<object> filter_ids = new List<object>();

      foreach (Dictionary<string, object> obj in obj_array) {
        if (obj.ContainsKey("method")) {
          method_key = "method";
        }
        if (obj.ContainsKey(method_key) && obj[method_key] != null) {
          obj_method = (string) obj[method_key];
          obj.Remove(method_key);
        }
        if (obj_method.ToUpper().Equals("CREATE")) {
          if (obj.ContainsKey("id") && !(obj["id"].ToString().Equals("0")) || obj.ContainsKey("lock_version")
            && !(obj["lock_version"].ToString().Equals("0"))) {
            // throw exception
            Dictionary<string, object> exceptionObj = new Dictionary<string, object>();
            Dictionary<string, object> errorObj = new Dictionary<string, object>();
            errorObj.Add("message", "Neither \"id\" nor \"lock_version\" can be set to create an object");
            errorObj.Add("type", "WorkbooksApiException");
            errorObj.Add("object", obj);

            exceptionObj.Add("workbooks_api", this);
            exceptionObj.Add("error", errorObj);

            WorkbooksApiException e = new WorkbooksApiException(exceptionObj);
            throw e;
          }
          obj.Add("__method", "POST");
          obj.Add("id", "0");
          obj.Add("lock_version", "0");
          filter_ids.Add("0");
        } else if (obj_method.ToUpper().Equals("UPDATE")) {
          obj.Add("__method", "PUT");
          if (!obj.ContainsKey("id") || !obj.ContainsKey("lock_version")) {
            // throw exception
            Dictionary<string, object> exceptionObj = new Dictionary<string, object>();
            Dictionary<string, object> errorObj = new Dictionary<string, object>();
            errorObj.Add("message", "Both \'id\' and \'lock_version\' must be set to update an object");
            errorObj.Add("type", "WorkbooksApiException");
            errorObj.Add("object", obj);

            exceptionObj.Add("workbooks_api", this);
            exceptionObj.Add("error", errorObj);

            WorkbooksApiException e = new WorkbooksApiException(exceptionObj);
            throw e;
          }
          filter_ids.Add(obj["id"]);
        } else if (obj_method.ToUpper().Equals("DELETE")) {
          obj.Add("__method", "DELETE");
          if (!obj.ContainsKey("id") || !obj.ContainsKey("lock_version")) {
            // throw exception
            Dictionary<string, object> exceptionObj = new Dictionary<string, object>();
            Dictionary<string, object> errorObj = new Dictionary<string, object>();
            errorObj.Add("message", "Both \'id\' and \'lock_version\' must be set to delete an object");
            errorObj.Add("type", "WorkbooksApiException");
            errorObj.Add("object", obj);

            exceptionObj.Add("workbooks_api", this);
            exceptionObj.Add("error", errorObj);

            WorkbooksApiException e = new WorkbooksApiException(exceptionObj);
            throw e;
          }
          filter_ids.Add(obj["id"]);
        } else {
          // throw exception
          Dictionary<string, object> exceptionObj = new Dictionary<string, object>();
          Dictionary<string, object> errorObj = new Dictionary<string, object>();
          errorObj.Add("message", "Unexpected method: " + method);
          errorObj.Add("type", "WorkbooksApiException");
          errorObj.Add("object", obj);

          exceptionObj.Add("workbooks_api", this);
          exceptionObj.Add("error", errorObj);

          WorkbooksApiException e = new WorkbooksApiException(exceptionObj);
          throw e;
        }
      } // end of for

      List<object> filter = new List<object>  ();
      // Must include a filter to 'select' the set of objects being operated
      // upon
      if (filter_ids.Count > 0) {
        filter.Add("_fm=or");
        for (int i = 0; i < filter_ids.Count; i++) {
          filter.Add("_ff[]=id");
          filter.Add("_ft[]=eq");
          filter.Add("_fc[]=" + filter_ids[i]);
        }
      }
      //    this.log("encodeMethodParams results ", new Object[] {filter});
      return filter;
    }

    /// <summary>
    /// The Workbooks wire protocol requires that each key which is used in any object be present in all objects, and delivered in the right order. Callers of this
    /// binding library will omit keys from some objects and not from others. Some special values are used in this encoding - :null_value: and :no_value:.
    /// </summary>
    /// <returns>Array the (encoded) set of objects suitable for passing to Workbooks.</returns>
    /// <param name="obj_array">Obj_array - Array Objects to be encoded.</param>
    /// <param name="url_encode">Boolean Whether to URL encode them, defaults to true </param>
    protected List<object> fullSquare(List<Dictionary<string, object>> obj_array, bool url_encode = true) {
      //     this.log("fullSquare() called with params", new Object[] {obj_array});

      // Use SortedSet so that the keys are unique and sorted
      SortedSet<string> allKeys = new SortedSet<string>();

      foreach (Dictionary<string, object> obj in obj_array) {
        allKeys.UnionWith(obj.Keys);
      }
      List<object> retval = new List<object>();
      Object value = new Object();

      foreach (Dictionary<string, object> obj in obj_array) {
        foreach (string key in allKeys) {
          if (obj.ContainsKey(key) && obj[key] == null) {
            value = ":null_value:";
          } else if (!obj.ContainsKey(key)) {
            value = ":no_value:";
          } else {
            value = obj[key];
          }
          String unnested_key = this.unnestKey(key);
          if(value.GetType().FullName.StartsWith("System.Collections.Generic.Dictionary")) {
            Dictionary<string, object> uploadFileDetails = (Dictionary<string, object>) value;
            if (uploadFileDetails.ContainsKey("tmp_name")) {
              Object tmpFile = uploadFileDetails["tmp_name"];
              if (tmpFile.GetType().FullName.Equals("System.IO.FileInfo")) {
                Dictionary<string, object> fileHash = new Dictionary<string, object>();
                fileHash.Add(unnested_key + "[]", value);
                retval.Add(fileHash);
              } else {
                String newValue = "[";
                foreach (string fileObj in uploadFileDetails.Keys) {
                  if (newValue != "[") {
                    newValue += ",";
                  }
                  newValue += uploadFileDetails[fileObj];
                }
                newValue += "]";
                retval.Add(url_encode ?  HttpUtility.UrlEncode (unnested_key) + "[]=" + HttpUtility.UrlEncode (newValue) : unnested_key + "[]=" + newValue);
              }
            }
          } else {
            retval.Add( url_encode ? HttpUtility.UrlEncode (unnested_key) + "[]=" + HttpUtility.UrlEncode (value.ToString()) : unnested_key + "[]=" + value.ToString());
          }
        }
      }
      //     this.log("fullSquare return value", new Object[] {retval});
      return retval;
    }

    /// <summary>
    /// Normalise any nested keys so they have the expected format for the wire, i.e. convert things like this: org_lead_party[main_location[email]] into this:
    /// org_lead_party[main_location][email]
    /// <summary>
    /// Normalise any nested keys so they have the expected format for the wire, i.e. convert things like this: org_lead_party[main_location[email]] into this:
    /// org_lead_party[main_location][email]
    /// </summary>
    /// <returns>The unnested attribute name.</returns>
    /// <param name="attribute_name">attribute_name -  the attribute name with potentially nested square brackets.</param>
    protected string unnestKey(string attribute_name) {
        // this->log('unnestKey() called with param', attribute_name);

        // If it does not end in ']]' then it is not a nested key.
        if (!System.Text.RegularExpressions.Regex.IsMatch(attribute_name, "\\]\\]$")) { return attribute_name; }

        // Otherwise it is nested: split and re-join
        string[] parts = System.Text.RegularExpressions.Regex.Split(attribute_name, "[\\[\\]]+");
        string retval = parts[0];
        for (int i = 1; i < parts.Length; i++) {
            if (!string.IsNullOrEmpty(parts[i])) {
                retval += string.Format("[{0}]", parts[i]);
            }
        }
        //this.log("unnestKey", new Object[] {retval});
        return retval;
    }

    /// <summary>
    /// Ensure we are logged in; if not then reconnect to the service if possible.
    /// </summary>

    public void ensureLogin() {

      if (!this.isLoggedIn && this.Username != null && this.SessionId != null && this.LogicalDatabaseId != null) {
        /*
       * A login failure results in it being logged in the Process Log and if the process is scheduled then it is disabled and a notification raised. Timeouts
       * result in a retry return code.
       */
        try {
          Dictionary<string, object> login_response = this.login(new Dictionary<string, object>());
          int http_status = (int) login_response["http_status"];
          if (http_status != WorkbooksApi.HTTP_STATUS_OK) {
            //this.log("Workbooks connection unsuccessful", new Object[] {login_response.get("failure_message")}, "error", DEFAULT_LOG_LIMIT);
            System.Environment.Exit(EXIT_RETRY); // retry later if the Action is scheduled
          }
        } catch (Exception e) {
          // Handle timeouts differently with a retry.

          if (System.Text.RegularExpressions.Regex.IsMatch(e.Message,"operation timed out")) {
            //            this.log("Workbooks connection timed out will re-try later", new Object[] {e.getMessage()}, "error", DEFAULT_LOG_LIMIT);
            System.Environment.Exit(EXIT_RETRY); // retry later if the Action is scheduled
          }
          //          this.log("Workbooks connection unsuccessful", new Object[] {e.getMessage()}, "error", DEFAULT_LOG_LIMIT);
          System.Environment.Exit(EXIT_RETRY); // retry later if the Action is scheduled
        }
      }

      if (this.isLoggedIn == false) {

        Dictionary<string, object> exceptionObj = new Dictionary<string, object>();
        Dictionary<string, object> errorObj = new Dictionary<string, object>();
        errorObj.Add("message", "Not logged in");
        errorObj.Add("type", "WorkbooksLoginException");

        exceptionObj.Add("workbooks_api", this);
        exceptionObj.Add("error", errorObj);

        WorkbooksApiException e = new WorkbooksApiException(exceptionObj);
        throw e;
      }
    }
  }// class WorkbooksApiConnection
} // namespace WorkbooksApi
