<?php
declare(strict_types=1);

namespace FilhoCodes\LaravelSailSslProxy\Http\Controllers;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * SailSslProxyController
 */
final class SailSslProxyController
{
    /**
     * SailSslProxyController()
     *
     * @param Request $request
     * @param ConfigRepository $config
     * @param ResponseFactory $responseFactory
     * @return Response
     */
    public function __invoke(Request $request, ConfigRepository $config, ResponseFactory $responseFactory): Response
    {
        if (in_array($request->query('domain'), $config->get('filhocodes-ssl-proxy.authorized_domains'))) {
            return $responseFactory->noContent(200);
        }

        return $responseFactory->noContent(503);
    }
}
