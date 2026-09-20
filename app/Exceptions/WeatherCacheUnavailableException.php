<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * aviationweather.gov served something that isn't a usable bulk cache file —
 * an empty body, an HTML error page behind a 200, or a truncated gzip stream.
 */
class WeatherCacheUnavailableException extends RuntimeException {}
