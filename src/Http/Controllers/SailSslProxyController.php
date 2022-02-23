<?php
declare(strict_types=1);

namespace FilhoCodes\LaravelSailSslProxy\Http\Controllers;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Psr\Log\LoggerInterface;

/**
 * SailSslProxyController
 */
final class SailSslProxyController
{
    /**
     * new SailSslProxyController()
     *
     * @param ConfigRepository $config
     * @param LoggerInterface $logger
     * @param ResponseFactory $responseFactory
     */
    public function __construct(
        private ConfigRepository $config,
        private LoggerInterface $logger,
        private ResponseFactory $responseFactory,
    ) { }

    /**
     * SailSslProxyController()
     *
     * @param Request $request
     * @return Response
     */
    public function __invoke(Request $request): Response {
        $domain = $request->query('domain');
        $this->logMessage($request, "Verifying domain {$domain}...");

        if (in_array($domain, $this->config->get('filhocodes-ssl-proxy.authorized_domains'))) {
            $this->logMessage($request, "Domain {$domain} was allowed to a secure connection.");
            return $this->responseFactory->noContent(200);
        }

        $this->logMessage($request, "Domain {$domain} was denied to a secure connection.");
        return $this->responseFactory->noContent(403);
    }

    /**
     * SailSslProxyController->logMessage()
     *
     * @param Request $request
     * @param string $message
     */
    private function logMessage(Request $request, string $message): void
    {
        if ($this->config->get('filhocodes-ssl-proxy.debug_authorization_controller') !== true) {
            return;
        }

        $context = [
            'config' => Arr::except(
                $this->config->get('filhocodes-ssl-proxy'),
                ['command_prefix', 'debug_authorization_controller'],
            ),
            'request' => [
                'query' => $request->query(),
                'headers' => $request->header(),
            ],
        ];

        $this->logger->debug("[SSL PROXY AUTHORIZATION CONTROLLER] ==> $message", $context);
    }
}
