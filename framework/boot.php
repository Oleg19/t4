<?php

namespace {
    const DS = DIRECTORY_SEPARATOR;
}

namespace T4 {
    const ROOT_PATH = __DIR__;
    const VERSION = 0.1;
}

namespace {
    define('ROOT_PATH', realpath(__DIR__ . '/../../'));
    define('ROOT_PATH_PROTECTED', ROOT_PATH . DS . 'protected');
    define('ROOT_PATH_PUBLIC', ROOT_PATH . DS . 'www');
}

namespace {
    require T4\ROOT_PATH . DS . 'autoload.php';
}