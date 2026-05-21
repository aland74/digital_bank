@extends('legal.layout')

@section('legal-content')
<h1>{{ __('Privacy Policy') }}</h1>
<p class="last-updated">{{ __('Last updated') }}: {{ date('F j, Y') }}</p>

<h2>1. {{ __('Information We Collect') }}</h2>
<p>{{ __('NexusBank collects personal information necessary to provide our banking services. This includes:') }}</p>
<ul>
    <li>{{ __('Personal identification details (name, email, phone number, national ID)') }}</li>
    <li>{{ __('Financial information (account balances, transaction history)') }}</li>
    <li>{{ __('Authentication credentials (encrypted passwords, session data)') }}</li>
    <li>{{ __('Device and usage information (IP address, browser type, login timestamps)') }}</li>
    <li>{{ __('KYC documents (passport scans, national ID copies)') }}</li>
</ul>

<h2>2. {{ __('How We Use Your Information') }}</h2>
<p>{{ __('Your information is used to:') }}</p>
<ul>
    <li>{{ __('Process banking transactions and transfers securely') }}</li>
    <li>{{ __('Verify your identity through our KYC protocol') }}</li>
    <li>{{ __('Detect and prevent fraudulent activity through our heuristic fraud engine') }}</li>
    <li>{{ __('Send notifications about account activity and security alerts') }}</li>
    <li>{{ __('Maintain and improve our services') }}</li>
    <li>{{ __('Comply with regulatory and legal requirements') }}</li>
</ul>

<h2>3. {{ __('Data Security') }}</h2>
<p>{{ __('We employ industry-standard security measures including AES-256 encryption for sensitive data, bcrypt hashing for passwords, and comprehensive audit logging for all account activities. Our distributed database architecture ensures data redundancy across multiple secure branches.') }}</p>

<h2>4. {{ __('Data Sharing') }}</h2>
<p>{{ __('NexusBank does not sell or share your personal information with third parties for marketing purposes. We may share data only in the following circumstances:') }}</p>
<ul>
    <li>{{ __('With your explicit consent') }}</li>
    <li>{{ __('To comply with legal obligations or regulatory requirements') }}</li>
    <li>{{ __('To prevent fraud or protect the security of our platform') }}</li>
    <li>{{ __('Between our distributed branch databases for service continuity') }}</li>
</ul>

<h2>5. {{ __('Your Rights') }}</h2>
<p>{{ __('You have the right to access, correct, or request deletion of your personal data. You can manage your profile information through your account settings. For data deletion requests, please contact our support team through the in-app support ticket system.') }}</p>

<h2>6. {{ __('Data Retention') }}</h2>
<p>{{ __('We retain your personal data for as long as your account is active. Transaction records are maintained as required by banking regulations. Upon account closure, we retain records for the legally mandated period before secure deletion.') }}</p>

<h2>7. {{ __('Contact Us') }}</h2>
<p>{{ __('If you have questions about this Privacy Policy, please submit a support ticket through your NexusBank account or contact our data protection team.') }}</p>
@endsection
