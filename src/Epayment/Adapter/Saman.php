<?php
namespace Tartan\Epayment\Adapter;

use SoapClient;
use SoapFault;
use Tartan\Epayment\Adapter\Parsian\Exception;

class Saman extends AdapterAbstract
{
//	protected $WSDL         = 'https://sep.shaparak.ir/ref-payment/ws/ReferencePayment?WSDL';
//	protected $endPoint     = 'https://sep.shaparak.ir/CardServices/controller';
	protected $WSDL         = 'https://sep.shaparak.ir/Payments/InitPayment.asmx?WSDL';
	protected $endPoint     = 'https://sep.shaparak.ir/Payment.aspx';

	protected $testWSDL     = 'http://banktest.ir/gateway/saman/ws?wsdl';
	protected $testEndPoint = 'http://banktest.ir/gateway/saman/gate';

	protected $reverseSupport = true;
	protected $validateReturnsAmount = true;

	public function setParameters(array $options = array())
	{
		parent::setParameters($options);

		foreach ($this->getParameters() as $name => $value) {
			switch ($name) {
				case 'resnum':
						$this->order_id = $value;
					break;
				case 'refnum':
						$this->ref_id = $value;
					break;
			}
		}
	}

	/**
	 * @return array
	 * @throws Exception
	 */
	protected function requestToken ()
	{
		if ($this->getInvoice()->checkForRequestToken() == false) {
			throw new Exception('epayment::epayment.could_not_request_payment');
		}

		$this->checkRequiredParameters([
			'merchant_id',
			'order_id',
			'amount',
			'redirect_url',
		]);

		$sendParams = [
			'TermID'      => $this->merchant_id,
			'TotalAmount' => intval($this->amount),
			'ResNum'      => $this->order_id,
		];

		try {
			$soapClient = new SoapClient($this->getWSDL());

			$response = $soapClient->__soapCall('PinPaymentRequest', $sendParams);

			if (isset($response->status, $response->authority)) {
				$this->setInvoiceReferenceId($response); // update invoice reference id
				if ($response->status == 0) {
					return $response->authority;
				}
				else {
					throw new Exception($this->status);
				}
			}
			else {
				throw new Exception('epayment::parsian.errors.invalid_response');
			}
		} catch (SoapFault $e) {
			throw new Exception('SoapFault: ' . $e->getMessage() . ' #' . $e->getCode(), $e->getCode());
		}
	}

	protected function generateForm()
	{
		if ($this->with_token) {
			return $this->generateFormWithToken();
		} else {
			return $this->generateFormWithoutToken(); // default
		}
	}
	protected function generateFormWithoutToken()
	{
		$this->checkRequiredParameters([
			'merchant_id',
			'amount',
			'order_id',
			'redirect_url'
		]);

		return view('epayment::saman-redirector', [
			'endPoint'    => $this->getEndPoint(),
			'amount'      => intval($this->amount),
			'merchantId'  => $this->merchant_id,
			'orderId'     => $this->order_id,
			'redirectUrl' => $this->redirect_url,
			'submitLabel' => !empty($this->submit_label) ? $this->submit_label : trans("epayment::epayment.goto_gate"),
			'autoSubmit'  => boolval($this->auto_submit),
		]);
	}

	protected function generateFormWithToken(array $options = array())
	{
		$this->checkRequiredParameters([
			'merchant_id',
			'order_id',
			'amount',
			'redirect_url',
		]);

		$token = $this->requestToken();

		return view('epayment::saman-redirector', [
			'endPoint'    => $this->getEndPoint(),
			'token'       => $token,
			'redirectUrl' => $this->redirect_url,
			'submitLabel' => !empty($this->submit_label) ? $this->submit_label : trans("epayment::epayment.goto_gate"),
			'autoSubmit'  => boolval($this->auto_submit),
		]);
	}

	protected function verifyTransaction()
	{
		$this->checkRequiredParameters([
			'State',
			'RefNum',
			'ResNum',
			'MID',
			'TraceNo',
		]);

		if ($this->State != 'OK') {
			throw new Exception('Error: ' . $this->getStatus());
		}

		try {
			$soapClient = new SoapClient($this->getWSDL());

			$res = $soapClient->VerifyTransaction(
				$this->RefNum, $this->merchant_id
			);
		} catch (SoapFault $e) {
			$this->_log($e->getMessage());
			throw new Exception('SOAP Exception: ' . $e->getMessage());
		}

		return (int) $res;
	}

	protected function reverseTransaction(array $options = array())
	{
		$this->setParameters($options);
		$this->_checkRequiredOptions([
			'ref_id',
			'merchant_id',
			'password',
			'amount'
		]);

		try {
			$soapClient = new SoapClient($this->getWSDL());

			$res = $soapClient->reverseTransaction(
				$this->ref_id,
				$this->merchant_id,
				$this->password,
				$this->amount
			);
		} catch (SoapFault $e) {
			$this->_log($e->getMessage());
			throw new Exception('SOAP Exception: ' . $e->getMessage());
		}

		return (int) $res;
	}


	public function getGatewayReferenceId()
	{
		$this->checkRequiredParameters([
			'RefNum',
		]);
		return $this->RefNum;
	}
}
