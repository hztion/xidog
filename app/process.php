<?php

/**
 * @param $http
 * @return \Swoole\Process
 */

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
                foreach ($data as $dir => $msg) {
                    $target_dir = '/var/data/' . $dir . '/';
                    file_exists($target_dir) || mkdir($target_dir);
                    $file =  $target_dir . date('Y-m-d') . '.txt';
                    file_put_contents($file, implode("\n", $msg) . "\n", FILE_APPEND);
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
