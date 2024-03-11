<?php

// Config

// Paths
$hostPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$formAction = rtrim($hostPath, '/') . '/#results';

// Default values of form
$defaultValues = [
    'method' => '0',
    'request-target' => 'post /pis/v2/connect?state=test',
    'date' => gmdate('D, d M Y H:i:s \G\M\T', time()),
    'x-request-id' => '963587a9-1fa4-42d2-bca8-85c27d0c859e',
    'app-id' => '2fa2be62-94b0-4e88-b089-b73cb1141de0',
    'payload' => <<<'EOD'
    { 
        "meta": {
            "psu_name" : "Bob McCheese", 
            "psu_email" : "bob@mccheese.com",
            "psu_phone" : "09743593535",
            "psu_address": {
                "street": "route de la france",
                "number": "33",
                "complement": "2nd floor",
                "zip": "12001",
                "city": "Paris",
                "country": "FR"
            }
        },
        "data": {
            "type" : "PIS", 
            "attributes" : {
                "amount" : "149.30", 
                "currency": "EUR", 
                "communication" : "Order 6543321"
            }
        }
    }
    EOD
];
