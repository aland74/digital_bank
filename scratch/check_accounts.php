<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Account;

$accounts = Account::on('mysql_hq')->get();
foreach ($accounts as $a) {
    echo "#{$a->id} | User #{$a->user_id} | {$a->account_number} | {$a->currency} | {$a->account_name} | {$a->account_type} | balance={$a->balance}\n";
}
