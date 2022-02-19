<?php
declare(strict_types=1);

namespace FilhoCodes\LaravelSailSslProxy\Helpers;

use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Logger
 */
final class Logger
{
    /**
     * Logger::logException()
     *
     * @param LoggerInterface $logger
     * @param Throwable $e
     */
    public static function logException(LoggerInterface $logger, Throwable $e): void
    {
        $logger->error(
            $e->getMessage(),
            array_merge(
                method_exists($e, 'context') ? $e->context() : [],
                ['exception' => $e]
            )
        );
    }
}
