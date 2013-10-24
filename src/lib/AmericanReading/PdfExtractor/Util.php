<?php

namespace AmericanReading\PdfExtractor;

class Util implements ConfigInterface
{
    /**
     * @param string $relativePath
     * @return string Absolute path for a file inside this phar.
     */
    public static function pharPath($relativePath)
    {
        if ($relativePath[0] !== '/') {
            $relativePath = '/' . $relativePath;
        }
        return "phar://" . self::PHAR_NAME . $relativePath;
    }

    /**
     * @param string $json JSON+comments string
     * @return string JSON-encoded string with comments removed
     */
    public static function stripJsonComments($json)
    {
        return preg_replace("#(/\*([^*]|[\r\n]|(\*+([^*/]|[\r\n])))*\*+/)|([\s\t]//.*)|(^//.*)#", '', $json);
    }

     /**
     * Join path components.
     * @path array|string $path,... Path component to join.
     * @return string
     */
    public static function joinPaths()
    {
        // Read any number of arguments into an array.
        $paths = func_get_args();

        // If one array was passed, use that array instead.
        if (count($paths) === 1 && is_array($paths[0])) {
            $paths = $paths[0];
        }

        // Check if the initial argument began with a slash.
        $startingSlash = $paths[0][0] === DIRECTORY_SEPARATOR;

        // Strip slashes from the ends of all the members,
        $paths = array_map(function ($path) {
                return trim($path, DIRECTORY_SEPARATOR);
            }, $paths);

        // Join the items with slashes. Re-add the starting slash, if needed.
        $path = join(DIRECTORY_SEPARATOR, $paths);
        if ($startingSlash) {
            $path = DIRECTORY_SEPARATOR . $path;
        }
        return $path;
    }

}
