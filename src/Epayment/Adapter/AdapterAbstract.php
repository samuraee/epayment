<?php
namespace Tartan\Epayment\Adapter;

use SoapClient;
use Tartan\Epayment\Invoice\InvoiceInterface;

abstract class AdapterAbstract
{
	protected $endPoint     = null;
	protected $WSDL         = null;

	protected $testWSDL     = null;
	protected $testEndPoint = null;

	protected $parameters  = [];
	protected $soapOptions = [];

	protected $reverseSupport = false;
	protected $validateReturnsAmount = false;

	protected $invoice;

	/**
	 * AdapterAbstract constructor.
	 *
	 * @param InvoiceInterface $invoice
	 * @param array $configs
	 *
	 * @throws Exception
	 */
	public function __construct (InvoiceInterface $invoice, array $configs = [])
	{
		$this->invoice = $invoice;

		if ($this->invoice->checkCanRequestToken() == false) {
			throw new Exception('could not handle this invoice payment');
		}

		$this->setParameters($configs);
	}

	/**
	 * @param string $key
	 * @param mixed $val
	 */
	public function __set ($key, $val)
	{
		$this->parameters[$key] = $val;
	}

	/**
	 * @param string $key
	 *
	 * @return mixed|null
	 */
	public function __get ($key)
	{
		return isset($this->parameters[$key]) ? trim($this->parameters[$key]) : null;
	}


	/**
	 * @return InvoiceInterface
	 */
	public function getInvoice ()
	{
		return $this->invoice;
	}

	/**
	 * @param array $parameters
	 *
	 * @return $this
	 */
	public function setParameters (array $parameters = [])
	{
		foreach ($parameters as $key => $value) {
			$this->parameters[$key] = $value;
		}
		return $this;
	}

	/**
	 * @return array
	 */
	public function getParameters ()
	{
		return $this->parameters;
	}

	/**
	 * check for required parameters
	 *
	 * @param array $parameters
	 *
	 * @throws Exception
	 */
	protected function checkRequiredParameters (array $parameters)
	{
		foreach ($parameters as $parameter) {
			if (!array_key_exists($parameter, $this->parameters)) {
				throw new Exception("Parameters array must have a key for '$parameter'");
			}
		}
	}

	/**
	 * @return string
	 */
	public function getWSDL ()
	{
		if (config('epayment.mode') == 'production') {
			return $this->WSDL;
		} else {
			return $this->testWSDL;
		}
	}

	/**
	 * @return string
	 */
	public function getEndPoint ()
	{
		if (config('epayment.mode') == 'production') {
			return $this->endPoint;
		} else {
			return $this->testEndPoint;
		}
	}

	/**
	 * @return bool
	 */
	public function reverseSupport()
	{
		return (bool) $this->reverseSupport;
	}

	/**
	 * @return bool
	 */
	public function validateReturnsAmount()
	{
		return (bool) $this->validateReturnsAmount;
	}

	/**
	 * @param array $options
	 *
	 * 'login'       => config('api.basic.username'),
	 * 'password'    => config('api.basic.password'),
	 * 'proxy_host' => 'localhost',
	 * 'proxy_port' => '8080'
	 *
	 */
	public function setSoapOptions(array $options = [])
	{
		$this->soapOptions = $options;
	}

	/**
	 * @return SoapClient
	 */
	public function getSoapClient()
	{
		return new SoapClient($this->getWSDL(), $this->soapOptions);
	}

	/**
	 * @throws Exception
	 */
	public function generateForm ( ){
		throw new Exception(__METHOD__ . ' not implemented');
	}

	/**
	 * @throws Exception
	 */
	public function verifyTransaction (){
		throw new Exception(__METHOD__ . ' not implemented');
	}

	/**
	 * @throws Exception
	 */
	public function reverseTransaction (){
		throw new Exception(__METHOD__ . ' not implemented');
	}

	/**
	 * @throws Exception
	 */
	public function settleTransaction (){
		throw new Exception(__METHOD__ . ' not implemented');
	}

	/**
	 * set invoice reference id
	 * @param $referenceId
	 */
	protected function setInvoiceReferenceId($referenceId)
	{
		$this->getInvoice()->setReferenceId($referenceId); // update invoice reference id
	}

	/**
	 * @param $cardNumber
	 */
	protected function setInvoiceCardNumber($cardNumber)
	{
		$this->getInvoice()->setCardNumber($this->CardHolderInfo);
	}

	/**
	 *  set invoice status to verified
	 */
	protected function setInvoiceVerified()
	{
		$this->getInvoice()->setVerified();
	}

	/**
	 * set invoice status to completed
	 */
	protected function setInvoiceCompleted()
	{
		$this->getInvoice()->setCompleted();
	}

	/**
	 * set invoice status to reversed
	 */
	protected function setInvoiceReversed()
	{
		$this->getInvoice()->setReversed();
	}
}
