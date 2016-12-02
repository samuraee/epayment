<?php
namespace Tartan\Epayment\Adapter;

use SoapClient;
use SoapFault;
use Tartan\Epayment\Adapter\Parsian\Exception;

class Parsian extends AdapterAbstract implements AdapterInterface
{
	protected $WSDL = 'https://pec.shaparak.ir/pecpaymentgateway/eshopservice.asmx?WSDL';
	protected $endPoint = 'https://pec.shaparak.ir/pecpaymentgateway';

	protected $testWSDL = 'http://banktest.ir/gateway/parsian/ws?wsdl';
	protected $testEndPoint = 'http://banktest.ir/gateway/parsian/gate';

	protected $reverseSupport = true;
	protected $validateReturnsAmount = false;

	/**
	 * @return array
	 * @throws Exception
	 */
	public function requestToken ()
	{
		if ($this->getInvoice()->checkForRequestToken() == false) {
			throw new Exception('epayment::epayment.could_not_request_payment');
		}

		$this->checkRequiredParameters([
			'terminal_id',
			'order_id',
			'amount',
			'redirect_url',
		]);

		$sendParams = [
			'pin'         => $this->terminal_id,
			'amount'      => intval($this->amount),
			'orderId'     => $this->order_id,
			'callbackUrl' => $this->redirect_url,
			'authority'   => $this->authority ? $this->authority : 0, //default authority
			'status'      => $this->status ? $this->status : 1, //default status
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

	/**
	 * @return mixed
	 */
	public function generateForm ()
	{
		$authority = $this->requestToken();

		return view('epayment::parsian-form', [
			'endPoint'    => $this->getEndPoint(),
			'refId'       => $authority,
			'submitLabel' => !empty($this->submit_label) ? $this->submit_label : trans("epayment::epayment.goto_gate"),
			'autoSubmit'  => boolval($this->auto_submit)
		]);
	}

	/**
	 * @return bool
	 * @throws Exception
	 */
	public function verifyTransaction ()
	{
		if ($this->getInvoice()->checkForVerify() == false) {
			throw new Exception('epayment::epayment.could_not_verify_payment');
		}

		$this->checkRequiredParameters([
			'terminal_id',
			'au',
			'rs'
		]);

		if ($this->rs !== '0') {
			throw new Exception('epayment::parsian.errors.could_not_continue_with_non0_rs');
		}

		$sendParams = [
			'pin'       => $this->terminal_id,
			'authority' => $this->au,
			'status'    => 1
		];

		try {
			$soapClient = $this->getSoapClient();
			$sendParams = array(
				'pin'       => $this->terminal_id,
				'authority' => $this->getReferenceId(),
				'status'    => 1
			);
			$response   = $soapClient->__soapCall('PinPaymentEnquiry', $sendParams);

			if (isset($response->status)) {
				if ($response->status == 0) {
					return true;
				}
				else {
					throw new Exception($response->status);
				}
			}
			else {
				throw new Exception('epayment::parsian.errors.invalid_response');
			}

		} catch (SoapFault $e) {
			throw new Exception('SoapFault: ' . $e->getMessage() . ' #' . $e->getCode(), $e->getCode());
		}
	}


	/**
	 * @return bool
	 * @throws Exception
	 */
	public function reverseTransaction ()
	{
		if ($this->reverseSupport == false || $this->getInvoice()->checkForReverse() == false) {
			throw new Exception('epayment::epayment.could_not_reverse_payment');
		}

		$this->checkRequiredParameters([
			'terminal_id',
			'order_id',
			'reverse_order_id',
			'authority'
		]);

		$sendParams = [
			'pin'             => $this->terminal_id,
			'orderId'         => $this->reverse_order_id,
			'orderToReversal' => $this->order_id,
			'status'          => 1,
		];

		try {
			$soapClient = new SoapClient($this->getWSDL());
			$response   = $soapClient->__soapCall('PinReversal', $sendParams);

			if (isset($response->status)) {
				if ($response->status == 0) {
					$this->setInvoiceReversed();

					return true;
				}
				else {
					throw new Exception($response->status);
				}
			}
			else {
				throw new Exception('epayment::parsian.errors.invalid_response');
			}
		} catch (SoapFault $e) {
			throw new Exception('SoapFault: ' . $e->getMessage() . ' #' . $e->getCode(), $e->getCode());
		}
	}
}
