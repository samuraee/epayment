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

		$this->loadTranslationsFrom(
			__DIR__ . '/../../translations', 'epayment'
		);

		$views  = __DIR__ . '/../../views/';

		$this->loadViewsFrom($views, 'epayment');

		$this->publishes([
			$views => base_path('resources/views/vendor/epayment'),
		], 'views');
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