<?php

use app\helpers\PermissionsHelper;
use yii\db\Migration;

class m160804_141118_dotplant_structure_permissions extends Migration
{
    private $_permissions = [
        'DotplantStructureManager' => [
            'descr' => 'DotPlant structure manager',
            'permits' => [
                'dotplant-structure-view' => 'You can view structure',
            ],
        ]
    ];

    public function up()
    {
        PermissionsHelper::createPermissions($this->_permissions);
    }

    public function down()
    {
        PermissionsHelper::removePermissions($this->_permissions);
    }
}
