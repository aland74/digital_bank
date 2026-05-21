<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

foreach (['sqlite_hq', 'mysql_hq'] as $conn) {
    try {
        $hasSessions = Schema::connection($conn)->hasTable('sessions');
        echo "Connection '{$conn}' has 'sessions' table: " . ($hasSessions ? "YES" : "NO") . "\n";
        if ($hasSessions) {
            $count = DB::connection($conn)->table('sessions')->count();
            echo "  Number of active sessions: {$count}\n";
        }
    } catch (\Exception $e) {
        echo "Connection '{$conn}' error: " . $e->getMessage() . "\n";
    }
}
