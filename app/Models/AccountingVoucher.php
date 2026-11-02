<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountingVoucher extends Model
{
    use HasFactory;

    protected $fillable = [
        'voucher_no',
        'voucher_type',
        'voucher_date',
        'party_type',
        'party_id',
        'party_name',
        'debit_ledger',
        'credit_ledger',
        'amount',
        'payment_method',
        'reference_no',
        'narration',
        'created_by',
    ];

    protected $casts = [
        'voucher_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function generateVoucherNo(string $type = 'receipt'): string
    {
        $prefix = match (strtolower($type)) {
            'receipt' => 'RCP',
            'payment' => 'PMT',
            'sales' => 'SLS',
            'purchase' => 'PUR',
            'purchase_return' => 'PRT',
            'contra' => 'CNT',
            default => 'VCH',
        };

        $yearMonth = date('Ym');
        $last = self::where('voucher_no', 'LIKE', "{$prefix}-{$yearMonth}-%")
            ->latest('id')
            ->first();

        $seq = 1;
        if ($last) {
            $parts = explode('-', $last->voucher_no);
            $seq = ((int) end($parts)) + 1;
        }

        return sprintf('%s-%s-%04d', $prefix, $yearMonth, $seq);
    }
}
