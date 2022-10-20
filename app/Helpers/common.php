<?php

use Illuminate\Support\Facades\Validator;
use App\Models\Sockpuppet;
use App\Exceptions\ApiErrorException;
use Illuminate\Support\Facades\Cache;

function successJson($data = []) 
{
  return response()->json([
    'code' => 200,
    'message' => 'OK',
    'data' => $data,
  ]);
}

function validateParams($params, $rules)
{
  $validator = Validator::make($params, $rules);
  if ($validator->fails()) {
    throw new ApiErrorException('FIELD_ERROR', $validator->errors()->first());
  }
}

function nullSecurity($string)
{
  return $string != null ? e($string) : '';
}



