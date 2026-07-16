<?php

namespace App\Features\LayingPlanning\Controllers;

use App\Http\Controllers\Controller;
use App\Features\LayingPlanning\Services\LayingPlanningService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use App\Helpers\SizeHelper;

class LayingPlanningPdfController extends Controller
{
    protected $service;

    public function __construct(LayingPlanningService $service)
    {
        $this->service = $service;
    }

    public function exportPdf(Request $request, $id)
    {
        // Authenticate via query string token (for browser tab access)
        $token = $request->query('token');
        if ($token) {
            $accessToken = PersonalAccessToken::findToken($token);
            if (!$accessToken || ($accessToken->expires_at && $accessToken->expires_at->isPast())) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }
            // Set the authenticated user
            auth()->setUser($accessToken->tokenable);
        } else {
            // Fallback: check normal session/header auth
            if (!auth()->check()) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }
        }

        $planning = $this->service->findById($id);

        if (!$planning) {
            return response()->json(['message' => 'Laying Planning not found'], 404);
        }

        $sizes = $planning->sizeDetails ?? collect();
        $details = $planning->details ?? collect();

        // 1. Urutkan details berdasarkan tanggal dibuat (yang paling dulu)
        $details = $details->sortBy('created_at')->values();

        // 2. Urutkan sizes dari terkecil ke terbesar menggunakan SizeHelper
        $sizes = SizeHelper::sortCollection($sizes, 'size');

        // Pastikan juga setiap ukuran di dalam detail ikut terurut
        foreach ($details as $detail) {
            if (isset($detail->sizes)) {
                $detail->setRelation('sizes', SizeHelper::sortCollection($detail->sizes, 'size.size'));
            }
        }

        $pdf = Pdf::loadView('pdf.laying-planning-report', [
            'data' => $planning,
            'sizes' => $sizes,
            'details' => $details,
        ])->setPaper('a4', 'landscape');

        return $pdf->stream('Laying_Planning_Report_' . $planning->serial_number . '.pdf');
    }
}
