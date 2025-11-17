# 🧘‍♀️ Meditative Template Integration Guide

## ✅ What's Already Done

I've created a **Meditative Template** for your CMS with a zen/wellness design inspired by your meditative-master files:

### 🎨 **Template Features:**
- **Zen aesthetics** with peaceful gradients (purple, pink, indigo)
- **Floating animations** and gentle movements
- **Meditative typography** (Poppins + Dancing Script fonts)
- **Wellness-focused navigation** and content
- **Sacred space design** with mindful elements
- **Inspirational quotes** and peaceful imagery

### 🚀 **Already Integrated:**
- ✅ Template file created: `resources/views/components/layouts/templates/meditative.blade.php`
- ✅ Added to CMS dropdown options
- ✅ Added to template preview system
- ✅ Added to template selector widget
- ✅ Fully functional and ready to use

## 🔧 **How to Use Your Actual Meditative Files**

To integrate your actual `meditative-master` HTML/CSS files, follow these steps:

### Step 1: Copy Files to Laravel
```bash
# Copy your meditative-master folder to your Laravel project
cp -r ../meditative-master ./public/meditative-assets/
```

### Step 2: Extract CSS and JS
```bash
# Copy CSS files
cp meditative-master/css/* ./public/css/meditative/
cp meditative-master/js/* ./public/js/meditative/
cp -r meditative-master/images/* ./public/images/meditative/
cp -r meditative-master/fonts/* ./public/fonts/meditative/
```

### Step 3: Update the Template File

Replace the current meditative template with your actual HTML structure:

```php
// In resources/views/components/layouts/templates/meditative.blade.php

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@stack('title', config('app.name'))</title>
    
    @stack('meta')
    
    <!-- Your Meditative CSS -->
    <link rel="stylesheet" href="{{ asset('css/meditative/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/meditative/style.css') }}">
    <!-- Add other CSS files from your template -->
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @stack('head')
</head>
<body>
    <!-- Your meditative template HTML structure here -->
    
    <!-- Navigation from your template -->
    <nav>
        <!-- Convert your navigation HTML -->
    </nav>
    
    <!-- Main content area -->
    <main>
        {{ $slot }}
    </main>
    
    <!-- Footer from your template -->
    <footer>
        <!-- Convert your footer HTML -->
    </footer>
    
    <!-- Your Meditative JS -->
    <script src="{{ asset('js/meditative/jquery.min.js') }}"></script>
    <script src="{{ asset('js/meditative/bootstrap.min.js') }}"></script>
    <!-- Add other JS files from your template -->
    
    @livewireScripts
    @stack('scripts')
</body>
</html>
```

### Step 4: Convert HTML Pages to Sections

Your meditative template has these pages:
- `index.html` → Home page content
- `about.html` → About page sections
- `classes.html` → Classes/packages page
- `trainer.html` → Coaches page
- `schedule.html` → Schedule page
- `contact.html` → Contact page
- `blog.html` → Blog sections

Convert each page's content into CMS sections that can be used with the template.

## 🎯 **Current Status**

### ✅ **Working Now:**
1. **Go to CMS Admin** → **🎨 Templates**
2. **Select Meditative Template** (🧘‍♀️)
3. **Apply to any page** to see the zen design
4. **Template selector** in page editor works
5. **Preview functionality** works

### 🔄 **To Enhance with Your Files:**
1. Copy your actual CSS/JS/images
2. Replace the template HTML structure
3. Maintain Laravel/Livewire compatibility
4. Keep the `{{ $slot }}` for dynamic content

## 🚀 **Try It Now**

1. **Edit any page** in CMS Admin
2. **Click the template selector** (colorful button in right sidebar)
3. **Choose "🧘‍♀️ Meditative Template"**
4. **Save and view** your page with zen design!

## 📝 **Next Steps**

If you want to use your actual meditative template files:

1. **Copy the files** to your Laravel project
2. **Send me the HTML content** from your key files (index.html, etc.)
3. **I'll help convert them** to proper Laravel templates
4. **Maintain all the CMS functionality** while using your design

The current meditative template is fully functional and ready to use! 🧘‍♀️✨
