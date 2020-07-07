# Introducing Workbooks

<a href="https://www.workbooks.com">Workbooks</a> is an integrated suite of CRM and Business applications designed explicitly to give small and mid-size organisations the tools to drive top line growth, increase productivity, reduce operating costs and improve the performance of their
business.

Accessed as an online service from anywhere with an internet connection, Workbooks brings together customer relationship management (CRM) and back-office accounting applications that up until now have been managed by disparate systems unable to
'talk' to each other.

Together, the Workbooks applications effortlessly drive best practice in sales and marketing, customer order management, customer service, invoicing, purchasing and company management.

# The Workbooks API

All external applications that integrate with the core Workbooks Service do so using the Workbooks API. The Workbooks user interface, Workbooks Desktop, is a single-page JavaScript UI that is built to a client/server model. The interactions between
the Workbooks Desktop and the core Workbooks Service are done using an extended version of the Workbooks API documented here. Using the Workbooks Desktop you can also learn about the relationships between the various Workbooks record types. Record types include CRM record types (such as People and Organisations, Activities and Cases), Transaction documents (such as Orders, Invoices and Credit Notes) with associated Line Items, configuration record types such as Picklists, Scripts and Processes, and custom record types.

To adapt Workbooks to the needs of your business you may not need to resort to the API; Workbooks can be extended through custom fields, form layouts, record templates and other techniques.

The Workbooks API allows developers to have access to information stored inside Workbooks using a simple, powerful and secure Web Service API. The API allows complete access to Workbooks record types, allowing you to read, create, update and delete records. Additionally it permits access to searching, metadata about the schema, and a variety of other facilities. 

The API is the "contract" between Workbooks and its client applications, as such we work to ensure that code written to the API in <a href="https://www.workbooks.com/resource-documents/workbooks-company-timeline/">2009</a> continues to work correctly with the evolved Workbooks service today.

We have published bindings for <a href="https://github.com/workbooks/client_lib/tree/master/php">PHP</a>, <a href="https://github.com/workbooks/client_lib/tree/master/ruby">Ruby</a>, <a href="https://github.com/workbooks/client_lib/tree/master/csharp">.NET (C#)</a> and <a href="https://github.com/workbooks/client_lib/tree/master/php">Java</a> on <a href="http://github.com/workbooks/client_lib/">github</a>. We recommend that you use these bindings rather than making calls directly using the raw HTTP and the underlying "wire protocol". Note that these bindings do not change often, because they do not need to.  They are "wrappers" for the Workbooks API which make it easier to work with than using HTTPS requests and responses directly.

We ensure backwards-compatability so that older versions of these bindings continue to work with the production Workbooks service. If you would like to request bindings for other languages, please <a href="mailto:support@workbooks.com">contact our support team</a>.

## Workbooks Process Engine
Using the Workbooks Process Engine you can host API scripts within Workbooks itself; Workbooks takes care of authentication and logging automatically for scripts that run under the Process Engine. The Script Library within Workbooks provides access to Scripts which you can take and add to your own Workbooks database which are run by the Process Engine. For the majority of users we recommend using PHP scripts within the Workbooks Process Engine. More information about using the Process Engine is in the documentation for the PHP binding.
# The Schema and its Metadata
Each Workbooks database can have custom record types in addition to standard record types, and each record type can have custom fields in addition to standard fields. The consequence of this is that an integration should use metadata to discover the configuration of the current database unless that integration depends solely on standard fields and record types. The available set of record types also depends on the licenced set of features. Over time the set of available record types and fields will change as the Workbooks service is enhanced (record types and fields are added) and customer Workbooks administrators configure custom record types and custom fields.

At any point the full API schema reference for a specific database is available from within the Workbooks Desktop. Navigate to _Configuration > Automation > API Reference_. This shows every record type available through the API plus their fields and associations to other record types. All of the information available visually here is also available via the metadata API.
# HTTP Methods, Certificates and URLs
The HTTP protocol defines a set of methods: GET for retrieving data, POST for submitting new data, PUT for modifying existing data, and DELETE for deleting data. There are other
methods, but for the purposes of a RESTful API these are sufficient. The Workbooks API is REST-like rather than RESTful: it offers two main methods: GET to retrieve a set of records,
and PUT to update a set of records. This batched interface enables more efficient clients than a truly RESTful API.
## Methods
Modern client libraries for HTTP fully support the various methods, but older libraries may not support PUT. To workaround this, you can send an \_method parameter in requests to the
Workbooks API to override the HTTP method. For example, if your client library does not support PUT, but can handle POST, then you can still use the Workbooks API to update data by
sending a POST request with _method=PUT. NOTE: the _method parameter should not be confused with the \_\_method[] parameter introduced in section Changing Objects: Methods, which
specifies how individual records in a batched PUT request are handled.
## Content-Type
Non-GET requests must specify a `Content-Type` header. This is normally `application/x-www-form-urlencoded` apart from file uploads when it should be `multipart/form-data`. Supply the header like this:

`Content-Type: application/x-www-form-urlencoded`

and encode the request correctly as expected for this Content-Type.

## Certificates
For security, communications with the Workbooks service are over HTTPS. The Workbooks service presents an SSL certificate to verify its identity, which clients should verify. Transfers
are compressed as indicated in the response header (normally gzip).

## Service URLs
The base Workbooks service URL for API requests is `https://secure.workbooks.com/` for the production Workbooks service. Sometimes, for example to access pre-release features, you may
be asked instead to connect to another base URL such as `https://alpha.workbooks.com/` or `https://beta.workbooks.com/`. All URLs used in the API are available beneath this point.

# Requests and Responses
## HTTP Response Codes, Errors and Failures
HTTP response codes are meaningful and attempt to summarise the overall result of a request. 

Values commonly seen from the Workbooks API are:

| Code | Explanation |
| ---: | ----------- |
| 200  | OK: the request was successfully processed and results have been returned (although those results may still indicate some failure). |
| 302  | Redirection - these may be received at any time and must be followed. |
| 401  | Authentication failure. |
| 403  | Database unavailable. |
| 404  | Not found: the resource could not be found (it did not exist, or access was denied). |
| 405  | The resource does not allow the http method that was used. |
| 406  | Unacceptable: the parameters passed are not consistent/suitable or the operation is not allowed. |
| 500  | An error has occurred. Typically Workbooks logged an exception. Further information may be available from the Workbooks Support team if the provided exception reference can be provided promptly. |
| 501  | The HTTP method is not implemented. |

## Failure Reporting
This is typically under the `failures` hash key in the response - for example if you pass in a bad lock_version to an update you will still get `HTTP/1.1 200 OK` in the HTTP headers
however the JSON will contain diagnostics in the `failures` element.
# Data Formats
## JSON
The API generates data in JSON; the data format used depends on the URL passed to the API by the client. Do NOT assume the order of entries within a JSON response except where it is
explicitly required (the order of records in a GET request conforms to the requested sort order, but the fields of each record can be rendered in any order).

Some users of the API, for example those using the API from JavaScript and similar languages directly, will find it useful to get JSON data back wrapped in a function call. This
technique is called JSONP. Workbooks supports this: if an additional parameter named either `callback` or `jsonp` is present it represents a javascript function and then the returned
JSON is wrapped in a call to the named function.
## Character Sets
All data is passed in the UTF-8 character set. UTF-8 is able to represent any character in the Unicode standard, yet is backwards compatible with ASCII. Parameter values should be
converted to UTF-8 and URL encoded. 
# Datatypes
* `array` Read and write. A string of the form "[value,value,value]". Spaces around the commas are optional and removed on the server. The
square brackets must be used. The values are all assumed to be strings on the wire, and converted as necessary by the server. Assume any
field called or ending in "id" to expect an array of integers. Current values cannot contain the comma separator value. Examples are [10,
20] and ['Partner', 'Competitor'].
* `binary` Read and write. A binary blob, sent as a string with HTTP encodings.
* `boolean` Read and write. JSON true or false; Accepted values: "true", "t", "on", "1", "yes" and "y" are all treated as true; everything
  is false.
* `breadcrumb` Read and write. A dotted string referencing an attribute.
* `currency` Read and write. A string of the form "value code flags". The value is a decimal value that can have up to 5 decimal places
  and up to 63 digits. The code is an ISO 4127 standard three letter currency code or `!!!`. A code set to `!!!` denotes a calculated
  value that has combined multiple currencies and therefore is not valid, for example £5000.00 + $1000.00. The flags control which parts
  of the currency value can be modified: 0 indicates that both the amount and the currency code of this currency value can be modified. 1
  indicates that the currency code of this currency value can not be modified. 2 indicates that the amount of this currency value can not
  be modified. 3 indicates that neither the currency code nor the amount of this currency value can be modified.
* `date` Read and write. Dates are represented as strings formatted using the 'C' locale as '%e %b %Y'. Depending on your language you may
  use a call such as `strptime()` to parse such strings into Dates and `strftime()` to do the reverse.
* `datetime` Read and write. Datetimes are represented as strings formatted using the 'C' locale as '%a %b %d %H:%M:%S %Z %Y', although
  the timezone is always UTC. Depending on your language you may use a call such as `strptime()` to parse such strings into DateTimes and
  `strftime()` to do the reverse. Datetime values in Workbooks are represented in the UTC timezone - you should convert to and from local
  time on the client.
* `decimal` Read and write. A decimal number represented as a decimal string, and stored with a fixed precision and scale providing
  accurate decimal mathematics. The precision is the number of significant digits, while the scale is the number of digits that can be
  stored following the decimal point. For example, the number 123.45 has a precision of 5 and a scale of 2. A decimal with a precision of
  5 and a scale of 2 can range from -999.99 to 999.99. Precision can be any value from 1 to 63 and scale can range from 0 to 30. The float
  data type can store numbers in a much greater range, but is not accurate.
* `file_upload` Write only. A binary file.
* `float` Read and write. Stored as either a float or a double in the database. A float allows the values -3.402823466E+38 to
  -1.175494351E-38, 0, and 1.175494351E-38 to 3.402823466E+38. A double allows the values -1.7976931348623157E+308 to -
  2.2250738585072014E-308, 0, and 2.2250738585072014E- 308 to 1.7976931348623157E+308. A float is represented as a decimal string, but be
  aware that it cannot be represented accurately for some values. See the decimal datatype for a way of storing accurate decimal values.
* `has_one` Read and write. A reference to another object. The id of the referenced object or JSON null if there is no referenced object.
* `icon_class` Read only. The name of an icon to be displayed.
* `integer` Read and write. An integer in the range -2147483648 to 2147483647.
* `picklist_data` Read and write. A picklist with a static list of selectable values. Sent as the value of the selected item.
* `picklist_url` Read and write. A picklist with a URL for querying potentially selectable values. Sent as the value of the selected item.
* `string` Read and write. A string up to 255 characters. All string data is transmitted in the UTF-8 character set.
* `tag_array` Read and write. A comma and space-separated string representing a list of tags. All string data is transmitted in the UTF-8
  character set.
* `text` Read and write. A string of characters up to 64KB, 16MB or 4GB depending on the attribute (many are 16MB). All string data is
  transmitted in the UTF-8 character set.
* `time_duration` Read only. A 32-bit integer treated as a time period in seconds. The integer is converted into a textual description in
  English, e.g. 300 => "15 minutes".
* `time_interval` Read only. A datetime value treated as a time period. The datetime is converted into a textual description in English,
  e.g. "0/00/0000 00:15:00" => "15 minutes".
* `time` Read and write. The time is represented in the C locale using the format '%H:%M:%S'.
* `time_relative` Read only. A datetime value rendered as a textual description in English of the difference between then and now, e.g.
  "15 minutes ago".
* `uri` Read and write. A UTF-8 string.

## Dates and Times
API responses typically contain several dates and times. By default they are formatted like this:

* `"due_date" : "22 May 2009",` (date)
* `"cf_meeting_time_field" : "21:30:00",` (time)
* `"updated_at" : "Fri May 15 14:36:54 UTC 2009",` (datetime)

The output format can be changed by passing optional parameters during login or on each request:

* `_json_output_date_format` Change the date output format from the default: `'%e %b %Y'`.
* `_json_output_time_format` Change the time output format from the default: `'%H:%M:%S'`.
* `_json_output_datetime_format` Change the date/time output format from the default: `'%a %b %d %H:%M:%S %Z %Y'`. Use the value `'%s'` to get a seconds-since-the-epoch timestamp.

Depending on your language you may use a call such as `strptime()` to parse such strings into Dates, DateTimes or Times and `strftime()` to do the reverse. Note that with the default
format the day of the month in a Date will contain a leading space when it is less than 10. For example the 2nd of May 2010 will be `" 2 May 2010"`. The API always uses the UTC
timezone for date-times, whereas dates and times, being less precise, do not have a timezone.

The API accepts date/times in the default formats and using epoch timestamps (integers).

# Designing for performance and scalability
Workbooks is a multi-user system, so the data in your database can be changing constantly as users and other API clients add, modify and delete records. Because Workbooks can store
very large numbers of records, it is not feasible to process all of the data at once. API clients must operate on chunks of the data, a page at a time. The consequence of paging is
that your client is only aware of a small subset of the data, which meanwhile is being modified and shifted around by other users and clients.

This has a number of impacts on the design of an API client: repeatedly paging through the dataset can miss records or return duplicate records, and modifying records can fail if
another user has modified or deleted it. An API client must be designed to consider these issues:

* Another user may modify a record that your API client also wants to modify. As a result, the `lock_version` will have been incremented and your client's change will be rejected with
  a failure message: "This record cannot be saved since it has already been updated elsewhere." To continue processing your client must reload the record to get the latest data and
  `lock_version` values before applying the change again.
*   Another user may delete a record. If your API client tries to modify the record it will fail because the record is no longer accessible.

* Another user may change the permissions on a record. Your API client will be unable to access the record, just as if it had been deleted.

* Another user may add a record. As a result, the next page of data that your API client selects may contain a duplicate caused by the dataset being offset. For example, if your client
  is paging through records sorted by creation time descending, the second page will now include a record from the first page, and so on.

* Another user may delete a record or change the permissions to make it inaccessible. As a result, the next page of data that your API client selects may skip a record caused by the
  dataset being offset. For example, if your client is paging through records 10 at a time and the other user deletes record 1, the second page will cover records 12-21, skipping
  record 11 because the first 10 records are now 2-11.

There are many other scenarios too. When designing an API client that will process large quantities of data it is very important to ensure that the data is retrieved correctly to avoid
processing the same records more than once, or to inadvertently skip records.
There are two main techniques that can be employed: queueing and deltas. Choosing filter criteria and the sort order are important to the success of either of the techniques.

## Queuing
An API client that processes "unprocessed" records to apply changes should repeatedly access the first page of data with an appropriate sort order and filters that exclude records that
have already been processed. This technique relies on having one or more fields that can be used to filter out records that need to be processed, and then marking those records once
they have been processed so they are not selected again.
For example, consider a client that sends a welcome email to new People. We would add a custom field to the Person record type called "Sent welcome email" of type checkbox with the
default value unticked. The client should select a page of People records ordered by id ascending, filtered by "Sent welcome email" is false, send the emails and then set the custom
field to true on all of those records. When selecting the first page of data again, the previous records will no longer match and so the effective next page of records will be
returned. The client should continue to repeatedly select the first page of records until there are no records left to process.

## Deltas
An API client that processes "recently changed" records should store the updated_at timestamp from the last record that was processed, and a list of record ids that were processed that
had the same `updated_at` timestamp. A page of data can selected sorted by `updated_at` ascending, filtered by `updated_at` greater than or equal to the last timestamp, and excluding
the records that have already been processed by id. Having processed the returned records, you can store the `updated_at` timestamp of the latest record, and the ids of all records
that were updated in the same second.

## Response sizes
The Workbooks API is designed so that clients can process large quantities of data in smaller chunks. For example, a common design for a client that synchronises Workbooks data with
another system is to repeatedly read and process the first page of data that matches a set of criteria. The data will often be fetched from a report defined in Workbooks so as to keep
complexity out of the program code. Each page of records is processed and modified so that they no longer match the criteria, e.g. setting a custom field to mark the record as
synchronised. As a result the next time around the loop, the first page now refers to a different subset of the overall data. This design also works well for scripts running in the
Workbooks Process Engine where they cannot continue running for an extended period, but can be scheduled to execute regularly.

The `_limit` parameter allows you to control how many rows of data are returned in a response from Workbooks. The default value is 100, although this can produce very large responses
for some record types where there can be a lot of data in each record; for example an Email record can contain a 16Mb (potentially larger) message, so requesting 100 Emails of this
size could theoretically produce a response of nearly 2Gb! Similarly, setting the `_limit` parameter to a very low value would mean that your client will need to perform many more
requests to complete the task, and so the network latency and bandwidth overheads become more relevant. Clearly the `_limit` parameter should be varied according to the type of data
being requested. Note that most performance benefits are achieved by increasing `_limit` from 1 to 10 or less; diminishing returns then kicks in for larger numbers.

To be able to set the `_limit` parameter to a high value you must design your client to accept the larger responses. For example, it would be wise to read the response in blocks from
the network and write it to a file before processing it. A complex client may choose to read the response into memory until it crosses some boundary and then send the rest to disk, so
that small responses don't require a file too.

Note that the Workbooks service will probably spend longer processing a request with a higher `_limit` value, although it will reduce the number of requests your client makes when
paging through a large dataset, and so will reduce the network latency cost and bandwidth overheads. This can be useful for a batch-style client, whereas an interactive client will
probably want to use a smaller `_limit` value to reduce the response time per request, to provide a responsive user interface.

## Counting rows
Although the quantity of data returned in a response is controlled by the `_start` and `_limit` parameters, by default Workbooks also returns a total value which is the number of
records that could match your criteria. This is very useful when paging through a large dataset because it tells you how much more data there is to access, but calculating the count
takes significant time. In many scenarios the total value is not needed for most requests. In fact, for a synchronisation client as described above there is never any need to know how
many records there are to process; the client just keeps requesting the first page until you get an empty response. To tell Workbooks not to count the rows for a request, set
`__skip_total_rows` to `true`; the total value returned will be the number of rows selected in the response rather than the full total.

## Visibility of Deleted Objects and Delta Synchronisation
By default, deleted records are not returned when records are retrieved via the API. In reality, most records are not deleted immediately within Workbooks, but flagged as deleted and
hidden from the user. Future service updates will allow users to manage these deleted records.
API clients can retrieve deleted records by adding the `is_deleted` attribute to the filter criteria, `"is_deleted = 1"`. Where this becomes very useful is to support 'delta'
synchronisation within clients that synchronise Workbooks records to third-party systems. An API request with a filter such as: `(is_deleted = 1 OR is_deleted = 0) AND updated_at >
[last synced time]` will return all records added, modified or deleted since the previous sync. This is much more efficient than retrieving all the records and then trying to determine
which have been deleted.

# Rate Limits
Rate limiting is imposed on API clients. There are limits of the size of result set which a client can request. Please ensure that your client is efficient and a "good citizen"; if
your client issues excessive API calls it may be delayed or redirected via a delaying URL. If a client receives a redirect response then it should be followed: this is part of the
normal operation of the API service.

# About the Examples and cURL
A command line is all you need to use the Workbooks API; if your system has cURL you have already got a great way to poke around the Workbooks API. cURL is a very versatile command line utility which is designed to script web page interactions; it is available for all widely-used operating systems.

The examples in this document are described using the `curl` program rather than any particular programming language, because it lends itself to being a simple way to explain the API's operation without lots of programming language distractions (see the documentation for each language-specific binding if you want to see that). It is unlikely
you will write your API client using cURL, but if you wanted to, you could! Some common cURL options used in the examples are below; see the cURL manual for the full set (run `curl --manual`):

* `--insecure` Allow cURL to accept insecure SSL connections - i.e. where the certificate cannot be verified. This is useful for connecting to test systems but is not necessary or recommended when connecting to the official Workbooks service at `https://secure.workbooks.com/`.
* `--compressed` Request a compressed response.
* `-i` Includes the HTTP header in the output, which allows you to see the HTTP response code amongst other things.
* `-g` Turn off "globbing" so that characters such as [] work as you would like.
* `-s` Turn off the progress bar which clutters things up with rate statistics.
* `-c cookiejar` Cookies received from the server are to be stored in the file cookiejar. Useful during login.
* `-v` Verbose - shows you the request headers as well as the response. Often you will use this instead of `-i`.
* `-b cookiejar` Take cookies from cookiejar and include them in the request to the server. Useful when you have an established session.
* `-d tag=value` Specifies a piece of data to be sent in a POST or PUT request without encoding - it is up to you to URL-encode the tag and or the value as necessary. You may use `-d` several times.
* `-X method` Cause a specific method to be passed to the server - typically method is DELETE, POST or PUT.
* `-A clientname` Specify a user-agent string to identify this client to the server.
* `-H 'Expect:'` Turn off the Expect header that cURL automatically adds.  This will stop the service occasionally sending a `100 Continue` response when you are uploading files.

Note that cURL automatically encodes parameters and adds the required `Content-Type:` header to POST requests for you: `Content-Type: application/x-www-form-urlencoded`.

Windows users: please be careful with quote marks. Avoid smart quotes and use "double quotation marks" instead.

# Authentication: Sessions, Login and Logout
If your client is hosted within the Workbooks Process Engine then sessions are automatically established for you by the PHP code in `workbooks_api.php` and there is no need to explicitly handle authentication. The Process Engine uses the APIs documented below to implement this but it is transparent to the user.

The Workbooks API is normally session-based. Most uses of the API require you to first establish a session using /login. Sessions timeout after
a period of inactivity, and are forcefully terminated by the service from time to time, for example when the service software is upgraded. A
well-behaved API client will close its sessions promptly using `/logout`. Session-IDs are hexadecimal strings passed in the `Workbooks-Session`
cookie.

The options available to authenticate are:

* Preferred: pass in an api_key to create a session using the login API.
* Use the login API using a username and password.
*   Pass in an api_key parameter without first establishing a session.  If sessions are not used then each API call incurs *significant* additional overhead in order to establish a context in which the request can be run. 

Users can create API Keys within the Workbooks Desktop; separate API Keys are normally created for each client a user wishes to access Workbooks on their behalf. API Keys grant access to a specific database.

## Login
It is good practice to clearly indicate the nature of your API client during login by sending a user- agent string (in cURL use the `-A` option). If you include the string `gzip` somewhere in the user-agent string then gzip compression will be enabled for the session (note that using cURL you will likely also need `--compressed`).

Using cURL to the login URL with an API Key:

<pre><code>
curl -i -g -s \
     -c '/tmp/cookiejar' \
     -A 'XYZ plugin/1.2.3 (gzip)' \
     --compressed \
     -X 'POST' \
     -d 'api_key=12345-12345-12345-12345-12345-12345-12345-12345' \
     -d 'client=api' \
     -d 'api_version=1' \
     -d 'json=pretty' \
     https://secure.workbooks.com/login.api

HTTP/1.1 200 OK
Date: Sat, 12 Dec 2009 07:08:19 GMT
Server: Apache
ETag: "57482a31ee12cdda121f3a8ab4837b3b" 
Cache-Control: private, max-age=0, must-revalidate
Set-Cookie: Workbooks-Session=159376d01524f2870e77ecc5f6c2d4c3; path=/
Status: 200 OK
Cache-Control: no-cache, no-store, must-revalidate
Expires: Fri, 30 Oct 1998 14:19:41 GMT
Vary: Accept-Encoding
Content-Encoding: gzip
Content-Length: 186
Content-Type: application/json; charset=utf-8

{"database_name": "System Test", "logical_database_id": 3, default_database_id: 3, "user_id": 2, "version": "Development (build 6009)",
"api_version": 1, "timezone": "London", "databases": [{"name": "System Test", "id": 3, "created_at": "Mon Jan 05 14:30:11 UTC 2009"}],
"authenticity_token": "dd2f9393d7739b0cb1f4be470abd08a2a7276473", "person_name": "System Test", "login_name": "system_test@workbooks.com",
"my_queues": {"Private::Crm::PersonQueue": 3, ...}, "database_instance_id": 123122, "session_id": "159376d01524f2870e77ecc5f6c2d4c3"}
</code></pre>
    
Note that in common with most of its other responses, Workbooks sends lots of headers instructing the client to not cache the response. The
cookie `Workbooks-Session` is passed back. It was a successful login since the response code is 200. Do not assume the order of the JSON elements; over time the Workbooks service introduces additional ones.

The Workbooks login takes the following parameters; valid combinations are: (`api_key`), (`username` and `password`), or (`username`, `logical_database_id` and `session_id`).

* `api_key` Optional. An API Key also identifies the database being accessed.
* `username` Optional. The user's login name, this is an email address.
* `password` Optional. The user's login password.
* `session_id` Optional. This identifies a session to 'reconnect' to. See 'reconnecting sessions' below.
* `client=api` Mandatory. This setting is used to distinguish API usage from that of the Workbooks Desktop.
* `api_version=1` Mandatory. Indicate which version of the API should be used. Currently this should always be 1. In the future the API's behaviour can be varied by setting this to larger numbers.
* `json=pretty` Optional. This setting causes the output to be formatted for the session so that it is easier to read although slightly larger
  and with a minor performance impact on the server. This is recommended only during development and testing.
* `logical_database_id` Optional. You should use this to select a specific database to log into by specifying the `logical_database_id` from
  the set returned in a login response when there is more than one database available. We recommend that API clients remember this value to use
  as the default in future logins. This parameter is not supported or required when an API Key is being used.
* `_application_name` Optional. You can specify a name for your application that will be used to stamp the created_through attribute on all new
  objects that you create in the session. If you do not specify an `_application_name`, then it will default to the first word of your
  User-Agent string.
* `_time_zone` Optional. You can set `_time_zone` to the name of a time zone that will be used for the session. If specified, it overrides the
  user's default time zone preference.
* `_strict_attribute_checking` Optional. If you set `_strict_attribute_checking`, then all of the attributes submitted in the requests in this
  session will be checked and errors reported for any unrecognised attributes.
* `_per_object_transactions` Optional. If you set `_per_object_transactions`, each object in a request is treated in a separate transaction.
  This means that any errors in updating an object are reported, but do not affect the successful update of other objects in the same request.
  If `_per_object_transactions` is not set, then any errors in updating any objects in a request are reported and none of the objects are
  updated.
* `_json_output_datetime_format`, `_json_output_date_format`, `_json_output_time_format` Optional. See Dates and Times, above.

The `_strict_attribute_checking`, `_per_object_transactions` and date/time format attributes can be specified in the login request, and they
will affect the whole session. You can also not specify them in the login request, and instead pass them to specific requests where they will
affect only that request. The `_time_zone` attribute must be specified in the login request - you cannot change time zone in the middle of a
session.

The response content is identified explicitly as being in UTF-8 and includes a set of potentially useful information. This set will be extended
in future. Workbooks has a model which permits multiple databases to be accessible to a given user; for most users there is exactly one
available database. The selected database is announced in the login response using the two values `database_name` and `logical_database_id`.
The full set of databases available to this login are listed within the databases hash so it is possible for an API client to present a choice
of databases to its user if there is more than one available. The authenticity_token is valid for the duration of the session and is required
when submitting changes to the system (create, update or delete) - this is a defence against some forms of Cross-Site Request Forgery. The
å attribute is an integer that defines the compatibility of the API of the server, it normally follows the requested value.

If the login fails, one of the following HTTP response codes will be returned, together with a `failure_reason`; note that this list will grow
over time so please handle Codes and Failure Reasons which are undocumented as yet:

* `401 password_expired` The password must be changed before login will be permitted (this cannot happen if you are using API Keys).
* `403 no_permission_for_api` The user does not have api_access capability.
* `401 login_disabled` The user, or the user's employer's account, has been disabled.
* `401 unrecognised_name_or_password` Either the name or the password was not correct.
* `403 requested_database_not_available` A specific database was requested by specifying the `logical_database_id` parameter but it is
  unavailable, it is likely this is due to system maintenance.
* `403 no_available_database` No database is available for the user, it is likely this is due to system maintenance.
* `403 database_being_migrated` A new release of software has been deployed and the selected database has not yet been upgraded. Try again soon.
* `403 database_archived` Your Workbooks database has not been accessed for an extended period of time and has been archived. It will now be
  retrieved from archive and will be available shortly.
* `403 invalid_application_name` The application name is reserved for internal use by Workbooks.
* `401 session_expired` The session has expired. Often this is after more than eight hours of inactivity but it may be due to system maintenance.
* `401 no_session` You do not have a session.
* `403 no_database_selection_made` The user's credentials are acceptable and the user has access to multiple databases, but the API client has
  not indicated which to use through the `logical_database_id` parameter. The API client should try to login again, this time specifying the
  `logical_database_id`. Note that, provided the credentials were OK, the login response includes a list of available databases. It is
  recommended that an API client remember, alongside other configuration such as the username, the ID of the database which was used in the
  previous session so it can be provided during the login. Note that the `default_database_id` value in the response is merely the most recent
  default selected when the user logged into the Workbooks user interface.
* `403 user_agent_required` You must provide a `User-Agent:` HTTP header value.
* `401 client_not_licensed` Workbooks reserves the right to restrict a client's access to the service.
* `401 licence_expired` Your licence has expired. An API client cannot log in when the licence has expired. A user can log into the Workbooks
  Desktop to renew the licence.
* `401 no_licensed_edition` The User has not been allocated a Licensed Edition by the Administrator.
* `401 no_session_found` A `session_id` was passed as a parameter alongside the username and no suitable session was found to reconnect to. See Reconnecting Sessions below.
* `401 client_not_activated` Additional authentication is required to permit a login to succeed. The user has been emailed an activation link
  which will permit a future authentication to succeed. We suggest clients display text to this effect, for example "Further security checks
  are required to permit you to login from this [device/browser]. Workbooks has sent you an activation email; please click on the activation
  link in that email to allow login to succeed next time."

Note: Workbooks API adheres to <a href="https://tools.ietf.org/html/rfc2616">RFC2616</a> (Hypertext Transfer Protocol HTTP/1.1) with the exception of the WWW-Authenticate header as documented
in <a href="https://tools.ietf.org/html/rfc2617">RFC2617</a> (HTTP Authentication: Basic and Digest Access Authentication). The API uses the standard HTTP status codes, but uses JSON to encode
more information in the response than is possible within the RFC specifications.

## Logout

A logout request will normally return HTTP response code 302 (redirect) together with a `Location:` header; the HTML response can be ignored.

The same response code is received when you try to use an invalid session-ID with another request; you must then successfully login again to
continue.

## Reconnecting Sessions

A successful session-based login returns a `session_id`, both as a cookie and within the main JSON response. Clients using Ajax have no access
to cookies so returning the ID in the JSON response allows API clients to discover the `session_id` which they can store (e.g. in HTML5
client-side storage). If the client attempts a login providing the username provided originally together with this `session_id` and the
`logical_database_id` (also returned by a successful login) then it may be logged in and effectively 'reconnected' to the service.

Sessions expire on the service for various reasons in which case the code `401 no_session_found` is returned. An example:

<pre><code>
curl -i -g -s \
     -c '/tmp/cookiejar' \
     -A 'XYZ plugin/1.2.3 (gzip)' \
     --compressed \
     -X 'POST' \
     -d 'username=system_test@workbooks.com' \
     -d 'session_id=134a657bd27d80fdf38c38ea025a0c8f' \
     -d 'client=api' \
     -d 'api_version=1' \
     -d 'json=pretty' \
     -d 'logical_database_id=6' \
     https://secure.workbooks.com/login.api

HTTP/1.1 200 OK
Date: Tue, 17 May 2011 18:49:36 GMT
Server: Apache
ETag: "280c60eb57e102f5ddd8aae3d1256b35"
Cache-Control: private, max-age=0, must-revalidate
Set-Cookie: Workbooks-Session=f4543f2730f9cef940116d7b80fa57a2; path=/; 
secure; HttpOnly
Status: 200 OK
Cache-Control: no-cache, no-store, must-revalidate
Expires: Fri, 30 Oct 1998 14:19:41 GMT
Vary: Accept-Encoding
Content-Encoding: gzip
Content-Length: 510
Content-Type: application/json; charset=utf-8

{"session_id": "f4543f2730f9cef940116d7b80fa57a2", ...
</code></pre>
         
Note that a successful 'reconnection' will result in a new session being created with a new `session_id`, which the client should now store in place of the original one.
  
# Retrieving Objects

Send a GET request to the Workbooks service; the request URL indicates the format for the response and the type of object being listed. You
will often specify parameters to specify the rows and columns you want and how the returned list should be sorted. It is important that
something is sent which is a "cache-buster" in case proxies along the way attempt to cache the data despite the presence of numerous headers in
the response; we recommend including a parameter `_dc` set to milliseconds-since-the-epoch.

Please note: The HTTP protocol does not define the use of a 'body' containing parameters within GET requests. GET requests usually append
parameters to the URL. Implementations may limit the length of the URL, limiting the parameters that may be sent. Our service implements an
extension to the GET request, that allows parameters to be passed in the body of the request, avoiding the above limitations. Some HTTP helper
classes or clients may prevent the use of a body with parameters when a GET request is constructed. If this is the case, a POST request can be
used, and an additional parameter included `'_method=GET'`. The `_method` parameter tells our service to interpret the request as a GET, even
though it was actually sent as a POST.

For example, to request the Task with the lowest id (note that lists start at the zeroth record; `_sort` and `_dir` specify a column to sort
the list on and the direction of the sort which can be `ASC` or `DESC`); the results come back in two hashes: of "data" and the "total" number
of records matching the request:

<pre><code>
curl -i -g -s \
     -b '/tmp/cookiejar' \
     -X 'GET' \
     -d '_dc=12577504357462' \
     -d '_start=0' \
     -d '_limit=1' \
     -d '_sort=id' \
     -d '_dir=ASC' \
     https://secure.workbooks.com/activity/tasks.api

HTTP/1.1 200 OK
Connection: close
Date: Mon, 12 Jul 2010 15:44:37 GMT
Set-Cookie: Workbooks-Session=de2571b2d9bd35f7746c0cdbcc543fdd; path=/
Status: 200 OK
ETag: "aed95a987facc6ccfa3c4312a8a0fe16"
X-Runtime: 148ms
Content-Type: application/json; charset=utf-8
Content-Length: 1918
Server: Mongrel 1.1.3
Cache-Control: private, max-age=0, must-revalidate

{
  "data" : [{
    "_can_chaccess" : true,
    "_can_chown" : true,
    "_can_delete" : true,
    "_can_modify" : true,
    "_can_read" : true,
    "activity_priority" : "High",
    "activity_status" : "New",
    "activity_type" : "Phone call",
    "assigned_to" : 1,
    ...
    "watched" : false
   }],
  "flash" : "",
  "success" : true,
  "total" : 2,
  "updates" : {}
}
</code></pre>

## Common Attributes

Most records, regardless of record type, share a set of attributes so that they can be manipulated in a consistent manner.

* `id` integer. The unique identifier for the object.
* `lock_version` integer. The server increments `lock_version` on every modification. Used to ensure that the object has not been modified elsewhere when an client attempts to operate on it: the latest
* `created_at` datetime. When the object was created.
* `created_by` integer. The `user_id` of the user who created this object. Only set when the object is initially inserted.
* `updated_at` datetime. When the object was last changed; when the object is initially inserted it will have the same value as `created_at`.
* `updated_by` integer. The `user_id` of the user who last updated this object; when the object is initially inserted it will have the same value as `created_by`.
* `created_through_reference` string. A free-form string that can be used by the application to store a reference to the equivalent object in some other system, e.g. if the application is synchronising changes between the two objects. Note that an alternative approach if the use of a single attribute is restrictive is to use the `Integration State` record type.
* `created_through` string. The name of the application that created this object (set through the `_application_name` parameter on the login request).
* `is_deleted` boolean. Whether the object has been deleted.
* `name` string. The common name for the object.
* `object_ref` string. The permanent unique Workbooks Object Reference for the object.
* `_can_chaccess` boolean. Whether the user associated with the session can change the object's access control list.
* `_can_chown` boolean. Whether the user associated with the session can change the object's ownership.
* `_can_delete` boolean. Whether the object can be deleted by the user associated with the session.
* `_can_modify` boolean. Whether the object can be modified by the user associated with the session.
* `_can_read` boolean. Whether the object can be read by the user associated with the session (always true).

Other attributes are present depending on the type of the object. Those which are present in related objects in the data model are indicated with square brackets like this for example: `"updated_by_user[person_name]" : "SystemTest Config"`.

The `updated_by` attribute is actually represented by an id internally however the list resolves to the user itself.

## Limiting your query

As shown in earlier examples, the `_start` and `_limit` parameters can be used to control the number of rows returned by a query. We recommend that you always specify `_start` and `_limit` for index queries. If you do not specify a `_start` and a `_limit` the service may impose a
limit; a `_limit` of 100 can be expected to work. Note that the service currently ignores `_limit` unless a `_start` is also present (this may change in future).

## Sorting

As shown in earlier examples, the `_sort` and `_dir` parameters can be used to specify a column to sort the list on and the direction of the sort which can be `ASC` or `DESC`. Sorting by more than one column is possible by using arrays of columns and directions using the the
`_sort[]` and `_dir[]` parameters.

For example:

<pre><code>
curl -i -g -s \
     -b '/tmp/cookiejar' \
     -X 'GET' \
     -d '_dc=12577504357462' \
     -d '_start=0' \
     -d '_limit=10' \
     -d '_sort[]=due_date' \
     -d '_dir[]=DESC' \
     -d '_sort[]=id' \
     -d '_dir[]=ASC' \
     https://secure.workbooks.com/activity/tasks.api
</code></pre>

## Filtering

In addition to using `_start` and `_limit` to control the set of rows returned you can specify one or more filters. Filters are specified as arrays of parameters; each filter contains the following elements:

* `_ff[]` Filter Field. The name of the field, in the same format as ordering and grouping.
* `_ft[]` Filter Type. A comparison operator. See list below.
* `_fc[]` Filter Criteria. The (value) data to match, UTF-8 encoded as everything else is. These can be comma-separated when `eq` comparisons are used resulting in the equivalent of an `IN ()` clause. Since you can specify multiple values for filter criteria separated by a comma, you must escape a comma by prefixing it with a backslash if you are looking for a string containing a comma.

Alongside the array of parameters you may also specify the matching mode through the `_fm` parameter. There are currently three possible value types:

* `_fm=and` The filter only includes rows that match all of the conditions specified by `_ff[]`, `_ft[]` and `_fc[]`. This is also the default if `_fm` is not specified.
* `_fm=or` The filter includes any rows that match the conditions specified by `_ff[]`, `_ft[]` and `_fc[]`.
* `_fm=`boolean expression. The filter only includes rows that match the conditions specified by `_ff[]`, `_ft[]` and `_fc[]` according to the boolean expression.

As a special case, if you only have a single filter, you can omit the square brackets from the filter parameters to pass `_ff[]`, `_ft[]` and `_fc[]`.

### Alternate filter syntax

Rather than passing over `_ff[]`, `_ft[]` and `_fc[]` separately you can combine them into a parameter called `_filter_json` which is a JSON representation of an array of filters, each filter being an array of three items: filter field, filter type, filter criteria:
`_filter_json=[["name","ct","Hello World"],["object_ref","eq","CASE-1234"]]`

### Boolean expressions in filtering

The boolean expression for the `_fm` parameter can include any of:

* `NOT`: boolean not (you can also use `!` to mean `NOT`)
* `AND`: boolean and
* `OR`: boolean or
* `XOR`: boolean exclusive or
* parentheses for grouping
* numbers referring to the filters

For example: `(1 AND 2) OR 3` would require that only rows that match filters 1 and 2, or that match filter 3 are returned.

### Filtering Types

| Operator | Meaning | Data types |
| -------: | ------- | ---------- |
| bg | Begins with | string |
| blank | Is blank | string, integer, float, currency, date, time, datetime |
| ct | Contains | string |
| eq | On | date |
| xeq | At | time, datetime |
| eq | Equals | boolean, string, integer, float, currency |
| false | Is false | boolean |
| ge | Greater than or equal | integer, float, currency |
| ge | On or after | date, time, datetime |
| ge_today | Is on or after today | date, datetime |
| gt_today | After today | date, datetime |
| gt | Greater than | integer, float, currency |
| gt | After | date,time, datetime |
| le | Less than or equal | integer, float, currency |
| le | On or before | date, time, datetime |
| lt | Less than | integer, float, currency |
| lt | Before | date, time, datetime |
| le_today | Is on or before today | date, datetime |
| lt_today | Before today | date, datetime |
| nbg | Does not begin with | string |
| nct | Does not contain | string |
| ne | Not equal | boolean, string, integer, float, currency |
| ne | Not on | date |
| ne | Not at | time, datetime |
| not_blank | Is not blank | string, integer, float, currency, date, time, datetime |
| today | Is today | date, datetime |
| true | Is true | boolean |

For the `blank`, `not_blank`, `true`, `false`, `today`, `le_today`, `lt_today`, `ge_today` and `gt_today` filtering types (which do not
need a value to compare with), `fc[]` should be present but is ignored. If you are testing boolean values using `eq` or `ne` then use the
values 0 to represent false or 1 to represent true.

An example filter to filter all names that start with 'James': `_ff[]=name&_ft[]=bg&_fc[]=James`
        
Further limiting the filter to employer 'Workbooks': `_ff[]=name&_ft[]=bg&_fc[]=James&_ff[]=employer[name]&_fc[]=ct&_fc[]=Workbooks`

As you can see above since arrays of parameters are being passed it is important to specify them grouped sensibly together.

Now look for the set of records with names starting with "James" plus those with employer's names equal to "Workbooks":
`_ff[]=name&_ft[]=bg&_fc[]=James&_ff[]=employer[name]&_fc[]=eq&_fc[]=Workbooks&_fm=or`

For example:

<pre><code>
curl -i -g -s \
     -b '/tmp/cookiejar'  \
     -X 'GET' \
     -d '_dc=12577549475076' \
     -d '_ff[]=id' \
     -d '_ft[]=eq' \
     -d '_fc[]=1' \
     -d '_ff[]=id' \
     -d '_ft[]=eq' \
     -d '_fc[]=2' \
     -d '_fm=or' \
     -d '_sort=id' \
     -d '_dir=ASC' \
     https://secure.workbooks.com/activity/tasks.api
</code></pre>
        
Here is an example with a complex boolean logic filter specified in the _fm parameter:

<pre><code>
curl -i -g -s \
     -b '/tmp/cookiejar' \
     -X 'GET' \
     -d '_dc=12612703759801' \
     -d '_start=0' \
     -d '_limit=20' \
     -d '_sort=id' \
     -d '_dir=ASC' \
     -d '_ff[]=main_location[email]' \
     -d '_ft[]=bg' \
     -d '_fc[]=an' \
     -d '_ff[]=refcode' \
     -d '_ft[]=ct' \
     -d '_fc[]=g' \
     -d '_ff[]=person_last_name' \
     -d '_ft[]=bg' \
     -d '_fc[]=go' \
     -d '_fm=(1 OR 2) AND 3' \
     http://secure.workbooks.com/crm/people.api
</code></pre>

This requests "People with (email address beginning with 'an' OR refcode containing 'g') AND last name beginning with 'go'". Instead of
passing 'or' or 'and' in the _fm parameter, you pass a boolean logic string with numbers referring to the `_ff[]`, `_ft[]` and `_fc[]`
arrays where 1 (not zero) is the first element in the arrays.

Here is an example to retrieve records deleted since a date and time:

<pre><code>
curl -i -g -s \
     -b '/tmp/cookiejar' \
     -X 'GET' \
     -d '_dc=12577549475076' \
     -d '_ff[]=is_deleted' \
     -d '_ft[]=eq' \
     -d '_fc[]=1' \
     -d '_ff[]=updated_at' \
     -d '_ft[]=gt' \
     -d '_fc[]=Fri Sept 7 14:00:00 UTC 2012' \
     -d '_sort=id' \
     -d '_dir=ASC' \
     http://secure.workbooks.com/crm/people.api
</code></pre>

### Selecting Columns

Use the `_select_columns[]` parameter multiple times to specify the set of columns you want to receive. If this is omitted you will get
the complete set of available columns; some columns can contain a lot of data such as rich-text data, and since records can have hundreds
of columns it can clutter logs and make debugging more difficult. For example

<pre><code>
curl -i -g -s \
     -b '/tmp/cookiejar' \
     -X 'GET' \
     -d '_dc=12577513410174' \
     -d '_start=0' \
     -d '_limit=1' \
     -d '_sort=id' \
     -d '_dir=ASC' \
     -d '_select_columns[]=lock_version' \
     -d '_select_columns[]=name' \
     -d '_select_columns[]=updated_by_user[person_name]' \
     https://secure.workbooks.com/activity/activities.api
</code></pre>

### Picklists

Use the 'Picklists' and 'Picklist Entries' API endpoints. Remember that you can fetch picklist entries for multiple picklists in a single requests to `picklist_entries.api` by comma-separating multiple values for `picklist_id`.

### Fetching PDF Documents

For some objects, especially transaction documents such as Quotations, Invoices and Credit Notes, you can generate a PDF rendering of that
object. The PDF is generated using a 'Template' which is configured in the 'PDF Configuration' section within the Workbooks Desktop. Using
the API you can select one of several Templates; by default Workbooks is configured with both a portrait and a 'Landscape' template for
each object type. To use this API, specify a file extension of 'pdf' rather than 'api'.

If there are multiple configured templates which could be used to render the document, a `406 Not Acceptable` response is returned listing
the templates which are available. Choose the appropriate ID for the template and resubmit the request.

If there is only one configured template, or a template ID has been specified using the 'template' parameter, then a 200 'OK' response is
returned complete with the requested PDF.

### Fetching Reports

As well as receiving JSON as report output it can be convenient to fetch a report by passing a .csv extension. 

The data can be obtained using two different paths: 

* A report can be accessed by ID if the ID is known. Use the path @/data_view/99/data.csv@ where 99 is the ID of the report. This is useful within a script that has been attached to a report, where the ID of the report will be available as the `data_view_id` parameter.

* Reports can also be accessed by name. Use the path @/data_view/name.csv@ where name is the name of the report. Note, it is possible to
  create more than one report with the same name so if you are accessing reports by name, make sure the names are unique otherwise you may
  not get the results you expect.

* By specifying an .xls extension, the report can be retrieved in XSL Spreadsheet format (compatible with recent versions of Microsoft Excel) using either of the approaches above.

# Changing Objects

Changing objects through the API begins with a request that looks similar to those documented in the previous section. You cause Workbooks
to do a mixture of creation, deletion and modification on a set of objects in a single request; the whole request either succeeds or
fails. Because a number of objects are being operated on, many parameters are array parameters. You are limited to operating on no more
than 100 objects per request and the set of objects being changed must be set within the request.

You should ALWAYS specify a filter which selects the current working set: for a CREATE operation this is the filter `id = 0`.

A request to change objects (create, modify or delete) is always a PUT request. NOTE: if you are using an old HTTP client library that
doesn't support PUT, see section HTTP Methods, Certificates and URLs above for a workaround.

The request begins like this:

<pre><code>
curl -i -b cookiejar -g \
     -X PUT \
     -d _authenticity_token=0240837db6a9a2dac47c3419a27c79b22be9e597
</code></pre>

when updating or deleting objects, include filters to define the set of objects to be acted upon, for example:

To select all the records that contain the word, Workbooks in a 'name' field:

<pre><code>
     -d _ff[]=name
     -d _ft[]=ct
     -d _fc[]=Workbooks
</code></pre>

or to only select a single object by id:

<pre><code>
     -d _ff[]=id
     -d _ft[]=eq
     -d _fc[]=10123
</code></pre>

or to select several objects by `id`: (note the use of the `_fm[]=or` parameter to cause the filter conditions to be or'd together instead
of the default 'and')

<pre><code>
     -d _fm[]=or

     -d _ff[]=id
     -d _ft[]=eq
     -d _fc[]=10123

     -d _ff[]=id
     -d _ft[]=eq
     -d _fc[]=10124

     -d _ff[]=id
     -d _ft[]=eq
     -d _fc[]=10125
</code></pre>
        
and ends specifying the appropriate URL, for example @https://secure.workbooks.com/activity/tasks.api@.

For each operation within the request you include a series of array parameters between the filter and the URL sections above.

When complete the response hash reports back the set of affected fields of each affected object as an array of hashes in the correct order
such that it mirrors the set of id values passed in but with any 0 values replaced by the id of each newly-created object.

## Methods

The Workbooks API allows you to create, modify and delete records in a single request. To achieve this you must send a "square array" of
parameters, i.e. you must supply the same parameters for every record in the request. Each record must be identified by its `id` and
`lock_version`. Because the parameters are arrays you must append [] to the parameter name. Hence, the `id[]` parameter identifies the
record, whilst the `lock_version[]` parameter ensures that you cannot overwrite changes made by another client at the same time (it is
incremented by Workbooks on every change to the record).

You must also specify the type of change you want to make to the record using the (double underscore) `__method[]` parameter. Although you
are sending an HTTP PUT request, you may want to update several records, delete one or more records and create other records in the same
request. So, for each record, the `__method[]` parameter specifies the action to take for that record.

For example, if you want to update two records, setting the name field in the first record, and the age field in the second record, then
you must supply two `name[]` parameters and two `age[]` parameters, one for each record. If you don't want a value to change, then you can
set it to `:no_value:`.

<pre><code>
     -d __method[]=PUT \
     -d id[]=1234 \
     -d lock_version[]=2 \
     -d name[]=John \
     -d age[]=:no_value: \
</code></pre>

<pre><code>
     -d __method[]=PUT \
     -d id[]=4567 \
     -d lock_version[]=0 \
     -d name[]=:no_value: \
     -d age[]=26 \
</code></pre>

You must specify filters that will load the records that your request wants to modify. In the example above you would need to include
filters that load records 1234 and 4567. The examples below illustrate this.

### Update

To modify a record you need to send a request marked with `__method[]=PUT` and specify the `id` and the `lock_version` of that record plus
a set of field values:

<pre><code>
     -d __method[]=PUT \
     -d id[]=1 \
     -d lock_version[]=5 \
     -d name[]=James%20Kay \
</code></pre>

<pre><code>
     -d _ff[]=id \
     -d _ft[]=eq \
     -d _fc[]=1 \
</code></pre>
        
For this to be successful your session needs the appropriate "update" capability, and you must also have permission to update the row. It
is also necessary to filter the objects to match the `id` required.

### Create

To create a record you need to send a request marked with `__method[]=POST` and specify `id=0` and `lock_version=0` plus a set of fields
to put in the record:

<pre><code>
     -d __method[]=POST \
     -d id[]=0 \
     -d lock_version[]=0 \
     -d name[]=New%20Record \
     -d size[]=400 \
     -d state[]=open \
</code></pre>

<pre><code>
     -d _ff[]=id \
     -d _ft[]=eq \
     -d _fc[]=0 \
</code></pre>

For this to be successful your session needs the appropriate "create" capability.

### Delete

To delete a record you need to send a request marked with `__method[]=DELETE` and specify the `id` and the `lock_version` of a record to
be deleted:

<pre><code>
     -d method[]=DELETE \
     -d id[]=1 \
     -d lock_version[]=5 \
</code></pre>

<pre><code>
     -d _ff[]=id \
     -d _ft[]=eq \
     -d _fc[]=1 \
</code></pre>

For this to be successful your session needs the appropriate "delete" capability, and you must also have permission to delete the row. It
is also necessary to filter the objects to match the `id` required.

### Affected objects

Workbooks returns information in the response to a request to change objects that details the actual changes that were made. The response
contains a value called `affected_objects` that is an Array of Hashes - one element per object, in the same order as the request. Each Hash
contains:

* the `id` of the object.
* the `lock_version` of the object, which will have been incremented by the change.
* any other attributes of the object that were changed by the request. If the object is new, then all of the attributes are returned (they
  are all changed); if the request has modified related objects (using the square-bracket attribute notation, e.g.
  `main_location[email]`), then those attributes and any other attributes that were implicitly changed are also returned.

Alongside the `affected_objects` Array of Hashes, there is another `affected_object_information` Array of Hashes

### Changing Objects by Example

#### Example: Updating a Task

<pre><code>
curl -i -g -s \
     -b '/tmp/cookiejar' \
     -X 'PUT' \
     -d '_authenticity_token=19453a4d3fee1c05970b8690088242208b6fe801' \
     -d '_ff[]=id' \
     -d '_ft[]=eq' \
     -d '_fc[]=1' \
     -d '__method[]=PUT' \
     -d 'id[]=1' \
     -d 'lock_version[]=1' \
     -d 'name[]=Call_Testrite_now' \
     https://secure.workbooks.com/activity/tasks.api

HTTP/1.1 200 OK
Connection: close
Date: Mon, 12 Jul 2010 16:03:09 GMT
Set-Cookie: Workbooks-Session=de2571b2d9bd35f7746c0cdbcc543fdd; path=/
Status: 200 OK
ETag: "a6a1e9d748ba38a9c799eaa96a451c5b"
X-Runtime: 882ms
Content-Type: application/json; charset=utf-8
Content-Length: 504
Server: Mongrel 1.1.3
Cache-Control: private, max-age=0, must-revalidate

{
    "affected_object_information" : [{
            "errors" : {},
            "success" : true,
            "warnings" : {}
        }],
    "affected_objects" : [{
            "descriptor" : "Call_Testrite_now (Phone call)",
            "id" : 1,
            "lock_version" : 2,
            "name" : "Call_Testrite_now",
            "updated_at" : "Mon Jul 12 16:03:09 UTC 2010",
            "updated_by" : 2
        }],
    "flash" : "Updated successfully",
    "success" : true,
    "updates" : {}
}        
</code></pre>

Note that the `affected_objects` hash reports back a number of fields which have changed as a side-effect of the operation. The
`affected_object_information` is additional "out of band" information on the affected objects, i.e. messages but often (as in the example
above) it is empty.

#### Example: Creating an Organisation

<pre><code>
curl -i -g -s \
     -b '/tmp/cookiejar' \
     -X 'PUT' \
     -d '_authenticity_token=e6f8360ca3e135f71bfc846305bf52040f6ce0ae' \
     -d '__method[]=POST' \
     -d 'id[]=0' \
     -d 'lock_version[]=0' \
     -d 'name[]=New+Organisation' \
     https://secure.workbooks.com/crm/organisations.api

HTTP/1.1 200 OK
Date: Mon, 23 Nov 2009 09:35:16 GMT
Server: Apache
ETag: "80841210880d6b5b6083f6c1a0cda429" 
Cache-Control: private, max-age=0, must-revalidate
Set-Cookie: Workbooks-Session=973ea27009d2d8e2f360ea6e8913a66b; path=/
Content-Length: 667
Status: 200 OK
Cache-Control: no-cache, no-store, must-revalidate
Expires: Fri, 30 Oct 1998 14:19:41 GMT
Content-Type: application/json; charset=utf-8

{
    "affected_object_information" : [{}],
    "affected_objects" : [{
            "assigned_to" : 3,
            "assigned_to_name" : "System Test",
            "created_at" : "Mon Nov 23 09:35:16 UTC 2009",
            "created_by" : 2,
            "id" : 226,
            "lock_version" : 0,
            "name" : "New Organisation",
            "queue_entry[updated_at]" : "less than a minute ago",
            "refcode" : "NEW1",
            "type" : "Private::Crm::Organisation",
            "updated_at" : "Mon Nov 23 09:35:16 UTC 2009",
            "updated_by" : 2
        }],
    "flash" : "Updated successfully",
    "success" : true,
    "updates" : {}
}
</code></pre>
                
#### Example: A failure

This example shows what happens when a uniqueness constraint is violated while creating a new Person:

<pre><code>
curl -i -g -s \
     -b '/tmp/cookiejar' \
     -X 'PUT' \
     -d '_authenticity_token=8c3981bfda37a1a9b28a31ed5120643d0ffab72c' \
     -d '__method[]=POST' \
     -d 'id[]=0' \
     -d 'lock_version[]=0' \
     -d 'name[]=New+Person' \
     -d 'refcode[]=DUPLICATE_PERSON_REFCODE' \
     https://secure.workbooks.com/crm/people.api

HTTP/1.1 200 OK
Date: Mon, 23 Nov 2009 09:35:37 GMT
Server: Apache
ETag: "8545c3a892a6d0eaafc9789012da74b0" 
Cache-Control: private, max-age=0, must-revalidate
Set-Cookie: Workbooks-Session=91659795feb3a5e4da6eb297c60119c7; path=/
Content-Length: 263
Status: 200 OK
Cache-Control: no-cache, no-store, must-revalidate
Expires: Fri, 30 Oct 1998 14:19:41 GMT
Content-Type: application/json; charset=utf-8

{
  "affected_object_errors" : [{
      "refcode" : ["'DUPLICATE_PERSON_REFCODE' is already used"]
  }],
  "errors" : {
    "[]" : {
      "refcode" : "'DUPLICATE_PERSON_REFCODE' is already used" 
    }
  },
  "success" : false
}      
</code></pre>
          
#### Example: Modifying the currency of an organisation

<pre><code>
curl -i -g -s \
     -b '/tmp/cookiejar' \
     -X 'PUT' \
     -d '_authenticity_token=e6f8360ca3e135f71bfc846305bf52040f6ce0ae' \
     -d '_ff[]=id' \
     -d '_ft[]=eq' \
     -d '_fc[]=1' \
     -d '__method[]=PUT' \
     -d 'id[]=1' \
     -d 'lock_version[]=0' \
     -d 'currency[]=XXX' \
     https://secure.workbooks.com/crm/organisations.api

HTTP/1.1 200 OK
Date: Mon, 23 Nov 2009 09:35:19 GMT
Server: Apache
ETag: "f3e34869a58c5bcfd7e1751be776b7e4" 
Cache-Control: private, max-age=0, must-revalidate
Set-Cookie: Workbooks-Session=973ea27009d2d8e2f360ea6e8913a66b; path=/
Content-Length: 606
Status: 200 OK
Cache-Control: no-cache, no-store, must-revalidate
Expires: Fri, 30 Oct 1998 14:19:41 GMT
Content-Type: application/json; charset=utf-8

{
    "affected_object_information" : [{
      "message" : [{
        "body" : "The Home Currency of this Own Organisation has been changed. Check the Foreign Currencies list as currencies without exchange rates (GBP,EUR,USD) have been removed.",
        "title" : "Information" 
      }]
    }],
    "affected_objects" : [{
      "currency" : "XXX",
        "id" : 1,
        "lock_version" : 1,
        "updated_at" : "Mon Nov 23 09:35:19 UTC 2009" 
      }],
    "flash" : "Updated successfully",
    "success" : true,
    "updates" : {}
}
</code></pre>
                
#### Example: Multiple operations

You can request several operations using the same controller in one request. The example below performs a delete, followed by an update,
followed by a create. Note that the same set of fields are included for each operation. When multiple operations are performed, each
operation MUST contain the same set of fields. In the case of the 'delete', all fields except the id and lock_version are irrelevant and
are just set to empty strings. If a field is to remain unchanged, as might be the case for due_date in the update request, the field value
may be set to `:no_value:`.

<pre><code>
curl -i -g -s \
     -b '/tmp/cookiejar' \
     -X 'PUT' \
     -d '_authenticity_token=19453a4d3fee1c05970b8690088242208b6fe801' \
     -d _fm=or \
     -d _ff[]=id \
     -d _ft[]=eq \
     -d _fc[]=1 \
     -d _ff[]=id \
     -d _ft[]=eq \
     -d _fc[]=2 \
     -d '__method[]=DELETE' \
     -d 'activity_priority[]=' \
     -d 'activity_status[]=' \
     -d 'activity_type[]=' \
     -d 'due_date[]=' \
     -d 'id[]=1' \
     -d 'lock_version[]=2' \
     -d 'name[]=' \
     -d '__method[]=PUT' \
     -d 'activity_priority[]=High' \
     -d 'activity_status[]=New' \
     -d 'activity_type[]=Email' \
     -d 'due_date[]=:no_value:' \
     -d 'id[]=2' \
     -d 'lock_version[]=1' \
     -d 'name[]=10197' \
     -d '__method[]=POST' \
     -d 'id[]=0' \
     -d 'lock_version[]=0' \
     -d 'activity_priority[]=High' \
     -d 'activity_status[]=New' \
     -d 'activity_type[]=Email' \
     -d 'due_date[]=22+May+2009' \
     -d 'name[]=create_10197' \
     https://secure.workbooks.com/activity/tasks.api

HTTP/1.1 200 OK
Connection: close
Date: Mon, 12 Jul 2010 16:12:28 GMT
Set-Cookie: Workbooks-Session=de2571b2d9bd35f7746c0cdbcc543fdd; path=/
Status: 200 OK
ETag: "9037cb8f8ac16db2c5f9fdc79d7fc063"
X-Runtime: 741ms
Content-Type: application/json; charset=utf-8
Content-Length: 2455
Server: Mongrel 1.1.3
Cache-Control: private, max-age=0, must-revalidate

{
    "affected_object_information" : [{
            "errors" : {},
            "success" : true,
            "warnings" : {}
        }, {
            "errors" : {},
            "success" : true,
            "warnings" : {}
        }, {
            "errors" : {},
            "success" : true,
            "warnings" : {}
        }],
    "affected_objects" : [{
            "id" : 1,
            "lock_version" : 2
        }, {
            "descriptor" : "10197 (Email)",
            "id" : 2,
            "lock_version" : 2,
            "name" : "10197",
            "updated_at" : "Mon Jul 12 16:12:28 UTC 2010",
            "updated_by" : 2
        }, {
            "_can_chaccess" : true,
            "_can_chown" : true,
            "_can_delete" : true,
            "_can_modify" : true,
            "_can_read" : true,
            "activity_priority" : "High",
            "activity_status" : "New",
            "activity_type" : "Email",
            "assigned_to" : 3,
            "completed_date" : null,
            "created_at" : "Mon Jul 12 16:12:28 UTC 2010",
            "created_by" : 2,
            "created_by_user[person_name]" : "System Test",
            "created_through" : "XYZ",
            "created_through_reference" : "",
            "description" : "",
            "descriptor" : "create_10197 (Email)",
            "due_date" : "22 May 2009",
            "id" : 4,
            "imported" : false,
            "lock_version" : 0,
            "name" : "create_10197",
            "owner[person_name]" : "System Test",
            "owner_id" : 2,
            "primary_contact[main_location[email]]" : "",
            "primary_contact[main_location[mobile]]" : "",
            "primary_contact[main_location[telephone]]" : "",
            "primary_contact[name]" : "",
            "primary_contact_id" : null,
            "primary_contact_type" : null,
            "queue[name]" : "System Test",
            "queue_entry[updated_at]" : "Mon Jul 12 16:12:28 UTC 2010",
            "reminder_datetime" : "Thu May 21 09:00:00 UTC 2009",
            "reminder_enabled" : false,
            "reminder_minutes" : 15,
            "type" : "Private::Activity::Task",
            "updated_at" : "Mon Jul 12 16:12:28 UTC 2010",
            "updated_by" : 2,
            "updated_by_user[person_name]" : "System Test",
            "watched" : false
        }],
    "flash" : "Updated successfully",
    "success" : true,
    "updates" : {}
}
</code></pre>
        
# Metadata

The Workbooks API offers an interface that can return information about the different Objects available in Workbooks. This metadata can be
used to develop external API clients that respond to changes in the Workbooks Objects as they are enhanced and improved (e.g. through the
configuration of 'Custom Fields').

The request is very simple: as you can discover by reviewing _Configuration > Automation > API Reference_ in the Workbooks Desktop, you
simply get a list from the `/metadata/types.api` controller. Optionally you can specify `_select_columns[]` and `class_names[]` to reduce
the set of data which is returned.

The `metadata/types` API returns an Array of Hashes - one for each object type that is exposed through the Workbooks API. Each Hash
contains the following key/value pairs:

* `actions` An array of hashes representing the set of actions for the main controller for this object (see controller_path, below). This
  will typically be a set of RESTful actions ('index', 'show', 'edit', 'new', 'create', 'update', 'destroy') but may include more or fewer
  actions. Alongside the action's name there is a permitted value which reports whether the current signed-in user is permitted to use
  that action.
* `base_class_name` The class name of the base class that this class inherits from.
* `class_name` The class name of the Workbooks object, e.g. "Private::Crm::Person".
* `controller_path` The path to the controller for this object, e.g. "crm/people". This should be prepended by the protocol, host and port
  of the Workbooks server.
* `help_url` The full URL of a web page that describes this object.
* `human_class_description` A short description of the object type, e.g. "People recorded in your database, e.g a contact, an employee".
* `human_class_name` The singular name of this object type, e.g. "Person".
* `human_class_name_plural` The plural name of this object type, e.g. "People".
* `icon` A reference to an icon that can be displayed for instances of this object type, e.g. "people". In some API clients this can be
  used as a reference to a CSS class or even a partial filename for the icon.
* `instances_described_by` The attribute of an instance of this object type that can be used to describe the instance, e.g. "name". For
  example, to display a short list of People, we would use each instance's name attribute.
* `fields` An Array of Hashes that describe the fields of the object type.
* `associations` An Array of Hashes that describe the associations between the object type and other object types.

Each element of the `associations` Array of Hashes has these attributes:

* `class_names` An Array of class names of the potential targets for this association.
* `datatype` The type of the association, one of "belongs_to", "has_one", "has_many" or "integer". A "belongs_to" association refers
  directly to the target instance, whereas "has_one" and "has_many" associations depend on the target object or objects referring to this
  object. An "integer" association is used by a "linked_item" field - see further information on fields below.
* `description` A description of the association.
* `foreign_key_name` The name of the field that holds the id of the target object.
* `maximum_n` The maximum number of instances that are allowed in this association. Only a "has_many" association can have more than 1
  instance.
* `minimum_n` The minimum number of instances that are allowed in this association.
* `name` The name of the association.
* `polymorphic` A boolean value declaring whether this association can refer to more than one type of object. If polymorphic is false,
  then the class_names attribute can only have one element. If polymorphic is true, then the class_names attribute can have more than one
  element.
* `through` If this association depends on another association to refer to the remote class, then the through attribute declares the
  'through' association.
* `title` The title of the association, as it can be presented in a user interface, e.g. as a label.

Each element of the `fields` Array of Hashes has these attributes:

* `datatype` The name of a Workbooks data type - see Workbooks API Developers Guide Object Model for the supported data types.
* `default_value` The default value for this field if it is not specified when the object is created.
* `is_createable` A boolean specifying whether this field can be set when creating an instance of the object type.
* `is_filterable` A boolean specifying whether this field can be filtered on when retrieving a list of this object type.
* `is_nullable` A boolean specifying whether this field can be set to null.
* `is_updateable` A boolean specifying whether this field can be set when updating an instance of the object type.
* `name` The internal name of the field.
* `required` A boolean specifying whether this field is required - must be submitted with a non-empty value.
* `title` A title for the field that can be presented in a user interface, e.g. as a label.

Optionally, a field can also have either a picklist or a `linked_item` value. If a field has one of these, then the value is another Hash
containing information on how to provide a picklist for the user to select an appropriate value for the field. Each picklist or
`linked_item` Hash has the following values:

* `auto_create` A boolean specifying whether the value will be added back into the set of picklist values if it does not already exist.
* `class_name` The name of the object type that this picklist selects from. Usually for a picklist this will be "Private::PicklistEntry".
* `display_field` The name of the field from the picklist object type that should be displayed to the user in the picklist, e.g "name".
* `value_field` The name of the field from the picklist object type that should be stored in the picklist field, e.g "id".
* `name` The name of a well-known picklist. For a `linked_item` and certain picklists, this can be null.
* `picklist_id` The id of a well-known picklist. For a `linked_item` and certain picklists, this can be null. The `picklist_id` should be
  submitted to the picklist controller when requesting values if it is not null.
* `path` The path to the controller for this picklist. This should be prepended by the protocol, host and port of the Workbooks server,
  e.g. `https://secure.workbooks.com/picklist_data/Private_Crm_Person/id/augmented_name.wbjson` and appended with any parameters. In
  particular, a parameter named query is used to restrict the returned items to those that match the query string. In general the match is
  a left-most case- insensitive partial match, e.g. "jon" would match "jon", "Jon" and "Jonathan", but not "John".
* `return_fields` An array of fields that the picklist can return from the picklist object type in addition to the `display_field` and the
  value_field which are returned automatically. To get these additional values, append an array of `return_fields[]` parameters listing
  the required fields to the request, e.g. `...?return_fields[]=field1&return_fields[]=field2`.
* `find_method` The name of a method to use for getting the picklist choices or null. If this is not null, it should be submitted as a
  query parameter.
* `unrestricted` A boolean specifying whether the user is required to pick a value from the list only. If it is true then the user can
  type in any value whether it is in the list or not.

Additionally, a `linked_item` Hash can also have:

* `association` The name of the association that this `linked_item` uses to refer to the target object. The type of object to select from
can be discovered from the `association` declaration.

## Support
API interactions as defined in this document are supported for current and future versions of the Workbooks Service. It is recommended that you work from the most recent version of this document.

Support is available via the Workbooks Customer Support team. We recommend that you use email as the most effective way of resolving API issues. Please contact <a href="mailto:support@workbooks.com">support@workbooks.com</a>. Enhancement suggestions should be logged on the Workbooks ideas forum at <a
href="https://www.workbooks.com/suggestions" target="_blank">https://www.workbooks.com/suggestions</a>.

Please include source code to demonstrate the issue. In particular you MUST supply:

*   The login name you are using to connect to the service.
*   The database ID you are accessing.
*   The exact time at which you last experienced any issue.
*   An exception reference if one is available.

We will be able to help you much more effectively if you can give us a clear context for your query.

## Document Revision History

| Date       | Description |
| ----       | ----------- |
| 01/12/2009 | Initial version, including support for Sessions, retrieving and modifying Contacts (People and Organisations) and retrieving Picklists. |
| 04/07/2010 | Updated cURL parameter information, and added the _application_name parameter to the login request. |
| 28/07/2010 | Added api_version to the login response, and updated the metadata information on associations and polymorphism. |
| 06/09/2010 | Removed the facility to remember a default database within Workbooks: API clients now always select the required database when more than one is available. Made examples refer to secure.workbooks.com. Documented explicitly that POST form submission uses the x-www-form-urlencoded content-type. |
| 13/09/2010 | Clarified the requirement to identify the set of objects to be acted upon in a request to update or delete one or more objects. Adjusted the examples for updating and deleting objects. |
| 17/09/2010 | Describe how POST requests can be used instead of GETs when client HTTP helper classes prevent the use of HTTP body with GET requests. |
| 26/09/2010 | Added the default_database_id to the response to a login request when there is no database selection made.  Changed examples on Activities to refer to Tasks instead. |
| 15/10/2010 | Remove references to REST which were confusing. |
| 12/11/2010 | Remove obsolete diagram. |
| 17/02/2011 | Add support for JSONP. |
| 17/05/2011 | Added the 'reconnect to session' feature to login. |
| 27/05/2011 | Added two options to /metadata/types.api: _select_columns[] and class_names[] to reduce the returned dataset. |
| 01/06/2011 | Added actions to metadata. |
| 03/06/2011 | Added client_not_activated to the list of responses from login. |
| 15/06/2011 | Added the /icons path for access to icon PNGs. |
| 20/06/2011 | Document access to PDF output documents and template selection. |
| 09/09/2011 | Added information about array parameters. |
| 25/09/2012 | Added information about deleted records. |
| 22/10/2012 | Added api_key authentication. |
| 15/03/2013 | Added information on designing an API client for performance. |
| 18/03/2013 | Updated accessing reports by name and by ID. |
| 01/05/2013 | Make explicit that when modifying the caller must specify a filter. |
| 07/06/2013 | General formatting and text tidy-up. |
| 12/02/2014 | Corrected mistake, explained “&”. More compact date in revision history. |
| 17/02/2014 | Documented filtering with array of values. |
| 19/02/2014 | Clarified recent edits. Requested TLS rather than SSL for security reasons. |
| 28/05/2014 | During login, api_version should now be requested and set to 1. |
| 30/01/2015 | It is no longer necessary to use the linked_item_association_for_ prefix when using Dynamic Linked Items. |
| 21/05/2015 | Add date/time format options - these allow you to use epoch time for example. |
| 07/07/2020 | Document moved to github and rewritten in markdown. |
