<?php

if (function_exists('fetchTpl') === false) {
    function fetchTpl($path)
    {
        global $tplCache;
        if (array_key_exists($path, $tplCache)) return $tplCache[$path];
        if (!file_exists($path)) throw new \Swoole\Exception('tpl not found');
        return $tplCache[$path] = file_get_contents($path);
    }
}

if (function_exists('remember') === false) {
    function remember($key, $callback)
    {
        global $tplCache;
        if (array_key_exists($key, $tplCache)) return $tplCache[$key];
        return $tplCache[$key] = $callback();
    }
}

if (function_exists('encrypt') === false) {
    function encrypt($string, $key = '')
    {
        $key = md5($key);
        $key_length = strlen($key);
        $string = substr(md5($string . $key), 0, 8) . $string;
        $string_length = strlen($string);
        $rndkey = $box = array();
        $result = '';
        for ($i = 0; $i <= 255; $i++) {
            $rndkey[$i] = ord($key[$i % $key_length]);
            $box[$i] = $i;
        }
        for ($j = $i = 0; $i < 256; $i++) {
            $j = ($j + $box[$i] + $rndkey[$i]) % 256;
            $tmp = $box[$i];
            $box[$i] = $box[$j];
            $box[$j] = $tmp;
        }
        for ($a = $j = $i = 0; $i < $string_length; $i++) {
            $a = ($a + 1) % 256;
            $j = ($j + $box[$a]) % 256;
            $tmp = $box[$a];
            $box[$a] = $box[$j];
            $box[$j] = $tmp;
            $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
        }
        return str_replace(['=', '+'], ['', '-'], base64_encode($result));
    }
}

function colorLog($str, $type = 'i')
{
    $str = sprintf('%s [%s] %s', date('Y/m/d H:i:s'), $type, $str);
    switch ($type) {
        case 'e': //error
            echo "\033[31m$str \033[0m\n";
            break;
        case 's': //success
            echo "\033[32m$str \033[0m\n";
            break;
        case 'w': //warning
            echo "\033[33m$str \033[0m\n";
            break;
        case 'i': //info
            echo "\033[36m$str \033[0m\n";
            break;
        default:
            # code...
            break;
    }
}

if (!function_exists('rc4')) {
    function rc4($key, $str)
    {
        $s = array();
        for ($i = 0; $i < 256; $i++) {
            $s[$i] = $i;
        }
        $j = 0;
        for ($i = 0; $i < 256; $i++) {
            $j = ($j + $s[$i] + ord($key[$i % strlen($key)])) % 256;
            $x = $s[$i];
            $s[$i] = $s[$j];
            $s[$j] = $x;
        }
        $i = 0;
        $j = 0;
        $res = '';
        for ($y = 0; $y < strlen($str); $y++) {
            $i = ($i + 1) % 256;
            $j = ($j + $s[$i]) % 256;
            $x = $s[$i];
            $s[$i] = $s[$j];
            $s[$j] = $x;
            $res .= $str[$y] ^ chr($s[($s[$i] + $s[$j]) % 256]);
        }
        return $res;
    }
}

if (!function_exists('getRandStr')) {
    function getRandStr($length = 8)
    {
        $str = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789-_';
        $len = strlen($str) - 1;
        $randstr = '';
        for ($i = 0; $i < $length; $i++) {
            $num = mt_rand(0, $len);
            $randstr .= $str[$num];
        }
        return $randstr;
    }
}
