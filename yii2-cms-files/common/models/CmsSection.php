<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\helpers\Json;

/**
 * This is the model class for table "cms_sections".
 *
 * @property int $id
 * @property string $uuid
 * @property int $cms_page_id
 * @property string $name
 * @property string $type
 * @property string|null $title
 * @property string|null $subtitle
 * @property string|null $content
 * @property array|null $settings
 * @property array|null $data
 * @property string $template
 * @property string|null $css_classes
 * @property array|null $styles
 * @property int $sort_order
 * @property bool $is_active
 * @property bool $is_visible
 * @property array|null $responsive_settings
 * @property int $created_at
 * @property int $updated_at
 * @property int|null $deleted_at
 *
 * @property CmsPage $page
 */
class CmsSection extends ActiveRecord
{
    // Common section types
    const TYPE_HERO = 'hero';
    const TYPE_CONTENT = 'content';
    const TYPE_CTA = 'cta';
    const TYPE_GALLERY = 'gallery';
    const TYPE_TESTIMONIALS = 'testimonials';
    const TYPE_CONTACT_FORM = 'contact_form';
    const TYPE_HEADING = 'heading';
    const TYPE_PARAGRAPH = 'paragraph';
    const TYPE_IMAGE = 'image';
    const TYPE_QUOTE = 'quote';
    const TYPE_LIST = 'list';
    const TYPE_BUTTON = 'button';
    const TYPE_SPACER = 'spacer';
    const TYPE_CODE = 'code';
    const TYPE_VIDEO = 'video';
    const TYPE_COLUMNS = 'columns';
    const TYPE_HTML = 'html';

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
            [['uuid'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'uuid' => 'UUID',
            'cms_page_id' => 'Page ID',
            'name' => 'Name',
            'type' => 'Type',
            'title' => 'Title',
            'subtitle' => 'Subtitle',
            'content' => 'Content',
            'settings' => 'Settings',
            'data' => 'Data',
            'template' => 'Template',
            'css_classes' => 'CSS Classes',
            'styles' => 'Styles',
            'sort_order' => 'Sort Order',
            'is_active' => 'Is Active',
            'is_visible' => 'Is Visible',
            'responsive_settings' => 'Responsive Settings',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
            'deleted_at' => 'Deleted At',
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
     * Get active sections query
     *
     * @return \yii\db\ActiveQuery
     */
    public static function getActive()
    {
        return static::find()
            ->where(['is_active' => true, 'is_visible' => true])
            ->andWhere(['is null', 'deleted_at'])
            ->orderBy(['sort_order' => SORT_ASC]);
    }

    /**
     * Get settings as array
     *
     * @return array
     */
    public function getSettingsArray()
    {
        if (is_string($this->settings)) {
            return Json::decode($this->settings);
        }
        
        return is_array($this->settings) ? $this->settings : [];
    }

    /**
     * Get data as array
     *
     * @return array
     */
    public function getDataArray()
    {
        if (is_string($this->data)) {
            return Json::decode($this->data);
        }
        
        return is_array($this->data) ? $this->data : [];
    }

    /**
     * Get styles as array
     *
     * @return array
     */
    public function getStylesArray()
    {
        if (is_string($this->styles)) {
            return Json::decode($this->styles);
        }
        
        return is_array($this->styles) ? $this->styles : [];
    }

    /**
     * Get responsive settings as array
     *
     * @return array
     */
    public function getResponsiveSettingsArray()
    {
        if (is_string($this->responsive_settings)) {
            return Json::decode($this->responsive_settings);
        }
        
        return is_array($this->responsive_settings) ? $this->responsive_settings : [];
    }

    /**
     * Get CSS classes string
     *
     * @return string
     */
    public function getCssClassesString()
    {
        $classes = ['section', 'section-' . $this->type];
        
        if ($this->css_classes) {
            $classes[] = $this->css_classes;
        }
        
        return implode(' ', $classes);
    }

    /**
     * Get inline styles string
     *
     * @return string
     */
    public function getInlineStyles()
    {
        $styles = $this->getStylesArray();
        $styleString = '';
        
        foreach ($styles as $property => $value) {
            $styleString .= $property . ': ' . $value . '; ';
        }
        
        return trim($styleString);
    }

    /**
     * Get type label
     *
     * @return string
     */
    public function getTypeLabel()
    {
        $labels = [
            self::TYPE_HERO => 'Hero Section',
            self::TYPE_CONTENT => 'Content',
            self::TYPE_CTA => 'Call to Action',
            self::TYPE_GALLERY => 'Gallery',
            self::TYPE_TESTIMONIALS => 'Testimonials',
            self::TYPE_CONTACT_FORM => 'Contact Form',
            self::TYPE_HEADING => 'Heading',
            self::TYPE_PARAGRAPH => 'Paragraph',
            self::TYPE_IMAGE => 'Image',
            self::TYPE_QUOTE => 'Quote',
            self::TYPE_LIST => 'List',
            self::TYPE_BUTTON => 'Button',
            self::TYPE_SPACER => 'Spacer',
            self::TYPE_CODE => 'Code',
            self::TYPE_VIDEO => 'Video',
            self::TYPE_COLUMNS => 'Columns',
            self::TYPE_HTML => 'Custom HTML',
        ];
        
        return $labels[$this->type] ?? ucfirst($this->type);
    }
}
