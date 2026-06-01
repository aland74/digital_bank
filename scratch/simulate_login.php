<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Http\Controllers\Auth\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

// Mock session and request
$request = Request::create('/login', 'POST', [
    'email' => 'admin@distributedbank.com',
    'password' => 'Admin@123456',
]);

// Laravel requires session to be started
$request->setLaravelSession(app('session')->driver());

$controller = app(AuthController::class);

try {
    $response = $controller->login($request);
    echo "Login Response Status: " . $response->getStatusCode() . "\n";
    echo "Login Response Target URL: " . ($response->isRedirection() ? $response->getTargetUrl() : 'N/A') . "\n";
    
    if (Auth::check()) {
        $user = Auth::user();
        echo "Authenticated User: id={$user->id}, name={$user->name}, role={$user->role}\n";
        echo "Active Database Connection: " . config('database.default') . "\n";
    } else {
        echo "Authentication FAILED! Check error messages:\n";
        $errors = session()->get('errors');
        if ($errors) {
            print_r($errors->all());
        } else {
            echo "No session errors.\n";
        }
    }
} catch (\Exception $e) {
    echo "Error during login: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
