package workbooks_app.client_lib.java;

import java.io.BufferedReader;
import java.io.DataOutputStream;
import java.io.File;
import java.io.FileInputStream;
import java.io.IOException;
import java.io.InputStreamReader;
import java.io.StringReader;
import java.io.UnsupportedEncodingException;
import java.net.HttpURLConnection;
import java.net.URL;
import java.net.URLConnection;
import java.net.URLEncoder;
import java.util.ArrayList;
import java.util.Arrays;
import java.util.HashMap;
import java.util.Iterator;
import java.util.List;
import java.util.Map;
import java.util.TreeSet;
import java.util.logging.ConsoleHandler;
import java.util.logging.Formatter;
import java.util.logging.Level;
import java.util.logging.LogRecord;
import java.util.logging.Logger;
import javax.json.Json;
import javax.json.JsonArray;
import javax.json.JsonObject;
import javax.json.JsonReader;
import javax.net.ssl.HostnameVerifier;
import javax.net.ssl.HttpsURLConnection;
import javax.net.ssl.SSLSession;
import javax.net.ssl.SSLSocketFactory;
import javax.xml.bind.DatatypeConverter;

/** A Java wrapper for the Workbooks API 
 * 
 * 	License: www.workbooks.com/mit_license
 * 	Last commit $Id: WorkbooksApi.java 22139 2014-05-28 21:29:09Z jkay $
 *
 *
 *  Significant methods in the class Workbooks:
 *     constructor               - create an API object, specifying various options
 *     login                     - authenticate
 *     logout                    - terminate logged-in session (automatic when object destroyed)
 *     get                       - get a list of objects, or show an object
 *     create                    - create objects
 *     update                    - update objects
 *     delete                    - delete objects
 *     batch                     - create, update, and delete objects together
 *     getSessionId/setSessionId - use these to connect to an existing session
 *     condensedStatus           - use this to quickly check the response
 */


public class WorkbooksApi {

	/**
	 * Exception class - Thrown when an API call returns an exception.
	 */
	@SuppressWarnings("unchecked")
	class WorkbooksApiException extends Exception {

		private static final long serialVersionUID = -8327935446470535913L;

		/**
		 * Make a new API Exception with the given result.
		 * 
		 * @param HashMap
		 *        result - the result from the API server
		 */
		public WorkbooksApiException(HashMap<String, Object> result) {
			int code = 0;
			WorkbooksApi workbooks_api = null;

			HashMap<String, Object> errorObject = (HashMap<String, Object>) result.get("error");

			String msg = errorObject.get("message").toString();
			if (result.containsKey("error_code")) {
				code = (Integer) result.get("error_code");
			}
			// If we have access to the Workbooks API object, log all that we can
			if (result.containsKey("workbooks_api")) {
				workbooks_api = (WorkbooksApi) result.get("workbooks_api");

				workbooks_api.log("new WorkbooksApiException", new Object[] {msg, code}, "error", 4096);
			}
		}
	} // end of WorkbooksApiException class

	/**
	 * The wrapper class to handle the response from Workbooks. It has methods which return the data and the affected objects from the Workbooks Response
	 */
	class WorkbooksApiResponse {
		HashMap<String, Object> response = null;
		private Integer total = null;

		public WorkbooksApiResponse(HashMap<String, Object> response) {
			this.response = response;
//			log("In Response: ", new Object[] {response});
		}

		/**
		 * @return - returns the total number of records returned from the response
		 */
		public int getTotal() {
			if (response != null) {
				JsonObject responseData = (JsonObject) response.get("response");
				if (responseData != null) {
					total = responseData.getInt("total");
				}
			}
			return total;
		}

		/**
		 * @return - returns the response object
		 */
		public HashMap<String, Object> print() {
			return response;
		}

		/**
		 * Method to return the data object from the Workbooks Response
		 * 
		 * @return array of the data
		 */
		public JsonArray getData() {
			if (response != null) {
				JsonObject responseData = (JsonObject) response.get("response");
				JsonArray allData = responseData.getJsonArray("data");
				return allData;
			}
			return null;
		}

		/**
		 * Method to return the affected objects from the Workbooks Response after an operation
		 * 
		 * @return array of the affected objects
		 */
		public JsonArray getAffectedObjects() {
			if (response != null) {
				JsonObject responseData = (JsonObject) response.get("response");
				JsonArray allData = responseData.getJsonArray("affected_objects");
				return allData;
			}
			return null;
		}

		/**
		 * Method to return the First element in the affected object in the Workbooks Response
		 * 
		 * @return first affected object
		 */
		public JsonObject getFirstAffectedObject() {
			JsonArray allData = getAffectedObjects();
			if (allData != null) {
				return allData.getJsonObject(0);
			} else {
				return null;
			}
		}

		/**
		 * Method to return the first element in the data object in the Workbooks Response
		 * 
		 * @return the first data object
		 */
		public JsonObject getFirstData() {
			JsonArray allData = getData();
			if (allData != null) {
				return allData.getJsonObject(0);
			} else {
				return null;
			}
		}

		/**
		 * Helper function which evaluates a response to determine how successful it was
		 * 
		 * @return String One of: 'failed', 'ok', 'not-ok' 'failed' - this is unexpected. 'not-ok' - something in the request could not be satisfied; you should
		 *         check the errors and warnings. 'ok' - completely successful.
		 */
		public String condensedStatus() {
			String status = "ok";
			if (response != null) {
				JsonObject responseData = (JsonObject) response.get("response");

				if (!responseData.containsKey("success")) {
					return "failed"; // Unexpected failure - there should
					// always be a
					// "success" element
				} else if (!responseData.getBoolean("success")) {
					return "failed"; // Something was quite wrong, not just a
					// validation failure
				} else if (responseData.containsKey("errors")) {
					status = "not-ok";
				} else if (!responseData.containsKey("affected_object_information") || !responseData.get("affected_object_information").getClass().isArray()) {
					return "ok";
				} else {
					JsonArray affected_objects = responseData.getJsonArray("affected_object_information");
					for (int i = 0; i < affected_objects.size(); i++) {
						JsonObject affected = affected_objects.getJsonObject(i);
						if (!affected.containsKey("success")) {
							status = "failed"; // Again, this is unexpected.
						}
						if (!affected.getBoolean("success")) {
							status = "not-ok"; // There will be warnings or
							// errors indicated which
							// prevented complete success.
						}
					}
				}
			} else {
				status = "not-ok";
			}
			return status;
		}

		/**
		 * Check responses are expected. Raises an exception if the response is not.
		 * 
		 * @param String
		 *          expected - the expected type of response, defaults to 'ok'.
		 * @param String
		 *          raise_on_error - the exception to raise if the response is not as expected.="Unexpected response from Workbooks API"
		 */
		public void assertResponse(String expected, String raise_on_error) throws Exception {

			String condensed_status = this.condensedStatus();
			if (!condensed_status.equals(expected)) {
				log("Received an unxpected response", new Object[] {condensed_status, response, expected}, "error", WorkbooksApi.DEFAULT_LOG_LIMIT);
				throw new Exception(raise_on_error);
			}
		}

		/**
		 * Overloading the assertResponse to pass the default values of expected and raise_on_error strings
		 * 
		 * @throws Exception
		 */
		public void assertResponse() throws Exception {
			this.assertResponse("ok", "Unexpected response from Workbooks API");
		}

	} // End of WorkbooksApiResponse class
	
	
	class WorkbookLogFormatter extends Formatter {

		@Override
		public String format(LogRecord record) {		
		  return super.formatMessage(record) + "\r\n";
			}
		
	}
	
	//****************** Beginning of the WorkbooksApi class
	
	public static final int API_VERSION = 1;

	/**
	 * Instance variables
	 */
	protected String session_id = null;
	protected String api_key = null;
	protected String username = null;
	protected String logical_database_id = null;
	protected String database_instance_id = null;
	protected String authenticity_token = null;
	protected int api_version = API_VERSION;
	protected boolean login_state = false; // true => logged in
	protected boolean auto_logout = true; // true => call logout() in destroy hook
	protected String application_name = null;
	protected String user_agent = null;
	protected int connect_timeout = 120; // 2 minutes
	protected boolean verify_peer = true; // false is not correct for
	// Production use.
	protected String service = "https://secure.workbooks.com";
	protected long last_request_duration = 0;
	protected String user_queues = null; // when logged in contains an array of user queues
	protected String jsonPretty = "pretty"; // have json print pretty
	
	
	final String CHARSET = "UTF-8";

	// Exit codes which mean something to the Workbooks Process Engine.
  public static final int EXIT_OK = 0;
  public static final int EXIT_RETRY = 1;
  public static final int EXIT_DISABLE = 2;

	/**
	 * Those HTTP status codes of particular significance to the API.
	 */
	public static final int HTTP_STATUS_OK = 200;
	public static final int HTTP_STATUS_FOUND = 302;
	public static final int HTTP_STATUS_FORBIDDEN = 403;

	/**
	 * The Workbooks session cookie
	 */
	public static final String SESSION_COOKIE = "Workbooks-Session";

	/**
	 * The content_type governs the encoding used for data transfer to the Service. Two forms are supported in this binding; use FORM_DATA for file uploads.
	 */
	public static final String FORM_URL_ENCODED = "application/x-www-form-urlencoded";
	public static final String FORM_DATA = "multipart/form-data";

	/**
	 * Define a hard limit of 1 MegaByte to limit the size of a log message logged with the default logger.
	 */
	public static final int HARD_LOG_LIMIT = 1048576;
	
	public static final int DEFAULT_LOG_LIMIT = 4096;
	
	private final Logger logger = Logger.getLogger(WorkbooksApi.class.getName());
	private ConsoleHandler consoleHandler = new ConsoleHandler();
	// Can also have the logs go to a File
	//private FileHandler fileHandler = new FileHandler("/Users/Home/Documents/JavaLogger.log", true);

	/**
	 * Constructor to build the WorkbooksApi object with the passed parameters
	 * 
	 * @param params -
	 *          parameters to be set in the object
	 * @throws Exception
	 */
	public WorkbooksApi(HashMap<String, Object> params) throws Exception {
		// Initialise the logger handler and the level
		consoleHandler.setLevel(Level.INFO);
		consoleHandler.setFormatter(new WorkbookLogFormatter());
		logger.addHandler(consoleHandler);
		// add the file Handler as well
//		logger.addHandler(fileHandler);
		logger.setUseParentHandlers(false); // Turn the parent handles off, so that we can get rid of the date and classname, use the formatter instead

		if (params.containsKey("application_name")) {
			this.setApplication_name((String) params.get("application_name"));
		} else {
			throw new Exception("An application name must be supplied");
		}
		if (params.containsKey("user_agent")) {
			this.setUser_agent((String) params.get("user_agent"));
		} else {
			throw new Exception("A user agent must be supplied");
		}
		if (params.containsKey("service")) {
			this.setService((String) params.get("service"));
		}
		if (params.containsKey("connect_timeout")) {
			this.setConnect_timeout(Integer.parseInt((String) params.get("connect_timeout")));
		}
		if (params.containsKey("api_key")) {
			this.setApi_key((String) params.get("api_key"));
		}
		if (params.containsKey("username")) {
			this.setUsername((String) params.get("username"));
		}
		if (params.containsKey("session_id")) {
			this.setSession_id((String) params.get("session_id"));
		}
		if (params.containsKey("logical_database_id")) {
			this.setLogical_database_id((String) params.get("logical_database_id"));
		}
		if (params.containsKey("verify_peer")) {
			this.setVerify_peer((Boolean) params.get("verify_peer"));
		}
		if (params.containsKey("jsonPretty")) {
			this.setJsonPretty((String)params.get("jsonPretty"));
		}
		if (params.containsKey("api_version")) {
			this.setApi_version(Integer.parseInt((String)params.get("api_version")));
		}
	}
	/**
	 * Get the session cookie
	 * 
	 * @return String - the session cookie
	 */
	public String getSessionCookie() {
		String session_cookie = null;
		if (this.session_id != null) {
			session_cookie = WorkbooksApi.SESSION_COOKIE + "=" +  this.session_id;
		}
		return session_cookie;
	}

	
	public void log(String msg) {
		log(msg, null);
	}
	
	public void log(String msg, Object[] messageObjects) {
		log(msg, messageObjects, "debug", DEFAULT_LOG_LIMIT);
	}

	public void log(String msg, Object[] messageObjects, String level) {
		log(msg, messageObjects, level, DEFAULT_LOG_LIMIT);
	}

	/**
	 * Call the logger function, if any.
	 * 
	 * @param String
	 *          msg a string to be logged
	 * @param Objects
	 *          any object values to output with the message
	 * @param String
	 *          level optional: one of 'error', 'warning', 'notice', 'info', 'debug' (the default), or 'output'
	 * @param Integer
	 *          log_size_limit the maximum size msg that will be logged. Logs the first and last parts of longer msgs and indicates the number of bytes that have
	 *          not been logged.
	 */	
	public void log(String msg, Object[] messageObjects, String level, int log_size_limit ) {
		msg += " «";
		if (messageObjects != null) {
			for (int i = 0; i < messageObjects.length; i++) {
				msg += "{" + i + "}, ";
			}
		}
		msg += "»";
		
		int msg_size = msg.length();

		if (msg_size > log_size_limit) {
			// Apply a hard limit to limit the load on the Workbooks service.
			log_size_limit = log_size_limit > HARD_LOG_LIMIT ? HARD_LOG_LIMIT : log_size_limit;
			msg = msg.substring(0, log_size_limit / 2) + "... (" + (msg_size - log_size_limit) + " bytes) ..." + msg.substring(msg_size - log_size_limit / 2);
		}
		
		// According to the level passed in, log the level for the logger
		if (level.equals("debug")) {			
			logger.log(Level.INFO, msg, messageObjects);
		} else if (level.equals("warning")) {
			logger.log(Level.WARNING, msg, messageObjects);
		} else if (level.equals("error")) {
			logger.log(Level.SEVERE, msg, messageObjects);
		} else {
			logger.log(Level.FINE, msg, messageObjects);
		}
	}

	/*
	 * Extract ids and lock_versions from the 'affected_objects' of the response and return them as an Array of Arrays. 
	 * @param response -  a response from the service API. 
	 * @return Array - a set of id and lock_version values, one per affected object.
	 */
	public ArrayList<HashMap<String, Object>> idVersions(WorkbooksApiResponse response) {
		ArrayList<HashMap<String, Object>> retval = new ArrayList<HashMap<String, Object>>();

		JsonArray affected_objects = response.getAffectedObjects();
		log("Affected Objects in idVersions: ", new Object[]{affected_objects});
		for (int i = 0; i < affected_objects.size(); i++) {
			JsonObject affected = (JsonObject) affected_objects.get(i);
			HashMap<String, Object> objectIdVersions = new HashMap<String, Object>();
			objectIdVersions.put("id", affected.get("id"));
			objectIdVersions.put("lock_version", affected.get("lock_version"));
			retval.add(objectIdVersions);
		}
		return retval;
	}

	/** Method to decode the response String in Json
	 * @param response - response string
	 * @return - JsonObject
	 */
	private JsonObject decodeJson(String response) {
		if (response == null || response == "") {
			return null;
		}
		response = response.replaceAll("\"", "\\\"");

		// Create JsonReader from Json.
		JsonReader reader = Json.createReader(new StringReader(response));
		// Get the JsonObject structure from JsonReader.
		JsonObject responseObject = reader.readObject();
		// We are done with the reader, let's close it.
		reader.close();
		return responseObject;
	}

  /**
   * Login to the service to set up a session.
   *   Optional settings
   *   - api_key: An API key (this is preferred over username/password).
   *   - username: The user's login name (required if not set using setUsername) or using an API key.
   *   - password: The user's login password. Either this or a session_id must be specified.
   *   - session_id: The ID of a session to reconnect to. Either this or a password must be specified.
   *   - logical_database_id: The ID of a database to select - not required when the user has access to exactly one.
   *   others as defined in the API documentation (e.g. _time_zone, _strict_attribute_checking, _per_object_transactions).
   * @param Array params credentials and other options to the login API endpoint.
   * @return HasMap (Integer the http status, String any failure reason, Array the decoded json)
   *
   * A successful login returns an http status of 200 (WorkbooksApi::HTTP_STATUS_OK).
   * If more than one database is available the http status is 403 (WorkbooksApi::HTTP_STATUS_FORBIDDEN), the failure reason 
   *   is 'no_database_selection_made' and the set of databases to choose from are in the decoded json beneath the 'databases' 
   *   key. Repeat the login() call, passing in a logical_database_id: you might use the 'default_database_id' value which 
   *   was returned in the previous login attempt.
   * Otherwise the login has failed outright: see the Workbooks API documentation for a list of the possible http statuses.
  **/
	public HashMap<String, Object> login(HashMap<String, Object> params) throws Exception {
		HashMap<String, Object> retval = null;

		// this->log('login() called with params', params);
		if (!params.containsKey("api_key")) {
			params.put("api_key", this.getApi_key());
		}
		if (!params.containsKey("username")) {
			params.put("username", this.getUsername());
		}
		if (!params.containsKey("api_key") && !params.containsKey("username")) {
			throw new Exception("An API key or a username must be supplied");
		}
		if (!params.containsKey("password") && !params.containsKey("session_id")) {
			params.put("session_id", this.getSession_id());
		}
		if (!params.containsKey("api_key") && !params.containsKey("password") && !params.containsKey("session_id")) {
			throw new Exception("A password or session_id must be supplied unless using an API Key");
		}
		if (!params.containsKey("logical_database_id")) {
			params.put("logical_database_id", this.getLogical_database_id());
		}
		if (!params.containsKey("logical_database_id") && !params.containsKey("password") && !params.containsKey("session_id")) {
			throw new Exception("A logical database ID must be supplied when trying to re-connect to a session");
		}

		// These default settings can be overridden by the caller.
		params.put("_application_name", this.getApplication_name());
		params.put("json", this.getJsonPretty());
		params.put("_strict_attribute_checking", Boolean.toString(true));
		params.put("api_version", this.getApi_version());
		
		HashMap<String, Object> serviceResponse = makeRequest("login.api", "POST", params, null, null);
		int http_status = 0;
		String response = null;
		if (serviceResponse != null) {
			http_status = (Integer) serviceResponse.get("http_status");
			response = (String) serviceResponse.get("http_body");
		}

		// Get the JsonObject structure from JsonReader.
		JsonObject responseObject = decodeJson(response);

		// The authenticity_token is valid for a specific session and is
		// required when any modifications are attempted.
		if (http_status == HTTP_STATUS_OK) {
			this.setLogin_state(true);
			if (responseObject != null) {
				this.setUser_queues(responseObject.getJsonObject("my_queues").toString());
				this.setAuthenticity_token(responseObject.getString("authenticity_token"));
				this.setDatabase_instance_id(Integer.toString(responseObject.getInt("database_instance_id")));
			}
		}
		retval = new HashMap<String, Object>();
		retval.put("http_status", http_status);
		if (responseObject != null) {
			retval.put("failure_message", responseObject.get("failure_message") != null ? responseObject.get("failure_message").toString() : "");
			retval.put("response", responseObject);
		}

		return retval;
	}

	/**
	 * Logout from the service.
	 * 
	 * @return HashMap : 'success' - whether it succeeded, 'http_status', 'response' - the response body
	 * 
	 * A successful logout will return a 'success' value of true
	 */
	public HashMap<String, Object> logout() throws Exception {
		HashMap<String, Object> retval = new HashMap<String, Object>();

		HashMap<String, Object> serviceResponse = this.makeRequest("logout", "POST", null, null, null);

		this.setLogin_state(false); // force a login regardless of the
		// server-side state
		this.setAuthenticity_token(null);

		int http_status = (Integer) serviceResponse.get("http_status");
		// int http_status = Integer.parseInt((String) serviceResponse.get("http_status"));

		String response = (String) serviceResponse.get("http_body");
		// Get the JsonObject structure from JsonReader.
//		JsonObject responseObject = decodeJson(response);

		boolean success = (http_status == WorkbooksApi.HTTP_STATUS_FOUND) ? true : false;

		retval.put("http_status", http_status);
		retval.put("success", success);
		retval.put("response", response);

		this.log("logout() returns", new Object[] {retval}, "info", 4096);
		return retval;
	}

	/**
	 * Construct a URL for the current Workbooks service including path and parameters.
	 * 
	 * @param path
	 *          String the path
	 * @param query_params
	 *          Array optional query params to append
	 * @return String the URL for the given parameters
	 */
	protected String getUrl(String path, HashMap<String, Object> query_params) throws WorkbooksApiException{
		String url = this.getService();
		if (!path.startsWith("/")) {
			url += "/";
		}
		url += path;
		if (query_params != null) {
			url += "?" + build_queryString(query_params);
		}

		return url;
	}

	/** Method which builds the queryString with the format key=value&key=value
	* @param - the HashMap of data
	* @return - the String in the format key=value&key=value
	*/
	public String build_queryString(HashMap<String, Object> data) throws WorkbooksApiException{
		StringBuffer queryString = new StringBuffer();
		try {
			for (Object pair : data.keySet()) {
				queryString.append(URLEncoder.encode(pair.toString(), CHARSET) + "=");
				if (data.get(pair) == null) {
					queryString.append("null&");
				} else {
					queryString.append(URLEncoder.encode((String) data.get(pair), CHARSET) + "&");
				}
			}

			if (queryString.length() > 0) {
				queryString.deleteCharAt(queryString.length() - 1);
			}
		} catch (UnsupportedEncodingException unSupportEncodeEx) {
			HashMap<String, Object> exceptionObj = new HashMap<String, Object>();
			HashMap<String, Object> errorObj = new HashMap<String, Object>();
			errorObj.put("message", "Error while encoding String: " + unSupportEncodeEx.getMessage());
			errorObj.put("type", "UnsupportedEncodingException");

			exceptionObj.put("workbooks_api", this);
			exceptionObj.put("error", errorObj);
			WorkbooksApiException e = new WorkbooksApiException(exceptionObj);
			throw e;
		}
		return queryString.toString();
	}

	/**
	 * Builds and sends an HTTP request.
	 * 
	 * Exceptions are raised if there is an error, for example a failure to resolve the service name, or an inability to make a connection to the service.
	 * Assuming the service can be contacted errors and warnings are passed back so the caller can capture the http_status of the response.
	 * 
	 * @param String
	 *          endpoint selects the portion of the API to use, e.g. 'crm/organisations'.
	 * @param String
	 *          method the restful method - one of 'GET', 'PUT', 'POST', 'DELETE'.
	 * @param HashMap
	 *          post_params A hash of uniquely-named parameters to add to the POST body.
	 * @param HashMap
	 *          ordered_post_params A simple array of additional parameters, to use for the POST body (may have duplicate keys e.g. 'id[]')
	 * @param HashMap
	 *          options Optional options, currently only 'content_type' is supported which defaults to 'application/x-www-form-urlencoded'
	 * @return HashMap (Integer the http status, String the response text)
	 * @throws WorkbooksApiException
	 */
	@SuppressWarnings("unchecked")
	public HashMap<String, Object> makeRequest(String endpoint, String method, HashMap<String, Object> post_params, ArrayList<Object> ordered_post_params,
			HashMap<String, Object> options) throws WorkbooksApiException {

		StringBuffer responseStr = new StringBuffer();
		Map<String, List<String>> responseHeader = null;
		URLConnection connection = null;
		String decodedString = null;
		int status = 0;
		final String LINE_FEED = "\r\n";
		// ByteArrayOutputStream byteOut = null;
		BufferedReader reader = null;
		DataOutputStream dataOutputStream = null;
		
//		this.log("makeRequest called with ordered post params & params", new Object[] {ordered_post_params, post_params});
		
		String content_type = FORM_URL_ENCODED;
		if (options != null && options.containsKey("content_type")) {
			content_type = (String) options.get("content_type");
		}
		long start_time = System.currentTimeMillis();

		HashMap<String, Object> url_params = new HashMap<String, Object>();
		url_params.put("_dc", Integer.toString(Math.round(start_time * 1000))); // cache-buster

		String url = this.getUrl(endpoint, url_params);
		if (post_params == null) {
			post_params = new HashMap<String, Object>();
		}

		// post_params.put("_method", method.toUpperCase());
		post_params.put("client", "api");

		if (!method.equals("GET") && this.getAuthenticity_token() != null) {
			post_params.put("_authenticity_token", this.getAuthenticity_token());
		}

		String post_fields = null;

		//************** content type is application/x-www-form-urlencoded *****************
		if (content_type != null && content_type.equals(FORM_URL_ENCODED)) {
			post_fields = build_queryString(post_params);
			if (ordered_post_params != null) {
				for (Object object_value : ordered_post_params) {
					post_fields += "&" + object_value.toString();
				}
			}
			try {
				if (method.equalsIgnoreCase("GET")) {
					url += "&" + post_fields;
				}
				connection = createHttpConnectionObject(url, method, post_fields, content_type);
//				this.log("post_fields", new Object[] {post_fields});

				if (!method.equalsIgnoreCase("GET")) {
					dataOutputStream = new DataOutputStream(connection.getOutputStream());
					dataOutputStream.writeBytes(post_fields);
					dataOutputStream.flush();
					if (dataOutputStream != null) {
						dataOutputStream.close();
					}
				}
			} catch (IOException ioEx) {
				//this.log("Error occured while getting the I/O stream from the connection.");
				HashMap<String, Object> exceptionObj = new HashMap<String, Object>();
  			HashMap<String, Object> errorObj = new HashMap<String, Object>();
  			errorObj.put("message", "Error occured while getting the I/O stream from the connection: " + ioEx.getMessage());
  			errorObj.put("type", "IOException");
  
  			exceptionObj.put("workbooks_api", this);
  			exceptionObj.put("error", errorObj);
  			WorkbooksApiException e = new WorkbooksApiException(exceptionObj);
  			throw e;
			}
		} else { // ***************** if the content type is multipart/form-data  **************************
			ArrayList<HashMap<String, Object>> fields = new ArrayList<HashMap<String, Object>>();

			for (String key : post_params.keySet()) {
				Object value = post_params.get(key);
				if (value.getClass().getName().equals("java.util.HashMap")) {
					HashMap<String, Object> fieldParts = (HashMap<String, Object>) value;
					for (String fieldKey : fieldParts.keySet()) {
						HashMap<String, Object> part = new HashMap<String, Object>();
						part.put(fieldKey, fieldParts.get(fieldKey));
						fields.add(part);
					}
				} else if (value != null) {
					HashMap<String, Object> part = new HashMap<String, Object>();
					part.put(key, value);
					fields.add(part);
				}
			}

			for (Object orderedParam : ordered_post_params) {
				if (orderedParam.getClass().getName().equals("java.util.HashMap")) {
					fields.add((HashMap<String, Object>) orderedParam);
				} else {
					String[] keyValue = ((String) orderedParam).split("=");
					HashMap<String, Object> part = new HashMap<String, Object>();
					part.put(keyValue[0], keyValue[1]);
					fields.add(part);
				}
			}

			try {
				String boundary = "-----------------------form-data-" + String.format("%08x%08x%08x", Double.doubleToLongBits(Math.random()), System.currentTimeMillis(), Double.doubleToLongBits(Math.random()));
				content_type = FORM_DATA + "; boundary=" + boundary;
				connection = createHttpConnectionObject(url, method, fields.toString(), content_type);
				dataOutputStream = new DataOutputStream(connection.getOutputStream());

				for (HashMap<String, Object> field : fields) {
					for (String fieldKey : field.keySet()) {
						Object fieldValue = field.get(fieldKey);
						if (fieldValue.getClass().getName().equals("java.util.HashMap")) {
							HashMap<String, Object> uploadFileDetails = (HashMap<String, Object>) fieldValue;
							String fileName = (String)uploadFileDetails.get("file_name");
							String fileContentType = (String) uploadFileDetails.get("file_content_type");
							String tmpFile = uploadFileDetails.get("tmp_name").toString();
							String fileClassName = uploadFileDetails.get("tmp_name").getClass().getName();
							
							if (fileName != null && fileContentType != null && fileClassName.equals("java.io.File")) {
								
								// send multipart form data (required) for file
								dataOutputStream.writeBytes("--" + boundary);
								dataOutputStream.writeBytes(LINE_FEED);
								dataOutputStream.writeBytes("Content-Disposition: form-data; name=\"" + fieldKey + "\"; filename=\"" + fileName
										+ "\"");
								dataOutputStream.writeBytes(LINE_FEED);
								dataOutputStream.writeBytes("Content-Type: " + fileContentType);
								dataOutputStream.writeBytes(LINE_FEED);
								dataOutputStream.writeBytes("Content-Transfer-Encoding: binary");
								dataOutputStream.writeBytes(LINE_FEED);
								dataOutputStream.writeBytes(LINE_FEED);

								try {
									File tempFile = new File(tmpFile);
									FileInputStream fileInputStream = new FileInputStream(tempFile);
									byte[] bytesRead = new byte[(int) tempFile.length()];
									fileInputStream.read(bytesRead);
									fileInputStream.close();

									int bufferLength = 1024;
									for (int i = 0; i < bytesRead.length; i += bufferLength) {
										if (bytesRead.length - i >= bufferLength) {
											dataOutputStream.write(bytesRead, i, bufferLength);
										} else {
											dataOutputStream.write(bytesRead, i, bytesRead.length - i);
										}
									}
									dataOutputStream.writeBytes(LINE_FEED);

								} catch (IOException ioEx) {
									HashMap<String, Object> exceptionObj = new HashMap<String, Object>();
					  			HashMap<String, Object> errorObj = new HashMap<String, Object>();
					  			errorObj.put("message", "Error while reading the file: " + ioEx.getMessage());
					  			errorObj.put("type", "IOException");
					  
					  			exceptionObj.put("workbooks_api", this);
					  			exceptionObj.put("error", errorObj);
					  			WorkbooksApiException e = new WorkbooksApiException(exceptionObj);
					  			throw e;
								} finally {
									try {
										if (reader != null)
											reader.close();
									} catch (IOException ioEx) {
										HashMap<String, Object> exceptionObj = new HashMap<String, Object>();
						  			HashMap<String, Object> errorObj = new HashMap<String, Object>();
						  			errorObj.put("message", "Error while closing the Reader: " + ioEx.getMessage());
						  			errorObj.put("type", "IOException");
						  
						  			exceptionObj.put("workbooks_api", this);
						  			exceptionObj.put("error", errorObj);
						  			WorkbooksApiException e = new WorkbooksApiException(exceptionObj);
						  			throw e;
									}
								}
							}
						} else {
							dataOutputStream.writeBytes("--" + boundary);
							dataOutputStream.writeBytes(LINE_FEED);
							dataOutputStream.writeBytes("Content-Disposition: form-data; name=\"" + fieldKey + "\"");
							dataOutputStream.writeBytes(LINE_FEED);
							dataOutputStream.writeBytes(LINE_FEED);
							dataOutputStream.writeBytes(fieldValue.toString());
							dataOutputStream.writeBytes(LINE_FEED);
							dataOutputStream.flush();
						}
					}
				} // end of for
				dataOutputStream.writeBytes("--" + boundary + "--");
				dataOutputStream.writeBytes(LINE_FEED);
				dataOutputStream.close();

			} catch (IOException ioEx) {
				HashMap<String, Object> exceptionObj = new HashMap<String, Object>();
  			HashMap<String, Object> errorObj = new HashMap<String, Object>();
  			errorObj.put("message", "Error while writing to the output stream of connection: " + ioEx.getMessage());
  			errorObj.put("type", "IOException");
  
  			exceptionObj.put("workbooks_api", this);
  			exceptionObj.put("error", errorObj);
  			WorkbooksApiException e = new WorkbooksApiException(exceptionObj);
  			throw e;
			}
		} // ************** END of content type is multipart/form-data ********************

		try {

			responseHeader = connection.getHeaderFields();
			log("Response Headers are: ", new Object[] {responseHeader});

			if (connection instanceof HttpsURLConnection) {
				status = ((HttpsURLConnection)connection).getResponseCode();
			} else {
				status = ((HttpURLConnection)connection).getResponseCode();
			}
			// Get the response back
			if (status != HttpURLConnection.HTTP_OK) {
				HashMap<String, Object> exceptionObj = new HashMap<String, Object>();
				HashMap<String, Object> errorObj = new HashMap<String, Object>();
				errorObj.put("message", "HTTP status not found: bad request?");
				errorObj.put("type", "ConnectionException");

				exceptionObj.put("workbooks_api", this);
				exceptionObj.put("error_code", status);
				exceptionObj.put("error", errorObj);

				WorkbooksApiException e = new WorkbooksApiException(exceptionObj);
				if (connection instanceof HttpsURLConnection) {
					((HttpsURLConnection)connection).disconnect();
				} else {
					((HttpURLConnection)connection).disconnect();
				}				
				throw e;
			} else { // Read the input from the Response
				BufferedReader in = new BufferedReader(new InputStreamReader(connection.getInputStream()));
				while ((decodedString = in.readLine()) != null) {
					responseStr.append(decodedString);
				}
				if (in != null) {
					in.close();
				}
			}

		} catch (IOException ioe) {
  			this.log("Exception in makeRequest while making request to connection: ");
  			HashMap<String, Object> exceptionObj = new HashMap<String, Object>();
  			HashMap<String, Object> errorObj = new HashMap<String, Object>();
  			errorObj.put("message", "Error while getting input stream from connection: " + ioe.getMessage());
  			errorObj.put("type", "IOException");
  
  			exceptionObj.put("workbooks_api", this);
  			exceptionObj.put("error", errorObj);
  			WorkbooksApiException e = new WorkbooksApiException(exceptionObj);
  			throw e;
		} finally {
			if (connection != null) {
				if (connection instanceof HttpsURLConnection) {
					((HttpsURLConnection)connection).disconnect();
				} else {
					((HttpURLConnection)connection).disconnect();
				}				
			}
		}
		decodedString = responseStr.toString();

		String body = decodedString;
//		log("Body:", new Object[] {body},"debug", 1000000);

		if (status == 0) {
			HashMap<String, Object> exceptionObj = new HashMap<String, Object>();
			HashMap<String, Object> errorObj = new HashMap<String, Object>();
			errorObj.put("message", "HTTP status not found: bad request?");
			errorObj.put("type", "BadRequest");
			errorObj.put("response", responseHeader);

			exceptionObj.put("workbooks_api", this);
			exceptionObj.put("error_code", 0);
			exceptionObj.put("error", errorObj);

			WorkbooksApiException e = new WorkbooksApiException(exceptionObj);
			throw e;
		}
		if (responseHeader != null && responseHeader.get("Set-Cookie") != null) {
			String cookieFromHeader = responseHeader.get("Set-Cookie").toString();
			this.setSession_id(cookieFromHeader);
		}
		long endtime = System.currentTimeMillis();
		this.setLast_request_duration(endtime - start_time);
		log("Time taken for request: ", new Object[] {this.getLast_request_duration()});

		HashMap<String, Object> retval = new HashMap<String, Object>();
		retval.put("http_status", status);
		retval.put("http_body", body);

		return retval;
	}

	
	/** Creates a HttpsURLConnection using the url and other parameters passed
	 * @param url - the url to connect
	 * @param method - GET/POST
	 * @param post_fields - fields to be sent to connection
	 * @param content_type - content-type 
	 * @return - HttpsURLConnection - the connection to the url provided with the request paramters set
	 * @throws IOException
	 */
	private URLConnection createHttpConnectionObject(String url, String method, String post_fields, String content_type) throws IOException {

		String cookie = this.getSessionCookie();
	//	HttpURLConnection connection = null;
		URLConnection connection = null;
		URL urlRequest = null;

		log("Url to connect: ", new Object[] {url});
		urlRequest = new URL(url);
		//connection = (HttpsURLConnection) urlRequest.openConnection();
		connection = urlRequest.openConnection();
		
		if (connection instanceof javax.net.ssl.HttpsURLConnection) {
			SSLSocketFactory sslsocketfactory = (SSLSocketFactory) SSLSocketFactory.getDefault();
			((HttpsURLConnection)connection).setSSLSocketFactory(sslsocketfactory);
  		if (!isVerify_peer()) {
  			((HttpsURLConnection)connection).setHostnameVerifier(new HostnameVerifier() {
  				public boolean verify(String string, SSLSession ssls) {
  					return true;
  				}
  			});
  		}
  		((HttpsURLConnection)connection).setInstanceFollowRedirects(false);
  		((HttpsURLConnection)connection).setRequestMethod(method.toUpperCase());
		} else {
			((HttpURLConnection)connection).setInstanceFollowRedirects(false);
			((HttpURLConnection)connection).setRequestMethod(method.toUpperCase());			
		}
		connection.setDoOutput(true);
		connection.setDoInput(true);
		connection.setRequestProperty("User-Agent", this.getUser_agent());
		connection.setRequestProperty("Content-Type", content_type);
		connection.setRequestProperty("Charset", CHARSET);
		connection.setRequestProperty("Content-Length", "" + Integer.toString(post_fields.getBytes().length));
		connection.setRequestProperty("Expect", "");
		if (cookie != null) {
			connection.setRequestProperty("Cookie", cookie);
		}
		connection.setUseCaches(false);

		return connection;
	}

	/**
	 * Make a call to an endpoint on the service, reconnecting to the session first if necessary if running beneath the Process Engine.
	 * 
	 * @param String
	 *          endpoint selects the portion of the API to use, e.g. 'crm/organisations'.
	 * @param String
	 *          method the restful method - one of 'GET', 'PUT', 'POST', 'DELETE'.
	 * @param HashMap
	 *          post_params A hash of uniquely-named parameters to add to the POST body.
	 * @param HashMap
	 *          ordered_post_params A simple array of additional parameters, to use for the POST body (may have duplicate keys e.g. 'id[]')
	 * @param HashMap
	 *          options Optional options to pass through to makeRequest(). For backwards-compatability, setting this instead to 'true' or 'false' toggles the
	 *          decoding of JSON
	 * @return WorkbooksApiResponse - the decoded json response if decode_json is true (default), or the raw response if not.
	 * @throws WorkbooksApiException
	 * 
	 * As usual, check the API documentation for further information.
	 */
	public WorkbooksApiResponse apiCall(String endpoint, String method, HashMap<String, Object> post_params, ArrayList<Object> ordered_post_params,
			HashMap<String, Object> options) throws WorkbooksApiException {

		//this.log("apiCall() called with params", new Object[] {endpoint, method, post_params, ordered_post_params, options});
		HashMap<String, Object> response = null;
		if (post_params == null) {
			post_params = new HashMap<String, Object>();
		}
		// NOTE: Client needs to pass decode_json=false if parsing as json is not required
		if (options == null) {
			options = new HashMap<String, Object>();
			options.put("decode_json", true);
		} else if (!options.containsKey("decode_json")) {
			options.put("decode_json", true);
		}

		// Clients using API Keys normally pass those on each request; otherwise
		// establish a session to span multiple requests.
		if (this.getApi_key() != null) {
			post_params.put("api_key", this.getApi_key());
			post_params.put("_api_version", String.valueOf(this.getApi_version()));
		} else {
			this.ensureLogin();
		}

		// API calls are always to a ".api" endpoint; the caller does not have to include this.
		// Including ANY extension will prevent ".api" from being appended.
		if (!endpoint.matches(".*\\.\\w{3,4}")) {
			endpoint += ".api";
		}
		HashMap<String, Object> serviceResponse = this.makeRequest(endpoint, method, post_params, ordered_post_params, options);

		int http_status = 0;
		Object http_body = null;
		if (serviceResponse != null) {
			http_status = (Integer) serviceResponse.get("http_status");
			http_body = serviceResponse.get("http_body");
		}

		if (http_status != WorkbooksApi.HTTP_STATUS_OK) {

			HashMap<String, Object> exceptionObj = new HashMap<String, Object>();
			HashMap<String, Object> errorObj = new HashMap<String, Object>();
			errorObj.put("message", "Non-OK response (" + http_status + ")");
			errorObj.put("type", "WorkbooksServiceException");
			errorObj.put("response", http_body);

			exceptionObj.put("workbooks_api", this);
			exceptionObj.put("error_code", http_status);
			exceptionObj.put("error", errorObj);

			WorkbooksApiException e = new WorkbooksApiException(exceptionObj);
			throw e;
		}
		response = new HashMap<String, Object>();
		boolean doDecodeJson = Boolean.parseBoolean(options.get("decode_json").toString());

		if (options.containsKey("decode_json") && doDecodeJson) {
			JsonObject responseObject = decodeJson((String) http_body);
			response.put("response", responseObject);
		} else {
			response.put("response", http_body);
		}

		//this.log("apiCall() returns", new Object[]{response}, "info", DEFAULT_LOG_LIMIT);

		WorkbooksApiResponse wbResponse = new WorkbooksApiResponse(response);
		return wbResponse;

	}

  /**
   * Make a request to an endpoint on the service to read or list objects. You must have logged in first
   * @param String endpoint selects the portion of the API to use, e.g. 'crm/organisations'
   * @param HashMap params the parameters to the API call - filter, limit, column selection as an array of hashes;
   *   each hash element can have a simple value or be an array of values e.g. for column selection.
   * @param HashMap options Optional options to pass through to makeRequest() potentially including 'content_type'. 
   *   For backwards-compatability, setting this instead to 'true' or 'false' toggles the decoding of JSON.
   * @return WorkbooksApiResponse the decoded json response if decode_json is true (default), or the raw response if not
   * @throws WorkbooksApiException
   *
   * As usual, check the API documentation for further information.
 **/
	@SuppressWarnings("unchecked")
	public WorkbooksApiResponse get(String endpoint, HashMap<String, Object> params, HashMap<String, Object> options) throws WorkbooksApiException {
		boolean url_encode = false;
		if (options == null) {
			options = new HashMap<String, Object>();
			options.put("decode_json", true);
		}
		if (options != null && options.containsKey("content_type")) {
			options.put("content_type", WorkbooksApi.FORM_URL_ENCODED);
		} else {
			url_encode = true;
		}

		ArrayList<Object> array_params = new ArrayList<Object>();
		int i = 0;
		if (params != null) {
			for (Iterator iterator = params.entrySet().iterator(); iterator.hasNext();) {
				Map.Entry<String, Object> entry = (Map.Entry) iterator.next();
				String key = entry.getKey();

				if (entry.getValue().getClass().isArray()) {
					if (key.equals("_filters[]")) {
						try {
							String[] fil = (String[]) entry.getValue();
							String[][] make_2d_filter = new String[1][fil.length];
							make_2d_filter[0] = Arrays.copyOf(fil, fil.length);
							entry.setValue(make_2d_filter);
						} catch (ClassCastException ce) {
							String[][] fil = (String[][]) entry.getValue();
						}
						String[][] filter_params = (String[][]) entry.getValue();
						for (String[] filter : filter_params) {
							array_params.add("_ff[]=" + filter[0]);
							array_params.add("_ft[]=" + filter[1]);
							array_params.add("_fc[]=" + filter[2]);
						}
					} else {
						String[] paramValues = (String[]) entry.getValue();
						for (String string : paramValues) {
							array_params.add(key + "=" + string);
						}
					}
					// Remove the key from the map
					iterator.remove();
				}
			}
		}
		return this.apiCall(endpoint, "GET", params, array_params, options);
	}

	/**
	 * Interface as per get() but if the response is not 'ok' it also logs an error and raises an exception.
	 */
	public WorkbooksApiResponse assertGet(String endpoint, HashMap<String, Object> params, HashMap<String, Object> options) throws Exception {
		if (options == null) {
			options = new HashMap<String, Object>();
			options.put("decode_json", true);
		}
		WorkbooksApiResponse response = this.get(endpoint, params, options);
		response.assertResponse();
		return response;
	}

	/**
	 * Make a request to an endpoint on the service to operate on multiple objects. You must have logged in first. You can request a combination of CREATE, UPDATE
	 * and DELETE operations, to be batched together. This is the core method upon which other methods are implemented which perform a subset of these operations.
	 * 
	 * @param String
	 *          endpoint selects the portion of the API to use, e.g. 'crm/organisations'.
	 * @param HashMap
	 *          objs an array of objects to create, update or delete.
	 * @param HashMap
	 *          a set of additional parameters to send along with the data, for example array('_per_object_transactions' => true) to change the commit behaviour.
	 * @param method
	 *          String The method (CREATE/UPDATE/DELETE) which is to be used if not specified for an object.
	 * @param HashMap
	 *          options Optional options to pass through to makeRequest() potentially including 'content_type'.
	 * @return WorkbooksApiResponse -  the decoded response.
	 * @throws WorkbooksApiException
	 * 
	 * As usual, check the API documentation for further information.
	 */
	public WorkbooksApiResponse batch(String endpoint, ArrayList<HashMap<String, Object>> objs, HashMap<String, Object> params, String method, HashMap<String, Object> options)
			throws WorkbooksApiException {
		// this->log('batch() called with params', array(endpoint, objs));

		ArrayList<Object> filter_params = this.populateFilters(objs, method);

		objs = this.encodeMethodParams(objs, method);

		boolean url_encode = true;
		if (options != null && options.containsKey("content_type")) {
			url_encode = !(options.get("content_type").toString().equals(WorkbooksApi.FORM_DATA));
		}

		ArrayList<Object> ordered_post_params = this.fullSquare(objs, url_encode);

		filter_params.addAll(ordered_post_params);

		WorkbooksApiResponse response = this.apiCall(endpoint, "PUT", params, filter_params, options);

//		this.log("batch returns", new Object[] {response}, "info", DEFAULT_LOG_LIMIT);
		return response;
	}

	/**
	 * Interface as per batch() but if the response is not 'ok' it also logs an error and raises an exception.
	 */
	public WorkbooksApiResponse assertBatch(String endpoint, ArrayList<HashMap<String, Object>> objs, HashMap<String, Object> params, String method, HashMap<String, Object> options)
			throws Exception {
		WorkbooksApiResponse response = this.batch(endpoint, objs, params, method, options);
		response.assertResponse();
		return response;
	}

	/**
	 * Make a request to an endpoint on the service to create objects. You must have logged in first.
	 * 
	 * @param String
	 *          endpoint selects the portion of the API to use, e.g. 'crm/organisations'.
	 * @param HashMap
	 *          objs an array of objects to create
	 * @param HashMap
	 *          a set of additional parameters to send along with the data, for example array('_per_object_transactions' => true) to change the commit behaviour.
	 * @param HashMap
	 *          options Optional options to pass through to makeRequest()
	 * @return WorkbooksApiResponse the decoded response.
	 * @throws WorkbooksApiException
	 * 
	 * As usual, check the API documentation for further information.
	 */
	public WorkbooksApiResponse create(String endpoint, ArrayList<HashMap<String, Object>> objs, HashMap<String, Object> params, HashMap<String, Object> options)
			throws WorkbooksApiException {
		return this.batch(endpoint, objs, params, "CREATE", options);
	}

	/**
	 * Interface as per create() but if the response is not 'ok' it also logs an error and raises an exception.
	 */
	public WorkbooksApiResponse assertCreate(String endpoint, ArrayList<HashMap<String, Object>> objs, HashMap<String, Object> params, HashMap<String, Object> options) throws Exception {
		WorkbooksApiResponse response = this.create(endpoint, objs, params, options);
		response.assertResponse();
		return response;
	}

	/**
	 * Make a request to an endpoint on the service to update objects. You must have logged in first.
	 * 
	 * @param String
	 *          endpoint selects the portion of the API to use, e.g. 'crm/organisations'.
	 * @param HashMap
	 *          objs an array of objects to update, specifying the id and lock_version of each together with the values to set.
	 * @param HashMap
	 *          a set of additional parameters to send along with the data, for example array('_per_object_transactions' => true) to change the commit behaviour.
	 * @param HashMap
	 *          options Optional options to pass through to makeRequest()
	 * @return WorkbooksApiResponse the decoded response.
	 * @throws WorkbooksApiException
	 * 
	 * As usual, check the API documentation for further information.
	 */
	public WorkbooksApiResponse update(String endpoint, ArrayList<HashMap<String, Object>> objs, HashMap<String, Object> params, HashMap<String, Object> options)
			throws WorkbooksApiException {
		return this.batch(endpoint, objs, params, "UPDATE", options);
	}

	/**
	 * Interface as per update() but if the response is not 'ok' it also logs an error and raises an exception.
	 */
	public WorkbooksApiResponse assertUpdate(String endpoint, ArrayList<HashMap<String, Object>> objs, HashMap<String, Object> params, HashMap<String, Object> options) throws Exception {
		WorkbooksApiResponse response = this.update(endpoint, objs, params, options);
		response.assertResponse();
		return response;
	}

	/**
	 * Make a request to an endpoint on the service to delete objects. You must have logged in first.
	 * 
	 * @param String
	 *          endpoint selects the portion of the API to use, e.g. 'crm/organisations'.
	 * @param HashMap
	 *          objs an array of objects to delete, specifying the id and lock_version of each.
	 * @param HashMap
	 *          a set of additional parameters to send along with the data, for example array('_per_object_transactions' => true) to change the commit behaviour.
	 * @param HashMap
	 *          options Optional options to pass through to makeRequest()
	 * @return WorkbooksApiResponse the decoded response.
	 * @throws WorkbooksApiException
	 * 
	 * As usual, check the API documentation for further information.
	 */
	public WorkbooksApiResponse delete(String endpoint, ArrayList<HashMap<String, Object>> objs, HashMap<String, Object> params, HashMap<String, Object> options)
			throws WorkbooksApiException {
		return this.batch(endpoint, objs, params, "DELETE", options);
	}

	/**
	 * Interface as per delete() but if the response is not 'ok' it also logs an error and raises an exception.
	 */
	public WorkbooksApiResponse assertDelete(String endpoint, ArrayList<HashMap<String, Object>> objs, HashMap<String, Object> params, HashMap<String, Object> options) throws Exception {
		WorkbooksApiResponse response = this.delete(endpoint, objs, params, options);
		response.assertResponse();
		return response;
	}

	/**
	 * Depending on the method (Create/Update/Delete) the objects passed to Workbooks have certain minimum requirements. Callers may specify a method for each
	 * object or assume the same operation for all objects.
	 * 
	 * @param obj_array Array Objects to be encoded, *modified in place* 
	 * @param method String The method (CREATE/UPDATE/DELETE) which is to be used if not specified for an object. 
	 * @return Array a set of parameters representing the filter which is required to define the working set of objects.
	 */
	protected ArrayList<Object> populateFilters(ArrayList<HashMap<String, Object>> obj_array, String method) throws WorkbooksApiException {

		//this.log("populateFilters called with params ", new Object[] {obj_array});

		ArrayList<Object> filter_ids = new ArrayList<Object>();
		String method_key = "__method";
		String obj_method = method;

		for (HashMap<String, Object> obj : obj_array) {
			if (obj.containsKey("method")) {
				method_key = "method";
			}
			if (obj.get(method_key) != null) {
				obj_method = (String) obj.get(method_key);
			}

			if (obj_method.toUpperCase().equals("CREATE")) {
				filter_ids.add("0");
			} else if (obj_method.toUpperCase().equals("UPDATE")) {
				filter_ids.add(obj.get("id"));
			} else if (obj_method.toUpperCase().equals("DELETE")) {
				filter_ids.add(obj.get("id"));
			} else {
				// throw exception
				HashMap<String, Object> exceptionObj = new HashMap<String, Object>();
				HashMap<String, Object> errorObj = new HashMap<String, Object>();
				errorObj.put("message", "Unexpected method: " + method);
				errorObj.put("type", "WorkbooksApiException");
				errorObj.put("object", obj);

				exceptionObj.put("workbooks_api", this);
				exceptionObj.put("error", errorObj);

				WorkbooksApiException e = new WorkbooksApiException(exceptionObj);
				throw e;
			}
		} // end of for

		ArrayList<Object> filter = new ArrayList<Object>();
		// Must include a filter to 'select' the set of objects being operated
		// upon
		if (filter_ids.size() > 0) {
			filter.add("_fm=or");

			for (int i = 0; i < filter_ids.size(); i++) {
				filter.add("_ff[]=id");
				filter.add("_ft[]=eq");
				filter.add("_fc[]=" + filter_ids.get(i));
			}
		}

//		this.log("populateFilters results ", new Object[] {filter});
		return filter;
	}

	/**
	 * Depending on the method (Create/Update/Delete) the objects passed to Workbooks have certain minimum requirements. Callers may specify a method for each
	 * object or assume the same operation for all objects.
	 * 
	 * @param obj_array Array Objects to be encoded, *modified in place* 
	 * @param method String The method (CREATE/UPDATE/DELETE) which is to be used if not specified for an object. 
	 * @return Array of the list which has the correct methodname and the lock_version, Id if required
	 */
	protected ArrayList<HashMap<String, Object>> encodeMethodParams(ArrayList<HashMap<String, Object>> obj_array, String method) throws WorkbooksApiException {

//		this.log("encodeMethodParams called with params ", new Object[] {obj_array});

		String method_key = "__method";
		String obj_method = method;

		for (HashMap<String, Object> obj : obj_array) {
			if (obj.containsKey("method")) {
				method_key = "method";
			}
			if (obj.get(method_key) != null) {
				obj_method = (String) obj.get(method_key);
				obj.remove(method_key);
			}

			if (obj_method.toUpperCase().equals("CREATE")) {
				if (obj.containsKey("id") && !(obj.get("id").toString().equals("0")) || obj.containsKey("lock_version")
						&& !(obj.get("lock_version").toString().equals("0"))) {
					// throw exception
					HashMap<String, Object> exceptionObj = new HashMap<String, Object>();
					HashMap<String, Object> errorObj = new HashMap<String, Object>();
					errorObj.put("message", "Neither \"id\" nor \"lock_version\" can be set to create an object");
					errorObj.put("type", "WorkbooksApiException");
					errorObj.put("object", obj);

					exceptionObj.put("workbooks_api", this);
					exceptionObj.put("error", errorObj);

					WorkbooksApiException e = new WorkbooksApiException(exceptionObj);
					throw e;
				}

				obj.put("__method", "POST");
				obj.put("id", "0");
				obj.put("lock_version", "0");
			} else if (obj_method.toUpperCase().equals("UPDATE")) {
				obj.put("__method", "PUT");
				if (!obj.containsKey("id") || !obj.containsKey("lock_version")) {
					// throw exception
					HashMap<String, Object> exceptionObj = new HashMap<String, Object>();
					HashMap<String, Object> errorObj = new HashMap<String, Object>();
					errorObj.put("message", "Both \'id\' and \'lock_version\' must be set to update an object");
					errorObj.put("type", "WorkbooksApiException");
					errorObj.put("object", obj);

					exceptionObj.put("workbooks_api", this);
					exceptionObj.put("error", errorObj);

					WorkbooksApiException e = new WorkbooksApiException(exceptionObj);
					throw e;
				}

			} else if (obj_method.toUpperCase().equals("DELETE")) {
				obj.put("__method", "DELETE");
				if (!obj.containsKey("id") || !obj.containsKey("lock_version")) {
					// throw exception
					HashMap<String, Object> exceptionObj = new HashMap<String, Object>();
					HashMap<String, Object> errorObj = new HashMap<String, Object>();
					errorObj.put("message", "Both \'id\' and \'lock_version\' must be set to delete an object");
					errorObj.put("type", "WorkbooksApiException");
					errorObj.put("object", obj);

					exceptionObj.put("workbooks_api", this);
					exceptionObj.put("error", errorObj);

					WorkbooksApiException e = new WorkbooksApiException(exceptionObj);
					throw e;
				}

			} else {
				// throw exception
				HashMap<String, Object> exceptionObj = new HashMap<String, Object>();
				HashMap<String, Object> errorObj = new HashMap<String, Object>();
				errorObj.put("message", "Unexpected method: " + method);
				errorObj.put("type", "WorkbooksApiException");
				errorObj.put("object", obj);

				exceptionObj.put("workbooks_api", this);
				exceptionObj.put("error", errorObj);

				WorkbooksApiException e = new WorkbooksApiException(exceptionObj);
				throw e;
			}
		} // end of for

//		this.log("encodeMethodParams updated Objects: ", new Object[] {obj_array});
		return obj_array;
	}

	/**
	 * The Workbooks wire protocol requires that each key which is used in any object be present in all objects, and delivered in the right order. Callers of this
	 * binding library will omit keys from some objects and not from others. Some special values are used in this encoding - :null_value: and :no_value:.
	 * 
	 * @param obj_array Array Objects to be encoded 
	 * @param url_encode Boolean Whether to URL encode them, defaults to true 
	 * @return Array the (encoded) set of objects suitable for passing to Workbooks
	 */
	@SuppressWarnings("unchecked")
	protected ArrayList<Object> fullSquare(ArrayList<HashMap<String, Object>> obj_array, boolean url_encode) throws WorkbooksApiException{
//		 this.log("fullSquare() called with params", new Object[] {obj_array});

		// Use TreeSet so that the keys are unique and sorted
		TreeSet<String> allKeys = new TreeSet<String>();

		for (HashMap<String, Object> obj : obj_array) {
			allKeys.addAll(obj.keySet());
		}

		ArrayList<Object> retval = new ArrayList<Object>();
		Object value = new Object();

		for (HashMap<String, Object> obj : obj_array) {
			for (String key : allKeys) {
				if (obj.containsKey(key) && obj.get(key) == null) {
					value = ":null_value:";
				} else if (!obj.containsKey(key)) {
					value = ":no_value:";
				} else {
					value = obj.get(key);
				}

				String unnested_key = this.unnestKey(key);

				if (value.getClass().getName().equals("java.util.HashMap")) {
					HashMap<String, Object> uploadFileDetails = (HashMap<String, Object>) value;
					if (uploadFileDetails.containsKey("tmp_name")) {
						Object tmpFile = uploadFileDetails.get("tmp_name");
						if (tmpFile.getClass().getName().equals("java.io.File")) {
							HashMap<String, Object> fileHash = new HashMap<String, Object>();
							fileHash.put(key + "[]", value);
							retval.add(fileHash);
						} else {
							String newValue = "[";
							for (String object : uploadFileDetails.keySet()) {
								if (newValue != "[") {
									newValue += ",";
								}
								newValue += uploadFileDetails.get(object);
							}
							newValue += "]";
							retval.add(key + "[]=" + newValue);
						}
					}

				} else {
					if (url_encode) {
						try {
							retval.add(URLEncoder.encode(unnested_key, CHARSET) + "[]=" + URLEncoder.encode(value.toString(), CHARSET));
						} catch (UnsupportedEncodingException unSupportEncodeEx) {
							HashMap<String, Object> exceptionObj = new HashMap<String, Object>();
							HashMap<String, Object> errorObj = new HashMap<String, Object>();
							errorObj.put("message", "Error while encoding String: " + unSupportEncodeEx.getMessage());
							errorObj.put("type", "UnsupportedEncodingException");

							exceptionObj.put("workbooks_api", this);
							exceptionObj.put("error", errorObj);
							WorkbooksApiException e = new WorkbooksApiException(exceptionObj);
							throw e;
						}
					} else {
						retval.add(unnested_key + "[]=" + value.toString());
					}
				}
			}
		}
//		 this.log("fullSquare return value", new Object[] {retval});

		return retval;
	}

	/**
	 * Normalise any nested keys so they have the expected format for the wire, i.e. convert things like this: org_lead_party[main_location[email]] into this:
	 * org_lead_party[main_location][email]
	 * 
	 * @param attribute_name
	 *          String the attribute name with potentially nested square brackets
	 * @return String the unnested attribute name
	 */
	protected String unnestKey(String attribute_name) {
		// this->log('unnestKey() called with param', attribute_name);

		// If it does not end in ']]' then it is not a nested key.
		if (!attribute_name.matches("\\]\\]$")) {
			return attribute_name;
		}
		// Otherwise it is nested: split and re-join
		String joinParts = null;
		String[] parts = attribute_name.split("[\\[\\]]+", 0);

		// join the parts from the 2nd part, and so i=1
		for (int i = 1; i < parts.length; i++) {
			joinParts = "][" + parts[i];
		}

		String retval = parts[0] + "[";
		if (joinParts != null) {
			retval += joinParts + "]";
		}
//		this.log("unnestKey", new Object[] {retval});
		return retval;
	}

	/**
	 * Ensure we are logged in; if not then reconnect to the service if possible.
	 */
	protected void ensureLogin() throws WorkbooksApiException {

		if (!this.isLogin_state() && this.getUsername() != null && this.getSession_id() != null && this.getLogical_database_id() != null) {

			/*
			 * A login failure results in it being logged in the Process Log and if the process is scheduled then it is disabled and a notification raised. Timeouts
			 * result in a retry return code.
			 */


			try {
				HashMap<String, Object> login_response = this.login(new HashMap<String, Object>());
				int http_status = (Integer) login_response.get("http_status");
				if (http_status != WorkbooksApi.HTTP_STATUS_OK) {
					this.log("Workbooks connection unsuccessful", new Object[] {login_response.get("failure_message")}, "error", DEFAULT_LOG_LIMIT);
					System.exit(EXIT_DISABLE); // disable the script and issue notification if the Action is scheduled
				}
			} catch (Exception e) {
				// Handle timeouts differently with a retry.

				if (e.getMessage().matches("operation timed out")) {
					this.log("Workbooks connection timed out will re-try later", new Object[] {e.getMessage()}, "error", DEFAULT_LOG_LIMIT);
					System.exit(EXIT_RETRY); // retry later if the Action is scheduled
				}
				this.log("Workbooks connection unsuccessful", new Object[] {e.getMessage()}, "error", DEFAULT_LOG_LIMIT);
				System.exit(EXIT_DISABLE); // disable the script and issue notification if the Action is scheduled
			}
		}

		if (this.isLogin_state() == false) {

			HashMap<String, Object> exceptionObj = new HashMap<String, Object>();
			HashMap<String, Object> errorObj = new HashMap<String, Object>();
			errorObj.put("message", "Not logged in");
			errorObj.put("type", "WorkbooksLoginException");

			exceptionObj.put("workbooks_api", this);
			exceptionObj.put("error", errorObj);

			WorkbooksApiException e = new WorkbooksApiException(exceptionObj);
			throw e;
		}
	}

	// ************************************Get/Set methods
	public String getSession_id() {
		return session_id;
	}

	public void setSession_id(String session_id) {
		this.session_id = session_id;
	}

	public String getApi_key() {
		return api_key;
	}

	public void setApi_key(String api_key) {
		this.api_key = api_key;
	}

	public String getUsername() {
		return username;
	}

	public void setUsername(String username) {
		this.username = username;
	}

	public String getLogical_database_id() {
		return logical_database_id;
	}

	public void setLogical_database_id(String logical_database_id) {
		this.logical_database_id = logical_database_id;
	}

	public String getDatabase_instance_ref() {
		return DatatypeConverter.printBase64Binary((database_instance_id + "17").getBytes());
	}

	public void setDatabase_instance_id(String database_instance_id) {
		this.database_instance_id = database_instance_id;
	}

	public String getAuthenticity_token() {
		return authenticity_token;
	}

	public void setAuthenticity_token(String authenticity_token) {
		this.authenticity_token = authenticity_token;
	}

	public boolean isLogin_state() {
		return login_state;
	}

	public void setLogin_state(boolean login_state) {
		this.login_state = login_state;
	}

	public boolean isAuto_logout() {
		return auto_logout;
	}

	public void setAuto_logout(boolean auto_logout) {
		this.auto_logout = auto_logout;
	}

	public String getApplication_name() {
		return application_name;
	}

	public void setApplication_name(String application_name) {
		this.application_name = application_name;
	}

	public String getUser_agent() {
		return user_agent;
	}

	public void setUser_agent(String user_agent) {
		this.user_agent = user_agent;
	}

	public int getConnect_timeout() {
		return connect_timeout;
	}

	public void setConnect_timeout(int connect_timeout) {
		this.connect_timeout = connect_timeout;
	}

	public boolean isVerify_peer() {
		return verify_peer;
	}

	public void setVerify_peer(boolean verify_peer) {
		this.verify_peer = verify_peer;
	}

	public String getService() {
		return service;
	}

	public void setService(String service) {
		this.service = service;
	}

	public long getLast_request_duration() {
		return last_request_duration;
	}

	public void setLast_request_duration(long last_request_duration) {
		this.last_request_duration = last_request_duration;
	}

	public String getUser_queues() {
		return user_queues;
	}

	public void setUser_queues(String user_queues) {
		this.user_queues = user_queues;
	}

	public String getJsonPretty() {
		return jsonPretty;
	}

	public void setJsonPretty(String jsonPretty) {
		this.jsonPretty = jsonPretty;
	}
	public int getApi_version() {
		return api_version;
	}
	public void setApi_version(int api_version) {
		this.api_version = api_version;
	}
}
