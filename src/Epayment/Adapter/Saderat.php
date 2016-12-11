<?php
namespace Tartan\Epayment\Adapter;

use SoapClient;
use SoapFault;
use Tartan\Epayment\Adapter\Saderat\Exception;
use Illuminate\Support\Facades\Log;

class Saderat extends AdapterAbstract implements AdapterInterface
{
	protected $WSDL = 'https://mabna.shaparak.ir/TokenService?wsdl';
	protected $endPoint = 'https://mabna.shaparak.ir';

	public function init()
	{
		if (!file_exists($this->public_key_path)) {
			throw new Exception('epayment::epayment.saderat.errors.public_key_file_not_found');
		}

		if (!file_exists($this->private_key_path)) {
			throw new Exception('epayment::epayment.saderat.errors.private_key_file_not_found');
		}

		$this->public_key = trim(file_get_contents($this->public_key_path));
		$this->private_key = trim(file_get_contents($this->private_key_path));

		Log::debug('public key: '. $this->public_key_path . ' --- ' .substr($this->public_key, 0, 64));
		Log::debug('private key: '. $this->private_key_path. ' --- ' .substr($this->private_key, 0, 64));
	}

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
			'MID',
			'TID',
			'public_key',
			'private_key',
			'amount',
			'order_id',
			'redirect_url',
		]);

		$sendParams = [
			"Token_param" => [
				"AMOUNT"        => $this->encryptText(intval($this->amount)),
				"CRN"           => $this->encryptText($this->order_id),
				"MID"           => $this->encryptText($this->MID),
				"REFERALADRESS" => $this->encryptText($this->redirect_url),
				"SIGNATURE"     => $this->makeSignature(),
				"TID"           => $this->encryptText($this->TID),
			]
		];

		try {
			Log::debug('reservation call', $sendParams);

			$soapClient = new SoapClient($this->getWSDL());

			$response = $soapClient->__soapCall('reservation', $sendParams);

			if (is_object($response)) {
				$response = $this->obj2array($response);
			}
			Log::debug('reservation raw response', $response);

			if (isset($response['return'])) {
				Log::info('reservation response', $response['return']);

				if ($response['return']['result'] != 0) {
					throw new Exception($response["return"]["token"]);
				}

				if (isset($response['return']['signature'])) {

					/**
					 * Final signature is created
					 */
					$signature = base64_decode($response['return']['signature']);

					/**
					 * State whether signature is okay or not
					 */
					$keyResource  = openssl_get_publickey($this->public_key);
					$verifyResult = openssl_verify($response["return"]["token"], $signature, $keyResource);

					if ($verifyResult == 1) {
						$this->getInvoice()->setReferenceId($response["return"]["token"]); // update invoice reference id
						return $response["return"]["token"];
					} else {
						throw new Exception('epayment::epayment.saderat.errors.invalid_verify_result');
					}
				}
				else {
					throw new Exception($response["return"]["result"]);
				}
			} else {
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
		$token = $this->requestToken();

		return view('epayment::mellat-form', [
			'endPoint'    => $this->getEndPoint(),
			'token'       => $token,
			'submitLabel' => !empty($this->submit_label) ? $this->submit_label : trans("epayment::epayment.goto_gate"),
			'autoSubmit'  => boolval($this->auto_submit)
		]);
	}

	private function encryptText()
	{
		/**
		 * get key resource to start based on public key
		 */
		$keyResource = openssl_get_publickey($this->public_key);

		openssl_public_encrypt($this->amount, $encryptedText, $keyResource);

		return $encryptedText;
	}

	private function makeSignature()
	{
		/**
		 * Make a signature temporary
		 * Note: each paid has it's own specific signature
		 */
		$source = $this->amount . $this->order_id . $this->MID . $this->redirect_url . $this->TID;

		/**
		 * Sign data and make final signature
		 */
		$signature = '';

		$privateKey = openssl_pkey_get_private($this->private_key);

		if (!openssl_sign($source, $signature, $privateKey, OPENSSL_ALGO_SHA1)) {
			throw new Exception('epayment::epayment.saderat.errors.making_openssl_sign_error');
		}

		return base64_encode($signature);
	}
}
