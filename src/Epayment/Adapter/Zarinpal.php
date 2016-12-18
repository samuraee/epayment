<?php
namespace Tartan\Epayment\Adapter;

use SoapClient;
use SoapFault;
use Tartan\Epayment\Adapter\Zarinpal\Exception;
use Illuminate\Support\Facades\Log;

class Zarinpal extends AdapterAbstract implements AdapterInterface
{
	protected $WSDL = 'https://www.zarinpal.com/pg/services/WebGate/wsdl';

	protected $endPoint = 'https://www.zarinpal.com/pg/StartPay/‫‪{authority}‬‬';
	protected $zarinEndPoint = 'https://www.zarinpal.com/pg/StartPay/‫‪{authority}‫‪/ZarinGate‬‬‬‬';
	protected $mobileEndPoint = '‫‪https://www.zarinpal.com/pg/StartPay/‫‪{authority}/MobileGate‬‬‬‬‬‬';


	protected $testWSDL = 'https://sandbox.zarinpal.com/pg/services/WebGate/wsdl';
	protected $testEndPoint = 'https://sandbox.zarinpal.com/pg/StartPay/‫‪{authority}‬‬';

//	protected $testWSDL = 'https://banktest.ir/gateway/zarinpal/ws?wsdl';
//	protected $testEndPoint = 'https://banktest.ir/gateway/zarinpal/gate/pay_invoice/‫‪{authority}';

	public $reverseSupport = false;

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
			'amount',
//			'description',
//			'email',
//			'mobile',
			'redirect_url',
		]);

		$sendParams = [
			'MerchantID'  => $this->merchant_id,
			'Amount'      => intval($this->amount),
			'Description' => $this->description ? $this->description : '',
			'Email'       => $this->email ? $this->email : '',
			'Mobile'      => $this->mobile ? $this->mobile : '',
			'CallbackURL' => $this->redirect_url,
		];

		try {
			$soapClient = new SoapClient($this->getWSDL());

			Log::debug('PaymentRequest call', $sendParams);

			$response = $soapClient->PaymentRequest($sendParams);

			Log::info('PaymentRequest response', $this->obj2array($response));


			if (isset($response->Status)) {

				if ($response->Status == 100) {
					$this->getInvoice()->setReferenceId($response->Authority); // update invoice reference id
					return $response->Authority;
				}
				else {
					throw new Exception($response->Status);
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
	 * @return mixed
	 */
	protected function generateForm ()
	{
		$authority = $this->requestToken();

		return view('epayment::zarinpal-form', [
			'endPoint'    => strtr($this->getEndPoint(), ['{authority}' => $authority]),
			'submitLabel' => !empty($this->submit_label) ? $this->submit_label : trans("epayment::epayment.goto_gate"),
			'autoSubmit'  => boolval($this->auto_submit)
		]);
	}
}
