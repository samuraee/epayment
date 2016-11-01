<?php
namespace Tartan\Epayment\Adapter;

use SoapClient;
use SoapFault;
use stdClass;

class Parsian extends AdapterAbstract
{
	protected $_WSDL = 'https://pec.shaparak.ir/pecpaymentgateway/eshopservice.asmx?WSDL';
	protected $_END_POINT = 'https://pec.shaparak.ir/pecpaymentgateway';

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
						$this->order_id = $value;
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
		return $this->order_id;
	}

	public function getReferenceId ()
	{
		return $this->authority;
	}

	public function getStatus ()
	{
		return $this->state;
	}

	public function doGenerateForm (array $options = [])
	{
		$this->setOptions($options);
		$this->_checkRequiredOptions(['terminal_id', 'amount', 'order_id', 'redirect_address']);

		if (!$this->status) {
			$this->status = 1;
		}

		if (!$this->authority) {
			$this->authority = 0; //default authority
		}

		try {
			$this->_log($this->getWSDL());
			$soapClient = new SoapClient($this->getWSDL());

			$sendParams = array(
				'pin'         => $this->terminal_id,
				'amount'      => $this->amount,
				'orderId'     => $this->order_id,
				'callbackUrl' => $this->redirect_address,
				'authority'   => $this->authority,
				'status'      => $this->status
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
			$form  = sprintf('<form id="goto-bank-form" method="get" action="%s">', $this->getEndPoint());
			$form .= sprintf('<input type="hidden" name="au" value="%s" />', $authority);
			$label = $this->submit_label ? $this->submit_label : trans("epayment::epayment.goto_gate");
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
		$this->_checkRequiredOptions(['terminal_id', 'authority']);

		try {
			$soapClient = new SoapClient($this->getWSDL());
			$sendParams = array(
				'pin'       => $this->terminal_id,
				'authority' => $this->authority,
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
		$this->_checkRequiredOptions(['terminal_id', 'order_id', 'authority']);
		try {
			$soapClient         = new SoapClient($this->getWSDL());
			$c                  = new stdClass();
			$c->pin             = $this->terminal_id;
			$c->status          = 1;
			$c->orderId         = $this->reverse_order_id;
			$c->orderToReversal = $this->order_id;

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
