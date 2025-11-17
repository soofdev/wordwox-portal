# 🚀 Quick Start: CMS Integration

## Step 1: Copy Files to Your Yii2 Project

```bash
# Navigate to your Yii2 project
cd /Users/macbook1993/Herd/wodworx-customer-portal-yii/

# Copy migration files
cp /path/to/yii2-cms-files/console/migrations/* console/migrations/

# Copy model files  
cp /path/to/yii2-cms-files/common/models/* common/models/

# Copy controller files
cp /path/to/yii2-cms-files/frontend/controllers/* frontend/controllers/

# Copy widget files
mkdir -p frontend/widgets/
cp /path/to/yii2-cms-files/frontend/widgets/* frontend/widgets/

# Copy view files
mkdir -p frontend/views/page/
cp /path/to/yii2-cms-files/frontend/views/page/* frontend/views/page/
```

## Step 2: Run Migrations

```bash
# Run the CMS migrations
./yii migrate
```

## Step 3: Update URL Rules

Add to `frontend/config/main.php`:

```php
'urlManager' => [
    'enablePrettyUrl' => true,
    'showScriptName' => false,
    'rules' => [
        '' => 'page/index',
        '<slug:[a-zA-Z0-9\-_]+>' => 'page/view',
        // ... your existing rules
    ],
],
```

## Step 4: Seed CMS Data from Laravel

```bash
# In your Laravel project
cd /Users/macbook1993/Herd/wodworx-foh\ copy/
php artisan db:seed --class=CmsPageSeeder
```

## Step 5: Test Integration

1. **Visit Homepage**: `http://your-yii2-site.local/`
2. **Visit CMS Page**: `http://your-yii2-site.local/about-us`
3. **Edit in Laravel**: `http://127.0.0.1:8000/cms-admin/pages`

## Step 6: Add Navigation Menu

Update your main layout (`frontend/views/layouts/main.php`):

```php
<?php
use common\models\CmsPage;
use frontend\controllers\PageController;

// Get CMS navigation pages
$navPages = PageController::getNavigationPages();
?>

<!-- In your navigation menu -->
<nav class="navbar">
    <a href="<?= Yii::$app->homeUrl ?>">Home</a>
    
    <?php foreach ($navPages as $page): ?>
        <a href="<?= $page->getUrl() ?>"><?= Html::encode($page->title) ?></a>
    <?php endforeach; ?>
</nav>
```

## 🎯 You're Done!

Your Yii2 website now displays content managed through the Laravel CMS admin interface!

### What You Get:

✅ **Laravel CMS Admin** - Modern block-based editor  
✅ **Yii2 Frontend** - Fast, SEO-friendly public pages  
✅ **Shared Database** - Seamless data integration  
✅ **Block Rendering** - Hero, content, CTA, and more  
✅ **SEO Optimization** - Meta tags, structured data  
✅ **Responsive Design** - Mobile-first layouts  

### Next Steps:

1. **Customize Styling** - Update CSS to match your design
2. **Add More Block Types** - Extend SectionRenderer widget
3. **Create Templates** - Add custom page templates
4. **Optimize Performance** - Add caching layers
5. **Add Media Management** - Integrate file uploads

Need help? Check the full integration plan in `CMS_INTEGRATION_PLAN.md`!
