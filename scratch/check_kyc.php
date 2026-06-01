<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$connections = ['mysql_hq', 'mysql_erbil', 'mysql_sulaimaniyah', 'mysql_duhok'];

foreach ($connections as $conn) {
    echo "=== Connection: $conn ===\n";
    try {
        $docs = DB::connection($conn)->table('kyc_documents')->get();
        if ($docs->isEmpty()) {
            echo "  No KYC documents found.\n";
        }
        foreach ($docs as $d) {
            echo "  ID: {$d->id} | User ID: {$d->user_id} | Type: {$d->document_type} | Status: {$d->status}\n";
        }
    } catch (\Exception $e) {
        echo "  Error: " . $e->getMessage() . "\n";
    }
}
