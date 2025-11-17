# 🎨 CMS Template System

Your CMS now supports multiple templates that automatically change the layout, styling, and functionality based on the page type.

## 📋 Available Templates

### 1. **🚀 Modern Template** (`modern`)
- **Use for**: Tech-focused pages, futuristic branding, modern presentations
- **Features**:
  - Glass morphism effects with backdrop blur
  - Gradient backgrounds and neon glows
  - Floating animations and modern typography
  - Emoji icons and futuristic styling
  - Fixed glass navigation with rounded corners
- **Best for**: Innovation pages, tech showcases, modern brand presentations

### 2. **🏛️ Classic Template** (`classic`)
- **Use for**: Traditional branding, heritage pages, formal presentations
- **Features**:
  - Elegant serif typography (Playfair Display)
  - Gold/amber color scheme with ornamental borders
  - Classic patterns and decorative elements
  - Traditional layout with formal styling
  - Heritage-focused design elements
- **Best for**: About heritage, formal announcements, traditional branding

### 3. **🧘‍♀️ Meditative Template** (`meditative`)
- **Use for**: Wellness pages, yoga studios, mindfulness content
- **Features**:
  - Zen aesthetics with peaceful gradients
  - Floating animations and gentle movements
  - Meditative typography (Poppins + Dancing Script)
  - Wellness-focused navigation and content
  - Sacred space design with mindful elements
- **Best for**: Wellness centers, yoga studios, meditation content

## 🛠️ How to Use Templates

### In the CMS Admin:
1. Go to **CMS Admin** → **Pages**
2. Edit any page or create a new one
3. In the **Page Settings** panel (right side), find the **Template** dropdown
4. Select the template that best fits your page content
5. Save the page

### Template Selection Guide:
- **Tech/Innovation Pages**: Use `modern` template 🚀
- **Heritage/Formal Pages**: Use `classic` template 🏛️
- **Wellness/Yoga Pages**: Use `meditative` template 🧘‍♀️

## 🎯 Template Features

### Automatic Styling
Each template automatically applies:
- **Different navigation styles** (floating, standard, focused)
- **Custom footer content** (social links, contact info, CTAs)
- **Specialized sections** (stats, hours, pricing CTAs)
- **Color schemes and animations**
- **Template-specific hover effects**

### SEO Optimization
All templates support:
- Custom page titles
- Meta descriptions
- Meta keywords
- Structured data (automatic)

### Responsive Design
Every template is:
- Mobile-first responsive
- Tablet optimized
- Desktop enhanced
- Touch-friendly

## 🔧 Customization

### Adding New Templates:
1. Create a new layout file in `resources/views/components/layouts/templates/`
2. Add the template to the mapping in `app/Livewire/CmsPageViewer.php`
3. Add the option to the dropdown in `resources/views/livewire/cms-pages-edit.blade.php`

### Template Structure:
```html
<!DOCTYPE html>
<html>
<head>
    <title>@stack('title', config('app.name'))</title>
    @stack('meta')
    @stack('head')
</head>
<body>
    <!-- Navigation -->
    <nav>...</nav>
    
    <!-- Page Content -->
    <main>{{ $slot }}</main>
    
    <!-- Footer -->
    <footer>...</footer>
    
    @stack('scripts')
</body>
</html>
```

## 🚀 Live Examples

Visit these URLs to see different templates in action:

- **🚀 Modern Template**: Change any page to use "modern" template (futuristic glass design)
- **🏛️ Classic Template**: Change any page to use "classic" template (elegant traditional design)
- **🧘‍♀️ Meditative Template**: Change any page to use "meditative" template (zen wellness design)

## 💡 Pro Tips

1. **Match Content to Template**: Choose templates that complement your page content
2. **Consistent Branding**: All templates maintain your brand colors and fonts
3. **Performance**: Templates are optimized for fast loading
4. **SEO Ready**: Each template includes proper meta tag support
5. **User Experience**: Navigation highlights the current page automatically

---

**Need help?** The template system is designed to be intuitive - just pick the template that matches your page purpose and the styling will automatically adapt! 🎉
