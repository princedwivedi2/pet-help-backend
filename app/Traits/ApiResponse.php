<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    protected function success(
        string $message,
        mixed $data = null,
        int $statusCode = 200
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'errors' => null,
        ], $statusCode);
    }

    protected function error(
        string $message,
        ?array $errors = null,
        int $statusCode = 400
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => null,
            'errors' => $errors,
        ], $statusCode);
    }

    protected function notFound(string $message = 'Resource not found', ?array $errors = null): JsonResponse
    {
        return $this->error($message, $errors, 404);
    }

    protected function unauthorized(string $message = 'Unauthorized', ?array $errors = null): JsonResponse
    {
        return $this->error($message, $errors, 401);
    }

    protected function forbidden(string $message = 'Forbidden', ?array $errors = null): JsonResponse
    {
        return $this->error($message, $errors, 403);
    }

    protected function validationError(string $message = 'Validation failed', ?array $errors = null): JsonResponse
    {
        return $this->error($message, $errors, 422);
    }

    protected function tooManyRequests(string $message = 'Too many requests', ?array $errors = null): JsonResponse
    {
        return $this->error($message, $errors, 429);
    }

    protected function created(string $message, mixed $data = null): JsonResponse
    {
        return $this->success($message, $data, 201);
    }
}
