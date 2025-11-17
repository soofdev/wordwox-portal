<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%cms_sections}}`.
 */
class m241117_000002_create_cms_sections_table extends Migration
{
    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%cms_sections}}');
    }
}
