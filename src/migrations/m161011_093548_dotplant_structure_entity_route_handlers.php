<?php

use DotPlant\EntityStructure\models\Entity;
use yii\db\Migration;

class m161011_093548_dotplant_structure_entity_route_handlers extends Migration
{
    public function up()
    {
        $this->addColumn(
            Entity::tableName(),
            'packed_json_route_handlers',
            $this->text()
        );
        $this->update(Entity::tableName(), ['packed_json_route_handlers' => '[]']);
    }

    public function down()
    {
        $this->dropColumn(Entity::tableName(), 'packed_json_route_handlers');
    }
}
