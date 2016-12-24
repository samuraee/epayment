<?php
namespace Tartan\Epayment\Adapter;

use Tartan\Epayment\Pasargad\Helper;
use Tartan\Epayment\Pasargad\RSAKeyType;
use Tartan\Epayment\Pasargad\RSAProcessor;

class Pasargad extends AdapterAbstract implements AdapterInterface
{

	protected $endPoint = 'https://pep.shaparak.ir/gateway.aspx';
	protected $checkTransactionUrl = 'https://pep.shaparak.ir/CheckTransactionResult.aspx';
	protected $verifyUrl = 'https://pep.shaparak.ir/VerifyPayment.aspx';
	protected $refundUrl = 'https://pep.shaparak.ir/doRefund.aspx';


	protected $testEndPoint = 'http://banktest.ir/gateway/pasargad/gate';
	protected $testCheckTransactionUrl = 'http://banktest.ir/gateway/pasargad/inquiry';
	protected $testVerifyUrl = 'http://banktest.ir/gateway/pasargad/verify';
	protected $testRefundUrl = 'http://banktest.ir/gateway/pasargad/refund';


	protected function generateForm()
	{
		$this->checkRequiredParameters([
			'amount',
			'order_id',
			'redirect_url'
		]);

		$processor = new RSAProcessor(config('epayment.pasargad.certificate_path'), RSAKeyType::XMLFile);

		$url           = $this->getEndPoint();
		$redirectUrl   = $this->redirect_url;
		$invoiceNumber = $this->order_id;
		$amount        = $this->amount;
		$terminalCode  = config('epayment.pasargad.terminalId');
		$merchantCode  = config('epayment.pasargad.merchantId');
		$timeStamp     = date("Y/m/d H:i:s");
		$invoiceDate   = date("Y/m/d H:i:s");
		$action        = 1003; // sell code

		$data          = "#" . $merchantCode . "#" . $terminalCode . "#" . $invoiceNumber . "#" . $invoiceDate . "#" . $amount . "#" . $redirectUrl . "#" . $action . "#" . $timeStamp . "#";
		$data          = sha1($data, true);
		$data          = $processor->sign($data); // امضاي ديجيتال
		$sign          = base64_encode($data); // base64_encode

		return view('epayment::pasargad-form')->with(compact(
			'url',
			'redirectUrl',
			'invoiceNumber',
			'invoiceDate',
			'amount',
			'terminalCode',
			'merchantCode',
			'timeStamp',
			'action',
			'sign'
		));
	}

//	public function inquiryTransaction ()
//	{
//
//	}

	protected function verifyTransaction()
	{
		$this->checkRequiredParameters([
			'iN',
			'iD',
			'tref',
		]);

		// update invoice transaction reference number
		if (!empty($this->tref)) {
			$this->setInvoiceReferenceId($this->tref); // update invoice reference id
		}

		$processor = new RSAProcessor(config('epayment.pasargad.certificate_path'), RSAKeyType::XMLFile);

		$terminalCode  = config('epayment.pasargad.terminalId');
		$merchantCode  = config('epayment.pasargad.merchantId');
		$invoiceNumber = $this->iN;
		$invoiceDate   = $this->iD;
		$amount        = $this->getInvoice()->getAmount();
		$timeStamp     = date("Y/m/d H:i:s");

		$data          = "#" . $merchantCode . "#" . $terminalCode . "#" . $invoiceNumber . "#" . $invoiceDate . "#" . $amount . "#" . $timeStamp . "#";
		$data          = sha1($data, true);
		$data          = $processor->sign($data); // امضاي ديجيتال
		$sign          = base64_encode($data); // base64_encode

		$parameters = compact(
			'terminalCode',
			'merchantCode',
			'invoiceNumber',
			'invoiceDate',
			'amount',
			'timeStamp',
			'sign'
		);

		$result = Helper::post2https($parameters , $this->getVerifyUrl());
		$array  = Helper::parseXML($result, [
			'invoiceNumber' => $this->iN,
			'invoiceDate'   => $this->iD
		]);


		if ($array['result'] != "True") {
			throw new Exception('epayment::epayment.verification_failed');
		} else {
			$this->getInvoice()->setCompleted();
			return true;
		}
	}

	protected function reverseTransaction()
	{
		$this->checkRequiredParameters([
			'iN',
			'iD',
			'tref',
		]);

		// update invoice transaction reference number
		if (!empty($this->tref)) {
			$this->setInvoiceReferenceId($this->tref); // update invoice reference id
		}

		$processor = new RSAProcessor(config('epayment.pasargad.certificate_path'), RSAKeyType::XMLFile);

		$terminalCode  = config('epayment.pasargad.terminalId');
		$merchantCode  = config('epayment.pasargad.merchantId');
		$invoiceNumber = $this->iN;
		$invoiceDate   = $this->iD;
		$amount        = $this->getInvoice()->getAmount();
		$timeStamp     = date("Y/m/d H:i:s");
		$action        = 1004; // reverse code

		$data          = "#" . $merchantCode . "#" . $terminalCode . "#" . $invoiceNumber . "#" . $invoiceDate . "#" . $amount . "#" . $action . "#" . $timeStamp . "#";
		$data          = sha1($data, true);
		$data          = $processor->sign($data); // امضاي ديجيتال
		$sign          = base64_encode($data); // base64_encode

		$parameters = compact(
			'terminalCode',
			'merchantCode',
			'invoiceNumber',
			'invoiceDate',
			'amount',
			'timeStamp',
			'action',
			'sign'
		);

		$result = Helper::post2https($parameters , $this->getRefundUrl());
		$array  = Helper::parseXML($result, [
			'invoiceNumber' => $this->iN,
			'invoiceDate'   => $this->iD
		]);

		if ($array['result'] != "True") {
			throw new Exception('epayment::epayment.reversed_failed');
		} else {
			$this->getInvoice()->setReversed();
			return true;
		}
	}

	protected function getVerifyUrl()
	{
		if (config('epayment.mode') == 'production') {
			return $this->verifyUrl;
		} else {
			return $this->testVerifyUrl;
		}
	}

	protected function getRefundUrl()
	{
		if (config('epayment.mode') == 'production') {
			return $this->refundUrl;
		} else {
			return $this->testRefundUrl;
		}
	}

	protected function getInquiryUrl()
	{
		if (config('epayment.mode') == 'production') {
			return $this->checkTransactionUrl;
		} else {
			return $this->testCheckTransactionUrl;
		}
	}
}