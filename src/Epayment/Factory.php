<?php
namespace Tartan\Epayment;

use Tartan\Epayment\Adapter\AdapterInterface;
use Tartan\Epayment\Transaction\TransactionInterface;
use Illuminate\Support\Facades\Log;

class Factory
{
	/**
	 * @var AdapterInterface
	 */
	protected $gateway;
	/**
	 * @param $adapter
	 * @param TransactionInterface $invoice
	 *
	 * @return $this
	 * @throws \Tartan\Epayment\Exception
	 */
	public function make($adapter, TransactionInterface $invoice)
	{
		$adapter = ucfirst(strtolower($adapter));

		/**
		 *  check for supported gateways
		 */
		$readyToServerGateways = explode(',', config('epayment.gateways'));

		Log::debug('selected gateway [' . $adapter .']');
		Log::debug('available gateways', $readyToServerGateways);

		if (!in_array($adapter, $readyToServerGateways)) {
			throw new Exception(trans('epayment::epayment.gate_not_ready'));
		}

		$adapterNamespace = 'Tartan\Epayment\Adapter\\';
		$adapterName  = $adapterNamespace . $adapter;

		if (!class_exists($adapterName)) {
			throw new Exception("Adapter class '$adapterName' does not exist");
		}

		$config = config('epayment.'.strtolower($adapter));
		Log::debug('init gateway config', $config);

		$bankAdapter = new $adapterName($invoice, $config);

		if (!$bankAdapter instanceof AdapterInterface) {
			throw new Exception(trans('epayment::epayment.gate_not_ready'));
		}

		// setting soapClient options if required
		if (config('epayment.soap.useOptions') == true) {
            $bankAdapter->setSoapOptions(config('epayment.soap.options'));
        }

		$this->gateway = $bankAdapter;

		return $this;
	}

	public function __call ($name, $arguments)
	{
		if (empty($this->gateway)) {
			throw new Exception("Gateway not defined before! please use make method to initialize gateway");
		}

		Log::info($name, $arguments);

		// چو ن همیشه متد ها با یک پارامتر کلی بصورت آرایه فراخوانی میشوند. مثلا:
		// $paymentGatewayHandler->generateForm($ArrayOfExtraPaymentParams)
		if (count($arguments) > 0) {
			$this->gateway->setParameters($arguments[0]); // set parameters
		}

		try {
			return call_user_func_array([$this->gateway, $name], $arguments); // call desire method
		} catch (\Exception $e) {
			Log::error($e->getMessage() .' #'.$e->getCode(). ' File:'.$e->getFile().':'.$e->getLine());
			throw $e;
		}
	}
}
