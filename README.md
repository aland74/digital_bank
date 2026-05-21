# NexusBank — Premium Digital Banking Platform

NexusBank is a robust, modern digital banking platform built on **Laravel 11**. It features a state-of-the-art multi-city distributed database architecture, asynchronous queue-based data syncing, a secure physical/virtual smart card system, and a stunning "Glassmorphism" UI optimized for both Light and Dark modes.

---

## 🚀 Key Features

*   **Multi-City Architecture**: Requests are dynamically routed to regional databases (Erbil, Sulaimaniyah, Duhok) via middleware.
*   **Asynchronous HQ Sync**: All transactions and record modifications on branch databases are synced to the global HQ database in real-time via `SyncToHqJob`.
*   **Actionable Notifications**: Real-time alerts for P2P transfer requests, PIN changes, and branch cash operations (fully localized in English and Kurdish).
*   **Escrow Transfers**: Money transfers require the recipient to "Accept" or "Decline" the funds.
*   **Admin Command Center**: Complete oversight of Reserve Health, KYC processing, Loan Approvals, and Branch Cash (Teller) management.
*   **Smart Cards**: Virtual/Physical cards with hashed PINs, custom limits, and instant freeze controls.

---

## 💻 Requirements

To run this project, you must have the following installed on your machine:
*   **PHP** 8.2 or higher
*   **Composer** (PHP Package Manager)
*   **Node.js & npm** (For compiling frontend assets)
*   *Note: This project relies on SQLite for its distributed database simulation, so no complex MySQL/PostgreSQL setup is required.*

---

## 🛠️ Complete Installation Guide

Follow these steps exactly to get the project running from scratch.

### 1. Install Dependencies
Open your terminal in the project root directory and run:
```bash
composer install
npm install
```

### 2. Environment Configuration
Copy the example environment file:
```bash
cp .env.example .env
```
Generate the application encryption key:
```bash
php artisan key:generate
```

### 3. Database Setup & Seeding
Because NexusBank uses a distributed database architecture, you need to create the branch databases and migrate them. The `DatabaseSeeder` will automatically handle creating the SQLite files for HQ and the branches, migrating the schemas, and populating them with test data.
```bash
php artisan migrate:fresh --seed
```

### 4. Compile Frontend Assets
The platform uses Vite to compile its Vanilla CSS (Glassmorphism) and JavaScript. You must compile these assets for the UI to render correctly.
```bash
# For a one-time production build:
npm run build

# OR, if you are actively developing and want hot-reloading:
npm run dev
```

### 5. Start the Application Servers
To fully simulate the platform, you need to run both the web server and the background queue worker (which handles the multi-city data syncing and notification processing).

**Terminal 1 (Web Server):**
```bash
php artisan serve
```

**Terminal 2 (Queue Worker):**
```bash
php artisan queue:work
```

---

## 🔑 Demo Accounts

Once the server is running (usually at `http://localhost:8000`), you can log in using the following seeded accounts:

### Super Administrator (Full Control & Teller Access)
*   **Email:** `admin@nexusbank.com`
*   **Password:** `Admin@123456`

### Staff Member (Regional Support)
*   **Email:** `sara@nexusbank.com`
*   **Password:** `Staff@123456`

### Standard Customer (Fully KYC Verified)
*   **Email:** `john@nexusbank.com`
*   **Password:** `Password@123`

---

## 🌍 Localization

NexusBank supports complete dynamic localization. You can toggle between **English (EN)** and **Kurdish - Sorani (CKB)** directly from the top navigation bar. The UI automatically adjusts text alignment (LTR / RTL) based on the active language.

## 🎨 Theming

The platform uses a CSS Custom Property-based theming engine. Users can click the Sun/Moon icon in the navigation bar to instantly toggle between the deep, premium **Dark Mode** (default) and the clean, frosted **Light Mode**.
