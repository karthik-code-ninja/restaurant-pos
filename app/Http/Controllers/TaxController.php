<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Bill;
use App\Models\Food;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TaxController extends Controller
{
    public function index(Request $request): View
    {
        $taxType = Setting::get('tax_type', 'exclusive');
        $defaultGst = Setting::get('default_gst_rate', '5.00');
        $cgst = Setting::get('cgst_rate', '2.50');
        $sgst = Setting::get('sgst_rate', '2.50');
        $igst = Setting::get('igst_rate', '5.00');
        $gstin = Setting::get('restaurant_gstin', '');

        // Food wise tax list
        $foods = Food::with('category')->orderBy('category_id')->orderBy('name')->paginate(20);

        // GST Summary for current month or selected date
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::today()->toDateString());

        $bills = Bill::where('status', 'completed')
            ->whereBetween('created_at', [Carbon::parse($startDate)->startOfDay(), Carbon::parse($endDate)->endOfDay()])
            ->get();

        $taxableAmount = (float) $bills->sum(function ($b) {
            return $b->subtotal - $b->discount_amount;
        });
        $cgstTotal = (float) $bills->sum('cgst_total');
        $sgstTotal = (float) $bills->sum('sgst_total');
        $igstTotal = (float) $bills->sum('igst_total');
        $totalTaxCollected = (float) $bills->sum('tax_total');
        $totalGrossSales = (float) $bills->sum('grand_total');

        $currency = Setting::get('currency_symbol', '₹');

        return view('tax.index', compact(
            'taxType',
            'defaultGst',
            'cgst',
            'sgst',
            'igst',
            'gstin',
            'foods',
            'startDate',
            'endDate',
            'taxableAmount',
            'cgstTotal',
            'sgstTotal',
            'igstTotal',
            'totalTaxCollected',
            'totalGrossSales',
            'currency'
        ));
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tax_type' => ['required', 'in:inclusive,exclusive'],
            'default_gst_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'cgst_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'sgst_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'igst_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'restaurant_gstin' => ['nullable', 'string', 'max:20'],
        ]);

        $old = [
            'tax_type' => Setting::get('tax_type'),
            'default_gst_rate' => Setting::get('default_gst_rate'),
            'cgst_rate' => Setting::get('cgst_rate'),
            'sgst_rate' => Setting::get('sgst_rate'),
            'igst_rate' => Setting::get('igst_rate'),
            'restaurant_gstin' => Setting::get('restaurant_gstin'),
        ];

        Setting::set('tax_type', $validated['tax_type'], 'tax');
        Setting::set('default_gst_rate', $validated['default_gst_rate'], 'tax', 'decimal');
        Setting::set('cgst_rate', $validated['cgst_rate'], 'tax', 'decimal');
        Setting::set('sgst_rate', $validated['sgst_rate'], 'tax', 'decimal');
        Setting::set('igst_rate', $validated['igst_rate'], 'tax', 'decimal');
        Setting::set('restaurant_gstin', $validated['restaurant_gstin'] ?? '', 'restaurant');

        AuditLog::log(
            action: 'tax_settings_updated',
            module: 'tax',
            oldValues: $old,
            newValues: $validated,
            description: "Tax & GST settings updated"
        );

        return back()->with('success', 'Tax / GST settings updated successfully!');
    }
}
