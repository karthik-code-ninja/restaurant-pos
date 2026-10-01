<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Setting;
use Illuminate\Support\Str;

class PrinterProfileService
{
    /**
     * Default printer profiles when none configured.
     */
    public static function defaultProfiles(): array
    {
        return [
            [
                'id' => 'prof_kitchen_default',
                'name' => 'Kitchen Thermal 80mm',
                'purpose' => 'kitchen', // kitchen, counter, bar, both
                'connection_type' => 'browser', // browser, network, driver
                'ip_address' => '192.168.1.200',
                'port' => 9100,
                'driver_name' => 'Kitchen_Printer',
                'paper_width' => '80mm', // 80mm, 58mm
                'copies' => 1,
                'auto_cut' => true,
                'open_cash_drawer' => false,
                'is_active' => true,
                'notes' => 'Kitchen Order Ticket (KOT) printer',
            ],
            [
                'id' => 'prof_counter_default',
                'name' => 'Counter Cashier 80mm',
                'purpose' => 'counter', // kitchen, counter, bar, both
                'connection_type' => 'browser', // browser, network, driver
                'ip_address' => '192.168.1.100',
                'port' => 9100,
                'driver_name' => 'Counter_Printer',
                'paper_width' => '80mm', // 80mm, 58mm
                'copies' => 1,
                'auto_cut' => true,
                'open_cash_drawer' => true, // Auto kick drawer on bill pay
                'is_active' => true,
                'notes' => 'Customer receipt and checkout billing printer',
            ],
        ];
    }

    /**
     * Retrieve all printer profiles from settings.
     */
    public static function getAllProfiles(): array
    {
        $raw = Setting::get('printer_profiles');
        if (empty($raw)) {
            return self::defaultProfiles();
        }

        $decoded = is_array($raw) ? $raw : json_decode($raw, true);
        if (!is_array($decoded) || empty($decoded)) {
            return self::defaultProfiles();
        }

        return $decoded;
    }

    /**
     * Retrieve active profiles by purpose (e.g. 'kitchen', 'counter', 'bar').
     */
    public static function getProfilesByPurpose(string $purpose): array
    {
        $all = self::getAllProfiles();
        return array_values(array_filter($all, function ($p) use ($purpose) {
            $isActive = !isset($p['is_active']) || (bool) $p['is_active'];
            $matchesPurpose = ($p['purpose'] ?? '') === $purpose || ($p['purpose'] ?? '') === 'both';
            return $isActive && $matchesPurpose;
        }));
    }

    /**
     * Get active kitchen printers.
     */
    public static function getKitchenPrinters(): array
    {
        return self::getProfilesByPurpose('kitchen');
    }

    /**
     * Get active counter printers.
     */
    public static function getCounterPrinters(): array
    {
        return self::getProfilesByPurpose('counter');
    }

    /**
     * Save / Upsert a single printer profile.
     */
    public static function saveProfile(array $data): array
    {
        $profiles = self::getAllProfiles();
        $id = $data['id'] ?? ('prof_' . substr(md5(uniqid('', true)), 0, 10));

        $cleanProfile = [
            'id' => $id,
            'name' => trim((string) ($data['name'] ?? 'Thermal Printer')),
            'purpose' => in_array($data['purpose'] ?? '', ['kitchen', 'counter', 'bar', 'both']) ? $data['purpose'] : 'kitchen',
            'connection_type' => in_array($data['connection_type'] ?? '', ['browser', 'network', 'driver']) ? $data['connection_type'] : 'browser',
            'ip_address' => trim((string) ($data['ip_address'] ?? '192.168.1.100')),
            'port' => (int) ($data['port'] ?? 9100),
            'driver_name' => trim((string) ($data['driver_name'] ?? 'POS_Thermal_Printer')),
            'paper_width' => in_array($data['paper_width'] ?? '', ['58mm', '80mm']) ? $data['paper_width'] : '80mm',
            'copies' => max(1, min(5, (int) ($data['copies'] ?? 1))),
            'auto_cut' => !empty($data['auto_cut']),
            'open_cash_drawer' => !empty($data['open_cash_drawer']),
            'is_active' => !empty($data['is_active']),
            'notes' => trim((string) ($data['notes'] ?? '')),
        ];

        $found = false;
        foreach ($profiles as $key => $existing) {
            if ($existing['id'] === $id) {
                $profiles[$key] = $cleanProfile;
                $found = true;
                break;
            }
        }

        if (!$found) {
            $profiles[] = $cleanProfile;
        }

        Setting::set('printer_profiles', $profiles, 'printer', 'json');

        AuditLog::log(
            action: 'printer_profile_saved',
            module: 'settings',
            referenceId: $id,
            description: "Printer profile '{$cleanProfile['name']}' saved for purpose: {$cleanProfile['purpose']}"
        );

        return $cleanProfile;
    }

    /**
     * Delete a printer profile by ID.
     */
    public static function deleteProfile(string $id): bool
    {
        $profiles = self::getAllProfiles();
        $initialCount = count($profiles);

        $filtered = array_values(array_filter($profiles, fn ($p) => ($p['id'] ?? '') !== $id));

        if (count($filtered) !== $initialCount) {
            Setting::set('printer_profiles', $filtered, 'printer', 'json');
            AuditLog::log(
                action: 'printer_profile_deleted',
                module: 'settings',
                referenceId: $id,
                description: "Deleted printer profile ID {$id}"
            );
            return true;
        }

        return false;
    }

    /**
     * Send direct ESC/POS socket stream to network printer IP:port.
     */
    public static function printRawEscpos(string $ip, int $port, string $data, float $timeout = 2.0): array
    {
        $fp = @fsockopen($ip, $port, $errno, $errstr, $timeout);
        if (!$fp) {
            return [
                'success' => false,
                'message' => "Connection to printer at {$ip}:{$port} failed: {$errstr} (code {$errno})",
            ];
        }

        // Set stream timeout
        stream_set_timeout($fp, (int) $timeout);
        fwrite($fp, $data);
        fflush($fp);
        fclose($fp);

        return [
            'success' => true,
            'message' => "Successfully sent raw data to printer at {$ip}:{$port}",
        ];
    }

    /**
     * Test a printer profile.
     */
    public static function testPrint(string $profileId): array
    {
        $profiles = self::getAllProfiles();
        $target = null;
        foreach ($profiles as $p) {
            if ($p['id'] === $profileId) {
                $target = $p;
                break;
            }
        }

        if (!$target) {
            return ['success' => false, 'message' => 'Printer profile not found.'];
        }

        if ($target['connection_type'] === 'network') {
            $ip = $target['ip_address'] ?? '127.0.0.1';
            $port = (int) ($target['port'] ?? 9100);

            $testEscpos = self::generateTestEscpos($target);
            return self::printRawEscpos($ip, $port, $testEscpos, 2.5);
        }

        return [
            'success' => true,
            'message' => "Profile '{$target['name']}' is ready for {$target['connection_type']} printing (Purpose: " . strtoupper($target['purpose']) . ").",
            'profile' => $target,
        ];
    }

    /**
     * Build ESC/POS bytes for a test ticket.
     */
    public static function generateTestEscpos(array $profile): string
    {
        $esc = "\x1B";
        $gs = "\x1D";

        $out = "";
        // Initialize
        $out .= "{$esc}@";
        
        // Center Align
        $out .= "{$esc}a\x01";
        $out .= "{$esc}E\x01"; // Bold on
        $out .= "=== PRINTER TEST PAGE ===\n";
        $out .= "RestroPOS Billing System\n";
        $out .= "{$esc}E\x00"; // Bold off
        $out .= "--------------------------------\n";

        // Left Align
        $out .= "{$esc}a\x00";
        $out .= "Profile Name: " . ($profile['name'] ?? 'Thermal') . "\n";
        $out .= "Assigned For: " . strtoupper($profile['purpose'] ?? 'kitchen') . "\n";
        $out .= "Paper Width : " . ($profile['paper_width'] ?? '80mm') . "\n";
        $out .= "Target IP   : " . ($profile['ip_address'] ?? '') . ":" . ($profile['port'] ?? 9100) . "\n";
        $out .= "Test Date   : " . date('d/m/Y h:i:s A') . "\n";
        $out .= "--------------------------------\n";

        // Center Align
        $out .= "{$esc}a\x01";
        $out .= "STATUS: HARDWARE OK!\n\n";

        // Cash drawer kick if enabled
        if (!empty($profile['open_cash_drawer'])) {
            $out .= "{$esc}p\x00\x19\xFA"; // Kick drawer 0
        }

        // Auto cut if enabled
        if (!empty($profile['auto_cut'])) {
            $out .= "\n\n\n\n{$gs}V\x41\x03"; // Feed and cut
        } else {
            $out .= "\n\n\n";
        }

        return $out;
    }

    /**
     * Build ESC/POS formatted bytes for a Kitchen Order Ticket (KOT).
     */
    public static function generateKotEscpos(array $kotData, array $profile): string
    {
        $esc = "\x1B";
        $gs = "\x1D";
        $width = ($profile['paper_width'] ?? '80mm') === '58mm' ? 32 : 42;

        $out = "{$esc}@"; // Init
        $out .= "{$esc}a\x01"; // Center
        $out .= "{$esc}E\x01"; // Bold
        $out .= "{$gs}!\x11"; // Double width & height
        $out .= "KOT #" . ($kotData['kot_number'] ?? '1') . "\n";
        $out .= "{$gs}!\x00"; // Normal size
        $out .= "TABLE: " . ($kotData['table_number'] ?? 'Counter') . "\n";
        $out .= "{$esc}E\x00"; // Bold off

        $out .= str_repeat('-', $width) . "\n";
        $out .= "{$esc}a\x00"; // Left
        $out .= "Time: " . ($kotData['time'] ?? date('h:i A')) . " | Waiter: " . ($kotData['waiter'] ?? 'Staff') . "\n";
        if (!empty($kotData['order_notes'])) {
            $out .= "Notes: " . $kotData['order_notes'] . "\n";
        }
        $out .= str_repeat('-', $width) . "\n";

        $out .= sprintf("%-28s %10s\n", "ITEM", "QTY");
        $out .= str_repeat('-', $width) . "\n";

        foreach ($kotData['items'] ?? [] as $item) {
            $out .= "{$esc}E\x01";
            $out .= sprintf("%-28s %10s\n", substr($item['name'] ?? '', 0, 28), "[ " . ($item['quantity'] ?? 1) . " ]");
            $out .= "{$esc}E\x00";

            if (!empty($item['addons'])) {
                foreach ($item['addons'] as $addon) {
                    $out .= "  + " . ($addon['name'] ?? '') . "\n";
                }
            }

            if (!empty($item['notes'])) {
                $out .= "  * Note: " . $item['notes'] . "\n";
            }
        }

        $out .= str_repeat('-', $width) . "\n";
        $out .= "Total Items: " . count($kotData['items'] ?? []) . " | Total Qty: " . ($kotData['total_quantity'] ?? 0) . "\n";

        if (!empty($profile['auto_cut'])) {
            $out .= "\n\n\n\n{$gs}V\x41\x03";
        } else {
            $out .= "\n\n\n";
        }

        return $out;
    }

    /**
     * Build ESC/POS formatted bytes for a Customer Bill Receipt.
     */
    public static function generateReceiptEscpos(array $receiptData, array $profile): string
    {
        $esc = "\x1B";
        $gs = "\x1D";
        $width = ($profile['paper_width'] ?? '80mm') === '58mm' ? 32 : 42;
        $curr = $receiptData['bill']['currency'] ?? 'Rs.';

        $out = "{$esc}@"; // Init
        $out .= "{$esc}a\x01"; // Center
        $out .= "{$esc}E\x01"; // Bold
        $out .= ($receiptData['restaurant']['name'] ?? 'RESTAURANT') . "\n";
        $out .= "{$esc}E\x00"; // Bold off
        
        if (!empty($receiptData['restaurant']['address'])) {
            $out .= $receiptData['restaurant']['address'] . "\n";
        }
        if (!empty($receiptData['restaurant']['contact'])) {
            $out .= "Ph: " . $receiptData['restaurant']['contact'] . "\n";
        }
        if (!empty($receiptData['restaurant']['gstin'])) {
            $out .= "GSTIN: " . $receiptData['restaurant']['gstin'] . "\n";
        }

        $out .= str_repeat('=', $width) . "\n";
        $out .= "TAX INVOICE #" . ($receiptData['bill']['invoice_number'] ?? '') . "\n";
        $out .= "Date: " . ($receiptData['bill']['date'] ?? '') . " " . ($receiptData['bill']['time'] ?? '') . "\n";
        if (!empty($receiptData['bill']['table_number'])) {
            $out .= "Table: " . $receiptData['bill']['table_number'] . " | Cashier: " . ($receiptData['bill']['cashier'] ?? '') . "\n";
        }
        $out .= str_repeat('-', $width) . "\n";

        // Items
        $out .= "{$esc}a\x00"; // Left
        $out .= sprintf("%-20s %4s %8s %8s\n", "ITEM", "QTY", "RATE", "TOTAL");
        $out .= str_repeat('-', $width) . "\n";

        foreach ($receiptData['items'] ?? [] as $item) {
            $out .= sprintf("%-42s\n", substr($item['name'] ?? '', 0, 40));
            $out .= sprintf("  %18s %4s %8s %8s\n", "", (int)($item['quantity'] ?? 1), number_format($item['unit_price'] ?? 0, 2), number_format($item['total'] ?? 0, 2));
        }

        $out .= str_repeat('-', $width) . "\n";
        $out .= sprintf("%-28s %12s\n", "Subtotal:", number_format($receiptData['bill']['subtotal'] ?? 0, 2));
        
        if (($receiptData['bill']['discount_amount'] ?? 0) > 0) {
            $out .= sprintf("%-28s -%11s\n", "Discount:", number_format($receiptData['bill']['discount_amount'], 2));
        }
        if (($receiptData['bill']['tax_total'] ?? 0) > 0) {
            $out .= sprintf("%-28s %12s\n", "GST Total:", number_format($receiptData['bill']['tax_total'], 2));
        }
        if (($receiptData['bill']['rounding_difference'] ?? 0) != 0) {
            $out .= sprintf("%-28s %12s\n", "Round Off:", number_format($receiptData['bill']['rounding_difference'], 2));
        }

        $out .= str_repeat('=', $width) . "\n";
        $out .= "{$esc}E\x01"; // Bold
        $out .= sprintf("%-24s %16s\n", "GRAND TOTAL:", $curr . number_format($receiptData['bill']['grand_total'] ?? 0, 2));
        $out .= "{$esc}E\x00"; // Bold off
        $out .= str_repeat('=', $width) . "\n";

        // Payments
        foreach ($receiptData['payments'] ?? [] as $p) {
            $out .= sprintf("Paid by %s: %s\n", $p['method'] ?? 'CASH', number_format($p['amount'] ?? 0, 2));
        }

        $out .= "{$esc}a\x01"; // Center
        $out .= "\nThank you for dining with us!\n";
        $out .= "Please visit again.\n";

        // Cash drawer kick
        if (!empty($profile['open_cash_drawer'])) {
            $out .= "{$esc}p\x00\x19\xFA";
        }

        // Cut
        if (!empty($profile['auto_cut'])) {
            $out .= "\n\n\n\n{$gs}V\x41\x03";
        } else {
            $out .= "\n\n\n";
        }

        return $out;
    }
}
