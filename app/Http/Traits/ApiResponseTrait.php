<?php

namespace App\Http\Traits;

trait ApiResponseTrait
{
    /**
     * Return a success JSON response.
     *
     * @param mixed $data
     * @param string $message
     * @param int $statusCode
     * @return \Illuminate\Http\JsonResponse
     */
    protected function successResponse($data = null, string $message = 'Operation successful', int $statusCode = 200)
    {
        $response = [
            'success' => true,
            'message' => $message,
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Return an error JSON response.
     *
     * @param string $message
     * @param mixed $errors
     * @param int $statusCode
     * @return \Illuminate\Http\JsonResponse
     */
    protected function errorResponse(string $message, $errors = null, int $statusCode = 422)
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Return a validation error response.
     *
     * @param string $message
     * @param array $errors
     * @return \Illuminate\Http\JsonResponse
     */
    protected function validationErrorResponse(string $message = 'Validation error', array $errors = [])
    {
        return $this->errorResponse($message, $errors, 422);
    }

    /**
     * Return a not found error response.
     *
     * @param string $message
     * @return \Illuminate\Http\JsonResponse
     */
    protected function notFoundResponse(string $message = 'Resource not found')
    {
        return $this->errorResponse($message, null, 404);
    }

    /**
     * Return a server error response.
     *
     * @param string $message
     * @param mixed $error
     * @return \Illuminate\Http\JsonResponse
     */
    protected function serverErrorResponse(string $message = 'Internal server error', $error = null)
    {
        return $this->errorResponse($message, $error, 500);
    }
}

