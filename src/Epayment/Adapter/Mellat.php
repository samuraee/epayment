<?php
namespace Tartan\Epayment\Adapter;

use SoapClient;
use SoapFault;
use Tartan\Epayment\Adapter\Mellat\Exception;

class Mellat extends AdapterAbstract implements AdapterInterface
{
	protected $WSDL = 'https://bpm.shaparak.ir/pgwchannel/services/pgw?wsdl';
	protected $endPoint = 'https://pgw.bpm.bankmellat.ir/pgwchannel/startpay.mellat';

	protected $testWSDL = 'http://banktest.ir/gateway/mellat/ws?wsdl';
	protected $testEndPoint = 'http://banktest.ir/gateway/mellat/gate';

	protected $reverseSupport = true;
	protected $validateReturnsAmount = false;

	/**
	 * @return array
	 * @throws Exception
	 */
	public function requestToken()
	{
		if($this->getInvoice()->checkForRequestToken() == false) {
			throw new Exception('epayment::epayment.could_not_request_payment');
		}

		$this->checkRequiredParameters([
			'terminal_id',
			'username',
			'password',
			'order_id',
			'amount',
			'redirect_url',
		]);

		$sendParams = [
			'terminalId'     => $this->terminal_id,
			'userName'       => $this->username,
			'userPassword'   => $this->password,
			'orderId'        => $this->order_id,
			'amount'         => intval($this->amount),
			'localDate'      => $this->local_date ? $this->local_date : date('Ymd'),
			'localTime'      => $this->local_time ? $this->local_time : date('His'),
			'additionalData' => $this->additional_data ? $this->additional_data : '',
			'callBackUrl'    => $this->redirect_url,
			'payerId'        => intval($this->payer_id),
		];

		try {
			$soapClient = new SoapClient($this->getWSDL());

			$response = $soapClient->bpPayRequest($sendParams);

			if (isset($response->return)) {
				$response = explode(',', $response->return);

				if ($response[0] == 0) {
					$this->setInvoiceReferenceId($response[1]); // update invoice reference id
					return $response[1];
				}
				else {
					throw new Exception($response[0]);
				}
			} else {
				throw new Exception('epayment::epayment.mellat.errors.invalid_response');
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
		$refId = $this->requestToken();

		return view('epayment::mellat-form', [
			'endPoint'    => $this->getEndPoint(),
			'refId'       => $refId,
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
		if($this->getInvoice()->checkForVerify() == false) {
			throw new Exception('epayment::epayment.could_not_verify_payment');
		}

		$this->checkRequiredParameters([
			'terminal_id',
			'terminal_user',
			'terminal_pass',
			'RefId',
			'ResCode',
			'SaleOrderId',
			'SaleReferenceId',
			'CardHolderInfo'
		]);

		$sendParams = [
			'terminalId'      => $this->terminal_id,
			'userName'        => $this->username,
			'userPassword'    => $this->password,
			'orderId'         => $this->SaleOrderId, // same as SaleOrderId
			'saleOrderId'     => $this->SaleOrderId,
			'saleReferenceId' => $this->SaleReferenceId
		];

		$this->setInvoiceCardNumber($this->CardHolderInfo);

		try {
			$soapClient = new SoapClient($this->getWSDL());
			$response   = $soapClient->bpVerifyRequest($sendParams);

			if (isset($response->return)) {
				if($response->return != '0') {
					throw new Exception($response->return);
				} else {
					$this->setInvoiceVerified();
					return true;
				}
			} else {
				throw new Exception('epayment::epayment.mellat.errors.invalid_response');
			}

		} catch (SoapFault $e) {

			throw new Exception('SoapFault: ' . $e->getMessage() . ' #' . $e->getCode(), $e->getCode());
		}
	}

	/**
	 * @return bool
	 * @throws Exception
	 */
	public function inquiryTransaction ()
	{
		if($this->getInvoice()->checkForInquiry() == false) {
			throw new Exception('epayment::epayment.could_not_inquiry_payment');
		}

		$this->checkRequiredParameters([
			'terminal_id',
			'terminal_user',
			'terminal_pass',
			'RefId',
			'ResCode',
			'SaleOrderId',
			'SaleReferenceId',
			'CardHolderInfo'
		]);

		$sendParams = [
			'terminalId'      => $this->terminal_id,
			'userName'        => $this->username,
			'userPassword'    => $this->password,
			'orderId'         => $this->SaleOrderId, // same as SaleOrderId
			'saleOrderId'     => $this->SaleOrderId,
			'saleReferenceId' => $this->SaleReferenceId
		];

		$this->setInvoiceCardNumber($this->CardHolderInfo);

		try {
			$soapClient = new SoapClient($this->getWSDL());
			$response   = $soapClient->bpInquiryRequest($sendParams);

			if (isset($response->return)) {
				if($response->return != '0') {
					throw new Exception($response->return);
				} else {
					$this->setInvoiceVerified();
					return true;
				}
			} else {
				throw new Exception('epayment::epayment.mellat.errors.invalid_response');
			}

		} catch (SoapFault $e) {

			throw new Exception('SoapFault: ' . $e->getMessage() . ' #' . $e->getCode(), $e->getCode());
		}
	}

	/**
	 * Send settle request
	 *
	 * @return bool
	 *
	 * @throws Exception
	 * @throws SoapFault
	 */
	public function settleTransaction()
	{
		if ($this->getInvoice()->checkForSettle() == false) {
			throw new Exception('epayment::epayment.could_not_settle_payment');
		}

		$this->checkRequiredParameters([
			'terminal_id',
			'terminal_user',
			'terminal_pass',
			'RefId',
			'ResCode',
			'SaleOrderId',
			'SaleReferenceId',
			'CardHolderInfo'
		]);

		$sendParams = [
			'terminalId'      => $this->terminal_id,
			'userName'        => $this->username,
			'userPassword'    => $this->password,
			'orderId'         => $this->SaleOrderId, // same as orderId
			'saleOrderId'     => $this->SaleOrderId,
			'saleReferenceId' => $this->SaleReferenceId
		];

		try {
			$soapClient = new SoapClient($this->getWSDL());
			$response = $soapClient->bpSettleRequest($sendParams);

			if (isset($response->return)) {
				if($response->return == '0' || $response->return == '45') {
					$this->setInvoiceCompleted();

					return true;
				} else {
					throw new Exception($response->return);
				}
			} else {
				throw new Exception('epayment::epayment.mellat.errors.invalid_response');
			}

		} catch (\SoapFault $e) {
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
			'terminal_user',
			'terminal_pass',
			'RefId',
			'ResCode',
			'SaleOrderId',
			'SaleReferenceId',
			'CardHolderInfo'
		]);

		$sendParams = [
			'terminalId'      => $this->terminal_id,
			'userName'        => $this->username,
			'userPassword'    => $this->password,
			'orderId'         => $this->SaleOrderId, // same as orderId
			'saleOrderId'     => $this->SaleOrderId,
			'saleReferenceId' => $this->SaleReferenceId
		];

		try {
			$soapClient = new SoapClient($this->getWSDL());
			$response = $soapClient->__soapCall('bpReversalRequest', $sendParams);

			if (isset($response->return)){
				if ($response->return == '0' || $response->return == '45') {
					$this->setInvoiceReversed();
					return true;
				} else {
					throw new Exception($response->return);
				}
			} else {
				throw new Exception('epayment::epayment.mellat.errors.invalid_response');
			}

		} catch (SoapFault $e) {
			throw new Exception('SoapFault: ' . $e->getMessage() . ' #' . $e->getCode(), $e->getCode());
		}
	}


}
