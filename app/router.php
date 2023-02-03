<?php

/**
 * 定义格式：
 * 键名是控制器名称
 *
 *
 * 键值数组格式：
 * 键名是路由
 * 键值是控制器方法名
 */

$baseRoute = [
    'index' => [
        '/hello' => 'index'
    ]
];

/**
 * 转换👆数组
 */
return function () use ($baseRoute) {
    $routes = [];
    foreach ($baseRoute as $controller => $item) {
        foreach ($item as $line => $method) {
            $routes[$line] = [
                ucfirst($controller),
                $method
            ];
        }
    }
    return $routes;
};
