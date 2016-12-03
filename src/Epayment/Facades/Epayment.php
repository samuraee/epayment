<?php
namespace Tartan\Epayment\Facades;
use Illuminate\Support\Facades\Facade;

/**
 * Class Epayment
 * @package Tartan\Epayment\Facades
 * @author Tartan <iamtartan@gmail.com>
 */
class Epayment extends Facade
{
	protected static function getFacadeAccessor()
	{
		return 'Tartan\Epayment\Factory';
	}
}