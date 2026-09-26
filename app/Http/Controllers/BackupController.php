<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\LoginLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BackupController extends Controller
{
    public function index(): View
    {
        return view('backup.index');
    }

    public function download(): StreamedResponse
    {
        $databaseName = config('database.connections.mysql.database');
        $filename = "backup-{$databaseName}-" . date('Y-m-d_H-i-s') . ".sql";

        AuditLog::log(
            action: 'database_backup_downloaded',
            module: 'backup',
            description: "Database SQL backup downloaded: {$filename}"
        );

        return response()->streamDownload(function () {
            $pdo = DB::connection()->getPdo();
            $tables = $pdo->query("SHOW TABLES")->fetchAll(\PDO::FETCH_COLUMN);

            echo "-- Restro Restaurant POS Database Backup\n";
            echo "-- Generated at: " . date('Y-m-d H:i:s') . "\n";
            echo "-- -----------------------------------------------------\n\n";
            echo "SET foreign_key_checks = 0;\n\n";

            foreach ($tables as $table) {
                // Get create table statement
                $createTable = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(\PDO::FETCH_ASSOC);
                echo "DROP TABLE IF EXISTS `{$table}`;\n";
                echo $createTable['Create Table'] . ";\n\n";

                // Get table rows
                $rows = $pdo->query("SELECT * FROM `{$table}`")->fetchAll(\PDO::FETCH_ASSOC);
                if (!empty($rows)) {
                    foreach ($rows as $row) {
                        $keys = array_map(fn ($k) => "`{$k}`", array_keys($row));
                        $values = array_map(function ($val) use ($pdo) {
                            if (is_null($val)) return "NULL";
                            return $pdo->quote($val);
                        }, array_values($row));

                        echo "INSERT INTO `{$table}` (" . implode(', ', $keys) . ") VALUES (" . implode(', ', $values) . ");\n";
                    }
                    echo "\n";
                }
            }

            echo "SET foreign_key_checks = 1;\n";
        }, $filename, [
            'Content-Type' => 'application/sql',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function auditLogs(Request $request): View
    {
        $query = AuditLog::with('user');

        if ($request->filled('module')) {
            $query->where('module', $request->module);
        }

        if ($request->filled('action')) {
            $query->where('action', 'like', "%{$request->action}%");
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('description', 'like', "%{$s}%")
                  ->orWhere('reference_id', 'like', "%{$s}%");
            });
        }

        $logs = $query->latest()->paginate(25)->withQueryString();
        $modules = AuditLog::select('module')->distinct()->pluck('module');

        return view('backup.audit_logs', compact('logs', 'modules'));
    }
}
