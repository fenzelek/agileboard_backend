<?php

namespace App\Http\Resources;

use App\Helpers\ApiResponse;
use Illuminate\Http\Resources\Json\JsonResource;

abstract class AbstractResource extends JsonResource
{
    /**
     * Relationships that will be ignored when transforming.
     *
     * @var array
     */
    protected $ignoredRelationships = [];

    /**
     * Fields that should be assigned to array from model (1:1). If set to * (string) all model
     * attributes will be used.
     *
     * @var array
     */
    protected $fields = [];

    protected $map = [];

    /**
     * AbstractResource constructor.
     * @param $resource
     * @param array $map
     */
    public function __construct($resource, $map = [])
    {
        parent::__construct($resource);

        $this->map = $map;
    }

    /**
     * Transforms given object to array - it will choose only specified fields.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        $data = [];

        if (is_string($this->fields) && $this->fields == '*') {
            $data = $this->resource->getAttributes();
        } else {
            foreach ($this->fields as $field) {
                $data[$field] = $this->$field;
            }
        }

        return $this->transformRelations($data);
    }

    /**
     * Transform all loaded relations for object (wrap relationship data into
     * data array).
     *
     * @param array $data
     *
     * @return array
     */
    protected function transformRelations(array $data)
    {
        $t = $this->resource;
        $t2 = $this->resource->getRelations();
        foreach ($this->resource->getRelations() as $relations_name => $data_relation) {
            if ($relations_name != 'pivot' &&
                ! in_array($relations_name, $this->ignoredRelationships)
            ) {
                //transform relation (one item)
                if (ApiResponse::isResource($data_relation)) {
                    if ($resource = ApiResponse::hasResource($data_relation, $this->map)) {
                        $data_relation = new $resource($data_relation, $this->map);
                    } else {
                        $data_relation = new ObjectResource($data_relation, $this->map);
                    }
                    $data_relation = ['data' => $data_relation];
                } elseif (ApiResponse::isCollection($data_relation)) {
                    $first = $data_relation->first();
                    $transformer = ApiResponse::isResource($first) ?
                        ApiResponse::hasResource($first, $this->map) : null;

//                    // if no object resource is found, we will use general object resource
                    if ($transformer) {
                        foreach ($data_relation as $key => $obj) {
                            $data_relation[$key] = new $transformer($obj, $this->map);
                        }
                    } else {
                        foreach ($data_relation as $key => $obj) {
                            $data_relation[$key] = new ObjectResource($obj, $this->map);
                        }
                    }

                    //add pagination
                    list($data_new, $meta_new) = ApiResponse::paginationFormat($data_relation);

                    $response['data'] = $data_new;

                    if ($meta_new !== null) {
                        $response['meta'] = $meta_new;
                    }
                    $data_relation = $response;
                } else {
                    $data_relation = ['data' => $data_relation];
                }

                $data[snake_case($relations_name)] = $data_relation;
            }
        }

        return $data;
    }
}
