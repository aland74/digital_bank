# 🚀 Digital Bank — Deployment & Presentation Guide

This guide ensures a flawless setup of the Distributed Bank system for presentation day. Follow these steps exactly to avoid configuration errors.

## 🛠️ Dependency Matrix
Ensure these exact versions are installed to prevent compatibility issues:

| Component | Version | Notes |
| :--- | :--- | :--- |
| **PHP** | 8.2+ | XAMPP recommended |
| **MySQL** | 8.0+ | XAMPP MySQL |
| **Composer** | 2.x | Dependency manager |
| **Node.js** | 18.x | For Vite assets |
| **Flutter SDK** | 3.32.1 | Mobile App |
| **Java JDK** | 17 | Android build |
| **Android Studio** | Hedgehog+ | Android Emulator / Physical Device |

## 🚀 Setup Sequence

### 1. Backend Setup (Laravel)
```bash
# Clone and enter the directory
cd digital_bank

# Install dependencies
composer install
npm install

# Environment configuration
cp .env.example .env
php artisan key:generate

# Database Setup
# 1. Start XAMPP MySQL
# 2. Create databases: 'digital_bank_hq', 'digital_bank_erbil', 'digital_bank_sulaimaniyah', 'digital_bank_duhok'
php artisan migrate --seed
npm run build
```

### 2. Mobile App Setup (Flutter)
```bash
cd mobile
flutter pub get
```

### 3. Network & Connectivity (CRITICAL)
To allow the physical mobile device to connect to the local Laravel server:
```bash
# 1. Start Laravel server on all interfaces
php artisan serve --host=0.0.0.0 --port=8000

# 2. Setup ADB Reverse Tunneling (via USB)
adb reverse tcp:8000 tcp:8000
```

## 🛡️ Emergency Manual (Presentation Day)

| Issue | Symptom | Solution |
| :--- | :--- | :--- |
| **Connection Timeout** | "Connection timeout please try again" | Check `adb reverse` status or ensure `--host=0.0.0.0` is used. |
| **500 Internal Error** | White screen / Laravel Error | Set `SESSION_DRIVER=file` and `CACHE_STORE=file` in `.env`. |
| **CSS/JS Missing** | Web design is "white and ugly" | Run `npm run build` and refresh the browser. |
| **DB Connection Error** | "Connection not found" | Verify XAMPP MySQL is running and branch database names match. |
| **Firebase Crash** | App crashes on startup | The system is configured with a try-catch bypass; ensure `google-services.json` is not missing. |

## 🗺️ Presentation Flow (Demo Path)
1. **Register**: Create a new customer account.
2. **KYC Upload**: Upload identity documents (Mobile).
3. **Admin Approval**: Login to Web Admin $\rightarrow$ Approve KYC $\rightarrow$ Watch account/card generate automatically.
4. **Banking**: Login to Mobile $\rightarrow$ View Virtual Card $\rightarrow$ Perform a transfer.
5. **Constraints**: Attempt a currency conversion $> \$200$ $\rightarrow$ Verify the block message.
6. **Security**: Use Fingerprint/FaceID to login to the app.
