<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AiInsightService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiInsightController extends Controller
{
    public function __construct(private readonly AiInsightService $insights)
    {
    }

    public function show(Request $request): JsonResponse
    {
        return response()->json($this->insights->forUser($request->user()));
    }
}
