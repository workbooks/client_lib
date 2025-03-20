#!/usr/bin/env python3

#  Login wrapper for Workbooks for API test purposes. This version uses an API Key to authenticate.
#
#  Last commit $Id: workbooks_test_login.py 63933 2024-09-03 14:06:12Z jmonahan $
#  License: www.workbooks.com/mit_license
#
import logging
import os
from workbooks_api import WorkbooksApi

class WorkbooksApiTestLogin:
    def __init__(self):
        logger = logging.getLogger()
        logger.setLevel(logging.DEBUG)
        formatter = logging.Formatter("[%(levelname)s] %(message)s")
        handler = logging.StreamHandler()
        handler.setFormatter(formatter)
        logger.addHandler(handler)

        service_url = 'https://localhost:3000'
        if os.getenv("RAILS_ENV") == 'test':
            service_url = 'http://localhost:3000'

        api_key = '01234-56789-01234-56789-01234-56789-01234-56789' 

        # allow the server and API key to be overridden with environment variables
        service_env = os.getenv("WB_SERVICE")
        api_key_env = os.getenv("WB_API_KEY")

        if service_env:
            service_url = service_env

        if api_key_env:
            api_key = api_key_env

        json_utf8_encoding = os.getenv("WB_JSON_UTF8")

        options = {
            'service': service_url,  # Omit this to use the production service
            'application_name': 'python_test_client',  # Give your application a useful name
            'user_agent': 'python_test_client/0.1',  # Give your application a useful label
            'api_key': api_key,
            'logger': logger,  # Omit this for silence from the binding
            'http_debug_output': True, # Noisy, omit this for production use
            'api_debug': True, # Noisy, omit this for production use
            'verify_peer': False # Omit this for production use: NOT recommended
        }

        if json_utf8_encoding:
            options['json_utf8_encoding'] = json_utf8_encoding

        self.workbooks = WorkbooksApi(options)
