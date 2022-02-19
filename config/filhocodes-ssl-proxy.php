<?php
declare(strict_types=1);

return [

    /*
     |--------------------------------------------------------------------------
     | Environments
     |--------------------------------------------------------------------------
     |
     | A list of application environments where the SSL proxy will be used.
     |
     | ATTENTION: This proxy should not be used on production environments, or
     | in a shared networks that is not contained. The reason this config exists
     | is to be accessible to projects that makes heavily use of the environment
     | config.
     |
     */

    'environments' => [
        'local',
    ],

    /*
    |--------------------------------------------------------------------------
    | Authorized Domains
    |--------------------------------------------------------------------------
    |
    | List of domains that are authorized to access the application via SSL.
    |
    */

    'authorized_domains' => [
        parse_url(config('app.url'), PHP_URL_HOST)
    ],

    /*
     |--------------------------------------------------------------------------
     | Artisan Command Prefix
     |--------------------------------------------------------------------------
     |
     | The prefix used in the signature of this library artisan commands.
     |
     | Feel free to change it in case of conflicts with other libraries / your
     | project commands.
     |
     */

    'command_prefix' => 'ssl-proxy',

    /*
     |--------------------------------------------------------------------------
     | Caddy SSL Authorization Route
     |--------------------------------------------------------------------------
     |
     | The prefix used in the signature of this library artisan commands.
     |
     | Feel free to change it in case of conflicts with other libraries / your
     | project commands.
     |
     */

    'authorization_route' => '.filhocodes-sail-ssl-proxy',

    /*
     |--------------------------------------------------------------------------
     | Laravel Sail SSL Proxy Server IP
     |--------------------------------------------------------------------------
     |
     | The IP that will be added to the trusted proxies.
     |
     | The intended use is to the IP to be stored in your .env file, and we will
     | read it as a config value. If the configuration of your runtime is
     | cached, Laravel will not load any Environment Variable. Again, this proxy
     | is intended to help local development, and not production usage, so
     | having it as a config value is just in case you really need to cache your
     | config.
     |
     */

    'proxy_server_ip' => env('FILHOCODES_LARAVEL_SAIL_SSL_PROXY_SERVER_IP'),

];
