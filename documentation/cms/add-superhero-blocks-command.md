# Add SuperHero Blocks Command

## Overview

The `cms:add-superhero-blocks` command creates CMS pages and blocks matching SuperHero CrossFit's structure. All pages render from blocks, similar to the Yii2 customer portal.

## Usage

```bash
php artisan cms:add-superhero-blocks [options]
```

### Options

- `--org-id=8` - Organization ID (default: 8)
- `--force` - Force recreation of existing pages (deletes and recreates)

### Examples

```bash
# Add blocks for default org (8)
php artisan cms:add-superhero-blocks

# Add blocks for specific org
php artisan cms:add-superhero-blocks --org-id=10

# Force recreation of existing pages
php artisan cms:add-superhero-blocks --force
```

## What It Creates

The command creates the following pages with blocks:

### 1. Home Page (`/home`)
- **Hero Block**: Main hero section with title, subtitle, and CTA button
- **Packages Preview Block**: Grid display of membership packages
- **Contact Form Block**: Contact form for inquiries

### 2. About Page (`/about`)
- **Heading Block**: Page title
- **Content Block**: About us content (paragraph)

### 3. Packages Page (`/packages`)
- **Packages Block**: Full grid of all membership packages with buy buttons

### 4. Coaches Page (`/coaches`)
- **Coaches Block**: Grid display of coaches with profile photos

### 5. Contact Page (`/contact-us`)
- **Contact Block**: Contact form, map, and contact information

### 6. Schedule Page (`/schedule`)
- **Schedule Block**: Class schedule display

## Block Types Used

1. **hero** - Hero section with background, title, subtitle, and CTA
2. **heading** - Simple heading text
3. **paragraph** - Rich text content (CKEditor)
4. **packages** - Membership packages grid
5. **coaches** - Coaches grid with photos
6. **contact** - Contact form and information
7. **schedule** - Class schedule

## Page Structure

All pages use the `fitness` template and are configured as:
- **Status**: Published
- **Navigation**: Visible in navigation (except home)
- **Template**: Fitness
- **Homepage**: Only home page is marked as homepage

## Block Settings

### Hero Block
```php
[
    'background_image' => null,
    'overlay_opacity' => 0.5,
    'text_alignment' => 'center',
    'button_text' => 'Get Started',
    'button_link' => '/packages',
]
```

### Packages Block
```php
[
    'layout' => 'grid',
    'columns' => 3,
    'show_programs' => true,
    'show_description' => false,
    'buy_button_text' => 'Buy',
]
```

### Coaches Block
```php
[
    'layout' => 'grid',
    'columns' => 3,
    'show_photo' => true,
    'view_profile_text' => 'View Profile',
]
```

### Contact Block
```php
[
    'show_map' => true,
    'show_contact_info' => true,
    'form_title' => 'Send us a message',
    'map_url' => null, // Set via CMS admin
]
```

## Rendering

All pages render from blocks using the CMS section wrapper:
- Each block is rendered via `resources/views/partials/cms/section-wrapper.blade.php`
- Block-specific partials are in `resources/views/partials/cms/sections/`
- Pages use the fitness template layout

## Customization

After running the command, you can:
1. Edit pages via CMS admin: `/cms-admin/pages`
2. Edit blocks via CMS admin: Click "Edit" on any page
3. Modify block settings, content, and styling
4. Add or remove blocks from pages
5. Reorder blocks using drag-and-drop

## Notes

- The command uses database transactions for data integrity
- Existing pages are preserved unless `--force` is used
- All blocks are created with `is_active = true` and `is_visible = true`
- Blocks are ordered by `sort_order` (1, 2, 3, etc.)

## Related Files

- Command: `app/Console/Commands/AddSuperHeroBlocks.php`
- Models: `app/Models/CmsPage.php`, `app/Models/CmsSection.php`
- Views: `resources/views/partials/cms/sections/`
- CMS Admin: `app/Livewire/CmsPagesEdit.php`



