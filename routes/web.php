<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\TransferController;
use App\Http\Controllers\BeneficiaryController;
use App\Http\Controllers\CardController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\CashController;
use App\Http\Controllers\SupportTicketController;

/*
|--------------------------------------------------------------------------
| Web Routes — NexusBank Digital Banking
|--------------------------------------------------------------------------
*/

// ── Public Routes ───────────────────────────────────────────
Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/lang/{locale}', function (string $locale) {
    if (in_array($locale, ['en', 'ckb'])) {
        session(['locale' => $locale]);
        app()->setLocale($locale);
    }
    return redirect()->back();
})->name('lang.switch');

Route::get('/currency/{code}', function (string $code) {
    if (in_array(strtoupper($code), ['USD', 'IQD'])) {
        session(['view_currency' => strtoupper($code)]);
    }
    return redirect()->back();
})->name('currency.switch');

// ── Legal Pages ─────────────────────────────────────────────
Route::get('/legal/privacy', function () { return view('legal.privacy'); })->name('legal.privacy');
Route::get('/legal/terms', function () { return view('legal.terms'); })->name('legal.terms');
Route::get('/legal/cookies', function () { return view('legal.cookies'); })->name('legal.cookies');
Route::get('/legal/compliance', function () { return view('legal.compliance'); })->name('legal.compliance');

// ── Auth Routes ─────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);

    // Google Sign-In Routes
    Route::get('/auth/google/redirect', [AuthController::class, 'redirectToGoogle'])->name('auth.google.redirect');
    Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');
    Route::post('/auth/google/callback/simulated', [AuthController::class, 'loginWithGoogleSimulated'])->name('auth.google.callback.simulated');
    Route::get('/auth/google/complete-profile', [AuthController::class, 'showGoogleCompleteProfile'])->name('auth.google.complete-profile');
    Route::post('/auth/google/complete-profile', [AuthController::class, 'storeGoogleCompleteProfile']);

    // Registration OTP Verification Routes
    Route::get('/auth/verify-otp', [AuthController::class, 'showVerifyOtp'])->name('auth.verify-otp');
    Route::post('/auth/verify-otp', [AuthController::class, 'verifyOtp'])->name('auth.verify-otp.submit');
    Route::post('/auth/verify-otp/resend', [AuthController::class, 'resendOtp'])->name('auth.verify-otp.resend');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// ── Authenticated Routes ────────────────────────────────────
Route::middleware(['auth', \App\Http\Middleware\CheckAccountStatus::class])->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Accounts (view only — accounts are auto-created with cards)
    Route::get('/accounts', [AccountController::class, 'index'])->name('accounts.index');
    Route::get('/accounts/{account}', [AccountController::class, 'show'])->name('accounts.show');

    // Transactions
    Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');
    Route::get('/transactions/{transaction}', [TransactionController::class, 'show'])->name('transactions.show');

    // Transfers
    Route::get('/transfers', [TransferController::class, 'create'])->name('transfers.create');
    Route::match(['GET', 'POST'], '/transfers/confirm', [TransferController::class, 'confirm'])->name('transfers.confirm');
    Route::post('/transfers', [TransferController::class, 'store'])->name('transfers.store');
    Route::get('/transfers/success', [TransferController::class, 'success'])->name('transfers.success');
    Route::get('/transfers/pending', [TransferController::class, 'pending'])->name('transfers.pending');
    Route::post('/transfers/{pendingTransfer}/accept', [TransferController::class, 'accept'])->name('transfers.accept');
    Route::post('/transfers/{pendingTransfer}/decline', [TransferController::class, 'decline'])->name('transfers.decline');
    Route::post('/transfers/{pendingTransfer}/cancel', [TransferController::class, 'cancel'])->name('transfers.cancel');

    // Currency Conversion (between own USD and IQD accounts)
    Route::get('/transfers/convert', [TransferController::class, 'convertForm'])->name('transfers.convert');
    Route::post('/transfers/convert', [TransferController::class, 'convert'])->name('transfers.convert.execute');

    // Beneficiaries
    Route::resource('beneficiaries', BeneficiaryController::class);
    Route::post('/beneficiaries/{beneficiary}/toggle-favorite', [BeneficiaryController::class, 'toggleFavorite'])->name('beneficiaries.toggle-favorite');

    // Cards
    Route::get('/cards', [CardController::class, 'index'])->name('cards.index');
    Route::get('/cards/create', [CardController::class, 'create'])->name('cards.create');
    Route::post('/cards', [CardController::class, 'store'])->name('cards.store');
    Route::get('/cards/{card}/created', [CardController::class, 'created'])->name('cards.created');
    Route::get('/cards/{card}', [CardController::class, 'show'])->name('cards.show');
    Route::post('/cards/{card}/freeze', [CardController::class, 'freeze'])->name('cards.freeze');
    Route::post('/cards/{card}/unfreeze', [CardController::class, 'unfreeze'])->name('cards.unfreeze');
    Route::put('/cards/{card}/limits', [CardController::class, 'updateLimits'])->name('cards.update-limits');
    Route::post('/cards/{card}/toggle-contactless', [CardController::class, 'toggleContactless'])->name('cards.toggle-contactless');
    Route::post('/cards/{card}/toggle-online', [CardController::class, 'toggleOnline'])->name('cards.toggle-online');
    Route::post('/cards/{card}/toggle-international', [CardController::class, 'toggleInternational'])->name('cards.toggle-international');
    Route::get('/cards/{card}/change-pin', [CardController::class, 'showRequestPinChange'])->name('cards.request-pin-change');
    Route::post('/cards/{card}/change-pin', [CardController::class, 'requestPinChange'])->name('cards.request-pin-change.store');
    Route::post('/cards/{card}/reveal', [CardController::class, 'reveal'])->name('cards.reveal');

    // Loans
    Route::get('/loans', [LoanController::class, 'index'])->name('loans.index');
    Route::get('/loans/apply', [LoanController::class, 'apply'])->name('loans.apply');
    Route::post('/loans', [LoanController::class, 'store'])->name('loans.store');
    Route::get('/loans/{loan}', [LoanController::class, 'show'])->name('loans.show');
    Route::post('/loans/{loan}/pay', [LoanController::class, 'pay'])->name('loans.pay');

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-read');
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount'])->name('notifications.unread-count');
    Route::get('/notifications/latest', [NotificationController::class, 'latest'])->name('notifications.latest');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/profile/security', [ProfileController::class, 'security'])->name('profile.security');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.update-password');
    Route::get('/profile/kyc', [ProfileController::class, 'showKycUpload'])->name('profile.kyc');
    Route::post('/profile/kyc', [ProfileController::class, 'uploadKyc'])->name('profile.kyc.upload');
    
    // Cash / ATM
    Route::get('/cash', [CashController::class, 'index'])->name('cash.index');
    Route::post('/cash/out', [CashController::class, 'cashOut'])->name('cash.out');

    // Support Tickets
    Route::get('/support', [SupportTicketController::class, 'index'])->name('support.index');
    Route::get('/support/create', [SupportTicketController::class, 'create'])->name('support.create');
    Route::post('/support', [SupportTicketController::class, 'store'])->name('support.store');
    Route::get('/support/{ticket}', [SupportTicketController::class, 'show'])->name('support.show');
    Route::post('/support/{ticket}/reply', [SupportTicketController::class, 'reply'])->name('support.reply');
    Route::post('/support/{ticket}/close', [SupportTicketController::class, 'close'])->name('support.close');
});

// ── Admin Routes ────────────────────────────────────────────
Route::middleware(['auth', \App\Http\Middleware\AdminMiddleware::class])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/users', [AdminController::class, 'users'])->name('users');
    Route::get('/users/{user}', [AdminController::class, 'showUser'])->name('users.show');
    Route::put('/users/{user}/status', [AdminController::class, 'updateUserStatus'])->name('users.update-status');
    Route::get('/loans', [AdminController::class, 'pendingLoans'])->name('loans');
    Route::post('/loans/{loan}/approve', [AdminController::class, 'approveLoan'])->name('loans.approve');
    Route::post('/loans/{loan}/reject', [AdminController::class, 'rejectLoan'])->name('loans.reject');
    Route::get('/kyc', [AdminController::class, 'kycDocuments'])->name('kyc');
    Route::post('/kyc/{document}/verify', [AdminController::class, 'verifyKyc'])->name('kyc.verify');
    Route::post('/kyc/{document}/reject', [AdminController::class, 'rejectKyc'])->name('kyc.reject');
    // Settings - all admins can view, only super admin can modify
    Route::get('/settings', [AdminController::class, 'settings'])->name('settings');
    // Super Admin Only Routes
    Route::middleware('super_admin')->group(function () {
        Route::put('/settings', [AdminController::class, 'updateSettings'])->name('settings.update');
        Route::get('/audit-logs', [AdminController::class, 'auditLogs'])->name('audit-logs');
    });
    Route::get('/pin-requests', [AdminController::class, 'pinRequests'])->name('pin-requests');
    Route::post('/pin-requests/{pinRequest}/approve', [AdminController::class, 'approvePinRequest'])->name('pin-requests.approve');
    Route::post('/pin-requests/{pinRequest}/reject', [AdminController::class, 'rejectPinRequest'])->name('pin-requests.reject');
    
    // Admin Branch Cash Management
    Route::get('/cash', [CashController::class, 'adminIndex'])->name('cash');
    Route::get('/cash/accounts', [CashController::class, 'getUserAccounts'])->name('cash.accounts');
    Route::post('/cash/deposit', [CashController::class, 'adminDeposit'])->name('cash.deposit');
    Route::post('/cash/withdraw', [CashController::class, 'adminWithdraw'])->name('cash.withdraw');

    // Admin Support Tickets
    Route::get('/support', [AdminController::class, 'supportTickets'])->name('support.index');
    Route::get('/support/{ticket}', [AdminController::class, 'showSupportTicket'])->name('support.show');
    Route::post('/support/{ticket}/reply', [AdminController::class, 'replySupportTicket'])->name('support.reply');
    Route::put('/support/{ticket}/status', [AdminController::class, 'updateTicketStatus'])->name('support.update-status');
    Route::post('/support/{ticket}/assign', [AdminController::class, 'assignTicket'])->name('support.assign');
});
