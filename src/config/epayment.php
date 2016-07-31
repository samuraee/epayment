<?php

return [
	/*
	|--------------------------------------------------------------------------
	| Operation mode
	|--------------------------------------------------------------------------
	|
	| Available Settings: "production", "test"
	|
	*/
	'mode' => env('EPAYMENT_MODE', 'production'),

	'saman' => [
		'terminal_id'   => env('SAMAN_TERMINAL_ID', 'merchantId'),
		'terminal_pass' => env('SAMAN_TERMINAL_PASS', 'merchantPass')
	],

	'mellat' => [
		'terminal_id'   => env('MELLAT_TERMINAL_ID', 'terminalId'),
		'terminal_user' => env('MELLAT_USERNAME', 'merchantUser'),
		'terminal_pass' => env('MELLAT_PASSWORD', 'merchantPass'),
	],

	'zarinpal' => [
		'terminal_id'   => env('ZARINPAL_TERMINAL_ID', 'merchantCode'),
	],

	'pasargad' => [],
	'melli' => [],

	'saderat' => [],
	'payline' => [],
];