<?php

use DevGroup\EventsSystem\models\EventGroup;
use DotPlant\EntityStructure\StructureModule;
use yii\db\Migration;

class m160927_072500_dotplant_structure_add_event_group extends Migration
{
    public function up()
    {
        $eventGroup = new EventGroup(
            [
                'name' => 'Entity Structure',
                'owner_class_name' => StructureModule::class
            ]
        );
        $eventGroup->save();
    }

    public function down()
    {
        EventGroup::deleteAll(['owner_class_name' => StructureModule::class]);
    }
}
