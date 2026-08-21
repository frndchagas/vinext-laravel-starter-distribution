<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

class AuthCapabilitiesController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'registration' => config('features.registration') === true,
        ]);
    }
}
