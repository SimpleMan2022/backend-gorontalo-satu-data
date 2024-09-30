<?php

namespace App\Http\Traits;

trait ApiResponse
{
    protected static $response1 = [
        'code' => 200,
        'status' => true,
        'message' => null,
        'data' => null
    ];
    protected static $response2 = [
        'code' => 200,
        'status' => true,
        'message' => null
    ];
    public static function successResponseWithDataIndex($data_model = null, $data_resource = null, $message = null, $code = 200)
    {
        self::$response1['code'] = $code;
        self::$response1['status'] = true;
        self::$response1['message'] = $message;
        self::$response1['data'] = [
            'pagination' => [
                'total' => $data_model->total(),
                'per_page' => $data_model->perPage(),
                'current_page' => $data_model->currentPage(),
                'total_pages' => $data_model->lastPage(),
            ],
            'data' => $data_resource
        ];
        return response()->json(self::$response1, self::$response1['code']);
    }
    public static function successResponseWithData($data = null, $message = null, $code = 200)
    {
        self::$response1['code'] = $code;
        self::$response1['status'] = true;
        self::$response1['message'] = $message;
        self::$response1['data'] = $data;
        return response()->json(self::$response1, self::$response1['code']);
    }
    public static function errorResponseWithData($data = null, $message = null, $code = 400)
    {
        self::$response1['code'] = $code;
        self::$response1['status'] = false;
        self::$response1['message'] = $message;
        self::$response1['data'] = $data;
        return response()->json(self::$response1, self::$response1['code']);
    }
    public static function successResponse($message = null, $code = 200)
    {
        self::$response2['code'] = $code;
        self::$response2['status'] = true;
        self::$response2['message'] = $message;
        return response()->json(self::$response2, self::$response2['code']);
    }
    public static function errorResponse($message = null, $code = 400)
    {
        self::$response2['code'] = $code;
        self::$response2['status'] = false;
        self::$response2['message'] = $message;
        return response()->json(self::$response2, self::$response2['code']);
    }
}
