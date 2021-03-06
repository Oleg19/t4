<?php

namespace T4\Mvc;

use T4\Core\Config;
use T4\Core\Exception;
use T4\Core\Flash;
use T4\Core\Session;
use T4\Core\Std;
use T4\Core\TSingleton;
use T4\Core\TStdGetSet;
use T4\Dbal\Connection;
use T4\Http\E404Exception;
use T4\Http\Request;
use T4\Threads\Helpers;

/**
 * Class Application
 * @package T4\Mvc
 * @property \T4\Core\Config $config
 * @property \T4\Dbal\Connection[] $db
 * @property \T4\Http\Request $request
 * @property \App\Models\User $user
 * @property \T4\Mvc\AssetsManager $assets
 * @property \T4\Core\Flash $flash
 */
class Application
{
    use
        TStdGetSet,
        TSingleton,
        TApplicationPaths;

    /*
     * Public properties
     */

    /**
     * @var \T4\Core\Std
     */
    public $extensions;

    /**
     * Возвращает конфиг роутинга приложения
     * @return \T4\Core\Config Объект конфига роутинга
     */
    public function getRouteConfig()
    {
        return new Config($this->getRouteConfigPath());
    }

    /**
     * Конструктор
     * Инициализация:
     * - сессий
     * - конфигурации приложения
     * - секций и блоков
     * - создание подключений к БД
     * - расширений
     */
    protected function __construct()
    {
        try {

            Session::init();

            /*
             * Extensions setup and initialize
             */
            $this->extensions = new Std;
            if (isset($this->config->extensions)) {
                foreach ($this->config->extensions as $extension => $options) {
                    $extensionClassName = 'Extensions\\' . ucfirst($extension) . '\\Extension';
                    if (class_exists('\\App\\' . $extensionClassName)) {
                        $extensionClassName = '\\App\\' . $extensionClassName;
                    } else {
                        $extensionClassName = '\\T4\\' . $extensionClassName;
                    }
                    $this->extensions->{$extension} = new $extensionClassName($options);
                    $this->extensions->{$extension}->setApp($this);
                    if (!isset($options->autoload) || true == $options->autoload) {
                        $this->extensions->{$extension}->init();
                    }
                }
            }

        } catch (Exception $e) {
            echo $e->getMessage();
            die;
        }
    }

    /**
     * Запуск веб-приложения
     * и формирование ответа
     */
    public function run()
    {
        try {
            $this->runInternalPath($this->request->fullPath);
        } catch (Exception $e) {
            try {
                if ($e instanceof E404Exception && !empty($this->config->errors['404'])) {
                    header("HTTP/1.0 404 Not Found", true, 404);
                    $this->runInternalPath($this->config->errors['404']);
                } else {
                    echo $e->getMessage();
                    die;
                }
            } catch (Exception $e2) {
                echo $e2->getMessage();
                die;
            }
        }
    }

    /**
     * @param callable $callback
     * @param array $args
     * @throws \T4\Threads\Exception
     * @return int Child process PID
     */
    public function runLater(callable $callback, $args=[])
    {
        return Helpers::run($callback, $args);
    }

    /**
     * @param string $path
     * @throws ControllerException
     * @throws Exception
     * @internal param Route $route
     */
    private function runInternalPath($path)
    {
        $route =
            Router::getInstance()
                ->setConfig($this->getRouteConfig())
                ->parseRequestPath($path);
        $controller = $this->createController($route->module, $route->controller);
        $controller->action($route->action, $route->params);
        $data = $controller->getData();

        switch ($route->format) {
            case 'json':
                header('Content-Type: application/json');
                echo json_encode($data->toArray());
                die;
            default:
            case 'html':
                header('Content-Type: text/html; charset=utf-8');
                $controller->view->display($route->action . '.' . $route->format, $data);
                break;
        }
    }

    /**
     * Вызов блока
     * @param string $path Внутренний путь до блока
     * @param string $template Шаблон блока
     * @param array $params Параметры, передаваемые блоку
     * @throws \T4\Core\Exception
     * @return mixed Результат рендера блока
     */
    public function callBlock($path, $template = '', $params = [])
    {
        $router = Router::getInstance();
        $route = $router->splitInternalPath($path);
        $route->params->merge($params);

        $canonicalPath = $router->makeInternalPath($route);
        if (!isset($this->config->blocks) || !isset($this->config->blocks[$canonicalPath]))
            throw new Exception('No config for block ' . $canonicalPath);

        $blockOptions = $this->config->blocks[$canonicalPath];

        $getBlock = function() use ($template, $route) {
            $controller = $this->createController($route->module, $route->controller);
            $controller->action($route->action, $route->params);
            return $controller->view->render(
                $route->action . (!empty($template) ? '.' . $template : '') . '.block.html',
                $controller->getData()
            );
        };

        if (isset($blockOptions['cache'])) {
            $cache = \T4\Cache\Factory::getInstance();
            $key = md5($canonicalPath . serialize($route->params));
            if (isset($blockOptions['cache']['time'])) {
                return $cache($key, $getBlock, $blockOptions['cache']['time']);
            } else {
                return $cache($key, $getBlock);
            }
        } else {
            return $getBlock();
        }

    }

    /**
     * Возвращает экземпляр контроллера
     * @param string $module
     * @param string $controller
     * @throws \T4\Core\Exception
     * @return \T4\Mvc\Controller
     */
    public function createController($module, $controller)
    {
        if (!$this->existsController($module, $controller))
            throw new Exception('Controller ' . $controller . ' does not exist');

        if (empty($module))
            $controllerClass = '\\App\\Controllers\\' . $controller;
        else
            $controllerClass = '\\App\\Modules\\' . ucfirst($module) . '\\Controllers\\' . ucfirst($controller);

        $controller = new $controllerClass;
        return $controller;
    }

    /*
     * Lazy properties loading
     */

    protected function getDb()
    {
        static $db = null;
        if (null === $db) {
            $db = new Std;
            foreach ($this->config->db as $connection => $connectionConfig) {
                $db->{$connection} = new Connection($connectionConfig);
            }
            $this->db = $db;
        }
        return $db;
    }

    protected function getConfig()
    {
        static $config = null;
        if (null == $config) {
            $config = new Config($this->getPath() . DS . 'config.php');
            $config->sections = new Config($this->getPath() . DS . 'sections.php');
            $config->blocks = new Config($this->getPath() . DS . 'blocks.php');
        }
        return $config;
    }

    protected function getRequest()
    {
        static $request = null;
        if (null === $request)
            $request = new Request();
        return $request;
    }

    protected function getUser()
    {
        static $user = null;
        if (null === $user) {
            if (class_exists('\\App\Components\Auth\Identity')) {
                $identity = new \App\Components\Auth\Identity();
                $user = $identity->getUser() ?: null;
            } else {
                return null;
            }
        }
        return $user;
    }

    protected function getAssets()
    {
        return AssetsManager::getInstance();
    }

    protected function getFlash()
    {
        static $flash = null;
        if (null === $flash)
            $flash = new Flash();
        return $flash;
    }

}