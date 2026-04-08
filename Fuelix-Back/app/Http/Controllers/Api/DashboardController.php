<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DashboardResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
   public function home(): JsonResponse
    {
        $user = Auth::user();
        return response()->json(new DashboardResource($user));
    }
}