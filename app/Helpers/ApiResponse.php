<?php

if (!function_exists('apiResponse')) {
    /**
     * Standardized API response
     *
     * @param mixed $data
     * @param string $message
     * @param int $status
     * @param array $errors
     * @return \Illuminate\Http\JsonResponse
     */
    function apiResponse($data = null, $message = 'Success', $status = 200, $errors = [])
    {
        $response = [
            'success' => $status >= 200 && $status < 300,
            'message' => $message,
            'data' => $data,
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $status);
    }
}
