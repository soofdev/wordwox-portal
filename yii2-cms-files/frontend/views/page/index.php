<?php

use yii\helpers\Html;

/* @var $this yii\web\View */

$this->title = 'Welcome';
?>

<div class="site-index">
    
    <!-- Hero Section -->
    <div class="hero-section bg-gradient-to-r from-blue-600 to-purple-700 text-white py-20">
        <div class="container mx-auto px-4 text-center">
            <h1 class="text-4xl md:text-6xl font-bold mb-6">
                Welcome to Our Website
            </h1>
            <p class="text-xl md:text-2xl mb-8 opacity-90">
                This is the default homepage. Configure your CMS to customize this content.
            </p>
            <div class="space-x-4">
                <a href="#about" class="bg-white text-blue-600 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition-colors inline-block">
                    Learn More
                </a>
                <a href="#contact" class="border-2 border-white text-white px-8 py-3 rounded-lg font-semibold hover:bg-white hover:text-blue-600 transition-colors inline-block">
                    Get Started
                </a>
            </div>
        </div>
    </div>

    <!-- About Section -->
    <div id="about" class="py-16 bg-gray-50">
        <div class="container mx-auto px-4">
            <div class="text-center mb-12">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                    About Our Platform
                </h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                    This is a powerful content management system built with Laravel and Yii2, 
                    providing seamless integration between modern CMS functionality and robust web applications.
                </p>
            </div>
            
            <div class="grid md:grid-cols-3 gap-8">
                <div class="text-center">
                    <div class="bg-blue-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold mb-2">Fast & Reliable</h3>
                    <p class="text-gray-600">Built with performance in mind, delivering fast loading times and reliable service.</p>
                </div>
                
                <div class="text-center">
                    <div class="bg-green-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold mb-2">Easy to Use</h3>
                    <p class="text-gray-600">Intuitive interface makes content management simple for users of all skill levels.</p>
                </div>
                
                <div class="text-center">
                    <div class="bg-purple-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold mb-2">Fully Customizable</h3>
                    <p class="text-gray-600">Flexible architecture allows for complete customization to meet your specific needs.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Features Section -->
    <div class="py-16">
        <div class="container mx-auto px-4">
            <div class="text-center mb-12">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                    Key Features
                </h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                    Everything you need to create and manage your website content effectively.
                </p>
            </div>
            
            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="bg-white p-6 rounded-lg shadow-md border">
                    <h4 class="font-semibold text-lg mb-2">Block Editor</h4>
                    <p class="text-gray-600 text-sm">Modern block-based content editor for creating rich, dynamic pages.</p>
                </div>
                
                <div class="bg-white p-6 rounded-lg shadow-md border">
                    <h4 class="font-semibold text-lg mb-2">SEO Optimized</h4>
                    <p class="text-gray-600 text-sm">Built-in SEO tools to help your content rank better in search engines.</p>
                </div>
                
                <div class="bg-white p-6 rounded-lg shadow-md border">
                    <h4 class="font-semibold text-lg mb-2">Responsive Design</h4>
                    <p class="text-gray-600 text-sm">Mobile-first design ensures your content looks great on all devices.</p>
                </div>
                
                <div class="bg-white p-6 rounded-lg shadow-md border">
                    <h4 class="font-semibold text-lg mb-2">Multi-tenant</h4>
                    <p class="text-gray-600 text-sm">Support for multiple organizations with complete data isolation.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- CTA Section -->
    <div id="contact" class="py-16 bg-blue-600 text-white">
        <div class="container mx-auto px-4 text-center">
            <h2 class="text-3xl md:text-4xl font-bold mb-4">
                Ready to Get Started?
            </h2>
            <p class="text-xl mb-8 opacity-90">
                Set up your CMS homepage to replace this default content.
            </p>
            <div class="space-x-4">
                <a href="/admin" class="bg-white text-blue-600 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition-colors inline-block">
                    Admin Panel
                </a>
                <a href="/cms-admin" class="border-2 border-white text-white px-8 py-3 rounded-lg font-semibold hover:bg-white hover:text-blue-600 transition-colors inline-block">
                    CMS Admin
                </a>
            </div>
        </div>
    </div>

</div>

<style>
/* Custom styles for the default homepage */
.hero-section {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.grid {
    display: grid;
}

.md\:grid-cols-2 {
    grid-template-columns: repeat(2, minmax(0, 1fr));
}

.md\:grid-cols-3 {
    grid-template-columns: repeat(3, minmax(0, 1fr));
}

.lg\:grid-cols-4 {
    grid-template-columns: repeat(4, minmax(0, 1fr));
}

.gap-6 {
    gap: 1.5rem;
}

.gap-8 {
    gap: 2rem;
}

.shadow-md {
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}

.rounded-lg {
    border-radius: 0.5rem;
}

.rounded-full {
    border-radius: 9999px;
}

@media (min-width: 768px) {
    .md\:text-4xl {
        font-size: 2.25rem;
        line-height: 2.5rem;
    }
    
    .md\:text-6xl {
        font-size: 3.75rem;
        line-height: 1;
    }
    
    .md\:text-2xl {
        font-size: 1.5rem;
        line-height: 2rem;
    }
}
</style>
