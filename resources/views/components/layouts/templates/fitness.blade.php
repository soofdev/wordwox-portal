<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@stack('title', config('app.name', 'Fitness Gym'))</title>
    
    @stack('meta')

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Fitness Template CSS -->
    @vite(['resources/css/fitness.css', 'resources/js/fitness.js'])
    @livewireStyles

    @stack('head')

    @php
        // Load custom theme colors from database
        $orgId = env('CMS_DEFAULT_ORG_ID', 8);
        $themeColor = \App\Models\TemplateThemeColor::where('org_id', $orgId)
            ->where('template', 'fitness')
            ->first();
        
        // Use custom colors if available, otherwise use defaults
        $primaryColor = $themeColor?->primary_color ?? '#ff6b6b';
        $secondaryColor = $themeColor?->secondary_color ?? '#4ecdc4';
        $textDark = $themeColor?->text_dark ?? '#2c3e50';
        $textGray = $themeColor?->text_gray ?? '#6c757d';
        $textBase = $themeColor?->text_base ?? '#333';
        $textLight = $themeColor?->text_light ?? '#ffffff';
        $textFooter = $themeColor?->text_footer ?? '#ffffff';
        $bgWhite = $themeColor?->bg_white ?? '#ffffff';
        $bgPackages = $themeColor?->bg_packages ?? '#f2f4f6';
        $bgCoaches = $themeColor?->bg_coaches ?? '#f8f9fa';
        $bgFooter = $themeColor?->bg_footer ?? '#2c3e50';
        
        // Convert hex to rgba for opacity variants
        function hexToRgb($hex) {
            $hex = str_replace('#', '', $hex);
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
            return "$r, $g, $b";
        }
        $primaryRgb = hexToRgb($primaryColor);
        $secondaryRgb = hexToRgb($secondaryColor);
    @endphp
    
    <style>
        /* Fitness Template Theme Colors - CSS Variables */
        :root {
            /* Primary Brand Colors */
            --fitness-primary: {{ $primaryColor }};
            --fitness-secondary: {{ $secondaryColor }};
            --fitness-gradient: linear-gradient(135deg, var(--fitness-primary) 0%, var(--fitness-secondary) 100%);
            
            /* Text Colors */
            --fitness-text-dark: {{ $textDark }};
            --fitness-text-gray: {{ $textGray }};
            --fitness-text-base: {{ $textBase }};
            --fitness-text-light: {{ $textLight }};
            --fitness-text-footer: {{ $textFooter }};
            
            /* Background Colors */
            --fitness-bg-white: {{ $bgWhite }};
            --fitness-bg-packages: {{ $bgPackages }};
            --fitness-bg-coaches: {{ $bgCoaches }};
            --fitness-bg-footer: {{ $bgFooter }};
            
            /* Interactive Colors - Hover colors are automatically lighter using CSS filter: brightness(1.15) */
            --fitness-primary-light: rgba({{ $primaryRgb }}, 0.1);
            --fitness-primary-shadow: rgba({{ $primaryRgb }}, 0.25);
            --fitness-secondary-light: rgba({{ $secondaryRgb }}, 0.1);
            
            /* Border & Shadow Colors */
            --fitness-border-light: rgba(0, 0, 0, 0.1);
            --fitness-shadow: rgba(0, 0, 0, 0.1);
            --fitness-shadow-lg: rgba(0, 0, 0, 0.15);
            
            /* Navbar Colors */
            --fitness-navbar-bg: rgba(255, 255, 255, 0.95);
            --fitness-navbar-bg-scroll: rgba(255, 255, 255, 0.98);
            --fitness-navbar-shadow: rgba(0, 0, 0, 0.1);
        }
        
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            background-color: var(--fitness-bg-white);
        }
        body {
            font-family: 'Poppins', sans-serif;
            line-height: 1.6;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            background-color: var(--fitness-bg-white);
        }
        
        .fitness-template {
            flex: 1 0 auto;
            display: flex;
            flex-direction: column;
            background-color: var(--fitness-bg-white);
        }
        
        main.fitness-content {
            flex: 1 0 auto;
            background-color: var(--fitness-bg-white);
        }
        
        /* Responsive Navbar */
        .navbar-fitness {
            background: var(--fitness-navbar-bg);
            backdrop-filter: blur(10px);
           
            padding: 0.75rem 0;
            transition: all 0.3s ease;
        }
        
        .navbar-brand {
            font-size: 1.25rem;
            display: flex;
            align-items: center;
        }
        
        .navbar-brand img {
            height: 35px;
            width: auto;
        }
        
        /* Mobile Menu Toggler */
        .navbar-toggler {
            border: 2px solid rgba(0, 0, 0, 0.1);
            border-radius: 5px;
            padding: 0.5rem 0.75rem;
            background: transparent;
            transition: all 0.3s ease;
        }
        
        .navbar-toggler:focus {
            box-shadow: 0 0 0 0.25rem rgba(0, 0, 0, 0.1);
            outline: none;
        }
        
        .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba%2833, 37, 41, 0.75%29' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
            width: 1.5em;
            height: 1.5em;
        }
        
        /* Mobile Menu Collapse */
        .navbar-collapse {
            margin-top: 1rem;
            background: var(--fitness-navbar-bg-scroll);
            border-radius: 10px;
            padding: 1rem;
            box-shadow: 0 4px 20px var(--fitness-shadow);
        }
        
        @media (min-width: 992px) {
            .navbar-collapse {
                margin-top: 0;
                background: transparent;
                border-radius: 0;
                padding: 0;
                box-shadow: none;
            }
        }
        
        /* Nav Links */
        .navbar-nav {
            gap: 0.5rem;
        }
        
        .nav-link {
            color: var(--fitness-text-base) !important;
            font-weight: 500;
            padding: 0.75rem 1rem !important;
            border-radius: 8px;
            transition: all 0.3s ease;
            text-align: center;
            display: block;
        }
        
        .nav-link:hover {
            background-color: var(--fitness-primary-light);
            color: var(--fitness-primary) !important;
            transform: translateX(5px);
        }
        
        .nav-link.active {
            background: var(--fitness-primary-light);
            color: var(--fitness-primary) !important;
            font-weight: 600;
            border-radius: 4px;
        }
        
        .nav-link.active:hover {
            background: var(--fitness-primary-light);
            color: var(--fitness-primary) !important;
            transform: translateX(0);
        }
        
        @media (min-width: 992px) {
            .nav-link {
                text-align: left;
                padding: 0.5rem 1rem !important;
            }
            
            .nav-link:hover {
                transform: translateY(-2px);
            }
            
            .nav-link.active:hover {
                transform: translateY(-2px);
            }
        }
        
        /* Dropdown Menu Styles */
        .dropdown-menu {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 20px var(--fitness-shadow);
            padding: 0.5rem 0;
            margin-top: 0.5rem;
        }
        
        .dropdown-item {
            padding: 0.75rem 1.5rem;
            color: var(--fitness-text-base);
            transition: all 0.3s ease;
        }
        
        .dropdown-item:hover {
            background-color: var(--fitness-primary-light);
            color: var(--fitness-primary);
        }
        
        .dropdown-item.text-danger:hover {
            background-color: rgba(220, 53, 69, 0.1);
            color: #dc3545;
        }
        
        .dropdown-divider {
            margin: 0.5rem 0;
        }
        
        @media (min-width: 768px) {
            .navbar-brand {
                font-size: 1.5rem;
            }
            .navbar-brand img {
                height: 40px;
            }
        }
        
        /* Responsive Hero Section */
        .hero-section {
            background: var(--fitness-gradient);
            color: var(--fitness-text-light);
            padding: 60px 0;
        }
        
        .hero-section-custom {
            padding: 60px 0 !important;
        }
        
        .hero-content-row {
            min-height: 400px;
        }
        
        .hero-section-custom h1 {
            font-size: 2rem;
            line-height: 1.2;
        }
        
        .hero-section-custom h3 {
            font-size: 1.25rem;
        }
        
        .hero-section-custom .lead {
            font-size: 1rem;
        }
        
        @media (min-width: 768px) {
            .hero-section {
                padding: 80px 0;
            }
            .hero-section-custom {
                padding: 100px 0 !important;
            }
            .hero-content-row {
                min-height: 500px;
            }
            .hero-section-custom h1 {
                font-size: 3rem;
            }
            .hero-section-custom h3 {
                font-size: 1.5rem;
            }
            .hero-section-custom .lead {
                font-size: 1.25rem;
            }
        }
        
        @media (min-width: 992px) {
            .hero-content-row {
                min-height: 600px;
            }
            .hero-section-custom h1 {
                font-size: 4rem;
            }
        }
        
        /* Responsive Page Header */
        .page-header-fitness {
            background: var(--fitness-bg-coaches);
            padding: 40px 0;
            margin-bottom: 0;
        }
        
        .page-title-fitness {
            font-size: 2rem;
            font-weight: 700;
            color: var(--fitness-text-dark);
            line-height: 1.2;
        }
        
        .page-description-fitness {
            font-size: 1rem;
            color: var(--fitness-text-gray);
            line-height: 1.6;
            margin: 0;
        }
        
        @media (min-width: 768px) {
            .page-header-fitness {
                padding: 60px 0;
            }
            .page-title-fitness {
                font-size: 2.5rem;
            }
            .page-description-fitness {
                font-size: 1.125rem;
            }
        }
        
        @media (min-width: 992px) {
            .page-header-fitness {
                padding: 80px 0;
            }
            .page-title-fitness {
                font-size: 3rem;
            }
            .page-description-fitness {
                font-size: 1.25rem;
            }
        }
        
        /* Responsive Section Padding */
        .section-padding {
            padding: 0px 0;
        }
        
        @media (min-width: 768px) {
            .section-padding {
                padding: 60px 0;
            }
        }
        
        @media (min-width: 992px) {
        .section-padding {
            padding: 80px 0;
            }
        }
        
        /* Responsive Content Sections */
        .content-section {
            font-size: 1rem;
            line-height: 1.8;
            color: var(--fitness-text-base);
        }
        
        .content-section h1,
        .content-section h2,
        .content-section h3,
        .content-section h4,
        .content-section h5,
        .content-section h6 {
            margin-top: 1.5rem;
            margin-bottom: 1rem;
            font-weight: 600;
        }
        
        .content-section h2 {
            font-size: 1.75rem;
        }
        
        .content-section h3 {
            font-size: 1.5rem;
        }
        
        .content-section p {
            margin-bottom: 1rem;
        }
        
        .content-section img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            margin: 1.5rem 0;
        }
        
        .content-section ul,
        .content-section ol {
            margin-bottom: 1rem;
            padding-left: 1.5rem;
        }
        
        .content-section li {
            margin-bottom: 0.5rem;
        }
        
        @media (min-width: 768px) {
            .content-section {
                font-size: 1.125rem;
            }
            .content-section h2 {
                font-size: 2rem;
            }
            .content-section h3 {
                font-size: 1.75rem;
            }
        }
        
        @media (min-width: 992px) {
            .content-section {
                font-size: 1.125rem;
            }
            .content-section h2 {
                font-size: 2.25rem;
            }
            .content-section h3 {
                font-size: 2rem;
            }
        }
        
        /* Section Heading - inherit color from parent, allow inline styles to override */
        .section-heading {
            font-size: 2rem;
            font-weight: 700;
            color: inherit; /* Inherit from parent section instead of forcing --fitness-text-dark */
            margin-bottom: 0.5rem;
        }
        
        /* Override section-heading color when inside heading-section */
        .heading-section .section-heading {
            color: #000000 !important;
        }
        
        @media (min-width: 768px) {
            .section-heading {
                font-size: 2.5rem;
            }
        }
        
        @media (min-width: 992px) {
            .section-heading {
                font-size: 3rem;
            }
        }
        /* Heading Section - force black color, override any inherited colors */
        .heading-section {
            color: #000000 !important;
        }
        
        .heading-section h1,
        .heading-section h2,
        .heading-section h3,
        .heading-section h4,
        .heading-section h5,
        .heading-section h6,
        .heading-section .section-heading,
        .heading-section p {
            color: #000000 !important;
        }
        
        /* Responsive Quote Block */
        .blockquote-fitness {
            border-left: 4px solid var(--fitness-primary);
            padding: 1.5rem;
            margin: 2rem 0;
            background: var(--fitness-bg-coaches);
            border-radius: 8px;
        }
        
        .quote-text-fitness {
            font-size: 1.25rem;
            font-style: italic;
            color: var(--fitness-text-dark);
            margin-bottom: 0.5rem;
            line-height: 1.6;
        }
        
        .quote-cite-fitness {
            font-size: 1rem;
            color: var(--fitness-text-gray);
            display: block;
            margin-top: 0.5rem;
        }
        
        @media (min-width: 768px) {
            .blockquote-fitness {
                padding: 2rem;
            }
            .quote-text-fitness {
                font-size: 1.5rem;
            }
            .quote-cite-fitness {
                font-size: 1.125rem;
            }
        }
        
        @media (min-width: 992px) {
            .quote-text-fitness {
                font-size: 1.75rem;
            }
        }
        
        /* Responsive List */
        .list-fitness {
            list-style: none;
            padding-left: 0;
            margin: 1.5rem 0;
        }
        
        .list-item-fitness {
            padding: 0.75rem 0;
            padding-left: 2rem;
            position: relative;
            font-size: 1rem;
            line-height: 1.6;
            color: #333;
        }
        
        .list-item-fitness::before {
            content: "•";
            color: var(--fitness-primary);
            font-weight: bold;
            position: absolute;
            left: 0.5rem;
            font-size: 1.5rem;
        }
        
        @media (min-width: 768px) {
            .list-item-fitness {
                font-size: 1.125rem;
                padding-left: 2.5rem;
            }
        }
        
        /* Responsive CTA Section */
        .cta-section-fitness {
            padding: 2rem 0;
        }
        
        .cta-description-fitness {
            font-size: 1rem;
            color: #6c757d;
            line-height: 1.6;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .cta-buttons-fitness {
            gap: 1rem;
        }
        
        .btn-secondary-fitness {
            background: linear-gradient(135deg, var(--fitness-text-gray) 0%, #495057 100%) !important;
        }
        
        .btn-outline-fitness {
            background: transparent !important;
            border: 2px solid var(--fitness-primary);
            color: var(--fitness-primary) !important;
        }
        
        .btn-outline-fitness:hover {
            background: var(--fitness-primary) !important;
            color: var(--fitness-text-light) !important;
        }
        
        @media (min-width: 768px) {
            .cta-section-fitness {
                padding: 3rem 0;
            }
            .cta-description-fitness {
                font-size: 1.125rem;
            }
        }
        
        @media (min-width: 992px) {
            .cta-section-fitness {
                padding: 4rem 0;
            }
            .cta-description-fitness {
                font-size: 1.25rem;
            }
        }
        
        /* Responsive Contact Form */
        .contact-form-card-fitness {
            border-radius: 15px;
        }
        
        .contact-form-label {
            font-weight: 600;
            color: var(--fitness-text-dark);
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
            display: block;
        }
        
        .contact-form-input,
        .contact-form-textarea {
            font-size: 0.95rem;
            padding: 0.7rem 0.9rem;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            transition: all 0.3s ease;
            width: 100%;
        }
        
        .contact-form-input:focus,
        .contact-form-textarea:focus {
            border-color: var(--fitness-primary);
            box-shadow: 0 0 0 0.2rem var(--fitness-primary-shadow);
            outline: none;
        }
        
        .contact-form-textarea {
            resize: vertical;
            min-height: 120px;
            font-family: inherit;
        }
        
        .contact-submit-btn {
            width: 100%;
            padding: 0.75rem 1.5rem;
            font-size: 0.95rem;
            font-weight: 600;
            border-radius: 25px;
            transition: all 0.3s ease;
            background: var(--fitness-primary, #ff6b6b) !important;
            border: none !important;
            color: var(--fitness-text-light, white) !important; /* White text like view-profile-btn */
            box-shadow: none !important;
        }
        
        .contact-submit-btn:hover:not(:disabled) {
            background: var(--fitness-primary-light) !important;
            border-color: var(--fitness-primary) !important;
            color: var(--fitness-primary, #ff6b6b) !important;
            box-shadow: none !important;
        }
        
        .contact-submit-btn:disabled {
            background: var(--fitness-primary, #ff6b6b) !important;
            color: var(--fitness-text-light, white) !important;
            opacity: 0.7;
            cursor: not-allowed;
            box-shadow: none !important;
        }
        
        @media (min-width: 576px) {
            .contact-form-label {
                font-size: 0.95rem;
            }
            .contact-form-input,
            .contact-form-textarea {
                font-size: 1rem;
                padding: 0.75rem 1rem;
            }
            .contact-submit-btn {
                font-size: 1rem;
                padding: 0.75rem 2rem;
            }
        }
        
        @media (min-width: 768px) {
            .contact-form-label {
                font-size: 1rem;
            }
            .contact-form-input,
            .contact-form-textarea {
                font-size: 1.05rem;
                padding: 0.875rem 1.125rem;
            }
            .contact-form-textarea {
                min-height: 150px;
            }
            .contact-submit-btn {
                width: auto;
                min-width: 150px;
            }
        }
        
        /* Contact Information Card */
        .contact-info-card-fitness {
            background: var(--fitness-gradient);
            color: var(--fitness-text-light);
            border-radius: 15px;
        }
        
        .contact-info-title-fitness {
            color: var(--fitness-text-light);
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 1.5rem;
        }
        
        .contact-info-item-fitness {
            color: rgba(255, 255, 255, 0.95);
            font-size: 0.9rem;
            line-height: 1.7;
            word-break: break-word;
        }
        
        .contact-info-item-fitness i {
            width: 20px;
            text-align: center;
        }
        
        .contact-info-link-fitness {
            color: var(--fitness-text-light);
            text-decoration: none;
            transition: opacity 0.3s ease;
            word-break: break-all;
        }
        
        .contact-info-link-fitness:hover {
            color: var(--fitness-text-light);
            opacity: 0.9;
            text-decoration: underline;
        }
        
        .contact-subtitle-fitness {
            font-size: 0.95rem;
            line-height: 1.6;
        }
        
        .contact-content-fitness {
            font-size: 0.95rem;
            line-height: 1.7;
        }
        
        @media (min-width: 576px) {
            .contact-info-title-fitness {
                font-size: 1.25rem;
            }
            .contact-info-item-fitness {
                font-size: 0.95rem;
            }
            .contact-subtitle-fitness {
                font-size: 1rem;
            }
            .contact-content-fitness {
                font-size: 1rem;
            }
        }
        
        @media (min-width: 768px) {
            .contact-info-title-fitness {
                font-size: 1.5rem;
            }
            .contact-info-item-fitness {
                font-size: 1rem;
            }
            .contact-subtitle-fitness {
                font-size: 1.125rem;
            }
            .contact-content-fitness {
                font-size: 1.125rem;
            }
        }
        
        @media (min-width: 992px) {
            .contact-info-item-fitness {
                font-size: 1.05rem;
            }
        }
        
        /* Responsive Alerts */
        .alert {
            font-size: 0.85rem;
            padding: 0.7rem 0.9rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            word-wrap: break-word;
        }
        
        .alert ul {
            margin-bottom: 0;
            padding-left: 1.1rem;
        }
        
        .alert li {
            margin-bottom: 0.25rem;
            word-wrap: break-word;
        }
        
        .alert .btn-close {
            padding: 0.5rem;
            font-size: 0.75rem;
        }
        
        @media (min-width: 576px) {
            .alert {
                font-size: 0.9rem;
                padding: 0.75rem 1rem;
            }
        }
        
        @media (min-width: 768px) {
            .alert {
                font-size: 1rem;
                padding: 1rem 1.25rem;
            }
            .alert ul {
                padding-left: 1.25rem;
            }
        }
        
        /* Contact Form Responsive Improvements */
        .contact-form {
            width: 100%;
        }
        
        .invalid-feedback {
            font-size: 0.85rem;
            display: block;
            margin-top: 0.25rem;
        }
        
        @media (min-width: 576px) {
            .invalid-feedback {
                font-size: 0.875rem;
            }
        }
        
        @media (max-width: 575px) {
            .contact-form-card-fitness .card-body {
                padding: 1.25rem !important;
            }
            .contact-info-card-fitness .card-body {
                padding: 1.25rem !important;
            }
        }
        
        @media (max-width: 767px) {
            .contact-form-card-fitness .card-body {
                padding: 1.5rem !important;
            }
        }
        
        /* Ensure form groups stack properly on mobile */
        .form-group {
            margin-bottom: 1rem;
        }
        
        @media (max-width: 575px) {
            .form-group {
                margin-bottom: 0.875rem;
            }
        }
        
        /* Responsive Main Content */
        main.fitness-content {
            margin-top: 70px;
        }
        
        @media (min-width: 768px) {
            main.fitness-content {
                margin-top: 80px;
            }
        }
        
        /* Override Bootstrap btn-primary to match package buttons (same background color) */
        .btn-primary,
        .btn-primary.btn-sm,
        .btn-primary.btn-lg,
        .package-btn,
        button.btn-primary,
        button.package-btn,
        a.btn-primary,
        a.package-btn {
            font-size: 0.9rem;
            padding: 0.625rem 1.25rem;
            background-color: var(--fitness-primary, #ff6b6b) !important;
            background: var(--fitness-primary, #ff6b6b) !important;
            border: none !important;
            border-color: var(--fitness-primary, #ff6b6b) !important;
            color: var(--fitness-text-light, white) !important; /* White text like view-profile-btn */
            box-shadow: none !important;
            opacity: 1 !important; /* Ensure full opacity, not lighter */
            filter: none !important; /* Remove any filters that might lighten the color */
        }
        
        /* Ensure login/signup buttons match package buttons exactly */
        .btn-primary.package-btn,
        button.btn-primary.package-btn {
            background-color: var(--fitness-primary, #ff6b6b) !important;
            background: var(--fitness-primary, #ff6b6b) !important;
            opacity: 1 !important;
            filter: none !important;
        }
        
        .btn-primary:hover,
        .btn-primary:focus,
        .btn-primary:active,
        .btn-primary.btn-sm:hover,
        .btn-primary.btn-sm:focus,
        .btn-primary.btn-sm:active,
        .btn-primary.btn-lg:hover,
        .btn-primary.btn-lg:focus,
        .btn-primary.btn-lg:active,
        .package-btn:hover:not(:disabled) {
            background: var(--fitness-primary-light) !important;
            border-color: var(--fitness-primary, #ff6b6b) !important;
            color: var(--fitness-primary, #ff6b6b) !important;
            box-shadow: none !important;
        }
        
        .btn-primary:disabled,
        .package-btn:disabled {
            background: var(--fitness-primary, #ff6b6b) !important;
            color: var(--fitness-text-light, white) !important;
            opacity: 0.7;
            cursor: not-allowed;
            box-shadow: none !important;
        }
        
        /* Responsive Buttons */
        .btn-fitness {
            background: var(--fitness-gradient);
            border: none;
            color: var(--fitness-text-light, white) !important; /* White text like view-profile-btn */
            padding: 10px 24px;
            border-radius: 25px;
            font-weight: 600;
            font-size: 0.9rem;
            transition: transform 0.3s ease;
            box-shadow: none !important;
        }
        
        @media (min-width: 576px) {
            .btn-fitness {
                padding: 12px 30px;
                font-size: 1rem;
            }
        }
        
        .btn-fitness:hover:not(:disabled) {
            transform: translateY(-2px);
            background: var(--fitness-primary-light);
            color: var(--fitness-primary, #ff6b6b) !important;
            box-shadow: none !important;
        }
        
        .btn-fitness:disabled {
            color: var(--fitness-text-light, white) !important;
            opacity: 0.7;
            cursor: not-allowed;
            box-shadow: none !important;
        }
        
        /* Responsive Cards */
        .card-fitness {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        .card-fitness:hover {
            transform: translateY(-5px);
        }
        
        /* Responsive Footer */
        .footer-fitness {
            background: var(--fitness-bg-footer);
            color: var(--fitness-text-footer, #ffffff);
            padding: 30px 0 0 0;
            margin: 0;
            margin-top: auto;
            flex-shrink: 0;
        }
        
        .footer-fitness .container {
            padding-bottom: 0 !important;
            margin-bottom: 0 !important;
        }
        
        .footer-fitness .footer-widget {
            margin-bottom: 2rem;
        }
        
        .footer-fitness h5 {
            font-size: 1.1rem;
            margin-bottom: 1rem;
        }
        
        .footer-fitness p,
        .footer-fitness .text-white-50,
        .footer-fitness,
        .footer-fitness * {
            color: var(--fitness-text-footer, #ffffff) !important;
        }
        
        .footer-fitness p,
        .footer-fitness .text-white-50 {
            font-size: 0.9rem;
        }
        
        @media (min-width: 768px) {
        .footer-fitness {
                padding: 40px 0 0 0;
            }
            .footer-fitness h5 {
                font-size: 1.25rem;
            }
            .footer-fitness p,
            .footer-fitness .text-white-50 {
                font-size: 1rem;
            }
            
            .footer-fitness,
            .footer-fitness * {
                color: var(--fitness-text-footer, #ffffff) !important;
            }
        }
        
        .footer-fitness hr { 
            margin-top: 1.5rem; 
            margin-bottom: 0.5rem; 
        }
        .footer-fitness p { 
            margin-bottom: 0 !important; 
            padding-bottom: 0 !important;
        }
        .footer-fitness .row:last-child {
            margin-bottom: 0 !important;
            padding-bottom: 0 !important;
        }
        .footer-fitness .row:last-child p {
            margin-bottom: 0 !important;
            padding-bottom: 0 !important;
        }
        .footer-fitness .row:last-child .col-md-12 {
            margin-bottom: 0 !important;
            padding-bottom: 0 !important;
        }
        
        /* Responsive Typography */
        h1, .h1 {
            font-size: 2rem;
        }
        
        h2, .h2 {
            font-size: 1.75rem;
        }
        
        h3, .h3 {
            font-size: 1.5rem;
        }
        
        @media (min-width: 768px) {
            h1, .h1 {
                font-size: 2.5rem;
            }
            h2, .h2 {
                font-size: 2rem;
            }
            h3, .h3 {
                font-size: 1.75rem;
            }
        }
        
        @media (min-width: 992px) {
            h1, .h1 {
                font-size: 3rem;
            }
            h2, .h2 {
                font-size: 2.5rem;
            }
            h3, .h3 {
                font-size: 2rem;
            }
        }
        
        /* Responsive Images */
        img {
            max-width: 100%;
            height: auto;
        }
        
        /* Responsive Container Padding */
        .container {
            padding-left: 15px;
            padding-right: 15px;
        }
        
        @media (min-width: 576px) {
            .container {
                padding-left: 20px;
                padding-right: 20px;
            }
        }
        
        body {
            margin-bottom: 0 !important;
            padding-bottom: 0 !important;
        }
        html {
            margin-bottom: 0 !important;
            padding-bottom: 0 !important;
        }
        
        @php
            // Determine page type for specific styling
            $pageType = $page->type ?? 'default';
            $pageSlug = $page->slug ?? 'home';
        @endphp
    </style>
</head>
<body class="fitness-template">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-fitness fixed-top">
        <div class="container">
            @php
                $orgId = env('CMS_DEFAULT_ORG_ID', 8);
                $org = \App\Models\Org::find($orgId);
                $orgName = $org?->name ?? config('app.name', 'Wodworx');
                $orgLogo = $org?->logoFilePath;
                
                // Construct S3 URL for logo
                $logoUrl = null;
                if ($orgLogo) {
                    $bucket = env('AWS_BUCKET', 'wodworx-dev');
                    $region = env('AWS_DEFAULT_REGION', 'us-east-1');
                    $logoUrl = "https://{$bucket}.s3.{$region}.amazonaws.com/{$orgLogo}";
                }
            @endphp
            <a class="navbar-brand fw-bold" href="/">
                @if($logoUrl)
                    <img src="{{ $logoUrl }}" alt="{{ $orgName }}" class="me-2" style="height: 35px; width: auto; max-height: 40px;">
                @else
                <i class="fas fa-dumbbell text-danger me-2"></i>
                @endif
                <span class="d-none d-md-inline">{{ $orgName }}</span>
                <span class="d-md-none">{{ Str::limit($orgName, 15) }}</span>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <!-- Home Link -->
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('/') || request()->is('home') ? 'active fw-bold' : '' }}" 
                           href="/">Home</a>
                    </li>
                    
                    <!-- Dynamic Navigation Pages -->
                    @if(isset($navigationPages) && $navigationPages->count() > 0)
                        @foreach($navigationPages as $navPage)
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is($navPage->slug) || request()->is($navPage->slug . '/*') ? 'active fw-bold' : '' }}" 
                                   href="/{{ $navPage->slug }}">{{ $navPage->title }}</a>
                            </li>
                        @endforeach
                    @endif
                    
                    <!-- Authentication Links -->
                    @guest
                        <li class="nav-item">
                            <a class="nav-link {{ request()->is('customer/signup') ? 'active fw-bold' : '' }}" 
                               href="{{ route('customer.signup') }}">Signup</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->is('login') || request()->is('customer/verify-otp') ? 'active fw-bold' : '' }}" 
                               href="{{ route('login') }}">Login</a>
                        </li>
                    @else
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user-circle me-1"></i>
                                {{ Auth::user()->fullName ?? Auth::user()->name }}
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="dropdown-item text-danger">
                                            <i class="fas fa-sign-out-alt me-2"></i>Logout
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </li>
                    @endguest
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="fitness-content">
        @php
            // Add page-specific classes and styling based on page type
            $pageClasses = match($pageType) {
                'home' => 'home-page',
                'about' => 'about-page',
                'contact' => 'contact-page',
                'blog' => 'blog-page',
                'coaches' => 'coaches-page',
                'packages' => 'packages-page',
                'schedule' => 'schedule-page',
                default => 'default-page'
            };
        @endphp
        
        <div class="{{ $pageClasses }}">
            {{ $slot }}
        </div>
    </main>

    <!-- Footer -->
    @php
        // Get dynamic footer blocks instead of static footer data
        $footerBlocks = \App\Models\CmsSection::where('container', 'footer')
            ->where('cms_page_id', null)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    @endphp
    <footer class="footer-fitness">
        <div class="container">
            @if($footerBlocks->count() > 0)
                <!-- Dynamic Footer Blocks in 4-Column Grid -->
                <div class="row g-4">
                    @foreach($footerBlocks->take(4) as $index => $block)
                        <div class="col-lg-3 col-md-6 mb-4">
                    <div class="footer-widget">
                                @switch($block->type)
                                    @case('heading')
                                        @if($block->content)
                                            <h5 class="fw-bold mb-3 text-white">
                            <i class="fas fa-dumbbell text-danger me-2"></i>
                                                {{ $block->content }}
                        </h5>
                        @endif
                                        @break
                                    
                                    @case('text')
                                        @if($block->content)
                                            <div class="text-white-50">
                                                {{ $block->content }}
                        </div>
                        @endif
                                        @break
                                    
                                    @case('paragraph')
                                        @if($block->content)
                                            <div class="text-white-50">
                                                {!! $block->content !!}
                </div>
                @endif
                                        @break
                                    
                                    @case('html')
                                        @if($block->content)
                                            <div class="footer-html-content">
                                                {!! $block->content !!}
                </div>
                        @endif
                                        @break
                                    
                                    @case('links')
                                        @php
                                            $links = [];
                                            try {
                                                if (is_string($block->content)) {
                                                    $links = json_decode($block->content, true) ?? [];
                                                } elseif (is_array($block->content)) {
                                                    $links = $block->content;
                                                }
                                            } catch (\Exception $e) {
                                                $links = [];
                                            }
                                        @endphp
                                        @if(is_array($links) && count($links) > 0)
                        <ul class="list-unstyled">
                                                @foreach($links as $link)
                                                    @if(is_array($link) && isset($link['label']) && isset($link['url']))
                            <li class="mb-2">
                                                            <a href="{{ $link['url'] }}" class="text-white-50 text-decoration-none">
                                                                <i class="fas fa-chevron-right me-2"></i>{{ $link['label'] }}
                                </a>
                            </li>
                                                    @endif
                            @endforeach
                        </ul>
                        @endif
                                        @break
                                    
                                    @case('contact')
                                        @php
                                            $contactData = is_array($block->data) ? $block->data : (json_decode($block->data ?? '{}', true) ?? []);
                                        @endphp
                                        <div class="contact-info">
                                            @if(!empty($contactData['address']))
                                                <p class="mb-2 text-white-50">
                                                    <i class="fas fa-map-marker-alt me-2 text-danger"></i>
                                                    {{ $contactData['address'] }}
                                                </p>
                                            @endif
                                            @if(!empty($contactData['phone']))
                                                <p class="mb-2">
                                                    <i class="fas fa-phone me-2 text-danger"></i>
                                                    <a href="tel:{{ $contactData['phone'] }}" class="text-white-50 text-decoration-none">
                                                        {{ $contactData['phone'] }}
                                                    </a>
                                                </p>
                                            @endif
                                            @if(!empty($contactData['email']))
                                                <p class="mb-2">
                                                    <i class="fas fa-envelope me-2 text-danger"></i>
                                                    <a href="mailto:{{ $contactData['email'] }}" class="text-white-50 text-decoration-none">
                                                        {{ $contactData['email'] }}
                                                    </a>
                                                </p>
                        @endif
                    </div>
                                        @break
                                    
                                    @case('image')
                                        @php
                                            $imageData = is_array($block->data) ? $block->data : (json_decode($block->data ?? '{}', true) ?? []);
                                            $imageUrl = $imageData['url'] ?? $block->content ?? null;
                                        @endphp
                                        @if($imageUrl)
                                            <div class="footer-image mb-3">
                                                <img src="{{ $imageUrl }}" 
                                                     alt="{{ $imageData['alt'] ?? 'Footer image' }}" 
                                                     class="img-fluid rounded">
                                                @if(!empty($imageData['caption']))
                                                    <p class="text-white-50 small mt-2 mb-0">{{ $imageData['caption'] }}</p>
                                                @endif
                </div>
                @endif
                                        @break
                                    
                                    @case('spacer')
                                        @php
                                            $height = $block->content ?: 30;
                                        @endphp
                                        <div style="height: {{ $height }}px;"></div>
                                        @break
                                    
                                    @default
                                        @if($block->content)
                                            <div class="text-white-50">
                                                {!! $block->content !!}
                                            </div>
                                        @endif
                                        @break
                                @endswitch
                            </div>
                    </div>
                    @endforeach
                </div>
            @else
                <!-- Fallback: Default Footer Content when no blocks exist -->
                <div class="row">
                    <div class="col-lg-3 col-md-6 mb-4">
                    <div class="footer-widget">
                            <h5 class="fw-bold mb-3 text-white">
                                <i class="fas fa-dumbbell text-danger me-2"></i>
                                {{ config('app.name', 'Fitness Gym') }}
                            </h5>
                            <p class="text-white-50 mb-3">Transform your body and mind with our comprehensive fitness programs designed for all levels.</p>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-4">
                    <div class="footer-widget">
                            <h5 class="fw-bold mb-3 text-white">Quick Links</h5>
                        <ul class="list-unstyled">
                            <li class="mb-2">
                                <a href="/" class="text-white-50 text-decoration-none">
                                    <i class="fas fa-chevron-right me-2"></i>Home
                                </a>
                            </li>
                                <li class="mb-2">
                                    <a href="/about" class="text-white-50 text-decoration-none">
                                        <i class="fas fa-chevron-right me-2"></i>About Us
                                    </a>
                                </li>
                                <li class="mb-2">
                                    <a href="/coaches" class="text-white-50 text-decoration-none">
                                        <i class="fas fa-chevron-right me-2"></i>Our Coaches
                                    </a>
                                </li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="footer-widget">
                            <h5 class="fw-bold mb-3 text-white">Classes</h5>
                            <ul class="list-unstyled">
                                <li class="mb-2">
                                    <a href="#" class="text-white-50 text-decoration-none">
                                        <i class="fas fa-chevron-right me-2"></i>Strength Training
                                    </a>
                                </li>
                                <li class="mb-2">
                                    <a href="#" class="text-white-50 text-decoration-none">
                                        <i class="fas fa-chevron-right me-2"></i>Cardio Fitness
                                    </a>
                                </li>
                                <li class="mb-2">
                                    <a href="#" class="text-white-50 text-decoration-none">
                                        <i class="fas fa-chevron-right me-2"></i>Group Classes
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="footer-widget">
                            <h5 class="fw-bold mb-3 text-white">Contact Info</h5>
                        <div class="contact-info">
                                <p class="mb-2 text-white-50">
                                <i class="fas fa-map-marker-alt me-2 text-danger"></i>
                                    123 Fitness Street, Gym City
                            </p>
                            <p class="mb-2">
                                <i class="fas fa-phone me-2 text-danger"></i>
                                    <a href="tel:+1234567890" class="text-white-50 text-decoration-none">
                                        +1 (234) 567-890
                                </a>
                            </p>
                            <p class="mb-2">
                                <i class="fas fa-envelope me-2 text-danger"></i>
                                    <a href="mailto:info@fitnessgym.com" class="text-white-50 text-decoration-none">
                                        info@fitnessgym.com
                                </a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
                @endif
            
            <!-- Copyright Section -->
            <hr class="border-secondary" style="margin-top: 1.5rem; margin-bottom: 0.5rem;">
            <div class="row" style="margin-bottom: 0; padding-bottom: 0;">
                <div class="col-md-12 text-center">
                    <p class="mb-0 text-white-50" style="margin-bottom: 0 !important; padding-bottom: 0;">
                        &copy; {{ date('Y') }} {{ config('app.name', 'Fitness Gym') }}. All rights reserved.
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    @livewireScripts
    @stack('scripts')
    
    <script>
        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
        
        // Navbar background on scroll
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar-fitness');
            const root = document.documentElement;
            if (window.scrollY > 50) {
                navbar.style.background = getComputedStyle(root).getPropertyValue('--fitness-navbar-bg-scroll') || 'rgba(255, 255, 255, 0.98)';
            } else {
                navbar.style.background = getComputedStyle(root).getPropertyValue('--fitness-navbar-bg') || 'rgba(255, 255, 255, 0.95)';
            }
        });
        
        // Close mobile menu when clicking on a nav link
        document.addEventListener('DOMContentLoaded', function() {
            const navLinks = document.querySelectorAll('.navbar-nav .nav-link');
            const navbarCollapse = document.querySelector('#navbarNav');
            const navbarToggler = document.querySelector('.navbar-toggler');
            
            navLinks.forEach(link => {
                link.addEventListener('click', function() {
                    // Check if menu is open (mobile view)
                    if (window.innerWidth < 992 && navbarCollapse && navbarCollapse.classList.contains('show')) {
                        // Use Bootstrap's collapse method to hide
                        if (typeof bootstrap !== 'undefined' && bootstrap.Collapse) {
                            const bsCollapse = bootstrap.Collapse.getInstance(navbarCollapse) || new bootstrap.Collapse(navbarCollapse, {
                                toggle: false
                            });
                            bsCollapse.hide();
                        } else {
                            // Fallback: manually hide
                            navbarCollapse.classList.remove('show');
                            if (navbarToggler) {
                                navbarToggler.setAttribute('aria-expanded', 'false');
                                navbarToggler.classList.add('collapsed');
                            }
                        }
                    }
                });
            });
        });
    </script>
</body>
</html>