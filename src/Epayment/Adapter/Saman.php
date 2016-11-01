<?php
namespace Tartan\Epayment\Adapter;

use SoapClient;
use SoapFault;

class Saman extends AdapterAbstract
{

	protected $_WSDL             = 'https://sep.shaparak.ir/ref-payment/ws/ReferencePayment?WSDL';
	protected $_END_POINT        = 'https://sep.shaparak.ir/CardServices/controller';

    protected $_TEST_WSDL        = 'http://banktest.ir/gateway/saman/ws?wsdl';
    protected $_TEST_END_POINT   = 'http://banktest.ir/gateway/saman/gate';

    public $reverseSupport = true;

    public $validateReturnsAmount = true;

	public function setOptions(array $options = array())
	{
		parent::setOptions($options);
		foreach ($this->_config as $name => $value) {
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

    public function getInvoiceId()
    {
        return $this->order_id;
    }

    public function getReferenceId()
    {
        return $this->ref_id;
    }

    public function getStatus()
    {
        return $this->state;
    }

    public function doGenerateForm(array $options = array())
    {
	    if ($this->with_token) {
		    return $this->doGenerateFormWithToken($options);
	    } else {
		    return $this->doGenerateFormWithoutToken($options); // default
	    }
    }
    public function doGenerateFormWithoutToken(array $options = array())
    {
        $this->setOptions($options);
        $this->_checkRequiredOptions(['amount', 'terminal_id', 'order_id', 'redirect_url']);

        $action = $this->getEndPoint();

        $form  = sprintf('<form id="goto-gate-form" method="post" action="%s">', $action );
        $form .= sprintf('<input type="hidden" name="Amount" value="%d">', $this->amount);
        $form .= sprintf('<input type="hidden" name="MID" value="%s">', $this->terminal_id);
        $form .= sprintf('<input type="hidden" name="ResNum" value="%s">', $this->order_id);
        $form .= sprintf('<input type="hidden" name="RedirectURL" value="%s">', $this->redirect_url);

        if (isset($this->logo_uri)) {
            $form .= sprintf('<input name="LogoURI" value="%s">', $this->logo_uri);
        }

        $label = $this->submit_label ? $this->submit_label : trans("epayment::epayment.goto_gate");

        $form .= sprintf('<div class="control-group"><div class="controls"><input type="submit" class="btn btn-success" value="%s"></div></div>', $label);

        $form .= '</form>';

        return $form;
    }

	public function doGenerateFormWithToken(array $options = array())
	{
		$this->setOptions($options);
		$this->_checkRequiredOptions(['amount', 'terminal_id', 'order_id', 'redirect_url']);

		$action = $this->getEndPoint();

		try {
			$this->_log($this->getWSDL());
			$soapClient = new SoapClient($this->getWSDL());

			$sendParams = array(
				'pin'         => $this->terminal_id,
				'amount'      => $this->amount,
				'orderId'     => $this->order_id
			);

			$res = $soapClient->__soapCall('PinPaymentRequest', $sendParams);

		} catch (SoapFault $e) {
			$this->log($e->getMessage());
			throw new Exception('SOAP Exception: ' . $e->getMessage());
		}

		$form  = sprintf('<form id="goto-bank-form" method="post" action="%s" class="form-horizontal">', $action );
		$form .= sprintf('<input name="Amount" value="%d">', $this->amount);
		$form .= sprintf('<input name="MID" value="%s">', $this->terminal_id);
		$form .= sprintf('<input name="ResNum" value="%s">', $this->order_id);
		$form .= sprintf('<input name="RedirectURL" value="%s">', $this->redirect_url);

		if (isset($this->logo_uri)) {
			$form .= sprintf('<input name="LogoURI" value="%s">', $this->logo_uri);
		}

		$label = $this->submit_label ? $this->submit_label : trans("epayment::epayment.goto_gate");

		$form .= sprintf('<div class="control-group"><div class="controls"><input type="submit" class="btn btn-success" value="%s"></div></div>', $label);

		$form .= '</form>';

		return $form;
	}

    public function doVerifyTransaction(array $options = array())
    {
        $this->setOptions($options);
        $this->_checkRequiredOptions(['ref_id', 'terminal_id', 'state']);

        if ($this->ref_id == '') {
	        throw new Exception('Error: ' . $this->state);
        }

        try {
            $soapClient = new SoapClient($this->getWSDL());

            $res = $soapClient->VerifyTransaction(
                $this->ref_id, $this->terminal_id
            );
        } catch (SoapFault $e) {
            $this->_log($e->getMessage());
            throw new Exception('SOAP Exception: ' . $e->getMessage());
        }

        return (int) $res;
    }

    public function doReverseTransaction(array $options = array())
    {
        $this->setOptions($options);
        $this->_checkRequiredOptions(['ref_id', 'terminal_id', 'password', 'amount']);

        try {
            $soapClient = new SoapClient($this->getWSDL());

            $res = $soapClient->reverseTransaction(
                $this->ref_id,
                $this->terminal_id,
                $this->password,
                $this->amount
            );
        } catch (SoapFault $e) {
            $this->_log($e->getMessage());
            throw new Exception('SOAP Exception: ' . $e->getMessage());
        }

        return (int) $res;
    }
}
