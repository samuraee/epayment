<?php
namespace Tartan\Epayment;

use Tartan\Epayment\Adapter\AdapterInterface;
use Tartan\Epayment\Invoice\InvoiceInterface;

class Factory
{
	/**
	 * @var AdapterInterface
	 */
	protected $gateway;
	/**
	 * @param $adapter
	 * @param InvoiceInterface $invoice
	 *
	 * @return $this
	 * @throws \Tartan\Epayment\Exception
	 */
    public function make($adapter, InvoiceInterface $invoice)
    {
	    $adapter = ucfirst(strtolower($adapter));

	    /**
	     *  check for supported gateways
	     */
	    $readyToServerGateways = explode(',', config('epayment.gateways'));

	    if (!in_array($adapter, $readyToServerGateways)) {
		    throw new Exception(trans('epayment::epayment.gate_not_ready'));
	    }

        $adapterNamespace = 'Tartan\Epayment\Adapter\\';
        $adapterName  = $adapterNamespace . $adapter;

        if (!class_exists($adapterName)) {
            throw new Exception("Adapter class '$adapterName' does not exist");
        }

        $bankAdapter = new $adapterName($invoice, config('epayment.'.strtolower($adapter)));

        if (!$bankAdapter instanceof AdapterInterface) {
            throw new Exception(trans('epayment::epayment.gate_not_ready'));
        }

        $this->gateway = $bankAdapter;

	    return $this;
    }

	public function __call ($name, $arguments)
	{
		if (empty($this->gateway)) {
			throw new Exception("Gateway not defined before! please use make method to initialize gateway");
		}

		$this->gateway->setParameters($arguments); // set parameters

		return call_user_func_array([$this->gateway, $name], $arguments); // call desire method
	}
}
