<?php
declare(strict_types=1);

namespace FilhoCodes\LaravelSailSslProxy\Helpers;

/**
 * Path
 */
final class Path
{
    /**
     * Path::toAbsolute()
     *
     * Ctrl-C/Ctrl-V from somewhere, forgot to keep the reference
     *
     * @param string $path
     * @return string
     */
    public static function toAbsolute(string $path): string
    {
        if (DIRECTORY_SEPARATOR !== '/') {
            $path = str_replace(DIRECTORY_SEPARATOR, '/', $path);
        }

        $search = array_filter(explode('/', $path), fn (string $part): bool => $part !== '.');
        $append = [];
        $match = false;

        while (count($search) > 0) {
            $match = realpath(implode('/', $search));
            if ($match !== false) {
                break;
            }

            array_unshift($append, array_pop($search));
        };

        if ($match === false) {
            $match = getcwd();
        }

        if (count($append) > 0) {
            $match .= DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $append);
        }

        return $match;
    }

    /**
     * Path::isAbsolute()
     *
     * Ctrl-C/Ctrl-V from:
     * https://github.com/WordPress/wordpress-develop/blob/a247caa0af4c492e420e513e5b635f2c64c74e65/src/wp-includes/functions.php#L2057-L2095
     *
     * @param string $path
     * @return bool
     */
    public static function isAbsolute(string $path): bool
    {
        if (self::isStream($path) && (is_dir($path) || is_file($path))) {
            return true;
        }

        if (realpath($path) == $path) {
            return true;
        }

        if (strlen($path) == 0 || '.' === $path[0]) {
            return false;
        }

        if (preg_match('#^[a-zA-Z]:\\\\#', $path)) {
            return true;
        }

        return ('/' === $path[0] || '\\' === $path[0]);
    }

    /**
     * Path::isStream()
     *
     * Ctrl-C/Ctrl-V from:
     * https://github.com/WordPress/wordpress-develop/blob/a247caa0af4c492e420e513e5b635f2c64c74e65/src/wp-includes/functions.php#L6931-L6950
     *
     * @param string $path
     * @return bool
     */
    public static function isStream(string $path): bool
    {
        $scheme_separator = strpos($path, '://');

        if (false === $scheme_separator) {
            return false;
        }

        $stream = substr($path, 0, $scheme_separator);

        return in_array($stream, stream_get_wrappers(), true);
    }
}
