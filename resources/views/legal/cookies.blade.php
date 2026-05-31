@extends('legal.layout')

@section('legal-content')
<h1>{{ __('Cookie Policy') }}</h1>
<p class="last-updated">{{ __('Last updated') }}: {{ date('F j, Y') }}</p>

<h2>1. {{ __('What Are Cookies') }}</h2>
<p>{{ __('Cookies are small text files stored on your device when you visit our platform. They help us provide essential functionality and improve your experience.') }}</p>

<h2>2. {{ __('Cookies We Use') }}</h2>

<h2>{{ __('Essential Cookies') }}</h2>
<p>{{ __('These cookies are required for the platform to function and cannot be disabled:') }}</p>
<ul>
    <li>{{ __('Session cookies — maintain your login state and CSRF protection') }}</li>
    <li>{{ __('Security cookies — prevent unauthorized access and detect suspicious activity') }}</li>
    <li>{{ __('Language preference — remember your selected language (English or Kurdish)') }}</li>
</ul>

<h2>{{ __('Functional Cookies') }}</h2>
<p>{{ __('These cookies enhance your experience:') }}</p>
<ul>
    <li>{{ __('Theme preference — remember your dark/light mode selection') }}</li>
    <li>{{ __('Notification preferences — store your notification display settings') }}</li>
</ul>

<h2>3. {{ __('Third-Party Cookies') }}</h2>
<p>{{ __('Distributed Bank does not use third-party advertising or tracking cookies. We load Google Fonts for typography, which may set minimal cookies for font caching.') }}</p>

<h2>4. {{ __('Managing Cookies') }}</h2>
<p>{{ __('You can manage cookies through your browser settings. However, disabling essential cookies may prevent you from using Distributed Bank services. Your theme preference is stored in your browser\'s localStorage, which you can clear through your browser settings.') }}</p>

<h2>5. {{ __('Data Stored Locally') }}</h2>
<p>{{ __('In addition to cookies, Distributed Bank uses browser localStorage to store:') }}</p>
<ul>
    <li>{{ __('Theme preference (dark/light mode)') }}</li>
</ul>
<p>{{ __('This data remains on your device and is not transmitted to our servers.') }}</p>
@endsection
