<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Services\ReceiptPrintService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PrintController extends Controller
{
    public function __construct(
        protected ReceiptPrintService $receiptPrintService
    ) {}

    /**
     * Render the thermal receipt view.
     */
    public function print(Bill $bill, Request $request): View
    {
        $printType = $request->input('type', 'print'); // print, reprint, duplicate
        $copyType = $request->input('copy', 'customer'); // customer, restaurant, both

        $receiptData = $this->receiptPrintService->prepareReceipt($bill, $printType, $copyType);

        return view('print.receipt', compact('receiptData'));
    }
}
