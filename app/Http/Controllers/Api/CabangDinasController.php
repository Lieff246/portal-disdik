<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CabangDinas;
use Illuminate\Http\JsonResponse;

class CabangDinasController extends Controller
{
    /**
     * Daftar semua cabang dinas beserta wilayah yang dicakupnya.
     */
    public function index(): JsonResponse
    {
        $data = CabangDinas::all();

        return response()->json([
            'status' => 'success',
            'data'   => $data,
        ]);
    }
}
