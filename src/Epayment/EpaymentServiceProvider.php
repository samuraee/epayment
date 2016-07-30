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
		$configPath = __DIR__ . '/../../config/epayment.php';
		$this->mergeConfigFrom($configPath, 'epayment');
	}
	/**
	 * Perform post-registration booting of services.
	 *
	 * @return void
	 */
	public function boot()
	{
		$configPath = __DIR__ . '/../../config/epayment.php';
		$this->publishes([$configPath => config_path('epayment.php')], 'config');
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