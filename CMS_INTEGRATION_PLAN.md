# CMS Integration Plan: Laravel CMS + Yii2 Website

## 🎯 Architecture Overview

### Current Setup
- **Laravel FOH Project**: `/Users/macbook1993/Herd/wodworx-foh copy/`
- **Yii2 Customer Portal**: `/Users/macbook1993/Herd/wodworx-customer-portal-yii/`

### Integration Strategy
We'll create a **hybrid architecture** where:
- **Laravel handles CMS admin** (what we just built)
- **Yii2 handles public website** (consuming CMS data)
- **Shared database** for seamless data access

## 📋 Step-by-Step Integration

### Phase 1: Database Integration

#### 1.1 Copy CMS Migrations to Yii2
```bash
# Copy Laravel CMS migrations to Yii2
cp /Users/macbook1993/Herd/wodworx-foh\ copy/database/migrations/2025_11_17_140245_create_cms_pages_table.php \
   /Users/macbook1993/Herd/wodworx-customer-portal-yii/console/migrations/

cp /Users/macbook1993/Herd/wodworx-foh\ copy/database/migrations/2025_11_17_140257_create_cms_sections_table.php \
   /Users/macbook1993/Herd/wodworx-customer-portal-yii/console/migrations/

cp /Users/macbook1993/Herd/wodworx-foh\ copy/database/migrations/2025_11_17_200540_alter_cms_pages_timestamps_to_integers.php \
   /Users/macbook1993/Herd/wodworx-customer-portal-yii/console/migrations/

cp /Users/macbook1993/Herd/wodworx-foh\ copy/database/migrations/2025_11_17_200653_alter_cms_sections_timestamps_to_integers.php \
   /Users/macbook1993/Herd/wodworx-customer-portal-yii/console/migrations/
```

#### 1.2 Convert Laravel Migrations to Yii2 Format
Create Yii2 migration files that match the Laravel schema.

#### 1.3 Run CMS Seeder in Laravel
```bash
cd /Users/macbook1993/Herd/wodworx-foh\ copy/
php artisan db:seed --class=CmsPageSeeder
```

### Phase 2: Yii2 Models and Controllers

#### 2.1 Create Yii2 CMS Models
- `common/models/CmsPage.php`
- `common/models/CmsSection.php`

#### 2.2 Create Yii2 Frontend Controllers
- `frontend/controllers/SiteController.php` (enhanced)
- `frontend/controllers/PageController.php` (new)

#### 2.3 Create Yii2 Views
- Dynamic page rendering based on CMS content
- Block-based content rendering system

### Phase 3: API Bridge (Optional)

#### 3.1 Laravel API Endpoints
Create REST API endpoints in Laravel for Yii2 to consume:
- `GET /api/cms/pages`
- `GET /api/cms/pages/{slug}`
- `GET /api/cms/sections/{page_id}`

#### 3.2 Yii2 API Client
Create Yii2 service to consume Laravel CMS API.

## 🛠️ Implementation Files

### 1. Yii2 Migration Files

#### `console/migrations/m241117_000001_create_cms_pages_table.php`
```php
<?php

use yii\db\Migration;

class m241117_000001_create_cms_pages_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%cms_pages}}', [
            'id' => $this->primaryKey(),
            'uuid' => $this->string(36)->notNull()->unique(),
            'org_id' => $this->integer()->notNull(),
            'orgPortal_id' => $this->integer()->notNull(),
            
            // Page identification
            'title' => $this->string()->notNull(),
            'slug' => $this->string()->notNull()->unique(),
            'description' => $this->text(),
            
            // Content
            'content' => $this->text(),
            'meta_data' => $this->json(),
            
            // Page settings
            'status' => "ENUM('draft', 'published', 'archived') DEFAULT 'draft'",
            'type' => "ENUM('page', 'post', 'home', 'about', 'contact', 'custom') DEFAULT 'page'",
            'is_homepage' => $this->boolean()->defaultValue(false),
            'show_in_navigation' => $this->boolean()->defaultValue(true),
            'sort_order' => $this->integer()->defaultValue(0),
            
            // SEO
            'seo_title' => $this->string(),
            'seo_description' => $this->text(),
            'seo_keywords' => $this->text(),
            
            // Template and layout
            'template' => $this->string()->defaultValue('default'),
            'layout' => $this->string()->defaultValue('default'),
            
            // Publishing
            'published_at' => $this->integer(),
            'created_by' => $this->integer(),
            'updated_by' => $this->integer(),
            
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
            'deleted_at' => $this->integer(),
        ]);

        // Indexes
        $this->createIndex('idx-cms_pages-org_id-orgPortal_id', '{{%cms_pages}}', ['org_id', 'orgPortal_id']);
        $this->createIndex('idx-cms_pages-slug-org_id', '{{%cms_pages}}', ['slug', 'org_id']);
        $this->createIndex('idx-cms_pages-status-published_at', '{{%cms_pages}}', ['status', 'published_at']);
        $this->createIndex('idx-cms_pages-sort_order', '{{%cms_pages}}', 'sort_order');
        
        // Foreign keys
        $this->addForeignKey('fk-cms_pages-org_id', '{{%cms_pages}}', 'org_id', '{{%org}}', 'id', 'CASCADE');
        $this->addForeignKey('fk-cms_pages-orgPortal_id', '{{%cms_pages}}', 'orgPortal_id', '{{%orgPortal}}', 'id', 'CASCADE');
        $this->addForeignKey('fk-cms_pages-created_by', '{{%cms_pages}}', 'created_by', '{{%user}}', 'id', 'SET NULL');
        $this->addForeignKey('fk-cms_pages-updated_by', '{{%cms_pages}}', 'updated_by', '{{%user}}', 'id', 'SET NULL');
    }

    public function safeDown()
    {
        $this->dropTable('{{%cms_pages}}');
    }
}
```

#### `console/migrations/m241117_000002_create_cms_sections_table.php`
```php
<?php

use yii\db\Migration;

class m241117_000002_create_cms_sections_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%cms_sections}}', [
            'id' => $this->primaryKey(),
            'uuid' => $this->string(36)->notNull()->unique(),
            'cms_page_id' => $this->integer()->notNull(),
            
            // Section identification
            'name' => $this->string()->notNull(),
            'type' => $this->string()->notNull(), // hero, content, gallery, etc.
            
            // Content
            'title' => $this->string(),
            'subtitle' => $this->text(),
            'content' => $this->text(),
            'settings' => $this->json(),
            'data' => $this->json(),
            
            // Layout and styling
            'template' => $this->string()->defaultValue('default'),
            'css_classes' => $this->string(),
            'styles' => $this->json(),
            
            // Position and visibility
            'sort_order' => $this->integer()->defaultValue(0),
            'is_active' => $this->boolean()->defaultValue(true),
            'is_visible' => $this->boolean()->defaultValue(true),
            
            // Responsive settings
            'responsive_settings' => $this->json(),
            
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
            'deleted_at' => $this->integer(),
        ]);

        // Indexes
        $this->createIndex('idx-cms_sections-cms_page_id-sort_order', '{{%cms_sections}}', ['cms_page_id', 'sort_order']);
        $this->createIndex('idx-cms_sections-type-is_active', '{{%cms_sections}}', ['type', 'is_active']);
        $this->createIndex('idx-cms_sections-sort_order', '{{%cms_sections}}', 'sort_order');
        
        // Foreign keys
        $this->addForeignKey('fk-cms_sections-cms_page_id', '{{%cms_sections}}', 'cms_page_id', '{{%cms_pages}}', 'id', 'CASCADE');
    }

    public function safeDown()
    {
        $this->dropTable('{{%cms_sections}}');
    }
}
```

### 2. Yii2 Models

#### `common/models/CmsPage.php`
```php
<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * CmsPage model
 *
 * @property int $id
 * @property string $uuid
 * @property int $org_id
 * @property int $orgPortal_id
 * @property string $title
 * @property string $slug
 * @property string $description
 * @property string $content
 * @property array $meta_data
 * @property string $status
 * @property string $type
 * @property bool $is_homepage
 * @property bool $show_in_navigation
 * @property int $sort_order
 * @property string $seo_title
 * @property string $seo_description
 * @property string $seo_keywords
 * @property string $template
 * @property string $layout
 * @property int $published_at
 * @property int $created_by
 * @property int $updated_by
 * @property int $created_at
 * @property int $updated_at
 * @property int $deleted_at
 *
 * @property CmsSection[] $sections
 */
class CmsPage extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%cms_pages}}';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            TimestampBehavior::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['title', 'slug'], 'required'],
            [['org_id', 'orgPortal_id', 'sort_order', 'published_at', 'created_by', 'updated_by'], 'integer'],
            [['description', 'content', 'seo_description', 'seo_keywords'], 'string'],
            [['meta_data'], 'safe'],
            [['is_homepage', 'show_in_navigation'], 'boolean'],
            [['status'], 'in', 'range' => ['draft', 'published', 'archived']],
            [['type'], 'in', 'range' => ['page', 'post', 'home', 'about', 'contact', 'custom']],
            [['uuid', 'title', 'slug', 'seo_title', 'template', 'layout'], 'string', 'max' => 255],
        ];
    }

    /**
     * Gets query for [[CmsSection]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSections()
    {
        return $this->hasMany(CmsSection::class, ['cms_page_id' => 'id'])
                    ->orderBy(['sort_order' => SORT_ASC]);
    }

    /**
     * Get published pages
     */
    public static function getPublished()
    {
        return static::find()
            ->where(['status' => 'published'])
            ->andWhere(['or', 
                ['published_at' => null], 
                ['<=', 'published_at', time()]
            ]);
    }

    /**
     * Get navigation pages
     */
    public static function getNavigation()
    {
        return static::getPublished()
            ->andWhere(['show_in_navigation' => true])
            ->orderBy(['sort_order' => SORT_ASC]);
    }

    /**
     * Find by slug
     */
    public static function findBySlug($slug)
    {
        return static::getPublished()
            ->andWhere(['slug' => $slug])
            ->one();
    }

    /**
     * Get homepage
     */
    public static function getHomepage()
    {
        return static::getPublished()
            ->andWhere(['is_homepage' => true])
            ->one();
    }
}
```

#### `common/models/CmsSection.php`
```php
<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * CmsSection model
 *
 * @property int $id
 * @property string $uuid
 * @property int $cms_page_id
 * @property string $name
 * @property string $type
 * @property string $title
 * @property string $subtitle
 * @property string $content
 * @property array $settings
 * @property array $data
 * @property string $template
 * @property string $css_classes
 * @property array $styles
 * @property int $sort_order
 * @property bool $is_active
 * @property bool $is_visible
 * @property array $responsive_settings
 * @property int $created_at
 * @property int $updated_at
 * @property int $deleted_at
 *
 * @property CmsPage $page
 */
class CmsSection extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%cms_sections}}';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            TimestampBehavior::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name', 'type'], 'required'],
            [['cms_page_id', 'sort_order'], 'integer'],
            [['subtitle', 'content'], 'string'],
            [['settings', 'data', 'styles', 'responsive_settings'], 'safe'],
            [['is_active', 'is_visible'], 'boolean'],
            [['uuid', 'name', 'type', 'title', 'template', 'css_classes'], 'string', 'max' => 255],
        ];
    }

    /**
     * Gets query for [[CmsPage]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPage()
    {
        return $this->hasOne(CmsPage::class, ['id' => 'cms_page_id']);
    }

    /**
     * Get active sections
     */
    public static function getActive()
    {
        return static::find()
            ->where(['is_active' => true, 'is_visible' => true])
            ->orderBy(['sort_order' => SORT_ASC]);
    }
}
```

### 3. Yii2 Controllers

#### `frontend/controllers/PageController.php`
```php
<?php

namespace frontend\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use common\models\CmsPage;

/**
 * Page controller for CMS pages
 */
class PageController extends Controller
{
    /**
     * Display a CMS page by slug
     */
    public function actionView($slug)
    {
        $page = CmsPage::findBySlug($slug);
        
        if (!$page) {
            throw new NotFoundHttpException('Page not found.');
        }

        return $this->render('view', [
            'page' => $page,
            'sections' => $page->sections,
        ]);
    }

    /**
     * Display homepage
     */
    public function actionIndex()
    {
        $page = CmsPage::getHomepage();
        
        if (!$page) {
            // Fallback to default homepage
            return $this->render('index');
        }

        return $this->render('view', [
            'page' => $page,
            'sections' => $page->sections,
        ]);
    }
}
```

### 4. Yii2 Views

#### `frontend/views/page/view.php`
```php
<?php

use yii\helpers\Html;
use frontend\widgets\SectionRenderer;

/* @var $this yii\web\View */
/* @var $page common\models\CmsPage */
/* @var $sections common\models\CmsSection[] */

$this->title = $page->seo_title ?: $page->title;
$this->registerMetaTag(['name' => 'description', 'content' => $page->seo_description ?: $page->description]);
if ($page->seo_keywords) {
    $this->registerMetaTag(['name' => 'keywords', 'content' => $page->seo_keywords]);
}
?>

<div class="cms-page" data-page-type="<?= Html::encode($page->type) ?>">
    <?php if ($page->title && $page->type !== 'home'): ?>
        <div class="page-header">
            <div class="container">
                <h1><?= Html::encode($page->title) ?></h1>
                <?php if ($page->description): ?>
                    <p class="page-description"><?= Html::encode($page->description) ?></p>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="page-content">
        <?php foreach ($sections as $section): ?>
            <?= SectionRenderer::widget(['section' => $section]) ?>
        <?php endforeach; ?>
    </div>
</div>
```

#### `frontend/widgets/SectionRenderer.php`
```php
<?php

namespace frontend\widgets;

use yii\base\Widget;
use yii\helpers\Html;
use yii\helpers\Json;
use common\models\CmsSection;

/**
 * Widget to render CMS sections
 */
class SectionRenderer extends Widget
{
    public $section;

    public function run()
    {
        if (!$this->section || !$this->section->is_active || !$this->section->is_visible) {
            return '';
        }

        $method = 'render' . ucfirst($this->section->type);
        
        if (method_exists($this, $method)) {
            return $this->$method();
        }

        return $this->renderDefault();
    }

    protected function renderHero()
    {
        $settings = $this->getSettings();
        $bgColor = $settings['background_color'] ?? '#1f2937';
        $textColor = $settings['text_color'] ?? '#ffffff';

        return Html::tag('section', 
            Html::tag('div', 
                Html::tag('div', 
                    Html::tag('h1', Html::encode($this->section->title), ['class' => 'hero-title']) .
                    Html::tag('p', Html::encode($this->section->subtitle), ['class' => 'hero-subtitle']) .
                    Html::tag('div', $this->section->content, ['class' => 'hero-content']),
                    ['class' => 'container']
                ),
                ['class' => 'hero-inner']
            ),
            [
                'class' => 'hero-section',
                'style' => "background-color: {$bgColor}; color: {$textColor};"
            ]
        );
    }

    protected function renderContent()
    {
        return Html::tag('section', 
            Html::tag('div', 
                ($this->section->title ? Html::tag('h2', Html::encode($this->section->title)) : '') .
                Html::tag('div', $this->section->content, ['class' => 'content-body']),
                ['class' => 'container']
            ),
            ['class' => 'content-section']
        );
    }

    protected function renderCta()
    {
        $data = $this->getData();
        $buttons = $data['buttons'] ?? [];

        $buttonHtml = '';
        foreach ($buttons as $button) {
            $buttonHtml .= Html::a(
                Html::encode($button['text']),
                $button['url'],
                ['class' => 'btn btn-' . ($button['style'] ?? 'primary')]
            );
        }

        return Html::tag('section', 
            Html::tag('div', 
                Html::tag('h2', Html::encode($this->section->title)) .
                Html::tag('p', Html::encode($this->section->content)) .
                Html::tag('div', $buttonHtml, ['class' => 'cta-buttons']),
                ['class' => 'container text-center']
            ),
            ['class' => 'cta-section']
        );
    }

    protected function renderDefault()
    {
        return Html::tag('section', 
            Html::tag('div', 
                ($this->section->title ? Html::tag('h2', Html::encode($this->section->title)) : '') .
                Html::tag('div', $this->section->content),
                ['class' => 'container']
            ),
            ['class' => 'section section-' . Html::encode($this->section->type)]
        );
    }

    protected function getSettings()
    {
        return is_string($this->section->settings) 
            ? Json::decode($this->section->settings) 
            : ($this->section->settings ?: []);
    }

    protected function getData()
    {
        return is_string($this->section->data) 
            ? Json::decode($this->section->data) 
            : ($this->section->data ?: []);
    }
}
```

## 🚀 Quick Start Commands

### 1. Copy CMS Files to Yii2 Project
```bash
# Navigate to your Yii2 project
cd /Users/macbook1993/Herd/wodworx-customer-portal-yii/

# Create the migration files (content provided above)
# Create the model files (content provided above)  
# Create the controller files (content provided above)
# Create the view files (content provided above)
# Create the widget files (content provided above)

# Run migrations
./yii migrate
```

### 2. Update Yii2 URL Rules
Add to `frontend/config/main.php`:
```php
'urlManager' => [
    'enablePrettyUrl' => true,
    'showScriptName' => false,
    'rules' => [
        '' => 'page/index',
        '<slug:[a-zA-Z0-9-_]+>' => 'page/view',
        // ... other rules
    ],
],
```

### 3. Add Navigation Menu
Update your main layout to include CMS navigation:
```php
// In frontend/views/layouts/main.php
use common\models\CmsPage;

$navPages = CmsPage::getNavigation()->all();
foreach ($navPages as $page) {
    echo Html::a($page->title, ['page/view', 'slug' => $page->slug]);
}
```

## 🎯 Benefits of This Approach

1. **✅ Preserve Existing Code**: Your Yii2 codebase remains intact
2. **✅ Modern CMS Admin**: Use the Laravel CMS admin we just built
3. **✅ Shared Database**: Both systems work with the same data
4. **✅ Scalable**: Can migrate to full Laravel later if needed
5. **✅ SEO Friendly**: Yii2 handles public URLs and SEO
6. **✅ Performance**: Native Yii2 performance for public pages

## 📞 Next Steps

1. **Create the files above** in your Yii2 project
2. **Run the migrations** to create CMS tables
3. **Seed the data** from Laravel
4. **Test the integration** by viewing pages in Yii2
5. **Customize the styling** to match your design

Would you like me to help you implement any specific part of this integration?
