<?php

use DotPlant\EntityStructure\models\BaseStructure;
use yii\db\Migration;

class m161206_075107_nullable_context extends Migration
{
    public function up()
    {
        $this->alterColumn(
            BaseStructure::tableName(),
            'context_id',
            $this->integer()->defaultExpression('NULL')
        );
    }

    public function down()
    {
        $this->alterColumn(
            BaseStructure::tableName(),
            'context_id',
            $this->integer()->notNull()
        );
    }
}
