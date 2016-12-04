<?php

namespace Tartan\Epayment\Adapter\Mellat;

class Exception extends \Tartan\Epayment\Adapter\Exception
{
	const UNHANDLED_ERR = 999;

	public function __construct($message = "", $code = 0, Exception $previous = null)
	{
		switch ($message)
		{
			case is_numeric($message): {
				$code = $message;
				$message = trans('epayment::epayment.mellat.errors.error_' . strval($message)); // fetch message from translation file
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
