<?php

namespace DotPlant\EntityStructure\actions;

use DevGroup\AdminUtils\actions\BaseAdminAction;
use DotPlant\EntityStructure\models\BaseStructureSearch;
use Yii;
use yii\web\ForbiddenHttpException;

/**
 * Class BaseEntityListAction
 *
 * @package DotPlant\EntityStructure\actions
 */
class BaseEntityListAction extends BaseAdminAction
{
    /** @var string View file to render */
    public $viewFile = '@DotPlant/EntityStructure/views/default/entity-list';

    /**
     * @inheritdoc
     */
    public function run($id = null, $context_id = null)
    {
        if (false === Yii::$app->user->can('backend-view')) {
            throw new ForbiddenHttpException(Yii::t(
                'yii', 'You are not allowed to perform this action.'
            ));
        }
        /** @var BaseStructureSearch $searchModel */
        $searchModel = new BaseStructureSearch(['is_active' => '']);
        if (null !== $id) {
            $searchModel->parent_id = (int)$id;
        }
        if (null !== $context_id) {
            $searchModel->context_id = (int)$context_id;
        }
        $params = Yii::$app->request->get();
        $dataProvider = $searchModel->search($params);
        return $this->controller->render(
            $this->viewFile,
            [
                'dataProvider' => $dataProvider,
                'searchModel' => $searchModel,
                'parentId' => $id,
            ]
        );
    }
}
