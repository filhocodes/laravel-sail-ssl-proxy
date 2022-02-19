<?php
declare(strict_types=1);

namespace FilhoCodes\LaravelSailSslProxy\ArtisanCommands;

use FilhoCodes\LaravelSailSslProxy\Helpers\Logger;
use FilhoCodes\LaravelSailSslProxy\Helpers\Path;
use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Str;
use ParseError;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\Yaml\Yaml;
use Throwable;

/**
 * InstallCommand
 */
final class InstallCommand extends Command
{
    /**
     * InstallCommand->signature
     *
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = ':install
                            {--s|service=laravel.test : The name of the docker-compose service that hosts the Laravel application}
                            {--d|directory=./docker/sail-ssl-proxy : The directory where certificates will be stored}
                            {--m|middleware : Update the Trust Proxies middleware}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install the necessary resources to run a local SSL proxy';

    /**
     * InstallCommand->commandPrefix
     *
     * @var string
     */
    private string $commandPrefix;

    /**
     * new InstallCommand()
     *
     * Create a new command instance.
     */
    public function __construct(ConfigRepository $config)
    {
        $this->commandPrefix = $config->get('filhocodes-ssl-proxy.command_prefix', 'ssl-proxy');
        $this->signature = $this->commandPrefix . $this->signature;

        parent::__construct();
    }

    /**
     * InstallCommand->handle()
     *
     * Execute the console command.
     *
     * @param Application $application
     * @param LoggerInterface $logger
     * @return int
     */
    public function handle(Application $application, LoggerInterface $logger): int
    {
        try {
            $directory = $this->ensureSslProxyVolumeDirectories($logger);
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());
            Logger::logException($logger, $e);
            return self::FAILURE;
        } catch (Throwable $e) {
            $this->error("Unable to ensure the directories that will be used as Docker Volumes for certificates and authorities");
            Logger::logException($logger, $e);
            return self::FAILURE;
        }

        try {
            $this->writeCaddyFile($directory);
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());
            Logger::logException($logger, $e);
            return self::FAILURE;
        } catch (Throwable $e) {
            $this->error("Unable to write the Caddyfile that will be used to configure the reverse proxy");
            Logger::logException($logger, $e);
            return self::FAILURE;
        }

        try {
            $dockerComposeData = $this->retrieveDockerComposeData($application);
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());
            Logger::logException($logger, $e);
            return self::FAILURE;
        } catch (ParseError $e) {
            $this->error("Unable to parse the YAML contents of docker-compose.yml");
            Logger::logException($logger, $e);
            return self::FAILURE;
        } catch (Throwable $e) {
            $this->error("Unable to retrieve the contents of docker-compose.yml");
            Logger::logException($logger, $e);
            return self::FAILURE;
        }

        try {
            $updatedDockerComposeData = $this->includeSslProxyDockerComposeSettings($dockerComposeData);
        } catch (Throwable $e) {
            $this->error("Unable to add the SSL Proxy Service to the Docker Compose settings");
            Logger::logException($logger, $e);
            return self::FAILURE;
        }

        try {
            $this->rewriteDockerComposeYml($application, $updatedDockerComposeData);
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());
            Logger::logException($logger, $e);
            return self::FAILURE;
        } catch (Throwable $e) {
            $this->error("Unable to rewrite docker-compose.yml with the updated settings");
            Logger::logException($logger, $e);
            return self::FAILURE;
        }

        $this->info('The Sail SSL Proxy service is installed in your docker-compose.yml');
        $this->newLine();

        try {
            if ($this->option('middleware')) {
                $this->rewriteTrustProxiesMiddleware($application);

                $this->info("The TrustProxies middleware was successfully configured.");
                $this->line("AFTER STARTING THE CONTAINERS, please execute the following command to set the environment variable with the proxy IP");
                $this->line("    ./vendor/bin/filhocodes-ssl-proxy-env");
            } else {
                $this->warn('You did not opted in to change the Trust Proxies middleware.');
                $this->warn('Please configure you application to trust the Laravel Sail SSL Proxy when running in the configured environments.');
            }

            $this->newLine();
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());
            $this->warn('Please configure you application to trust the Laravel Sail SSL Proxy when running in the configured environments.');
            Logger::logException($logger, $e);
            return self::FAILURE;
        } catch (Throwable $e) {
            $this->error("Unable to rewrite TrustProxies.php");
            $this->warn('Please configure you application to trust the Laravel Sail SSL Proxy when running in the configured environments.');
            Logger::logException($logger, $e);
            return self::FAILURE;
        }

        $this->line('To configure the behavior of the SSL Proxy inside your application, publish the config file:');
        $this->line('    sail php artisan vendor:publish --tag=filhocodes-ssl-proxy-config');
        $this->newLine();

        $this->info('To deploy your development environment:');
        $this->info("    ./vendor/bin/sail up -d");
        $this->newLine();

        return self::SUCCESS;
    }

    /**
     * InstallCommand->ensureSslProxyVolumeDirectories()
     *
     * @param LoggerInterface $logger
     * @return string
     */
    private function ensureSslProxyVolumeDirectories(LoggerInterface $logger): string
    {
        $directory = Path::toAbsolute((string) $this->option('directory'));
        if (!Path::isAbsolute($directory)) {
            throw new RuntimeException("Unable to understand which directory will be used to store the certificates and authorities");
        }

        if (!(is_dir($directory) || mkdir($directory, 0755, true))) {
            throw new RuntimeException("Unable to create the directory that will be used to store the certificates and authorities");
        }

        if (!(is_dir("{$directory}/certificates") || mkdir("{$directory}/certificates", 0755, true))) {
            throw new RuntimeException("Unable to create the directory that will be used to store the certificates");
        }

        if (!(is_dir("{$directory}/authorities") || mkdir("{$directory}/authorities", 0755, true))) {
            throw new RuntimeException("Unable to create the directory that will be used to store the authorities");
        }

        try {
            $gitIgnoreContents = "*\n!.gitignore\n";
            file_put_contents("{$directory}/certificates/.gitignore", $gitIgnoreContents);
            file_put_contents("{$directory}/authorities/.gitignore", $gitIgnoreContents);
        } catch (Throwable $e) {
            Logger::logException($logger, $e);
        }

        return $directory;
    }

    /**
     * InstallCommand->writeCaddyFile()
     *
     * @param string $destDirectory
     */
    private function writeCaddyFile(string $destDirectory): void
    {
        $laravelServiceName = (string) $this->option('service');

        $caddyFile = __DIR__ . '/../../docker/Caddyfile';
        $caddyContents = file_get_contents($caddyFile);

        $updatedCaddyContents = Str::replace('LARAVEL_APP_SERVICE_HOST', $laravelServiceName, $caddyContents);

        if (false === file_put_contents("{$destDirectory}/Caddyfile", $updatedCaddyContents, LOCK_EX)) {
            throw new RuntimeException("Unable to write to {$destDirectory}/Caddyfile");
        }
    }

    /**
     * InstallCommand->getDockerComposeContents()
     *
     * @param Application $application
     * @return array
     */
    private function retrieveDockerComposeData(Application $application): array
    {
        $dockerComposeFile = $application->basePath('docker-compose.yml');
        if (!(file_exists($dockerComposeFile) && is_file($dockerComposeFile))) {
            throw new RuntimeException('Unable to access docker-compose.yml');
        }

        $dockerComposeContents = file_get_contents($dockerComposeFile);
        $dockerComposeData = Yaml::parse($dockerComposeContents);

        return (array) $dockerComposeData;
    }

    /**
     * InstallCommand->includeSslProxyDockerComposeSettings()
     *
     * @param array $dockerComposeData
     * @return array
     */
    private function includeSslProxyDockerComposeSettings(array $dockerComposeData): array
    {
        $laravelServiceName = (string) $this->option('service');
        $volumesDirectory = rtrim((string) $this->option('directory'), '/\\');
        if (!Str::startsWith($volumesDirectory, '.')) {
            $volumesDirectory = './'.$volumesDirectory;
        }

        $dockerComposeDataCopy = array_slice($dockerComposeData, 0);

        $sslProxyDockerServiceConfig = [
            'build' => [
                'context' => './vendor/filhocodes/laravel-sail-ssl-proxy/docker',
                'dockerfile' => 'Dockerfile',
                'args' => [
                    'WWWGROUP' => '${WWWGROUP}',
                ],
            ],
            'image' => 'sail/filhocodes-ssl-proxy',
            'restart' => 'unless-stopped',
            'volumes' => [
                '.:/srv:cache',
                'sailcaddydata:/data:cache',
                'sailcaddyconfig:/config:cache',
                "{$volumesDirectory}/Caddyfile:/etc/caddy/Caddyfile",
                "{$volumesDirectory}/certificates:/data/caddy/certificates/local",
                "{$volumesDirectory}/authorities:/data/caddy/pki/authorities/local",
            ],
            'ports' => [
                '${APP_PORT:-80}:80',
                '${APP_SSL_PORT:-443}:443'
            ],
            'networks' => [
                'sail'
            ],
            'depends_on' => [
                $laravelServiceName,
            ],
        ];
        $sslProxyDockerVolumeConfig = [
            'driver' => 'local',
        ];

        $sslProxyDockerService = ["{$laravelServiceName}.proxy" => $sslProxyDockerServiceConfig];
        $dockerComposeDataCopy['services'] = $sslProxyDockerService + $dockerComposeDataCopy['services'];

        $dockerComposeDataCopy['volumes']['sailcaddydata'] = $sslProxyDockerVolumeConfig;
        $dockerComposeDataCopy['volumes']['sailcaddyconfig'] = $sslProxyDockerVolumeConfig;

        if (isset($dockerComposeDataCopy['services'][$laravelServiceName]['ports'])) {
            foreach ($dockerComposeDataCopy['services'][$laravelServiceName]['ports'] as $key => $value) {
                if (is_string($value) && (
                    $value === '80'
                    || $value === '443'
                    || Str::startsWith($value, '80:')
                    || Str::startsWith($value, '443:')
                    || Str::startsWith($value, '${APP_PORT:-80}:')
                )) {
                    unset($dockerComposeDataCopy['services'][$laravelServiceName]['ports'][$key]);
                }

                if (isset($value['published']) && (
                    ((string) $value['published']) === '80'
                    || ((string) $value['published']) === '443'
                )) {
                    unset($dockerComposeDataCopy['services'][$laravelServiceName]['ports'][$key]);
                }
            }
        }

        return $dockerComposeDataCopy;
    }

    /**
     * InstallCommand->rewriteDockerComposeYml()
     *
     * @param Application $application
     * @param array $dockerComposeData
     */
    private function rewriteDockerComposeYml(Application $application, array $dockerComposeData): void
    {
        $dockerComposeFile = $application->basePath('docker-compose.yml');
        $yamlContents = Yaml::dump($dockerComposeData, 4);

        if (false === file_put_contents($dockerComposeFile, $yamlContents, LOCK_EX)) {
            throw new RuntimeException('Unable to write to docker-compose.yml');
        }
    }

    /**
     * InstallCommand->rewriteTrustProxiesMiddleware()
     *
     * @param Application $application
     */
    private function rewriteTrustProxiesMiddleware(Application $application): void
    {
        $trustProxiesFile = $application->basePath('app/Http/Middleware/TrustProxies.php');
        if (!(file_exists($trustProxiesFile) && is_file($trustProxiesFile))) {
            throw new RuntimeException("The default TrustProxies middleware doesn't exists");
        }

        $trustProxiesContents = file_get_contents($trustProxiesFile);
        $newContent = file_get_contents(__DIR__.'/../../stubs/trust-ssl-proxy.php.stub');

        $updatedContents = preg_replace("/(}[\s\n\r]*)$/m", $newContent.'$1', $trustProxiesContents);

        if (false === file_put_contents($trustProxiesFile, $updatedContents, LOCK_EX)) {
            throw new RuntimeException('Unable to write to TrustProxies.php');
        }
    }
}
