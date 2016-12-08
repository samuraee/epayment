<?php
namespace Tartan\Epayment\Invoice;

interface InvoiceInterface
{
	public function setReferenceId($referenceId);

	public function checkForRequestToken();

	public function checkForVerify();

	public function checkForInquiry();

	public function checkForReverse();

	public function checkForAfterVerify();

	public function setCardNumber($cardNumber);

	public function setVerified();

	public function setAfterVerified();

	public function setSuccessful();

	public function setReversed();

	public function getAmount();

	public function setPaidAt($time = 'now');
}