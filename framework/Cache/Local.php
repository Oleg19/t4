<?php

namespace T4\Cache;

use T4\Fs\Helpers;

class Local
    extends ACache
{

    public function __invoke($key, $callback, $time = self::DEFAULT_CACHE_TIME)
    {
        $cachePath = ROOT_PATH_PUBLIC . DS . 'Cache';
        if (!is_readable($cachePath))
            Helpers::mkDir($cachePath);
        $fileName = $cachePath . DS . md5($key) . '.cache';
        if (!is_readable($fileName) || (time() - filemtime($fileName) > (int)$time)) {
            $res = call_user_func($callback);
            file_put_contents($fileName, serialize($res));
            return $res;
        } else {
            return unserialize(file_get_contents($fileName));
        }
    }

}