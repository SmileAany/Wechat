<?php

if (!function_exists('success')){
    function success(string $message, array $data = []) : array{
        return [
            'status'  => 'success',
            'code'    => 200,
            'message' => $message,
            'data'    => $data
        ];
    }
}

if (!function_exists('error')){
    function error(string $message, array $data = []) : array{
        return [
            'status'  => 'error',
            'code'    => 500,
            'message' => $message,
            'data'    => $data
        ];
    }
}
if (!function_exists('getClientIp')) {
    function getClientIp(): string
    {
        if (getenv("HTTP_CLIENT_IP")) {
            $ip = getenv("HTTP_CLIENT_IP");
        } elseif (getenv("HTTP_X_FORWARDED_FOR")) {
            $ip = getenv("HTTP_X_FORWARDED_FOR");
        } elseif (getenv("REMOTE_ADDR")) {
            $ip = getenv("REMOTE_ADDR");
        } else {
            $ip = "0.0.0.0";
        }
        if (strpos($ip, ',') !== false) {
            $ip_arr = explode(',', $ip);
            return trim($ip_arr[0]);
        }
        return $ip;
    }
}
