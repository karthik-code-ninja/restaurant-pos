<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Bill;
use App\Models\Customer;
use App\Models\CustomerLedger;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CustomerLedgerService
{
    /**
     * Record a debit transaction for a bill charged to customer's ledger/credit.
     */
    public function recordBillCredit(
        Customer $customer,
        Bill $bill,
        float $amount,
        ?int $userId = null,
        ?string $notes = null
    ): CustomerLedger {
        return DB::transaction(function () use ($customer, $bill, $amount, $userId, $notes) {
            /** @var Customer $lockedCustomer */
            $lockedCustomer = Customer::lockForUpdate()->findOrFail($customer->id);

            $previousBalance = (float) $lockedCustomer->balance;
            $newBalance = round($previousBalance + $amount, 2);

            $lockedCustomer->update([
                'balance' => $newBalance,
            ]);

            $ledger = CustomerLedger::create([
                'customer_id' => $lockedCustomer->id,
                'bill_id' => $bill->id,
                'entry_date' => now(),
                'type' => 'debit',
                'amount' => $amount,
                'balance_after' => $newBalance,
                'payment_mode' => 'credit_sale',
                'reference_number' => $bill->invoice_number,
                'description' => $notes ?: "Credit sale on Invoice #{$bill->invoice_number}",
                'created_by' => $userId ?? Auth::id(),
            ]);

            AuditLog::log(
                action: 'customer_bill_credited',
                module: 'customers',
                referenceId: (string) $lockedCustomer->id,
                oldValues: ['balance' => $previousBalance],
                newValues: ['balance' => $newBalance, 'ledger_id' => $ledger->id],
                description: "Charged ₹{$amount} to {$lockedCustomer->name}'s ledger for Bill #{$bill->invoice_number}. New balance: ₹{$newBalance}"
            );

            return $ledger;
        });
    }

    /**
     * Record a payment (credit) received from customer settling their balance/due.
     */
    public function recordPayment(
        Customer $customer,
        float $amount,
        string $paymentMode = 'cash',
        ?string $reference = null,
        ?string $description = null,
        ?int $userId = null,
        ?int $billId = null,
        ?int $paymentId = null
    ): CustomerLedger {
        return DB::transaction(function () use (
            $customer,
            $amount,
            $paymentMode,
            $reference,
            $description,
            $userId,
            $billId,
            $paymentId
        ) {
            /** @var Customer $lockedCustomer */
            $lockedCustomer = Customer::lockForUpdate()->findOrFail($customer->id);

            $previousBalance = (float) $lockedCustomer->balance;
            // Payment decreases the amount customer owes
            $newBalance = round($previousBalance - $amount, 2);

            $lockedCustomer->update([
                'balance' => $newBalance,
            ]);

            $desc = $description ?: ('Payment received via '.strtoupper($paymentMode));

            $ledger = CustomerLedger::create([
                'customer_id' => $lockedCustomer->id,
                'bill_id' => $billId,
                'payment_id' => $paymentId,
                'entry_date' => now(),
                'type' => 'credit',
                'amount' => $amount,
                'balance_after' => $newBalance,
                'payment_mode' => $paymentMode,
                'reference_number' => $reference,
                'description' => $desc,
                'created_by' => $userId ?? Auth::id(),
            ]);

            AuditLog::log(
                action: 'customer_payment_received',
                module: 'customers',
                referenceId: (string) $lockedCustomer->id,
                oldValues: ['balance' => $previousBalance],
                newValues: ['balance' => $newBalance, 'ledger_id' => $ledger->id],
                description: "Recorded payment of ₹{$amount} ({$paymentMode}) for {$lockedCustomer->name}. New balance: ₹{$newBalance}"
            );

            return $ledger;
        });
    }

    /**
     * Record a manual ledger adjustment (either debit or credit).
     */
    public function recordAdjustment(
        Customer $customer,
        string $type,
        float $amount,
        ?string $reference = null,
        ?string $description = null,
        ?int $userId = null
    ): CustomerLedger {
        return DB::transaction(function () use ($customer, $type, $amount, $reference, $description, $userId) {
            /** @var Customer $lockedCustomer */
            $lockedCustomer = Customer::lockForUpdate()->findOrFail($customer->id);

            $previousBalance = (float) $lockedCustomer->balance;

            if ($type === 'debit') {
                $newBalance = round($previousBalance + $amount, 2);
            } else {
                $newBalance = round($previousBalance - $amount, 2);
            }

            $lockedCustomer->update([
                'balance' => $newBalance,
            ]);

            $desc = $description ?: (ucfirst($type).' adjustment');

            $ledger = CustomerLedger::create([
                'customer_id' => $lockedCustomer->id,
                'entry_date' => now(),
                'type' => $type,
                'amount' => $amount,
                'balance_after' => $newBalance,
                'payment_mode' => 'adjustment',
                'reference_number' => $reference,
                'description' => $desc,
                'created_by' => $userId ?? Auth::id(),
            ]);

            AuditLog::log(
                action: 'customer_ledger_adjusted',
                module: 'customers',
                referenceId: (string) $lockedCustomer->id,
                oldValues: ['balance' => $previousBalance],
                newValues: ['balance' => $newBalance, 'ledger_id' => $ledger->id],
                description: "Manual {$type} adjustment of ₹{$amount} for {$lockedCustomer->name}. New balance: ₹{$newBalance}"
            );

            return $ledger;
        });
    }

    /**
     * Revert customer ledger entries if a bill is cancelled.
     */
    public function revertBill(Bill $bill, ?string $reason = null, ?int $userId = null): void
    {
        DB::transaction(function () use ($bill, $reason, $userId) {
            // Find any debit ledger entries tied to this bill
            $billDebits = CustomerLedger::where('bill_id', $bill->id)
                ->where('type', 'debit')
                ->get();

            foreach ($billDebits as $debitEntry) {
                /** @var Customer $lockedCustomer */
                $lockedCustomer = Customer::lockForUpdate()->find($debitEntry->customer_id);
                if (! $lockedCustomer) {
                    continue;
                }

                $previousBalance = (float) $lockedCustomer->balance;
                // Reversing a debit is a credit
                $newBalance = round($previousBalance - (float) $debitEntry->amount, 2);

                $lockedCustomer->update([
                    'balance' => $newBalance,
                ]);

                $reasonText = $reason ? " (Reason: {$reason})" : '';

                CustomerLedger::create([
                    'customer_id' => $lockedCustomer->id,
                    'bill_id' => $bill->id,
                    'entry_date' => now(),
                    'type' => 'credit',
                    'amount' => $debitEntry->amount,
                    'balance_after' => $newBalance,
                    'payment_mode' => 'adjustment',
                    'reference_number' => $bill->invoice_number,
                    'description' => "Reversal of Bill #{$bill->invoice_number}{$reasonText}",
                    'created_by' => $userId ?? Auth::id(),
                ]);

                AuditLog::log(
                    action: 'customer_bill_reversal',
                    module: 'customers',
                    referenceId: (string) $lockedCustomer->id,
                    oldValues: ['balance' => $previousBalance],
                    newValues: ['balance' => $newBalance],
                    description: "Reversed ₹{$debitEntry->amount} for cancelled Bill #{$bill->invoice_number} from {$lockedCustomer->name}'s ledger"
                );
            }
        });
    }

    /**
     * Recalculate customer's balance from all transaction history.
     */
    public function recalculateBalance(Customer $customer): float
    {
        return DB::transaction(function () use ($customer) {
            $totalDebit = (float) CustomerLedger::where('customer_id', $customer->id)
                ->where('type', 'debit')
                ->sum('amount');

            $totalCredit = (float) CustomerLedger::where('customer_id', $customer->id)
                ->where('type', 'credit')
                ->sum('amount');

            $newBalance = round($totalDebit - $totalCredit, 2);

            $customer->update(['balance' => $newBalance]);

            return $newBalance;
        });
    }
}
