<?php
namespace Tartan\Epayment;

use Illuminate\Support\ServiceProvider;

class EpaymentServiceProvider extends ServiceProvider
{
	/**
	 * Register the application services.
	 *
	 * @return void
	 */
	public function register()
	{
	}
	/**
	 * Perform post-registration booting of services.
	 *
	 * @return void
	 */
	public function boot()
	{
		$this->publishes([
			__DIR__ . '/../config/epayment.php' => config_path('epayment.php')
		], 'config');

		$this->loadTranslationsFrom(
			__DIR__ . '/../lang', 'epayment'
		);
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