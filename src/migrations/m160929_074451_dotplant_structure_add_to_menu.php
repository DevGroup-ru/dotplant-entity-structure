<?php

use app\models\BackendMenu;
use yii\db\Migration;

class m160929_074451_dotplant_structure_add_to_menu extends Migration
{
    public function up()
    {
        $this->insert(
            BackendMenu::tableName(),
            [
                'name' => 'Structure',
                'icon' => 'fa fa-file-o',
                'sort_order' => 10,
                'added_by_ext' => 'structure',
                'rbac_check' => 'backend-view',
                'url' => '/structure/entity-manage/index',
                'translation_category' => 'dotplant.structure',
            ]
        );
    }

    public function down()
    {
        $this->delete(BackendMenu::tableName(), ['added_by_ext' => 'structure']);
    }
}
