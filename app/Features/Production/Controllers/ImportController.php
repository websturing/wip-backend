<?php

namespace App\Features\Production\Controllers;

use App\Http\Controllers\Controller;
use App\Features\Production\Services\ProductionImportService;
use Illuminate\Http\Request;

class ImportController extends Controller
{
    protected $importService;

    public function __construct(ProductionImportService $importService)
    {
        $this->importService = $importService;
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        try {
            $file = $request->file('file');
            $summary = $this->importService->import($file->getPathname());

            return response()->json([
                'status' => 'success',
                'message' => 'Production data imported successfully',
                'summary' => $summary
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Import failed: ' . $e->getMessage()
            ], 500);
        }
    }
}
