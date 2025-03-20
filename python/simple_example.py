#!/usr/bin/env python3

# A demonstration of using the Workbooks API via a thin Python wrapper.

# Last commit $Id: simple_example.py 60951 2024-01-03 18:13:10Z jkay $
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

create_three_organisations = [
    {
        'name': 'Freedom & Light Ltd',
        'created_through_reference': '12345',
        'industry': 'Media & Entertainment',
        'main_location[country]': 'United Kingdom',
        'main_location[county_province_state]': 'Berkshire',
        'main_location[fax]': '0234 567890',
        'main_location[postcode]': 'RG99 9RG',
        'main_location[street_address]': '100 Main Street',
        'main_location[telephone]': '0123 456789',
        'main_location[town]': 'Beading',
        'no_phone_soliciting': True,
        'no_post_soliciting': True,
        'organisation_annual_revenue': '10000000',
        'organisation_category': 'Marketing Agency',
        'organisation_company_number': '12345678',
        'organisation_num_employees': 250,
        'organisation_vat_number': 'GB123456',
        'website': 'www.freedomandlight.com',
    },
    {
        'name': 'Freedom Power Tools Limited',
        'created_through_reference': '12346',
    },
    {
        'name': 'Freedom o\' the Seas Recruitment',
        'created_through_reference': '12347',
    },
]

response = workbooks.assert_create('crm/organisations', create_three_organisations)
object_id_lock_versions = response.id_versions()

update_three_organisations = [
    {
        'id': object_id_lock_versions[0]['id'],
        'lock_version': object_id_lock_versions[0]['lock_version'],
        'name': 'Freedom & Light Unlimited',
        'main_location[postcode]': 'RG66 6RG',
        'main_location[street_address]': '199 High Street',
    },
    {
        'id': object_id_lock_versions[1]['id'],
        'lock_version': object_id_lock_versions[1]['lock_version'],
        'name': 'Freedom Power',
    },
    {
        'id': object_id_lock_versions[2]['id'],
        'lock_version': object_id_lock_versions[2]['lock_version'],
        'name': 'Sea Recruitment',
    },
]

response = workbooks.assert_update('crm/organisations', update_three_organisations)
object_id_lock_versions = response.id_versions()

batch_organisations = [
    {
        'method': 'create',
        'name': 'Abercrombie Pies',
        'industry': 'Food',
        'main_location[country]': 'United Kingdom',
        'main_location[county_province_state]': 'Berkshire',
        'main_location[town]': 'Beading',
    },
    {
        'method': 'update',
        'id': object_id_lock_versions[0]['id'],
        'lock_version': object_id_lock_versions[0]['lock_version'],
        'name': 'Lights \'R Us',
        'main_location[postcode]': None,
    },
    {
        'method': 'delete',
        'id': object_id_lock_versions[1]['id'],
        'lock_version': object_id_lock_versions[1]['lock_version'],
    },
    {
        'method': 'delete',
        'id': object_id_lock_versions[2]['id'],
        'lock_version': object_id_lock_versions[2]['lock_version'],
    },
]

response = workbooks.assert_batch('crm/organisations', batch_organisations)
object_id_lock_versions = response.id_versions()

create_one_organisation = {
    'method': 'create',
    'name': 'Birkbeck Burgers',
    'industry': 'Food',
    'main_location[country]': 'United Kingdom',
    'main_location[county_province_state]': 'Oxfordshire',
    'main_location[town]': 'Oxford',
}

response = workbooks.assert_create('crm/organisations', create_one_organisation)
created_id_lock_versions = response.id_versions()
object_id_lock_versions = object_id_lock_versions[:2] + created_id_lock_versions

filter_limit_select = {
    '_start': '0',
    '_limit': '100',
    '_sort': 'id',
    '_dir': 'ASC',
    '_filters[]': ['main_location[county_province_state]', 'bg', 'Berkshire'],
    '_select_columns[]': [
        'id',
        'lock_version',
        'name',
        'main_location[town]',
        'updated_by_user[person_name]',
    ],
}

response = workbooks.assert_get('crm/organisations', filter_limit_select)
workbooks.log('Fetched objects', response.data)

response = workbooks.assert_delete('crm/organisations', object_id_lock_versions)

workbooks.logout()

workbooks.log('Passed', __file__)
