<?php
namespace Tartan\Epayment\Adapter;

use Illuminate\Contracts\Logging\Log;
use SoapClient;
use SoapFault;
use Tartan\Epayment\Adapter\Saman\Exception;

class Saman extends AdapterAbstract implements AdapterInterface
{
	protected $WSDL         = 'https://sep.shaparak.ir/payments/referencepayment.asmx?WSDL';
	protected $tokenWSDL    = 'https://sep.shaparak.ir/Payments/InitPayment.asmx?WSDL';

	protected $endPoint     = 'https://sep.shaparak.ir/Payment.aspx';

	protected $testWSDL     = 'https://banktest.ir/gateway/saman/ws?wsdl';
	protected $testEndPoint = 'https://banktest.ir/gateway/saman/gate';

	protected $reverseSupport = true;

	/**
	 * @return array
	 * @throws Exception
	 */
	protected function requestToken()
	{
		if($this->getInvoice()->checkForRequestToken() == false) {
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

			Log::debug('RequestToken call', $sendParams);

			$response = $soapClient->__soapCall('RequestToken', $sendParams);

			if (!empty($response))
			{
				Log::info('RequestToken response', ['response' => $response]);

				if (strlen($response) > 10) { // got string token
					$this->getInvoice()->setReferenceId($response); // update invoice reference id
				} else {
					throw new Exception($response); // negative integer as error
				}
			}
			else {
				throw new Exception('epayment::epayment.invalid_response');
			}

		} catch (SoapFault $e) {
			throw new Exception('SoapFault: ' . $e->getMessage() . ' #' . $e->getCode(), $e->getCode());
		}
	}

	public function generateForm()
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

		return view('epayment::saman-form', [
			'endPoint'    => $this->getEndPoint(),
			'amount'      => intval($this->amount),
			'merchantId'  => $this->merchant_id,
			'orderId'     => $this->order_id,
			'redirectUrl' => $this->redirect_url,
			'submitLabel' => !empty($this->submit_label) ? $this->submit_label : trans("epayment::epayment.goto_gate"),
			'autoSubmit'  => boolval($this->auto_submit),
		]);
	}

	protected function generateFormWithToken()
	{
		$this->checkRequiredParameters([
			'merchant_id',
			'order_id',
			'amount',
			'redirect_url',
		]);

		$token = $this->requestToken();

		return view('epayment::saman-form', [
			'endPoint'    => $this->getEndPoint(),
			'token'       => $token,
			'redirectUrl' => $this->redirect_url,
			'submitLabel' => !empty($this->submit_label) ? $this->submit_label : trans("epayment::epayment.goto_gate"),
			'autoSubmit'  => boolval($this->auto_submit),
		]);
	}

	protected function verifyTransaction()
	{
		if($this->getInvoice()->checkForVerify() == false) {
			throw new Exception('epayment::epayment.could_not_verify_payment');
		}

		$this->checkRequiredParameters([
			'State',
			'RefNum',
			'ResNum',
			'merchant_id',
			'TraceNo',
		]);

		if ($this->State != 'OK') {
			throw new Exception('Error: ' . $this->State);
		}

		try {
			$soapClient = new SoapClient($this->getWSDL());

			Log::info('VerifyTransaction call', [$this->RefNum, $this->merchant_id]);
			$response = $soapClient->VerifyTransaction($this->RefNum, $this->merchant_id);

			if (isset($response))
			{
				Log::info('VerifyTransaction response', ['response' => $response]);

				if ($response == $this->getInvoice()->getAmount()) { // check by invoice amount
					$this->getInvoice()->setVerified();
					return true;
				} else {
					throw new Exception($response);
				}
			}
			else {
				throw new Exception('epayment::epayment.invalid_response');
			}

		} catch (SoapFault $e) {
			throw new Exception('SoapFault: ' . $e->getMessage() . ' #' . $e->getCode(), $e->getCode());
		}
	}

	protected function reverseTransaction()
	{
		if ($this->reverseSupport == false || $this->getInvoice()->checkForReverse() == false) {
			throw new Exception('epayment::epayment.could_not_reverse_payment');
		}

		$this->checkRequiredParameters([
			'ref_id',
			'merchant_id',
			'password',
			'amount'
		]);

		try {
			$soapClient = new SoapClient($this->getWSDL());

			Log::info('reverseTransaction call', [$this->RefNum, $this->merchant_id]);
			$response = $soapClient->reverseTransaction(
				$this->ref_id,
				$this->merchant_id,
				$this->password,
				$this->amount
			);

			if (isset($response))
			{
				Log::info('reverseTransaction response', ['response' => $response]);

				if ($response === 1) { // check by invoice amount
					$this->getInvoice()->setReversed();
					return true;
				} else {
					throw new Exception($response);
				}
			}
			else {
				throw new Exception('epayment::epayment.invalid_response');
			}

		} catch (SoapFault $e) {
			throw new Exception('SoapFault: ' . $e->getMessage() . ' #' . $e->getCode(), $e->getCode());
		}
	}

	/**
	 * @return bool
	 */
	public function canContinueWithCallbackParameters()
	{
		if ($this->State == 'OK') {
			return true;
		}
		return false;
	}

	public function getGatewayReferenceId()
	{
		$this->checkRequiredParameters([
			'RefNum',
		]);
		return $this->RefNum;
	}

	protected function getWSDL ($type = null)
	{
		if (config('epayment.mode') == 'production') {
			switch (strtoupper($type)) {
				case 'TOKEN':
					return $this->tokenWSDL;
					break;
				default:
					return $this->WSDL;
					break;
			}
		} else {
			return $this->testWSDL;
		}
	}
}
