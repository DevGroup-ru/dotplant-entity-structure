<?php

use DotPlant\EntityStructure\models\BaseStructure;
use DotPlant\EntityStructure\models\Entity;
use yii\db\Migration;

class m160904_180717_add_route_attr extends Migration
{
    public function up()
    {
        $this->addColumn(
            Entity::tableName(),
            'route',
            $this->string()->notNull()->defaultValue('')
        );
        $this->dropColumn(
            BaseStructure::tableName(),
            'packed_json_content'
        );
        $this->dropColumn(
            BaseStructure::tableName(),
            'packed_json_providers'
        );
    }

    public function down()
    {
        $this->dropColumn(
            Entity::tableName(),
            'route'
        );
        $this->addColumn(
            BaseStructure::tableName(),
            'packed_json_content',
            $this->db->driverName === 'mysql' ? 'LONGTEXT' : $this->text()->notNull()
        );
        $this->addColumn(
            BaseStructure::tableName(),
            'packed_json_providers',
            $this->db->driverName === 'mysql' ? 'LONGTEXT' : $this->text()->notNull()
        );
    }

    /*
    // Use safeUp/safeDown to run migration code within a transaction
    public function safeUp()
    {
    }

    public function safeDown()
    {
    }
    */
}
