<?php

namespace App\Http\Controllers\feeds;

use App\Services\FeedsServices;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class FeedsController extends Controller
{
    public function updateFromApi(Request $request, FeedsServices $service)
    {
        $limit = (int) $request->query('limit', 200);
        $sleep = (int) $request->query('sleep', 0);
        $result = $service->updateFromApi($limit, $sleep);

        return response()->json([
            'message' => 'Atualização executada com sucesso.',
            'data' => $result,
        ]);
    }
}