<?php
namespace Tartan\Epayment\Adapter;

class Exception extends \Tartan\Epayment\Exception
{
	const UNHANDLED_ERR = 999;

	protected $adapter = 'epaymentadapter';

	public function __construct($message = "", $code = 0, Exception $previous = null)
	{
		$gate = explode('\\', $this->adapter );
		$gate = end($gate);
		$gate = strtolower($gate);

		switch ($message)
		{
			case is_numeric($message): {
				$code = $message;
				$message = 'epayment::epayment.'.$gate.'.errors.error_' . str_replace('-', '_', strval($message)); // fetch message from translation file
				break;
			}

			case preg_match('/^epayment::/', $message) == 1 : {
				$code = static::UNHANDLED_ERR;
				$message = trans(strval($message)); // fetch message from translation file
				break;
			}
		}

		parent::__construct($message, $code, $previous);
	}
}