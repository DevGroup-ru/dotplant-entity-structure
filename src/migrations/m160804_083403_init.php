<?php

use yii\db\Migration;
use DotPlant\EntityStructure\models\Entity;
use DotPlant\EntityStructure\models\BaseStructure;
use DotPlant\EntityStructure\models\StructureTranslation;
use \yii\helpers\Console;

class m160804_083403_init extends Migration
{
    public function up()
    {
        mb_internal_encoding("UTF-8");
        $tableOptions = $this->db->driverName === 'mysql'
            ? 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB'
            : null;
        $this->createTable(
            Entity::tableName(),
            [
                'id' => $this->primaryKey(),
                'name' => $this->string(100)->notNull(),
                'class_name' => $this->string(255)->notNull()
            ],
            $tableOptions
        );
        $this->createTable(
            BaseStructure::tableName(),
            [
                'id' => $this->primaryKey(),
                'parent_id' => $this->integer()->notNull()->defaultValue(0),
                'context_id' => $this->integer()->notNull(),
                'entity_id' => $this->integer()->notNull(),
                'expand_in_tree' => $this->boolean()->notNull()->defaultValue(true),
                'sort_order' => $this->integer()->notNull()->defaultValue(0),
                'is_deleted' => $this->boolean()->notNull()->defaultValue(false),
                'created_at' => $this->integer(),
                'created_by' => $this->integer(),
                'updated_at' => $this->integer(),
                'updated_by' => $this->integer(),
            ],
            $tableOptions
        );
        $this->createIndex('st_entity_index', BaseStructure::tableName(), 'entity_id');
        $this->addForeignKey(
            'fkEtToSt',
            BaseStructure::tableName(),
            ['entity_id'],
            Entity::tableName(),
            ['id'],
            'CASCADE'
        );
        $this->createTable(
            StructureTranslation::tableName(),
            [
                'model_id' => $this->integer()->notNull(),
                'language_id' => $this->integer()->notNull(),
                'name' => $this->string(255)->notNull(),
                'title' => $this->string(255),
                'h1' => $this->string(255),
                'breadcrumbs_label' => $this->string(255),
                'meta_description' => $this->string(400),
                'slug' => $this->string(80)->notNull(),
                'url' => $this->string(800),
                'is_active' => $this->boolean()->notNull()->defaultValue(true),
                'packed_json_content' => 'LONGTEXT NOT NULL',
                'packed_json_providers' => 'LONGTEXT NOT NULL',
            ],
            $tableOptions
        );
        $this->addPrimaryKey('sttr_pk', StructureTranslation::tableName(), ['model_id', 'language_id']);
        $this->createIndex('sttr_index', StructureTranslation::tableName(), ['model_id', 'language_id'], true);
        $this->addForeignKey(
            'fkStRtToSt',
            StructureTranslation::tableName(),
            ['model_id'],
            BaseStructure::tableName(),
            ['id'],
            'CASCADE'
        );

    }

    public function down()
    {
        $c = Entity::find()->count();
        if ($c > 0) {
            Console::stderr(
                "Please, first deactivate all extensions that uses 'DotPlant Entity Structure' extension!" . PHP_EOL
            );
            return false;
        }
        $this->dropForeignKey('fkEtToSt', BaseStructure::tableName());
        $this->dropForeignKey('fkStRtToSt', StructureTranslation::tableName());
        $this->dropIndex('sttr_index', StructureTranslation::tableName());
        $this->dropIndex('st_entity_index', BaseStructure::tableName());
        $this->dropPrimaryKey('sttr_pk', StructureTranslation::tableName());
        $this->dropTable(Entity::tableName());
        $this->dropTable(BaseStructure::tableName());
        $this->dropTable(StructureTranslation::tableName());
    }
}
