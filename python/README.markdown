# Python language binding for the Workbooks API

See the python code here in github for simple usage examples to explore the objects returned by the API. The comments in the `workbooks_api.py` file contain additional information to that here.

This version of the binding for python does not yet implement file upload/download.

Note that this software does not change often, because it does not need to.  The Workbooks API is designed so that it is based on metadata and although features are added to Workbooks with every release the API does not change.  As far as possible API changes are backwards compatible.  We are proud that software written to our original 1.0 API back in 2009 continues to be both supported and supportable.

To find out more about the many records and fields within your Workbooks database navigate within Workbooks to Configuration > Automation > API Reference.

## Usage

External scripts can authenticate using an API Key or a username and password. In the examples included here authentication is done in `workbooks_test_login.py` using an API Key: just pass the `api_key` parameter when you create a new `WorkbooksApi` object.

### Using login() and logout()

An alternative is to use a username and password to establish a session with the Workbooks service: pass them to the `login()` call. Sessions can be reconnected using an existing session whose ID you have retained. When you are finished, it is polite to `logout()` or you may want to retain a session ID for future use.

Having obtained a session you can use any of the following methods: `get()`, `create()`, `update()`, `delete()`, `batch()`, or the assert versions.

### new()

_Initialise the Workbooks API_

Example:
<pre><code>
import logging
from workbooks_api import WorkbooksApi

logger = logging.getLogger()

workbooks = WorkbooksApi({
    'application_name': 'python_test_client',                      # Please give your application a useful name
    'user_agent': 'python_test_client/0.1',                        # Please give your application a useful label
    'api_key': '01234-56789-01234-56789-01234-56789-01234-56789',  # Replace this with an API Key you create
    'logger': logger                                               # Omit this for silence from the binding
})
</code></pre>

If you omit the api_key above you will instead need to use `login()` to establish a session.

## Further Information

The API is documented at <a href="http://www.workbooks.com/api" target="_blank">http://www.workbooks.com/api</a>.

## Requirements

For most systems the requirements will already be present with a standard Python installation with any additional modules loaded via pip.

This binding has been tested on Python 3.11 on macOS Sonoma 14.2 

## License

Licensed under the MIT License.

> The MIT License
> 
> Copyright (c) 2008-2025, Workbooks Online Limited.
> 
> Permission is hereby granted, free of charge, to any person obtaining a copy
> of this software and associated documentation files (the "Software"), to deal
> in the Software without restriction, including without limitation the rights
> to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
> copies of the Software, and to permit persons to whom the Software is
> furnished to do so, subject to the following conditions:
> 
> The above copyright notice and this permission notice shall be included in
> all copies or substantial portions of the Software.
> 
> THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
> IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
> FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
> AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
> LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
> OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
> THE SOFTWARE.

## Support

We ensure backwards-compatability so that older versions of these bindings continue to work with the production Workbooks service.  *These bindings are provided "as-is" and without any commitment to support.* If you do find issues with the bindings published here we welcome the submission of patches which we will evaluate and may merge in due course. This version of the binding for python does not yet implement file upload/download.

