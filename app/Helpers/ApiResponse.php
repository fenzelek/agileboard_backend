<?php

namespace App\Helpers;

use App\Http\Resources\ObjectResource;
use Illuminate\Support\Facades\Log;
use Traversable;
use Illuminate\Support\Arr;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Response helper class providing consistent structure of the json content
 * that is sent by the application. It tries to transform the resources
 * using dedicated transformer if there is one in default namespace.
 *
 * @see \App\Http\Resources\
 */
class ApiResponse
{
    /**
     * Json response to valid request.
     *
     * @param mixed $data
     * @param int $code
     * @param array $additional
     * @param array $headers
     * @param int $options
     * @param array $transformers
     *
     * @return JsonResponse
     */
    public static function responseOk(
        $data = [],
        $code = 200,
        array $additional = [],
        array $headers = [],
        $options = 0,
        array $transformers = []
    ) {
        list($data, $meta) = self::transform($data, $transformers);

        $response['data'] = $data;

        if ($meta !== null) {
            $response['meta'] = $meta;
        }

        $json = array_merge(
            $response,
            $additional,
            ['exec_time' => self::getExecutionTime()]
        );

        return new JsonResponse($json, $code, $headers, $options);
    }

    public static function transResponseOk(
        $data = [],
        $code = 200,
        array $transformers = []
    ) {
        return self::responseOk($data, $code, [], [], 0, $transformers);
    }

    /**
     * Json response to invalid request.
     *
     * @param string $errorCode
     * @param int $status
     * @param array $fields
     * @param array $headers
     * @param int $options
     *
     * @return JsonResponse
     */
    public static function responseError(
        $errorCode,
        $status,
        $fields = [],
        $headers = [],
        $options = 0
    ) {
        $json = [
            'code' => $errorCode,
            'fields' => $fields,
            'exec_time' => self::getExecutionTime(),
        ];

        // log any response error
        Log::error('Error response occurred', [
            'error_code' => $errorCode,
            'status' => $status,
            'fields' => $fields,
        ]);

        return new JsonResponse($json, $status, $headers, $options);
    }

    public static function paginationFormat($data)
    {
        $data = $data->toArray();
        $responseData = Arr::pull($data, 'data', $data);
        if (isset($data['total'])) {
            return [$responseData, ['pagination' => [
                'total' => $data['total'],
                'count' => count($responseData),
                'per_page' => $data['per_page'],
                'current_page' => $data['current_page'],
                'total_pages' => ceil($data['total'] / $data['per_page']),
                'first_page_url' => $data['first_page_url'],
                'last_page_url' => $data['last_page_url'],
                'links' => [
                    'previous' => $data['prev_page_url'],
                    'next' => $data['next_page_url'],
                    ],
            ]]];
        }

        return [$responseData, null];
    }

    public static function hasResource($resource, array $transformers = [])
    {
        if (isset($transformers[get_class($resource)])) {
            return $transformers[get_class($resource)];
        }

        return class_exists(
            $transformer = 'App\Http\Resources\\' . class_basename($resource)
        )
            ? $transformer
            : false;
    }

    public static function isResource($data)
    {
        return $data instanceof Model;
    }

    public static function isCollection($data)
    {
        return (is_array($data) || $data instanceof Traversable) &&
            isset($data[0]);
    }

    /**
     * Get API execution time.
     *
     * @return float
     */
    protected static function getExecutionTime()
    {
        return defined('LARAVEL_START') ?
            round(microtime(true) - LARAVEL_START, 4) : 0;
    }

    /**
     * Transform resource with laravel resource if possible.
     *
     * @param mixed $data
     *
     * @return mixed
     */
    private static function transform($data, array $transformers = [])
    {
        //one resource
        if (self::isResource($data) &&
            $resource = self::hasResource($data, $transformers)
        ) {
            if (self::hasResource($data, $transformers)) {
                $data = new $resource($data, $transformers);
            } else {
                $data = new ObjectResource($data, $transformers);
            }

            return [Arr::get($data, 'data', $data), null];
        }

        //collection
        if ((self::isCollection($data) && self::isResource($data[0])) ||
            $data instanceof LengthAwarePaginator
        ) {
            // get transformer
            $resource = self::isResource($data[0]) ?
                self::hasResource($data[0], $transformers) : null;

            // if no object transformer is found, we will use general object
            // transformer
            if ($resource) {
                foreach ($data as $key => $obj) {
                    $data[$key] = new $resource($obj, $transformers);
                }
            } else {
                foreach ($data as $key => $obj) {
                    $data[$key] = new ObjectResource($obj, $transformers);
                }
            }

            return self::paginationFormat($data);
        }

        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = self::transform($value, $transformers)[0];
            }
        }

        //other
        return [$data, null];
    }
}
