<?php

namespace App\Http\Controllers;

use App\Services\BarcodeHistoryService;
use App\Services\SettingService;
use Illuminate\Http\Request;

class BarcodeHistoryController extends Controller
{
    public function __construct(
        private BarcodeHistoryService $historyService,
        private SettingService $settings,
    ) {}

    /**
     * Show the barcode scanner / history page.
     */
    public function index()
    {
        return view('barcode-history.index', [
            'currencySymbol' => $this->settings->get('currency_symbol', '₹'),
        ]);
    }

    /**
     * AJAX: resolve a barcode or lot code and return the full product history as JSON.
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
