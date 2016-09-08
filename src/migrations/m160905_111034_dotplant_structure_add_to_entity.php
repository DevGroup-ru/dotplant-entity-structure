<?php

use yii\db\Migration;
use DotPlant\EntityStructure\models\Entity;
use DotPlant\EntityStructure\models\StructureTranslation;

class m160905_111034_dotplant_structure_add_to_entity extends Migration
{
    public function up()
    {
        $this->addColumn(
            Entity::tableName(),
            'tree_icon',
            $this->string(255)->notNull()->defaultValue('')
        );
        $this->addColumn(
            StructureTranslation::tableName(),
            'can_stop_url_parsing',
            $this->boolean()->notNull()->defaultValue(false)
        );
        $this->addColumn(
            StructureTranslation::tableName(),
            'url_processing_params',
            $this->text()
        );
    }

    public function down()
    {
        $schema = $this->db->getTableSchema(Entity::tableName());
        if (true === in_array('edit_route', $schema->columnNames)) {
            $this->dropColumn(Entity::tableName(), 'edit_route');
        }
        $this->dropColumn(Entity::tableName(), 'tree_icon');
        $this->dropColumn(StructureTranslation::tableName(), 'can_stop_url_parsing');
        $this->dropColumn(StructureTranslation::tableName(), 'url_processing_params');
    }
}
