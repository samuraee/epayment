<?php

return [

	/*
	|--------------------------------------------------------------------------
	| Tartan e-payment component`s operation mode
	|--------------------------------------------------------------------------
	|
	| *** very important config ***
	| please do not change it if you don't know what BankTest is
	|
	| production: component operates with real payments gateways
	| development: component operates with simulated "Bank Test" (banktest.ir) gateways
	|
	*/
	'mode'     => env('EPAYMENT_MODE', 'production'),

	/*
	|--------------------------------------------------------------------------
	| ready to serve gateways
	|--------------------------------------------------------------------------
	|
	| specifies ready to serve gateways.
	| gateway characters are case sensitive and should be exactly same as their folder name.
	|    eg, "Pasargad" is correct not "PasarGad" or "pasargad"
	| the gateways list is comma separated
	|
	*/
	'gateways' => env('EPAYMENT_GATES', 'Mellat,Saman,Pasargad'),

    /*
    |--------------------------------------------------------------------------
    | reverse policy of epayment transactions
    |--------------------------------------------------------------------------
    |
    | specifies that should thew component reverse verified transactions if
    | serving to customer was not successful or not
    |
    */
    'reverse_mode' => env('PAYMENT_REVERSE_MODE', false),

	/*
	|--------------------------------------------------------------------------
	| Mellat gateway configuration
	|--------------------------------------------------------------------------
	*/
	'mellat'   => [
		'username'     => env('MELLAT_USERNAME', ''),
		'password'     => env('MELLAT_PASSWORD',''),
		'terminal_id'  => env('MELLAT_TERMINAL_ID', ''),
		'callback_url' => env('MELLAT_CALLBACK_URL', '')
	],

	/*
	|--------------------------------------------------------------------------
	| Parsian gateway configuration
	|--------------------------------------------------------------------------
	*/
	'parsian'  => [
		'pin'          => env('PARSIAN_PIN', ''),
	],
	/*
	|--------------------------------------------------------------------------
	| Pasargad gateway configuration
	|--------------------------------------------------------------------------
	*/
	'pasargad' => [
		'terminalId'       => env('PASARGAD_TERMINAL_ID', ''),
		'merchantId'       => env('PASARGAD_MERCHANT_ID', ''),
		'certificate_path' => storage_path(env('PASARGAD_CERT_PATH', 'payment/pasargad/certificate.xml')),
		'callback_url'     => env('PASARGAD_CALLBACK_URL', '')
	],

	/*
	|--------------------------------------------------------------------------
	| Payline gateway configuration
	|--------------------------------------------------------------------------
	*/
	'payline'  => [
		'api'          => env('PAYLINE_API', ''),
		'callback_url' => env('PAYLINE_CALLBACK_URL', ''),
	],

	/*
	|--------------------------------------------------------------------------
	| Sadad gateway configuration
	|--------------------------------------------------------------------------
	*/
	'sadad'    => [
		'merchant'        => env('SADAD_MERCHANT', ''),
		'transaction_key' => env('SADAD_TRANS_KEY', ''),
		'terminal_id'     => env('SADAD_TERMINAL_ID', ''),
		'callback_url'    => env('SADAD_CALLBACK_URL', ''),
	],

	'saderat' => [
		'MID' => env('SADERAT_MID', ''),
		'TID' => env('SADERAT_TID', ''),
		'public_key_path' => storage_path(env('SADERAT_CERT_PATH', 'payment/saderat/public.key')),
		'private_key_path' => storage_path(env('SADERAT_CERT_PATH', 'payment/saderat/private.key')),
	],

	/*
	|--------------------------------------------------------------------------
	| Saman gateway configuration
	|--------------------------------------------------------------------------
	*/
	'saman'    => [
		'merchant_id'   => env('SAMAN_MERCHANT_ID', ''),
		'merchant_pass' => env('SAMAN_MERCHANT_PASS', ''),
	],

	/*
	|--------------------------------------------------------------------------
	| Zarinpal gateway configuration
	|--------------------------------------------------------------------------
	|
	| types: acceptable values  --- zarin-gate or normal
	| server: acceptable values --- germany or iran or test
	|
	*/
	'zarinpal' => [
		'merchant_id'  => env('ZARINPAL_MERCHANT_ID', ''),
		'type'         => env('ZARINPAL_TYPE', 'zarin-gate'),
		'callback_url' => env('ZARINPAL_CALLBACK_URL', ''),
		'server'       => env('ZARINPAL_SERVER', 'germany'),
		'email'        => env('ZARINPAL_EMAIL', ''),
		'mobile'       => env('ZARINPAL_MOBILE', '09xxxxxxxxx'),
		'description'  => env('ZARINPAL_MOBILE', 'powered-by-TartanPayment'),
	],

    /*
    |--------------------------------------------------------------------------
    | SoapClient Options
    |--------------------------------------------------------------------------
    |
    | useOptions: true/false
    | options: soapClient Options
    |
    */
    'soap' => [
        'useOptions' => env('SOAP_HAS_OPTIONS', false),
        'options' => [
            'proxy_host' => env('SOAP_PROXY_HOST', ''),
            'proxy_port' => env('SOAP_PROXY_PORT', ''),
            'stream_context' => stream_context_create(
                [
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                    ],
                ]
            ),
        ]
    ]
];
