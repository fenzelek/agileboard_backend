<?php

namespace Tests\Helpers;

use App\Helpers\ErrorCode;
use Illuminate\Testing\TestResponse;

trait VerifyResponse
{
    public function decodeResponseJson()
    {
        $help = app()->makeWith(TestResponse::class, ['response' => $this->response]);

        return $help->decodeResponseJson();
    }

    /**
     * Verify error response (Laravel < 5.4 way).
     *
     * @param int $httpCode
     * @param string|null $errorCode
     * @param array|null $fields
     * @param array $notFields
     */
    protected function verifyErrorResponse(
        $httpCode,
        $errorCode = null,
        $fields = [],
        array $notFields = []
    ) {
        $response = $this->seeStatusCode($httpCode);

        $response->seeJsonStructure([
                'fields',
                'code',
                'exec_time',
            ]);
        $response->isJson();

        $help = app()->makeWith(TestResponse::class, ['response' => $this->response]);
        $json = $help->json();

        $this->makeVerificationOfErrorResponse($json, $errorCode, $fields, $notFields);
    }

    protected function makeVerificationOfErrorResponse($json, $errorCode, $fields, $notFields, TestResponse $response = null)
    {
        $this->dontSeeFields($json, $notFields);

        $expected = [];

        if ($fields !== null) {
            if ($fields) {
                $response ? $response->assertJsonStructure(['fields' => $fields]) :
                    $this->seeJsonStructure(['fields' => $fields]);
                unset($json['fields']);
            } else {
                $expected['fields'] = [];
            }
        } else {
            unset($json['fields']);
        }

        if ($errorCode !== null) {
            $expected['code'] = $errorCode;
        } else {
            unset($json['code']);
        }

        unset($json['exec_time']);

        $this->assertEquals($expected, $json);
    }

    /**
     * Verify error response (Laravel 5.4+ way).
     *
     * @param TestResponse $response
     * @param int $httpCode
     * @param string|null $errorCode
     * @param array $fields
     * @param array $notFields
     */
    protected function verifyResponseError(
        TestResponse $response,
        $httpCode,
        $errorCode = null,
        array $fields = [],
        array $notFields = []
    ) {
        $response->assertStatus($httpCode)
            ->assertJsonStructure([
                'fields',
                'code',
                'exec_time',
            ]);

        $json = $response->json();

        $this->makeVerificationOfErrorResponse($json, $errorCode, $fields, $notFields, $response);
    }

    /**
     * Verify standard 422 "validation failed" response (Laravel < 5.4).
     *
     * @param array|null $fields
     * @param array $notFields Fields that should not be in response
     */
    protected function verifyValidationResponse(
        $fields,
        array $notFields = []
    ) {
        $this->verifyErrorResponse(
            422,
            ErrorCode::VALIDATION_FAILED,
            $fields,
            $notFields
        );
    }

    /**
     * Verify standard 422 "validation failed" response (Laravel 5.4+).
     *
     * @param TestResponse $response
     * @param array $fields
     * @param array $notFields Fields that should not be in response
     */
    protected function verifyResponseValidation(
        TestResponse $response,
        array $fields,
        array $notFields = []
    ) {
        $this->verifyResponseError(
            $response,
            422,
            ErrorCode::VALIDATION_FAILED,
            $fields,
            $notFields
        );
    }

    /**
     * Verify if there are no given fields in given json array.
     *
     * @param array $json
     * @param array $fields
     */
    protected function dontSeeFields(array $json, array $fields)
    {
        if (! $fields) {
            return;
        }

        $jsonFields = array_keys($json['fields']);

        foreach ($fields as $field) {
            $this->assertFalse(
                in_array($field, $jsonFields),
                'There is no ' . $field . ' in fields'
            );
        }
    }
}
