<?php
namespace Tartan\Epayment\Adapter;

use SoapClient;
use Tartan\Epayment\Invoice\InvoiceInterface;

abstract class AdapterAbstract
{
	protected $endPoint;
	protected $WSDL;

	protected $testWSDL;
	protected $testEndPoint;

	/**
	 * @var array
	 */
	protected $parameters  = [];
	/**
	 * @var array
	 */
	protected $soapOptions = [];

	/**
	 * @var InvoiceInterface
	 */
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

		if ($this->invoice->checkForRequestToken() == false) {
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

	public function form()
	{
		return $this->generateForm();
	}

	public function verify()
	{
		return $this->verifyTransaction();
	}

	public function reverse()
	{
		return $this->reverseTransaction();
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
	protected function getWSDL ()
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
	protected function getEndPoint ()
	{
		if (config('epayment.mode') == 'production') {
			return $this->endPoint;
		} else {
			return $this->testEndPoint;
		}
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
	protected function setSoapOptions(array $options = [])
	{
		$this->soapOptions = $options;
	}

	/**
	 * @return SoapClient
	 */
	protected function getSoapClient()
	{
		return new SoapClient($this->getWSDL(), $this->soapOptions);
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

	/**
	 * @return mixed
	 * @throws Exception
	 */
	public function getGatewayReferenceId()
	{
		throw new Exception(__METHOD__ . ' not implemented');
	}
}
