<?php

namespace App\Traits;

trait ApiResponse
{
  protected function success($data = null, $message = '', $code = 200)
  {
    return response()->json([
      'success' => true,
      'message' => $message,
      'data' => $data,
    ], $code);
  }

  protected function error($message = '', $code = 400, $errors = [])
  {
    return response()->json([
      'success' => false,
      'message' => $message,
      'errors' => $errors,
    ], $code);
  }
}
