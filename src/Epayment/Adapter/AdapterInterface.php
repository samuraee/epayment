<?php
namespace Tartan\Epayment\Adapter;

interface AdapterInterface
{
    public function generateForm($options);

    public function verifyTransaction($options);

    public function reverseTransaction($options);
}
