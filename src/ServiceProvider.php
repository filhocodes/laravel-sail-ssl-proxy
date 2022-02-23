<?php
declare(strict_types=1);

namespace FilhoCodes\LaravelSailSslProxy;

use FilhoCodes\LaravelSailSslProxy\ArtisanCommands\InstallCommand;
use FilhoCodes\LaravelSailSslProxy\Http\Controllers\SailSslProxyController;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Foundation\CachesRoutes;
use Illuminate\Contracts\Routing\Registrar;
use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;

/**
 * ServiceProvider
 */
final class ServiceProvider extends IlluminateServiceProvider
{
    /**
     * ServiceProvider->boot()
     *
     * @throws BindingResolutionException
     */
    public function boot(): void
    {
        $this->registerArtisanCommands();
        $this->registerConfig();
        $this->registerRoutes();
    }

    /**
     * ServiceProvider->registerArtisanCommands()
     */
    private function registerArtisanCommands(): void
    {
        if (!$this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            InstallCommand::class,
        ]);
    }

    /**
     * ServiceProvider->registerConfig()
     */
    private function registerConfig(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/filhocodes-ssl-proxy.php', 'filhocodes-ssl-proxy');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/filhocodes-ssl-proxy.php' => $this->app->configPath('filhocodes-ssl-proxy.php'),
            ], 'filhocodes-ssl-proxy-config');
        }
    }

    /**
     * ServiceProvider->registerRoutes()
     *
     * @throws BindingResolutionException
     */
    private function registerRoutes(): void
    {
        if (! ($this->app instanceof CachesRoutes && $this->app->routesAreCached())) {
            /** @var Registrar $router */
            $router = $this->app->make('router');

            /** @var ConfigRepository $config */
            $config = $this->app->make('config');

            $router->get($config->get('filhocodes-ssl-proxy.authorization_route'), SailSslProxyController::class);
        }
    }
}
