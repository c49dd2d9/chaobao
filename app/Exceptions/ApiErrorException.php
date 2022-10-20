<?php

namespace App\Exceptions;

use Exception;

class ApiErrorException extends Exception
{
    const HTTP_OK = 200;
    protected $data;
    public function __construct($message, $data = [], int $code = self::HTTP_OK)
    {
        $this->data = $data;
        parent::__construct($message, $code);
    }
    public function render()
    {
        $errorCode = $this->message;
        $errorInfo = config('error.'.$errorCode);
        return response()->json([
            'code' => $errorInfo[0],
            'message' => $errorInfo[1],
            'data' => $this->data,
        ]);
    }
}
