<?php

namespace frontend\widgets;

use yii\base\Widget;
use yii\helpers\Html;
use yii\helpers\Url;
use common\models\CmsSection;

/**
 * Widget to render CMS sections based on their type
 */
class SectionRenderer extends Widget
{
    /**
     * @var CmsSection The section to render
     */
    public $section;

    /**
     * @var bool Whether this is a preview mode
     */
    public $isPreview = false;

    /**
     * @var array Additional HTML options for the section container
     */
    public $options = [];

    /**
     * {@inheritdoc}
     */
    public function run()
    {
        if (!$this->section) {
            return '';
        }

        // Don't render inactive/invisible sections unless in preview mode
        if (!$this->isPreview && (!$this->section->is_active || !$this->section->is_visible)) {
            return '';
        }

        // Get the render method for this section type
        $method = 'render' . ucfirst(str_replace('_', '', $this->section->type));
        
        if (method_exists($this, $method)) {
            return $this->$method();
        }

        // Fallback to default renderer
        return $this->renderDefault();
    }

    /**
     * Render hero section
     */
    protected function renderHero()
    {
        $settings = $this->section->getSettingsArray();
        $bgColor = $settings['background_color'] ?? '#1f2937';
        $textColor = $settings['text_color'] ?? '#ffffff';

        $heroContent = '';
        
        if ($this->section->title) {
            $heroContent .= Html::tag('h1', Html::encode($this->section->title), [
                'class' => 'hero-title text-4xl md:text-6xl font-bold mb-4'
            ]);
        }
        
        if ($this->section->subtitle) {
            $heroContent .= Html::tag('p', Html::encode($this->section->subtitle), [
                'class' => 'hero-subtitle text-xl md:text-2xl mb-6 opacity-90'
            ]);
        }
        
        if ($this->section->content) {
            $heroContent .= Html::tag('div', $this->section->content, [
                'class' => 'hero-content text-lg mb-8'
            ]);
        }

        $innerContent = Html::tag('div', $heroContent, [
            'class' => 'container mx-auto px-4 text-center'
        ]);

        return Html::tag('section', $innerContent, [
            'class' => 'hero-section py-20 md:py-32 ' . $this->section->getCssClassesString(),
            'style' => "background-color: {$bgColor}; color: {$textColor};" . $this->section->getInlineStyles(),
        ]);
    }

    /**
     * Render content section
     */
    protected function renderContent()
    {
        $content = '';
        
        if ($this->section->title) {
            $content .= Html::tag('h2', Html::encode($this->section->title), [
                'class' => 'text-3xl font-bold mb-6'
            ]);
        }
        
        if ($this->section->subtitle) {
            $content .= Html::tag('p', Html::encode($this->section->subtitle), [
                'class' => 'text-xl text-gray-600 mb-6'
            ]);
        }
        
        if ($this->section->content) {
            $content .= Html::tag('div', $this->section->content, [
                'class' => 'prose prose-lg max-w-none'
            ]);
        }

        $innerContent = Html::tag('div', $content, [
            'class' => 'container mx-auto px-4'
        ]);

        return Html::tag('section', $innerContent, [
            'class' => 'content-section py-16 ' . $this->section->getCssClassesString(),
            'style' => $this->section->getInlineStyles(),
        ]);
    }

    /**
     * Render call-to-action section
     */
    protected function renderCta()
    {
        $data = $this->section->getDataArray();
        $buttons = $data['buttons'] ?? [];

        $content = '';
        
        if ($this->section->title) {
            $content .= Html::tag('h2', Html::encode($this->section->title), [
                'class' => 'text-3xl md:text-4xl font-bold mb-4'
            ]);
        }
        
        if ($this->section->content) {
            $content .= Html::tag('p', Html::encode($this->section->content), [
                'class' => 'text-xl mb-8'
            ]);
        }

        // Render buttons
        if (!empty($buttons)) {
            $buttonHtml = '';
            foreach ($buttons as $button) {
                $buttonClass = 'btn ';
                switch ($button['style'] ?? 'primary') {
                    case 'primary':
                        $buttonClass .= 'bg-blue-600 hover:bg-blue-700 text-white';
                        break;
                    case 'secondary':
                        $buttonClass .= 'bg-gray-600 hover:bg-gray-700 text-white';
                        break;
                    case 'outline':
                        $buttonClass .= 'border-2 border-blue-600 text-blue-600 hover:bg-blue-600 hover:text-white';
                        break;
                }
                $buttonClass .= ' px-6 py-3 rounded-lg font-semibold transition-colors mr-4 mb-4 inline-block';

                $buttonHtml .= Html::a(
                    Html::encode($button['text']),
                    $button['url'],
                    ['class' => $buttonClass]
                );
            }
            
            $content .= Html::tag('div', $buttonHtml, [
                'class' => 'cta-buttons'
            ]);
        }

        $innerContent = Html::tag('div', $content, [
            'class' => 'container mx-auto px-4 text-center'
        ]);

        return Html::tag('section', $innerContent, [
            'class' => 'cta-section py-16 bg-gray-100 ' . $this->section->getCssClassesString(),
            'style' => $this->section->getInlineStyles(),
        ]);
    }

    /**
     * Render heading section
     */
    protected function renderHeading()
    {
        $settings = $this->section->getSettingsArray();
        $level = $settings['level'] ?? 2;
        $tag = 'h' . min(6, max(1, $level));

        $content = Html::tag($tag, Html::encode($this->section->content), [
            'class' => 'text-2xl md:text-3xl font-bold mb-4'
        ]);

        $innerContent = Html::tag('div', $content, [
            'class' => 'container mx-auto px-4'
        ]);

        return Html::tag('section', $innerContent, [
            'class' => 'heading-section py-8 ' . $this->section->getCssClassesString(),
            'style' => $this->section->getInlineStyles(),
        ]);
    }

    /**
     * Render paragraph section
     */
    protected function renderParagraph()
    {
        $content = Html::tag('div', $this->section->content, [
            'class' => 'prose prose-lg max-w-none'
        ]);

        $innerContent = Html::tag('div', $content, [
            'class' => 'container mx-auto px-4'
        ]);

        return Html::tag('section', $innerContent, [
            'class' => 'paragraph-section py-8 ' . $this->section->getCssClassesString(),
            'style' => $this->section->getInlineStyles(),
        ]);
    }

    /**
     * Render quote section
     */
    protected function renderQuote()
    {
        $content = Html::tag('blockquote', Html::encode($this->section->content), [
            'class' => 'text-2xl italic text-gray-700 mb-4'
        ]);

        if ($this->section->title) {
            $content .= Html::tag('cite', '— ' . Html::encode($this->section->title), [
                'class' => 'text-lg text-gray-600'
            ]);
        }

        $innerContent = Html::tag('div', $content, [
            'class' => 'container mx-auto px-4 text-center'
        ]);

        return Html::tag('section', $innerContent, [
            'class' => 'quote-section py-16 bg-gray-50 ' . $this->section->getCssClassesString(),
            'style' => $this->section->getInlineStyles(),
        ]);
    }

    /**
     * Render list section
     */
    protected function renderList()
    {
        $settings = $this->section->getSettingsArray();
        $listType = $settings['list_type'] ?? 'bullet';
        
        $items = explode("\n", $this->section->content);
        $listItems = '';
        
        foreach ($items as $item) {
            $item = trim($item);
            if (!empty($item)) {
                // Remove bullet points if they exist
                $item = preg_replace('/^[•\-\*]\s*/', '', $item);
                $listItems .= Html::tag('li', Html::encode($item), ['class' => 'mb-2']);
            }
        }

        $tag = $listType === 'numbered' ? 'ol' : 'ul';
        $listClass = $listType === 'numbered' ? 'list-decimal' : 'list-disc';
        
        $content = Html::tag($tag, $listItems, [
            'class' => $listClass . ' list-inside space-y-2'
        ]);

        $innerContent = Html::tag('div', $content, [
            'class' => 'container mx-auto px-4'
        ]);

        return Html::tag('section', $innerContent, [
            'class' => 'list-section py-8 ' . $this->section->getCssClassesString(),
            'style' => $this->section->getInlineStyles(),
        ]);
    }

    /**
     * Render button section
     */
    protected function renderButton()
    {
        $url = $this->section->title ?: '#'; // URL stored in title field
        $text = $this->section->content ?: 'Click me';

        $content = Html::a(Html::encode($text), $url, [
            'class' => 'btn bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-semibold transition-colors inline-block'
        ]);

        $innerContent = Html::tag('div', $content, [
            'class' => 'container mx-auto px-4 text-center'
        ]);

        return Html::tag('section', $innerContent, [
            'class' => 'button-section py-8 ' . $this->section->getCssClassesString(),
            'style' => $this->section->getInlineStyles(),
        ]);
    }

    /**
     * Render spacer section
     */
    protected function renderSpacer()
    {
        $height = (int)($this->section->content ?: 50);

        return Html::tag('div', '', [
            'class' => 'spacer-section ' . $this->section->getCssClassesString(),
            'style' => "height: {$height}px;" . $this->section->getInlineStyles(),
        ]);
    }

    /**
     * Render code section
     */
    protected function renderCode()
    {
        $settings = $this->section->getSettingsArray();
        $language = $settings['language'] ?? 'text';

        $content = Html::tag('pre', 
            Html::tag('code', Html::encode($this->section->content), [
                'class' => "language-{$language}"
            ]), 
            ['class' => 'bg-gray-900 text-green-400 p-4 rounded-lg overflow-x-auto']
        );

        $innerContent = Html::tag('div', $content, [
            'class' => 'container mx-auto px-4'
        ]);

        return Html::tag('section', $innerContent, [
            'class' => 'code-section py-8 ' . $this->section->getCssClassesString(),
            'style' => $this->section->getInlineStyles(),
        ]);
    }

    /**
     * Default renderer for unknown section types
     */
    protected function renderDefault()
    {
        $content = '';
        
        if ($this->section->title) {
            $content .= Html::tag('h3', Html::encode($this->section->title), [
                'class' => 'text-2xl font-bold mb-4'
            ]);
        }
        
        if ($this->section->subtitle) {
            $content .= Html::tag('p', Html::encode($this->section->subtitle), [
                'class' => 'text-lg text-gray-600 mb-4'
            ]);
        }
        
        if ($this->section->content) {
            $content .= Html::tag('div', $this->section->content, [
                'class' => 'content'
            ]);
        }

        if (empty($content)) {
            $content = Html::tag('p', 'Section content will appear here.', [
                'class' => 'text-gray-500 italic'
            ]);
        }

        $innerContent = Html::tag('div', $content, [
            'class' => 'container mx-auto px-4'
        ]);

        return Html::tag('section', $innerContent, [
            'class' => 'section section-' . Html::encode($this->section->type) . ' py-8 ' . $this->section->getCssClassesString(),
            'style' => $this->section->getInlineStyles(),
        ]);
    }
}
