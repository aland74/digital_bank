@extends('legal.layout')

@section('legal-content')
<h1>{{ __('Terms of Service') }}</h1>
<p class="last-updated">{{ __('Last updated') }}: {{ date('F j, Y') }}</p>

<h2>1. {{ __('Acceptance of Terms') }}</h2>
<p>{{ __('By creating an account or using NexusBank services, you agree to these Terms of Service. If you do not agree, please do not use our platform.') }}</p>

<h2>2. {{ __('Account Registration') }}</h2>
<p>{{ __('To use NexusBank services, you must:') }}</p>
<ul>
    <li>{{ __('Be at least 18 years of age') }}</li>
    <li>{{ __('Provide accurate and complete registration information') }}</li>
    <li>{{ __('Verify your email address through our OTP verification system') }}</li>
    <li>{{ __('Complete identity verification (KYC) as requested') }}</li>
    <li>{{ __('Maintain the security of your account credentials') }}</li>
</ul>

<h2>3. {{ __('Banking Services') }}</h2>
<p>{{ __('NexusBank provides digital banking services including:') }}</p>
<ul>
    <li>{{ __('Savings, checking, and business accounts in multiple currencies') }}</li>
    <li>{{ __('Escrow-protected money transfers between accounts') }}</li>
    <li>{{ __('Virtual and physical debit/credit card management') }}</li>
    <li>{{ __('Personal, business, and education loan applications') }}</li>
    <li>{{ __('Cash deposit and withdrawal services') }}</li>
</ul>

<h2>4. {{ __('Escrow Transfers') }}</h2>
<p>{{ __('All transfers on NexusBank use an escrow model. Funds are debited from the sender\'s account and held securely until the recipient accepts the transfer. Recipients may decline transfers, in which case funds are returned to the sender. Pending transfers may be cancelled by the sender before acceptance.') }}</p>

<h2>5. {{ __('Account Security') }}</h2>
<p>{{ __('You are responsible for maintaining the confidentiality of your account credentials. NexusBank implements automatic account lockout after 5 consecutive failed login attempts (30-minute lock period). You must notify us immediately of any unauthorized access.') }}</p>

<h2>6. {{ __('Prohibited Activities') }}</h2>
<p>{{ __('You may not use NexusBank services for:') }}</p>
<ul>
    <li>{{ __('Money laundering or terrorist financing') }}</li>
    <li>{{ __('Fraudulent transactions or identity theft') }}</li>
    <li>{{ __('Circumventing our fraud detection systems') }}</li>
    <li>{{ __('Any activity that violates applicable laws') }}</li>
</ul>

<h2>7. {{ __('Account Suspension') }}</h2>
<p>{{ __('NexusBank reserves the right to suspend, freeze, or terminate accounts that violate these terms, are involved in suspicious activity, or fail to complete required KYC verification within the specified timeframe.') }}</p>

<h2>8. {{ __('Limitation of Liability') }}</h2>
<p>{{ __('NexusBank is a demonstration platform. While we implement real security measures, banking services are simulated. NexusBank shall not be liable for any indirect, incidental, or consequential damages arising from the use of our services.') }}</p>

<h2>9. {{ __('Changes to Terms') }}</h2>
<p>{{ __('We may update these Terms of Service from time to time. Continued use of our services after changes constitute acceptance of the updated terms.') }}</p>
@endsection
