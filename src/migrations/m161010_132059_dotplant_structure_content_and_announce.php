<?php

use DotPlant\EntityStructure\models\StructureTranslation;
use yii\db\Migration;

class m161010_132059_dotplant_structure_content_and_announce extends Migration
{
    public function up()
    {
        $this->addColumn(
            StructureTranslation::tableName(),
            'announce',
            $this->text()
        );

        $this->addColumn(
            StructureTranslation::tableName(),
            'description',
            $this->text()
        );
    }

    public function down()
    {

        $this->dropColumn(
            StructureTranslation::tableName(),
            'announce'
        );

        $this->dropColumn(
            StructureTranslation::tableName(),
            'description'
        );
    }

}
