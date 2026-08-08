<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiProblem
{
    /** @param array<int, array<string, mixed>> $errors */
    public static function response(
        Request $request,
        int $status,
        string $code,
        string $title,
        string $detail,
        array $errors = [],
        array $headers = [],
    ): JsonResponse {
        $body = [
            'type' => "https://simutu.example/problems/{$code}",
            'title' => $title,
            'status' => $status,
            'detail' => $detail,
            'instance' => '/'.$request->path(),
            'code' => $code,
            'request_id' => $request->attributes->get('request_id'),
        ];

        if ($errors !== []) {
            $body['errors'] = $errors;
        }

        return response()->json($body, $status, $headers + [
            'Content-Type' => 'application/problem+json',
        ]);
    }
}
