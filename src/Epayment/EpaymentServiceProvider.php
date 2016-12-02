<?php
namespace Tartan\Epayment;

use Illuminate\Support\ServiceProvider;

class EpaymentServiceProvider extends ServiceProvider
{
	/**
	 * Perform post-registration booting of services.
	 *
	 * @return void
	 */
	public function boot()
	{
		$this->publishes([
			__DIR__ . '/../../config/epayment.php' => config_path('epayment.php')
		], 'config');

		$this->publishes([
			__DIR__ . '/../../views/' => resource_path('/views/vendor/epayment'),
		], 'views');

		$this->loadViewsFrom(__DIR__ . '/../../views/', 'epayment');

		$this->publishes([
			__DIR__ . '/../../translations/' => resource_path('lang/vendor/epayment'),
		], 'translations');

		$this->loadTranslationsFrom(__DIR__ . '/../../translations', 'epayment');

	}
	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return [];
	}
}