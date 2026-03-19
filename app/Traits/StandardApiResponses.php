<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait StandardApiResponses
{
    /**
     * Success response.
     */
    protected function success($data = [], string $message = 'Operation successful', int $code = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ], $code);
    }

    /**
     * Error response.
     */
    protected function error(string $message = 'Error occurred', int $code = 400, $errors = null, string $internalCode = 'ERR_UNKNOWN'): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
            'code'    => $internalCode,
        ];

        if ($errors) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }

    /**
     * Paginated response.
     */
    protected function paginated($paginator, string $message = 'Operation successful'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $paginator->items(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
            ],
        ]);
    }
}
