@extends('legal.layout')

@section('legal-content')
<h1>{{ __('Compliance') }}</h1>
<p class="last-updated">{{ __('Last updated') }}: {{ date('F j, Y') }}</p>

<h2>1. {{ __('Regulatory Framework') }}</h2>
<p>{{ __('NexusBank operates as a digital banking platform designed to comply with international banking standards and regulations. Our compliance program covers anti-money laundering (AML), know your customer (KYC), and data protection requirements.') }}</p>

<h2>2. {{ __('Know Your Customer (KYC)') }}</h2>
<p>{{ __('NexusBank implements a strict KYC protocol to verify the identity of all customers:') }}</p>
<ul>
    <li>{{ __('Email verification via one-time password (OTP) at registration') }}</li>
    <li>{{ __('National ID or passport document upload and verification') }}</li>
    <li>{{ __('Admin review and approval of all submitted documents') }}</li>
    <li>{{ __('Periodic re-verification as required by regulations') }}</li>
</ul>

<h2>3. {{ __('Anti-Money Laundering (AML)') }}</h2>
<p>{{ __('Our platform implements multiple layers of AML protection:') }}</p>
<ul>
    <li>{{ __('Transaction monitoring with heuristic fraud detection') }}</li>
    <li>{{ __('Velocity checks to detect unusual transaction patterns') }}</li>
    <li>{{ __('Large transaction flagging and manual review workflows') }}</li>
    <li>{{ __('Comprehensive audit logging of all financial activities') }}</li>
    <li>{{ __('Escrow-based transfer model that prevents unauthorized fund movement') }}</li>
</ul>

<h2>4. {{ __('Data Protection') }}</h2>
<p>{{ __('NexusBank is designed with data protection principles:') }}</p>
<ul>
    <li>{{ __('AES-256 encryption for sensitive card and financial data') }}</li>
    <li>{{ __('Bcrypt password hashing with configurable rounds') }}</li>
    <li>{{ __('Distributed database architecture with encrypted replication') }}</li>
    <li>{{ __('Role-based access control (RBAC) separating customer and admin permissions') }}</li>
    <li>{{ __('Automatic session management and secure cookie handling') }}</li>
</ul>

<h2>5. {{ __('Audit & Transparency') }}</h2>
<p>{{ __('All significant actions on the platform are recorded in our audit log system. This includes logins, transactions, account modifications, and administrative actions. Super administrators have full access to audit logs for review and compliance reporting.') }}</p>

<h2>6. {{ __('Branch Compliance') }}</h2>
<p>{{ __('Each NexusBank branch (Erbil, Sulaimaniyah, Duhok) maintains its own database with automatic synchronization to the headquarters database. This architecture ensures data sovereignty while maintaining centralized oversight for compliance monitoring.') }}</p>

<h2>7. {{ __('Reporting Concerns') }}</h2>
<p>{{ __('If you suspect any compliance violations or fraudulent activity, please report it immediately through our in-app support ticket system by selecting the "Complaint" category. All reports are treated confidentially.') }}</p>
@endsection
