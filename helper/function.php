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
