<?php
namespace Tartan\Epayment\Adapter;

interface AdapterInterface
{
	public function setParameters(array $parameters = []);

    public function form();

    public function verify();

    public function reverse();

    public function getGatewayReferenceId();
}
