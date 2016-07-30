<?php
namespace Tartan\Epayment;

use Tartan\Epayment\Adapter\AdapterInterface;
use Tartan\Epayment\Adapter\AdapterAbstract;

class Factory
{
    var $gateway;
    var $options;

    public function __construct(AdapterInterface $gateway, $options = array()
    ) {
        $this->gateway = $gateway;
        $this->options = $options;
    }

	/**
	 * @return string
	 */
    public function generateForm() {
        return $this->gateway->generateForm($this->options);
    }

	/**
	 * @return string
	 */
    public function verifyTransaction() {
        return $this->gateway->verifyTransaction($this->options);
    }

	/**
	 * @return string
	 */
    public function reverseTransaction() {
        return $this->gateway->reverseTransaction($this->options);
    }

	/**
	 * @param $adapter
	 * @param array $options
	 * @param array $banks
	 *
	 * @return \Tartan\Epayment\Adapter\AdapterAbstract
	 * @throws \Tartan\Epayment\Exception
	 */
    public static function made($adapter, array $options = [], array $banks = [])
    {
        if (!is_array($options)) {
            throw new Exception(
                'Bank parameters must be in an array'
            );
        }

        if (!is_array($banks)) {
            throw new Exception(
                'Available banks must be in an array'
            );
        }

        if (!is_string($adapter) || empty($adapter)) {
            throw new Exception(
                'Bank name must be specified in a string'
            );
        }

        if (count($banks) > 0 && !in_array($adapter, $banks)) {
            throw new Exception(
                ucfirst($adapter) .
                    " bank adapter might exist, but is not listed among available banks"
            );
        }

        $adapterNamespace = 'Tartan\Epayment\Adapter\\';
        $adapterName  = $adapterNamespace . ucfirst(strtolower($adapter));

        if (!class_exists($adapterName)) {
            throw new Exception(
                "Adapter class '$adapterName' does not exist"
            );
        }

        $bankAdapter = new $adapterName($options);

        if (!$bankAdapter instanceof AdapterAbstract) {
            throw new Exception(
                "Adapter class '$adapterName' does not extend \\Tartan\\Epayment\\Adapter\\AdapterAbstract"
            );
        }

        $bankAdapter->bank = $adapter;

        return $bankAdapter;
    }
}
