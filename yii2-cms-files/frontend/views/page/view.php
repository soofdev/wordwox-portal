<?php

use yii\helpers\Html;
use frontend\widgets\SectionRenderer;

/* @var $this yii\web\View */
/* @var $page common\models\CmsPage */
/* @var $sections common\models\CmsSection[] */
/* @var $isPreview bool */

$isPreview = $isPreview ?? false;

// Set page title and meta tags (already set in controller, but can be overridden here)
$this->title = $page->seo_title ?: $page->title;

if ($page->seo_description) {
    $this->registerMetaTag([
        'name' => 'description', 
        'content' => $page->seo_description
    ]);
}

if ($page->seo_keywords) {
    $this->registerMetaTag([
        'name' => 'keywords', 
        'content' => $page->seo_keywords
    ]);
}

// Add structured data for SEO
$structuredData = [
    '@context' => 'https://schema.org',
    '@type' => 'WebPage',
    'name' => $page->title,
    'description' => $page->description,
    'url' => $page->getUrl(),
];

$this->registerMetaTag([
    'name' => 'application/ld+json',
    'content' => json_encode($structuredData)
], 'structured-data');

// Add page-specific CSS classes to body
$pageClasses = [
    'cms-page',
    'page-type-' . $page->type,
    'page-template-' . $page->template,
];

if ($page->is_homepage) {
    $pageClasses[] = 'homepage';
}

if ($isPreview) {
    $pageClasses[] = 'preview-mode';
}

$this->registerJs("document.body.className += ' " . implode(' ', $pageClasses) . "';");
?>

<div class="cms-page-wrapper" data-page-id="<?= $page->id ?>" data-page-type="<?= Html::encode($page->type) ?>">
    
    <?php if ($isPreview): ?>
        <div class="preview-banner bg-yellow-100 border-b border-yellow-300 p-3 text-center">
            <strong>Preview Mode:</strong> This is how your page will look when published.
            <span class="text-sm text-gray-600 ml-2">
                Status: <?= $page->getStatusLabel() ?> | 
                Type: <?= $page->getTypeLabel() ?> |
                Template: <?= Html::encode($page->template) ?>
            </span>
        </div>
    <?php endif; ?>

    <?php if ($page->title && $page->type !== 'home' && !$this->hasPageHeader($sections)): ?>
        <div class="page-header bg-gray-50 py-12">
            <div class="container mx-auto px-4">
                <div class="text-center">
                    <h1 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4">
                        <?= Html::encode($page->title) ?>
                    </h1>
                    <?php if ($page->description): ?>
                        <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                            <?= Html::encode($page->description) ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <main class="page-content">
        <?php if (empty($sections)): ?>
            <!-- Fallback content if no sections are defined -->
            <div class="container mx-auto px-4 py-16">
                <div class="text-center">
                    <?php if ($page->content): ?>
                        <div class="prose prose-lg mx-auto">
                            <?= $page->content ?>
                        </div>
                    <?php else: ?>
                        <div class="text-gray-500">
                            <h2 class="text-2xl font-semibold mb-4">Page Content</h2>
                            <p>This page is currently being updated. Please check back soon.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <!-- Render all sections -->
            <?php foreach ($sections as $section): ?>
                <?= SectionRenderer::widget([
                    'section' => $section,
                    'isPreview' => $isPreview,
                ]) ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>

    <?php if ($isPreview): ?>
        <div class="preview-footer bg-gray-100 border-t p-4 text-center text-sm text-gray-600">
            <strong>Preview Information:</strong>
            Page ID: <?= $page->id ?> | 
            Sections: <?= count($sections) ?> | 
            Last Updated: <?= date('Y-m-d H:i:s', $page->updated_at) ?>
        </div>
    <?php endif; ?>

</div>

<?php
// Helper function to check if sections contain a hero or header section
function hasPageHeader($sections) {
    foreach ($sections as $section) {
        if (in_array($section->type, ['hero', 'header', 'banner'])) {
            return true;
        }
    }
    return false;
}
$this->hasPageHeader = 'hasPageHeader';
?>
