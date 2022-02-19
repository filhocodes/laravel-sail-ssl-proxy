# FilhoCodes' Laravel Sail SSL Proxy

An easy and ready way to configure a proxy that allows you to host local projects using Laravel Sail via HTTPS.

The design of the SSL proxy was taken from https://dev.to/adrianmejias/how-to-enable-ssl-for-local-development-using-laravel-sail-and-docker-51ee.
Basically, Caddy will be executed on top of the Laravel Server, and will generate a local certificate. Then you can add
this certificate to your local trusted store.

## Quick usage

If you don't have a local installation of PHP and Composer, you can use this alias to execute both via docker

```bash
alias sail81 = 'mkdir -p $HOME/.sail/81/.composer/ && docker run --rm -it -u "$(id -u):$(id -g)" -v $HOME/.sail/81/.composer:/.composer -v $(pwd):/opt -w /opt laravelsail/php81-composer:latest'
```

Then you can just prepend the commands bellow with `sail81`:

```bash
# Running composer
sail81 composer {...}

# Running an artisan command
sail81 php artisan {...}
```

Before setting up the proxy, Laravel Sail shall already be configured in your project. Installations made using the
script provided by the [Laravel Documentation](https://laravel.com/docs/9.x/installation#your-first-laravel-project)
will already have a Laravel Sail environment. Other than that, you will need to configure it yourself. Please refer to
the [Laravel Sail Documentation](https://laravel.com/docs/9.x/sail) to do so.

With your project using Laravel Sail, require this package using Composer:

```bash
composer require --dev filhocodes/laravel-sail-ssl-proxy
```

Then install the proxy service using the following command:

```bash
php artisan ssl-proxy:install -m

# -m will write a logic into app/Http/Middleware/TrustProxies.php to trust the SSL proxy.
# You may update this logic as you wish, or you can omit the option so that the file is not modified.
# In either case, is recommended in development to trust the reverse proxy
```

After that, spin up the development environment:

```bash
./vendor/bin/sail up -d

# If you are using our custom logic to trust the SSL proxy, after the containers are booted up, you may use the
# following command to retrieve the SSL proxy IP (will not work on Windows):
#     ./vendor/bin/filhocodes-ssl-proxy-env
```

After your first HTTP request to the application, a certificate will be created at
`docker/sail-ssl-proxy/authorities/intermediate.crt`. You can then add this certificate to your trusted store. In Linux,
you may use the command `./vendor/bin/filhocodes-ssl-proxy-trust` to add the certificate to the system store.
