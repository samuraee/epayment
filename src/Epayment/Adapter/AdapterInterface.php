<?php
namespace Tartan\Epayment\Adapter;

interface AdapterInterface
{
	public function setParameters($parameters);

    public function generateForm();

    public function verifyTransaction();

    public function reverseTransaction();

	public function settleTransaction();
}
