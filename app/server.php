#!/usr/bin/env php
<?php

set_error_handler(function ($errno, $errmsg) {
    colorLog($errmsg);
    file_put_contents('/var/data/app.log', $errmsg . "\n\n" . var_export(debug_backtrace(), true), FILE_APPEND);
});

const APP_ROOT = __DIR__;

require APP_ROOT . '/vendor/autoload.php';
require APP_ROOT . '/utils.php';

$router = require APP_ROOT . '/router.php';
$routes = $router();

use App\Exception\HttpDisableException;
use App\Exception\HttpNotFoundException;
use App\Exception\HttpRootException;

$table = new Swoole\Table(2048);
$table->column('ip', Swoole\Table::TYPE_STRING, 128);
$table->column('ua', Swoole\Table::TYPE_STRING, 256);
$table->column('time', Swoole\Table::TYPE_INT, 4);
$table->column('type', Swoole\Table::TYPE_STRING, 32);
$table->create();

$http = new Swoole\Http\Server("0.0.0.0", 9501);
$http->set([
    'worker_num'               => swoole_cpu_num() * 3,
    'document_root'            => '/var/html',
    'enable_static_handler'    => true,
    'compression_min_length'   => 6144,
    'heartbeat_check_interval' => 60,
    'heartbeat_idle_time'      => 180
]);
$http->table = $table;

/**
 * config array 配置属性
 */
$http->config = null;

$tplCache = [];

/**
 * 引入单独进程并添加
 */
[$process, $handlerMsg] = require APP_ROOT . '/process.php';
$process = $process($http);
$http->addProcess($process);

/**
 * 配置事件接收器
 */
$http->on('pipeMessage', $handlerMsg);

/**
 * http请求处理
 */
$http->on(
    "request",
    function (Swoole\Http\Request $request, Swoole\Http\Response $response) use ($routes, $http) {
        try {
            $uri = rtrim($request->server['request_uri'], '/');

            if ($uri == '') throw new HttpRootException();
            if ($uri == '/favicon.ico') {
                return $response->end('');
            }
            if (array_key_exists($uri, $routes)) {
                [$controller, $method] = $routes[$uri];
                $fullController = 'App\\Controller\\' . ucfirst($controller) . 'Controller';
                $handle = new $fullController($request, $response, $controller, $http);
                $handle->{$method}();
                if ($response->isWritable()) {
                    $response->end('n');
                }
            } else {
                throw new HttpNotFoundException();
            }
        } catch (HttpRootException $exception) {
            $response->status(401);
            return $response->end('boom!');
        } catch (HttpNotFoundException $exception) {
            /**
             * 处理未匹配的访问
             */
            $response->status(404);
            return $response->end('your request in unreachable!');
        } catch (HttpDisableException $exception) {
            $response->status(403);
            return $response->end('Module has been disabled!');
        } catch (Throwable $throwable) {
            /**
             * 处理未捕获的错误，一般是运行时错误
             */
            colorLog($throwable->getMessage());
            $response->status(500);
            return $response->end('internal error!');
        } catch (\Exception $exception) {
            colorLog($exception);
        }
    }
);

/**
 * 启动server
 */
$http->start();
