<?php
use Illuminate\Support\Facades\Hash;
use App\Models\User;

// Reset passwords for all users in all databases using the correct Laravel Hash
$password = 'Password123!';
$hash = Hash::make($password);

// We use a raw query to avoid any model-level connection issues during the mass reset
$connections = \App\Services\DistributedDatabaseService::allConnections();
foreach ($connections as $conn) {
    \Illuminate\Support\Facades\DB::connection($conn)->table('users')->update([
        'password' => $hash
    ]);
}

echo "Successfully reset all passwords to Password123! across all connections.";
