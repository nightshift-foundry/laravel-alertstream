<?php

namespace NightshiftFoundry\AlertStream\Tests;

use NightshiftFoundry\AlertStream\Providers\AlertStreamServiceProvider;
use NightshiftFoundry\AlertStream\Services\AlertStreamService;
use Orchestra\Testbench\TestCase;

class AlertStreamServiceTest extends TestCase
{
    /**
     * Test that the service can be resolved from the container.
     */
    public function test_service_is_resolvable(): void
    {
        $service = $this->app->make(AlertStreamService::class);
        $this->assertInstanceOf(AlertStreamService::class, $service);
    }

    /**
     * Test that the service is registered as singleton.
     */
    public function test_service_is_singleton(): void
    {
        $service1 = $this->app->make(AlertStreamService::class);
        $service2 = $this->app->make(AlertStreamService::class);

        $this->assertSame($service1, $service2);
    }

    /**
     * Test that configuration is correctly merged.
     */
    public function test_configuration_is_merged(): void
    {
        $config = $this->app['config']->get('alertstream');
        $this->assertTrue($config['enabled']);
        $this->assertContains('single', $config['log_channels']);
    }

    /**
     * Test getting configuration values.
     */
    public function test_get_config(): void
    {
        $service = $this->app->make(AlertStreamService::class);
        $this->assertTrue($service->getConfig('enabled'));
        $this->assertNull($service->getConfig('non_existent_key'));
        $this->assertEquals('default_value', $service->getConfig('non_existent_key', 'default_value'));
    }

    /**
     * Get package providers.
     *
     * @param \Illuminate\Foundation\Application $app
     *
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            AlertStreamServiceProvider::class,
        ];
    }

    /**
     * Define environment setup.
     *
     * @param \Illuminate\Foundation\Application $app
     */
    protected function defineEnvironment($app): void
    {
        $app['config']->set('alertstream.enabled', true);
        $app['config']->set('alertstream.log_channels', ['single']);
    }
}
