<?php

return [
    /*
    |--------------------------------------------------------------------------
    | API Configuration for Manufacturer Strategies
    |--------------------------------------------------------------------------
    |
    | This file contains configuration for the various manufacturer APIs
    | used by the application.
    |
    */

    'api_config' => [
        'signify' => [
            'agents_url' => 'https://ws.bullseyelocations.com/RestSearch.svc/DoSearch2?distanceUnit=mi&loading=false&ready=true&countryId=1&_reference=A9&isManualSearch=true&manualSearching=true&searchSourceName=false&PromotionName=false&CategoryLimiterIDs=&doSearchMethod=false&internetOnlySearch=false&findNearestForNoResults=false&returnEvent=false&returnCoupon=false&pageSize=1000&getHoursForUpcomingWeek=true&startIndex=0&languageCode=en&InterfaceID=25928&fillAttr=false&showAllLocationsPerCountry=false&showNearestLocationsInList=false&ClientId=8419&ApiKey=6b3c11d7-9d3b-44e2-9aad-2c89c151a901',
            'headers' => [
                'accept' => 'application/json, text/plain, */*',
                'accept-language' => 'en-US,en;q=0.9',
                'cache-control' => 'no-cache',
                'origin' => 'https://genlyte-us.bullseyelocations.com',
                'pragma' => 'no-cache',
                'referer' => 'https://genlyte-us.bullseyelocations.com/',
                'user-agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36'
            ],
        ],
        'cooper' => [
            'agents_url' => 'https://ws.bullseyelocations.com/RestSearch.svc/DoSearch2?countryId=1&distanceUnit=mi&ready=true&_reference=A8&isManualSearch=true&manualSearching=true&searchSourceName=false&PromotionName=false&doSearchMethod=false&internetOnlySearch=false&findNearestForNoResults=false&returnEvent=false&returnCoupon=false&pageSize=1000&getHoursForUpcomingWeek=true&startIndex=0&languageCode=en&InterfaceID=26391&fillAttr=false&showAllLocationsPerCountry=false&showNearestLocationsInList=false&ClientId=8638&ApiKey=55786055-3909-40f2-856d-469ddf685f14',
            'headers' => [
                'accept' => 'application/json, text/plain, */*',
                'accept-language' => 'en-US,en;q=0.9',
                'user-agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36'
            ],
        ],
        'acuity' => [
            'agents_url' => 'https://www.acuitybrands.com/api/howtobuy/getfromsource/agents?filter=true%20eq%20true',
            'headers' => [
                'accept' => 'application/json, text/plain, */*',
                'accept-language' => 'en-US,en;q=0.9',
                'user-agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36'
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Major Cities for API Queries
    |--------------------------------------------------------------------------
    |
    | List of major US cities used when querying APIs that require location data
    |
    */

    'major_cities' => [
        // New York
        ['city' => 'New York', 'state' => 'NY'],
        ['city' => 'Buffalo', 'state' => 'NY'],
        ['city' => 'Rochester', 'state' => 'NY'],
        ['city' => 'Syracuse', 'state' => 'NY'],
        ['city' => 'Albany', 'state' => 'NY'],
        ['city' => 'Yonkers', 'state' => 'NY'],

        // California
        ['city' => 'Los Angeles', 'state' => 'CA'],
        ['city' => 'San Diego', 'state' => 'CA'],
        ['city' => 'San Jose', 'state' => 'CA'],
        ['city' => 'San Francisco', 'state' => 'CA'],
        ['city' => 'Sacramento', 'state' => 'CA'],
        ['city' => 'Fresno', 'state' => 'CA'],
        ['city' => 'Long Beach', 'state' => 'CA'],
        ['city' => 'Oakland', 'state' => 'CA'],
        ['city' => 'Bakersfield', 'state' => 'CA'],
        ['city' => 'Anaheim', 'state' => 'CA'],

        // Illinois
        ['city' => 'Chicago', 'state' => 'IL'],
        ['city' => 'Aurora', 'state' => 'IL'],
        ['city' => 'Naperville', 'state' => 'IL'],
        ['city' => 'Peoria', 'state' => 'IL'],
        ['city' => 'Springfield', 'state' => 'IL'],
        ['city' => 'Rockford', 'state' => 'IL'],

        // Texas
        ['city' => 'Houston', 'state' => 'TX'],
        ['city' => 'San Antonio', 'state' => 'TX'],
        ['city' => 'Dallas', 'state' => 'TX'],
        ['city' => 'Austin', 'state' => 'TX'],
        ['city' => 'Fort Worth', 'state' => 'TX'],
        ['city' => 'El Paso', 'state' => 'TX'],
        ['city' => 'Arlington', 'state' => 'TX'],
        ['city' => 'Corpus Christi', 'state' => 'TX'],
        ['city' => 'Plano', 'state' => 'TX'],
        ['city' => 'Lubbock', 'state' => 'TX'],

        // Arizona
        ['city' => 'Phoenix', 'state' => 'AZ'],
        ['city' => 'Tucson', 'state' => 'AZ'],
        ['city' => 'Mesa', 'state' => 'AZ'],
        ['city' => 'Chandler', 'state' => 'AZ'],
        ['city' => 'Scottsdale', 'state' => 'AZ'],
        ['city' => 'Glendale', 'state' => 'AZ'],

        // Pennsylvania
        ['city' => 'Philadelphia', 'state' => 'PA'],
        ['city' => 'Pittsburgh', 'state' => 'PA'],
        ['city' => 'Allentown', 'state' => 'PA'],
        ['city' => 'Erie', 'state' => 'PA'],
        ['city' => 'Reading', 'state' => 'PA'],
        ['city' => 'Scranton', 'state' => 'PA'],

        // Ohio
        ['city' => 'Columbus', 'state' => 'OH'],
        ['city' => 'Cleveland', 'state' => 'OH'],
        ['city' => 'Cincinnati', 'state' => 'OH'],
        ['city' => 'Toledo', 'state' => 'OH'],
        ['city' => 'Akron', 'state' => 'OH'],
        ['city' => 'Dayton', 'state' => 'OH'],

        // North Carolina
        ['city' => 'Charlotte', 'state' => 'NC'],
        ['city' => 'Raleigh', 'state' => 'NC'],
        ['city' => 'Greensboro', 'state' => 'NC'],
        ['city' => 'Durham', 'state' => 'NC'],
        ['city' => 'Winston-Salem', 'state' => 'NC'],
        ['city' => 'Fayetteville', 'state' => 'NC'],

        // Indiana
        ['city' => 'Indianapolis', 'state' => 'IN'],
        ['city' => 'Fort Wayne', 'state' => 'IN'],
        ['city' => 'Evansville', 'state' => 'IN'],
        ['city' => 'South Bend', 'state' => 'IN'],
        ['city' => 'Carmel', 'state' => 'IN'],
        ['city' => 'Bloomington', 'state' => 'IN'],

        // Washington
        ['city' => 'Seattle', 'state' => 'WA'],
        ['city' => 'Spokane', 'state' => 'WA'],
        ['city' => 'Tacoma', 'state' => 'WA'],
        ['city' => 'Vancouver', 'state' => 'WA'],
        ['city' => 'Bellevue', 'state' => 'WA'],
        ['city' => 'Everett', 'state' => 'WA'],

        // Colorado
        ['city' => 'Denver', 'state' => 'CO'],
        ['city' => 'Colorado Springs', 'state' => 'CO'],
        ['city' => 'Aurora', 'state' => 'CO'],
        ['city' => 'Fort Collins', 'state' => 'CO'],
        ['city' => 'Lakewood', 'state' => 'CO'],
        ['city' => 'Boulder', 'state' => 'CO'],

        // Massachusetts
        ['city' => 'Boston', 'state' => 'MA'],
        ['city' => 'Worcester', 'state' => 'MA'],
        ['city' => 'Springfield', 'state' => 'MA'],
        ['city' => 'Cambridge', 'state' => 'MA'],
        ['city' => 'Lowell', 'state' => 'MA'],
        ['city' => 'New Bedford', 'state' => 'MA'],

        // Georgia
        ['city' => 'Atlanta', 'state' => 'GA'],
        ['city' => 'Augusta', 'state' => 'GA'],
        ['city' => 'Columbus', 'state' => 'GA'],
        ['city' => 'Savannah', 'state' => 'GA'],
        ['city' => 'Athens', 'state' => 'GA'],
        ['city' => 'Macon', 'state' => 'GA'],

        // Florida
        ['city' => 'Jacksonville', 'state' => 'FL'],
        ['city' => 'Miami', 'state' => 'FL'],
        ['city' => 'Tampa', 'state' => 'FL'],
        ['city' => 'Orlando', 'state' => 'FL'],
        ['city' => 'St. Petersburg', 'state' => 'FL'],
        ['city' => 'Hialeah', 'state' => 'FL'],
        ['city' => 'Fort Lauderdale', 'state' => 'FL'],
        ['city' => 'Tallahassee', 'state' => 'FL'],

        // Michigan
        ['city' => 'Detroit', 'state' => 'MI'],
        ['city' => 'Grand Rapids', 'state' => 'MI'],
        ['city' => 'Warren', 'state' => 'MI'],
        ['city' => 'Sterling Heights', 'state' => 'MI'],
        ['city' => 'Ann Arbor', 'state' => 'MI'],
        ['city' => 'Lansing', 'state' => 'MI'],

        // Oregon
        ['city' => 'Portland', 'state' => 'OR'],
        ['city' => 'Salem', 'state' => 'OR'],
        ['city' => 'Eugene', 'state' => 'OR'],
        ['city' => 'Gresham', 'state' => 'OR'],
        ['city' => 'Hillsboro', 'state' => 'OR'],
        ['city' => 'Beaverton', 'state' => 'OR'],

        // Nevada
        ['city' => 'Las Vegas', 'state' => 'NV'],
        ['city' => 'Henderson', 'state' => 'NV'],
        ['city' => 'Reno', 'state' => 'NV'],
        ['city' => 'North Las Vegas', 'state' => 'NV'],
        ['city' => 'Sparks', 'state' => 'NV'],
        ['city' => 'Carson City', 'state' => 'NV'],

        // Minnesota
        ['city' => 'Minneapolis', 'state' => 'MN'],
        ['city' => 'St. Paul', 'state' => 'MN'],
        ['city' => 'Rochester', 'state' => 'MN'],
        ['city' => 'Duluth', 'state' => 'MN'],
        ['city' => 'Bloomington', 'state' => 'MN'],
        ['city' => 'Brooklyn Park', 'state' => 'MN'],

        // Louisiana
        ['city' => 'New Orleans', 'state' => 'LA'],
        ['city' => 'Baton Rouge', 'state' => 'LA'],
        ['city' => 'Shreveport', 'state' => 'LA'],
        ['city' => 'Lafayette', 'state' => 'LA'],
        ['city' => 'Lake Charles', 'state' => 'LA'],
        ['city' => 'Monroe', 'state' => 'LA'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Field Mapping for CSV Export
    |--------------------------------------------------------------------------
    |
    | Maps API response fields to standardized field names
    |
    */

    'field_mapping' => [
        'signify' => [
            'Name' => 'company_name',
            'PhoneNumber' => 'company_phone',
            'Address1' => 'address_line_1',
            'Address2' => 'address_line_2',
            'PostCode' => 'zip_code',
            'City' => 'city_or_region',
            'State' => 'state',
            'CountryName' => 'country',
            'URL' => 'website',
            'EmailAddress' => 'email',
            'ContactName' => 'contact_name',
            'MobileNumber' => 'contact_phone'
        ],
        'cooper' => [
            'Name' => 'company_name',
            'PhoneNumber' => 'company_phone',
            'Address1' => 'address_line_1',
            'Address2' => 'address_line_2',
            'PostCode' => 'zip_code',
            'City' => 'city_or_region',
            'State' => 'state',
            'CountryName' => 'country',
            'URL' => 'website',
            'EmailAddress' => 'email',
            'ContactName' => 'contact_name',
            'MobileNumber' => 'contact_phone'
        ],
        'acuity' => [
            'Business' => 'company_name',
            'Phone' => 'company_phone',
            'AddressLine1' => 'address_line_1',
            'AddressLine2' => 'address_line_2',
            'PostalCode' => 'zip_code',
            'Locality' => 'city_or_region',
            'AdminDistrict' => 'state',
            'CountryRegion' => 'country', 
            'Web' => 'website'
        ],
    ],
];