<?php
namespace Tartan\Epayment\Adapter;

use SoapClient;
use SoapFault;
use stdClass;

class Parsian extends AdapterAbstract
{
	protected $_WSDL = 'https://www.pec24.com/pecpaymentgateway/EShopService.asmx?WSDL';
	protected $_END_POINT = 'https://www.pec24.com/pecpaymentgateway';

	protected $_TEST_WSDL = 'http://banktest.ir/gateway/parsian/ws?wsdl';
	protected $_TEST_END_POINT = 'http://banktest.ir/gateway/parsian/gate';

	public $reverseSupport = true;

	public $validateReturnsAmount = false;

	public function setOptions (array $options = [])
	{
		parent::setOptions($options);
		foreach ($this->_config as $name => $value) {
			switch ($name) {
				case 'in':
					if (preg_match('/^[a-z0-9]+$/', $value))
						$this->reservationNumber = $value;
					break;
				case 'au':
					if (preg_match('/^[a-z0-9]+$/', $value))
						$this->authority = $value;
					break;
				case 'rs':
					if ($value !== '0') {
						throw new Exception('Invalid Request at ' . __METHOD__);
					}
					else {
						$this->state = '0';
					}
			}
		}
	}

	public function getInvoiceId ()
	{
		if (!isset($this->_config['orderId'])) {
			return null;
		}

		return $this->_config['orderId'];
	}

	public function getReferenceId ()
	{
		if (!isset($this->_config['authority'])) {
			return null;
		}

		return $this->_config['authority'];
	}

	public function getStatus ()
	{
		if (!isset($this->_config['state'])) {
			return null;
		}

		return $this->_config['state'];
	}

	public function doGenerateForm (array $options = [])
	{
		$this->setOptions($options);
		$this->_checkRequiredOptions(['merchantCode', 'amount', 'orderId', 'redirectAddress']);

		if (!isset($this->_config['status'])) {
			$this->_config['status'] = 1;
		}

		if (!isset($this->_config['authority'])) {
			$this->_config['authority'] = 0;
		}

		try {
			$this->_log($this->getWSDL());
			$soapClient = new SoapClient($this->getWSDL());

			$sendParams = array(
				'pin'         => $this->_config['merchantCode'],
				'amount'      => $this->_config['amount'],
				'orderId'     => $this->_config['orderId'],
				'callbackUrl' => $this->_config['redirectAddress'] . '/iN/' . $this->_config['orderId'],
				'authority'   => $this->_config['authority'],
				'status'      => $this->_config['status']
			);

			$res = $soapClient->__soapCall('PinPaymentRequest', $sendParams);

		} catch (SoapFault $e) {
			$this->_log($e->getMessage());
			throw new Exception('SOAP Exception: ' . $e->getMessage());
		}

		$status    = $res->status;
		$authority = $res->authority;

		if (($authority) && ($status == 0))
		{
			$form = sprintf('<form id="goto-bank-form" method="get" action="%s" class="form-horizontal">', $this->getEndPoint());
			$form .= sprintf('<input type="hidden" name="au" value="%s" />', $authority);

			$label = isset($this->_config['submitLabel']) ? $this->_config['submitLabel'] : trans("epayment::epayment.goto_gate");

			$form .= sprintf('<div class="control-group"><div class="controls"><input type="submit" class="btn btn-success" value="%s"></div></div>', $label);
			$form .= '</form>';

			return $form;
		} else {
			throw new Exception('Error: non 0 status : ' . $status);
		}
	}

	public function doVerifyTransaction (array $options = array())
	{
		$this->setOptions($options);
		$this->_checkRequiredOptions(['authority', 'merchantCode']);

		try {
			$soapClient = new SoapClient($this->getWSDL());
			$sendParams = array(
				'pin'       => $this->_config['merchantCode'],
				'authority' => $this->_config['authority'],
				'status'    => 1
			);
			$res        = $soapClient->__soapCall('PinPaymentEnquiry', $sendParams);
		} catch (SoapFault $e) {
			$this->_log($e->getMessage());
			throw new Exception('SOAP Exception: ' . $e->getMessage());
		}

		if ($res->status == 0)
			return 1;
		else
			return -1 * $res->status;
	}

	public function doReverseTransaction (array $options = array())
	{
		$this->setOptions($options);
		$this->_checkRequiredOptions(['merchantCode', 'orderId', 'authority']);
		try {
			$soapClient         = new SoapClient($this->getWSDL());
			$c                  = new stdClass();
			$c->pin             = $this->_config['merchantCode'];
			$c->status          = 1;
			$c->orderId         = $this->_config['reverseOrderId'];
			$c->orderToReversal = $this->_config['orderId'];

			$res = $soapClient->PinReversal($c);
		} catch (SoapFault $e) {
			$this->_log($e->getMessage());
			throw new Exception('SOAP Exception: ' . $e->getMessage());
		}

		if ($res->status == 0)
			return 1;
		else
			return $res->status;
	}
}
