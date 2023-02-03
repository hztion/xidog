<?php

/**
 * @param $http
 * @return \Swoole\Process
 */

use Doctrine\DBAL\DriverManager;
use function Swoole\Coroutine\Http\post;

$process = function (&$http) {
    return new Swoole\Process(function () use ($http) {
        Swoole\Timer::tick(1000 * 60, function () use ($http) {
            $keys = [];
            $data = [];
            foreach ($http->table as $key => $item) {
                $keys[] = $key;
                $data[$item['type']][] = implode('=|=', $item);
            }
            foreach ($keys as $key) {
                $http->table->del($key);
            }
            if (count($keys)) {
                // 写入数据库
                $connectionParams = [
                    'dbname' => 'xidog',
                    'user' => 'root',
                    'password' => '123123',
                    'host' => 'mysql',
                    'driver' => 'pdo_mysql',
                ];
                $conn = DriverManager::getConnection($connectionParams);
                foreach ($data as $value) {
                    foreach ($value as $val) {
                        $item = explode('=|=', $val);
                        $arr = [
                            'ip' => $item[0],
                            'user_agent' => $item[1],
                            'add_time' => $item[2],
                            'type' => $item[3]
                        ];
                        var_dump($item);
                        $conn->insert('xg_access_log', $arr);
                    }
                }
            }
        });

        /**
         * 定时任务
         */
        $callback = function () use ($http) {
            go(function () use ($http) {
                try {
                    $arr = [1, 2, 3];
                    for ($index = $http->setting['worker_num'] - 1; $index >= 0; $index--) {
                        $http->sendMessage($arr, $index);
                    }
                    $http->table->set(time(), [
                        'ip'   => '192.168.1.1',
                        'ua'   => 'none',
                        'type' => 'test',
                        'time' => time()
                    ]);
                } catch (Throwable $exception) {
                    colorLog('定时推送出错:' . $exception->getMessage());
                }
            });
        };

        if ($http->config == null) {
            colorLog('初次启动');
            $callback();
        }
        Swoole\Timer::tick(1000 * 30, $callback);
    }, false, 1, true);
};

/**
 * @param $server
 * @param $src_worker_id
 * @param $data
 */
$handlerMsg = function (&$server, $src_worker_id, $data) {
    $server->config = $data;
};

return [$process, $handlerMsg];
