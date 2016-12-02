<?php
namespace Tartan\Epayment\Adapter;

class Zarinpal extends AdapterAbstract implements AdapterInterface
{
    const INCOMPLETE_DATA = 'Incomplete data';
    const INVALID_WEBSERVICE = 'Invalid webservice';
    const LOW_AMOUNT = 'Amount is less than Shetab`s valid value';
    const NOT_SILVER_USER_LEVEL = 'User is not in silver level';
    const UNKNOWN_REQUEST_RESPONSE = "Unknown PaymentRequest response";

	protected $_WSDL              = 'https://www.zarinpal.com/pg/services/WebGate/wsdl';

	protected $_WEB_GATE          = 'https://www.zarinpal.com/pg/StartPay/‫‪{authority}‬‬';
    protected $_ZARIN_GATE        = 'https://www.zarinpal.com/pg/StartPay/‫‪{authority}‫‪/ZarinGate‬‬‬‬';
    protected $_MOBILE_GATE       = '‫‪https://www.zarinpal.com/pg/StartPay/‫‪{authority}/MobileGate‬‬‬‬‬‬';

    protected $_TEST_WSDL        = 'http://banktest.ir/gateway/zarinpal/ws?wsdl';
    protected $_TEST_END_POINT   = 'http://banktest.ir/gateway/zarinpal/gate/pay_invoice/%s';



    public $reverseSupport = false;

    public $validateReturnsAmount = false;

	public function getWSDL ($secure = false)
	{
		if (config('app.env') == 'production')
		{
			if ($secure == true) {
				return $this->_SECURE_WSDL;
			}
			else {
				return $this->_WSDL;
			}
		}
		else {
			return $this->_TEST_WSDL;
		}
	}

	public function getEndPoint ($mobile = false)
	{
		if (config('app.env') == 'production')
		{
			if ($mobile == true) {
				return $this->_MOBILE_END_POINT;
			}
			else {
				return $this->_END_POINT;
			}
		}
		else {
			if ($mobile == true) {
				return $this->_TEST_MOBILE_END_POINT;
			}
			else {
				return $this->_TEST_END_POINT;
			}
		}
	}

    public function setOptions(array $options = array())
    {
        parent::setOptions($options);
        foreach ($this->_config as $name => $value) {
            switch ($name) {
            case 'in':
                if (preg_match('/^[a-z0-9]+$/', $value))
                    $this->reservationNumber = $value;
                break;
            case 'au':
                if (preg_match('/([a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12})/', $value))
                    $this->referenceId = $value;
                break;
            }
        }
    }

    public function getInvoiceId()
    {
        if (!isset($this->_config['reservationNumber'])) {
            return null;
        }
        return $this->_config['reservationNumber'];
    }

    public function getReferenceId()
    {
        if (!isset($this->_config['referenceId'])) {
            return null;
        }
        return $this->_config['referenceId'];
    }

    public function doGenerateForm(array $options = array())
    {
        $this->setOptions($options);
        $this->_checkRequiredOptions(array(
            'merchantCode', 'amount', 'reservationNumber', 'redirectAddress', 'description'
        ));

        try {
            $soapClient  = new SoapClient($this->getWSDL(), array('encoding'=>'UTF-8'));
            $merchantID  = $this->_config['merchantCode'];
            $amount      = $this->_config['amount'];
            $callBackUrl = $this->_config['redirectAddress'].'/in/'.$this->_config['reservationNumber'];
            $description = urlencode($this->_config['description']);

            $res = $soapClient->PaymentRequest($merchantID, $amount, $callBackUrl, $description);
        } catch (SoapFault $e) {
            $this->log($e->getMessage());
            throw new Exception('SOAP Exception: ' . $e->getMessage());
        }

        if (strlen($res) == 36)
        {

            $view = Zend_Layout::getMvcInstance()->getView();

            $form = '<form id="gotobank-form" method="post" action="' . sprintf($this->getEndPoint(), $res) . '" class="form-horizontal">';

            $label =  isset($this->_config['submitlabel']) ? $this->_config['submitlabel'] : '';

            $submit = sprintf('<div class="control-group"><div class="controls"><input type="submit" class="btn btn-success" value="%s"></div></div>', $label);

            $form .= $submit;
            $form .= '</form>';

            return $form;
        } else {
            switch ($res) {
                case "-1":
                    $msg = static::INCOMPLETE_DATA;
                    break;
                case "-2":
                    $msg = static::INVALID_WEBSERVICE;
                    break;
                case "-3":
                    $msg = static::LOW_AMOUNT;
                    break;
                case "-4":
                    $msg = static::NOT_SILVER_USER_LEVEL;
                    break;
                default:
                    $msg = static::UNKNOWN_REQUEST_RESPONSE;
                    break;
            }
            $this->log($e->getMessage());
            throw new Exception($msg, $res);
        }
    }

    public function doVerifyTransaction(array $options = array())
    {
        $this->setOptions($options);
        $this->_checkRequiredOptions(array('referenceId', 'merchantCode'));

        try {
            $soapClient   = new SoapClient($this->getWSDL());
            $invoice = Model_Invoice::id($this->getInvoiceId());
            $pin       = $this->_config['merchantCode'];
            $au        = $this->_config['referenceId'];
            $amount    = $invoice->price;
            $status    = 1;

            $res = $soapClient->PaymentVerification($pin, $au, $amount, $status);
        } catch (SoapFault $e) {
            $this->log($e->getMessage());
            throw new Exception('SOAP Exception: ' . $e->getMessage());
        }

        if ($res == 1)
            return 1; // Verified
        else
            return $res; // VerifyError
    }

    public function getStatus()
    {
        return false;
    }

    public function doReverseTransaction(array $options = array())
    {
        return false;
    }
}
