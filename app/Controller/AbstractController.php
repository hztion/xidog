<?php

namespace App\Controller;

use App\Exception\HttpDisableException;
use HttpException;
use Swoole\Exception;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server;

abstract class AbstractController
{
    /**
     * @var Request
     */
    protected $request = null;

    /**
     * @var Response
     */
    protected $response = null;

    /**
     * @var null
     */
    protected $controller = null;
    /**
     * @var Server
     */
    protected $server;

    /**
     * @var array 配置数组
     */
    protected $config;

    /**
     * AbstractController constructor.
     * @param $request
     * @param $response
     * @throws HttpDisableException
     */
    public function __construct($request, $response, $controller = null, $server = null)
    {
        $this->request  = $request;
        $this->response = $response;
        $this->controller = $controller;
        $this->server = $server;
        if (!isset($server->config[strtolower($controller)])) {
            // throw new HttpDisableException();
        }
        $this->config = $server->config[strtolower($controller)] ?? null;
    }

    /**
     * @param int $http_code
     */
    protected function redirect($url, $http_code = 301)
    {
        $this->response->redirect($url, $http_code);
    }

    /**
     * 异常处理
     * @param $msg
     * @param int $code
     * @throws HttpException
     */
    protected function error($msg, $code = 400)
    {
        throw new HttpException($msg, $code);
    }

    /**
     * @param $code
     * @return $this
     */
    protected function httpCode($code)
    {
        $this->response->status($code);
        return $this;
    }

    /**
     * @param $data
     * @return mixed
     */
    protected function html($data)
    {
        return $this->custom($data, 'text/html; charset=UTF-8');
    }

    /**
     * @param $data
     * @return string
     */
    protected function json($data = [])
    {
        return $this->custom($data, 'application/json');
    }

    /**
     * @param $data
     * @return string
     */
    protected function js($data)
    {
        return $this->custom($data, 'application/javascript');
    }

    /**
     * @param $data
     * @param $type
     * @return mixed
     */
    protected function custom($data, $type)
    {
        try {
            $this->response->header('Content-Type', $type);
            is_array($data) && $data = json_encode($data);
            return $this->response->end($data);
        } catch (\Throwable $exception) {
            colorLog($exception->getMessage(), 'e');
            var_dump($exception->getTraceAsString());
        }
    }


    /**
     * 渲染模板
     * @param array $data
     * @throws Exception
     */
    protected function display(array $data = [], $view = '')
    {
        $tpl = $view ?: debug_backtrace()[1]['function'];
        $file = join('/', [APP_ROOT, 'View', $this->controller, $tpl . '.html']);
        $content = fetchTpl($file);
        if ($data) {
            foreach ($data as $key => $value) {
                $search['{{' . $key . '}}'] = $value;
            }
            $content = strtr($content, $search);
        }
        $this->html($content);
    }

    /**
     * 渲染模板
     * @param array $data
     * @throws Exception
     */
    protected function render(array $data = [], $tpl = null, $output = true)
    {
        if (!$tpl) {
            $tpl = debug_backtrace()[1]['function'];
        }
        $file = join('/', [APP_ROOT, 'View', $this->controller, $tpl . '.html']);
        $content = fetchTpl($file);
        if ($data) {
            foreach ($data as $key => $datum) {
                $search['{{' . $key . '}}'] = $datum;
            }
            $content = strtr($content, $search);
        }
        if (!$output) {
            return $content;
        } else {
            $this->html($content);
        }
    }

    /**
     * 预渲染模版
     * @param string $view 模板名称
     * @param array $data
     * @throws Exception
     */
    protected function preRender($view, array $data = [], $output = false)
    {
        $file = join('/', [APP_ROOT, 'View', $this->controller, $view . '.html']);
        $content = remember($file, function () use ($file) {
            if (!file_exists($file)) throw new Exception('tpl not found');
            $content = file_get_contents($file);
            return $content;
        });
        if ($data) {
            foreach ($data as $key => $datum) {
                $search['{{' . $key . '}}'] = $datum;
            }
            $content = strtr($content, $search);
        }
        if ($output === false) {
            return $content;
        }
        return $this->html($content);
    }
}
