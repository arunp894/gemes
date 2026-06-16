<?php

namespace App\Http\Controllers;

use App\Services\BarcodeHistoryService;
use Illuminate\Http\Request;

class BarcodeHistoryController extends Controller
{
    public function __construct(private BarcodeHistoryService $historyService) {}

    /**
     * Show the barcode scanner / history page.
     */
    public function index()
    {
        return view('barcode-history.index');
    }

    /**
     * AJAX: resolve a barcode and return the full product history as JSON.
     *
     * GET /barcode-history/lookup?barcode=<value>
     */
    public function lookup(Request $request)
    {
        $request->validate([
            'barcode' => 'required|string|max:150',
        ]);

        $result = $this->historyService->lookup(
            trim($request->input('barcode'))
        );

        return response()->json($result);
    }
}
