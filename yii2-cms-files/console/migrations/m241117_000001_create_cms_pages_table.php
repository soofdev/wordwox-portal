<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%cms_pages}}`.
 */
class m241117_000001_create_cms_pages_table extends Migration
{
    /**
     * {@inheritdoc}
     */
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
        
        // Foreign keys (uncomment if these tables exist)
        // $this->addForeignKey('fk-cms_pages-org_id', '{{%cms_pages}}', 'org_id', '{{%org}}', 'id', 'CASCADE');
        // $this->addForeignKey('fk-cms_pages-orgPortal_id', '{{%cms_pages}}', 'orgPortal_id', '{{%orgPortal}}', 'id', 'CASCADE');
        // $this->addForeignKey('fk-cms_pages-created_by', '{{%cms_pages}}', 'created_by', '{{%user}}', 'id', 'SET NULL');
        // $this->addForeignKey('fk-cms_pages-updated_by', '{{%cms_pages}}', 'updated_by', '{{%user}}', 'id', 'SET NULL');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%cms_pages}}');
    }
}
