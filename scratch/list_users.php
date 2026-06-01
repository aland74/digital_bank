<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$users = App\Models\User::select('id', 'name', 'email', 'role', 'status')->get();
foreach ($users as $u) {
    echo "{$u->id} | {$u->name} | {$u->email} | {$u->role} | {$u->status}\n";
}
