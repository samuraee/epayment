# epayment
All Iranian payment gateways handler


## Installation

1.Installing Via composer

```bash
composer require keraken/epayment:"dev-master"
```
2.Add this to your app service providers :

```php
Tartan\Epayment\EpaymentServiceProvider::class,
```
3.Add this to your aliases :

```php
'Epayment' => Tartan\Epayment\Facades\Epayment::class,
```
4.Publish the package assets and configs

```bash
php artisan vendor:publish
```

5. Preparing your db (eloquent) model for epayment integration

    * Your Transaction/Invoice (Eloquent) model MUST implement 

```php 
namespace App\Model;

use Tartan\Epayment\Transaction;

class Transaction extends Model implements TransactionInterface
{
	public function setReferenceId($referenceId, $save = true){}

	public function checkForRequestToken(){}

	public function checkForVerify(){}

	public function checkForInquiry(){}

	public function checkForReverse(){}

	public function checkForAfterVerify(){}

	public function setCardNumber($cardNumber){}

	public function setVerified(){}

	public function setAfterVerified(){}

	public function setSuccessful($flag){}

	public function setReversed(){}

	public function getAmount(){}

	public function setPaidAt($time = 'now'){}

	public function setExtra($key, $value, $save = false){}
}
```

