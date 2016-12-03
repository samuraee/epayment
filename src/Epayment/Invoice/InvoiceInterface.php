<?php
namespace Tartan\Epayment\Invoice;

interface InvoiceInterface
{
	public function setReferenceId($referenceId);

	public function checkForRequestToken();

	public function checkForVerify();

	public function checkForInquiry();

	public function checkForReverse();

	public function checkForSettle();

	public function setCardNumber($cardNumber);

	public function setVerified();

	public function setCompleted();

	public function setReversed();

	public function getAmount();

	public function setPaidAt($time = 'now');
}