<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "cms_pages".
 *
 * @property int $id
 * @property string $uuid
 * @property int $org_id
 * @property int $orgPortal_id
 * @property string $title
 * @property string $slug
 * @property string|null $description
 * @property string|null $content
 * @property array|null $meta_data
 * @property string $status
 * @property string $type
 * @property bool $is_homepage
 * @property bool $show_in_navigation
 * @property int $sort_order
 * @property string|null $seo_title
 * @property string|null $seo_description
 * @property string|null $seo_keywords
 * @property string $template
 * @property string $layout
 * @property int|null $published_at
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property int $created_at
 * @property int $updated_at
 * @property int|null $deleted_at
 *
 * @property CmsSection[] $sections
 */
class CmsPage extends ActiveRecord
{
    const STATUS_DRAFT = 'draft';
    const STATUS_PUBLISHED = 'published';
    const STATUS_ARCHIVED = 'archived';

    const TYPE_PAGE = 'page';
    const TYPE_POST = 'post';
    const TYPE_HOME = 'home';
    const TYPE_ABOUT = 'about';
    const TYPE_CONTACT = 'contact';
    const TYPE_CUSTOM = 'custom';

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
            [['status'], 'in', 'range' => [self::STATUS_DRAFT, self::STATUS_PUBLISHED, self::STATUS_ARCHIVED]],
            [['type'], 'in', 'range' => [self::TYPE_PAGE, self::TYPE_POST, self::TYPE_HOME, self::TYPE_ABOUT, self::TYPE_CONTACT, self::TYPE_CUSTOM]],
            [['uuid', 'title', 'slug', 'seo_title', 'template', 'layout'], 'string', 'max' => 255],
            [['slug'], 'unique'],
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
            'org_id' => 'Organization ID',
            'orgPortal_id' => 'Portal ID',
            'title' => 'Title',
            'slug' => 'Slug',
            'description' => 'Description',
            'content' => 'Content',
            'meta_data' => 'Meta Data',
            'status' => 'Status',
            'type' => 'Type',
            'is_homepage' => 'Is Homepage',
            'show_in_navigation' => 'Show In Navigation',
            'sort_order' => 'Sort Order',
            'seo_title' => 'SEO Title',
            'seo_description' => 'SEO Description',
            'seo_keywords' => 'SEO Keywords',
            'template' => 'Template',
            'layout' => 'Layout',
            'published_at' => 'Published At',
            'created_by' => 'Created By',
            'updated_by' => 'Updated By',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
            'deleted_at' => 'Deleted At',
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
                    ->where(['is_active' => true, 'is_visible' => true])
                    ->orderBy(['sort_order' => SORT_ASC]);
    }

    /**
     * Gets query for [[AllSections]] (including inactive).
     *
     * @return \yii\db\ActiveQuery
     */
    public function getAllSections()
    {
        return $this->hasMany(CmsSection::class, ['cms_page_id' => 'id'])
                    ->orderBy(['sort_order' => SORT_ASC]);
    }

    /**
     * Get published pages query
     *
     * @return \yii\db\ActiveQuery
     */
    public static function getPublished()
    {
        return static::find()
            ->where(['status' => self::STATUS_PUBLISHED])
            ->andWhere(['is null', 'deleted_at'])
            ->andWhere(['or', 
                ['published_at' => null], 
                ['<=', 'published_at', time()]
            ]);
    }

    /**
     * Get navigation pages query
     *
     * @return \yii\db\ActiveQuery
     */
    public static function getNavigation()
    {
        return static::getPublished()
            ->andWhere(['show_in_navigation' => true])
            ->orderBy(['sort_order' => SORT_ASC]);
    }

    /**
     * Find page by slug
     *
     * @param string $slug
     * @return static|null
     */
    public static function findBySlug($slug)
    {
        return static::getPublished()
            ->andWhere(['slug' => $slug])
            ->one();
    }

    /**
     * Get homepage
     *
     * @return static|null
     */
    public static function getHomepage()
    {
        return static::getPublished()
            ->andWhere(['is_homepage' => true])
            ->one();
    }

    /**
     * Get page URL
     *
     * @return string
     */
    public function getUrl()
    {
        if ($this->is_homepage) {
            return Yii::$app->homeUrl;
        }
        
        return Yii::$app->urlManager->createUrl(['page/view', 'slug' => $this->slug]);
    }

    /**
     * Get formatted published date
     *
     * @return string|null
     */
    public function getPublishedDate()
    {
        return $this->published_at ? date('Y-m-d H:i:s', $this->published_at) : null;
    }

    /**
     * Check if page is published
     *
     * @return bool
     */
    public function isPublished()
    {
        return $this->status === self::STATUS_PUBLISHED && 
               ($this->published_at === null || $this->published_at <= time());
    }

    /**
     * Get status label
     *
     * @return string
     */
    public function getStatusLabel()
    {
        $labels = [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_PUBLISHED => 'Published',
            self::STATUS_ARCHIVED => 'Archived',
        ];
        
        return $labels[$this->status] ?? $this->status;
    }

    /**
     * Get type label
     *
     * @return string
     */
    public function getTypeLabel()
    {
        $labels = [
            self::TYPE_PAGE => 'Page',
            self::TYPE_POST => 'Post',
            self::TYPE_HOME => 'Homepage',
            self::TYPE_ABOUT => 'About',
            self::TYPE_CONTACT => 'Contact',
            self::TYPE_CUSTOM => 'Custom',
        ];
        
        return $labels[$this->type] ?? $this->type;
    }
}
