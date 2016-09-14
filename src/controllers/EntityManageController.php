<?php

namespace DotPlant\EntityStructure\controllers;

use DevGroup\AdminUtils\controllers\BaseController;
use DotPlant\EntityStructure\actions\BaseEntityAutocompleteAction;
use DotPlant\EntityStructure\actions\BaseEntityDeleteAction;
use DotPlant\EntityStructure\actions\BaseEntityEditAction;
use DotPlant\EntityStructure\actions\BaseEntityListAction;
use DotPlant\EntityStructure\actions\BaseEntityRestoreAction;
use DotPlant\EntityStructure\actions\BaseEntityTreeAction;
use DotPlant\EntityStructure\actions\BaseEntityTreeMoveAction;
use DotPlant\EntityStructure\actions\BaseEntityTreeReorderAction;
use DotPlant\EntityStructure\models\BaseStructure;
use DotPlant\EntityStructure\StructureModule;
use yii\filters\VerbFilter;
use Yii;
use yii\helpers\ArrayHelper;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * Class EntityManageController
 *
 * @package DotPlant\EntityStructure\controllers
 */
class EntityManageController extends BaseController
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST'],
                ],
            ]
        ];
    }

    /**
     * @inheritdoc
     */
    public function actions()
    {
        $actions = [
            'index' => [
                'class' => BaseEntityListAction::class,
            ],
            'edit' => [
                'class' => BaseEntityEditAction::class,
            ],
            'autocomplete' => [
                'class' => BaseEntityAutocompleteAction::class,
            ],
            'delete' => [
                'class' => BaseEntityDeleteAction::class,
            ],
            'restore' => [
                'class' => BaseEntityRestoreAction::class,
            ],
            'get-tree' => [
                'class' => BaseEntityTreeAction::class,
                'showHiddenInTree' => StructureModule::module()->showHiddenInTree,
            ],
            'tree-reorder' => [
                'class' => BaseEntityTreeReorderAction::class,
            ],
            'tree-parent' => [
                'class' => BaseEntityTreeMoveAction::class,
                'saveAttributes' => ['parent_id', 'context_id']
            ],
        ];
        $injectionActions = BaseStructure::prepareActionsInjection();
        return ArrayHelper::merge($actions, $injectionActions);
    }

    /**
     * Returns context_id according to given Entity id
     *
     * @return false|null|string
     * @throws NotFoundHttpException
     */
    public function actionGetContextId()
    {
        if (false === Yii::$app->request->isAjax) {
            throw new NotFoundHttpException(
                Yii::t('dotplant.entity.structure', 'Page not found')
            );
        }
        Yii::$app->response->format = Response::FORMAT_JSON;
        $structureId = Yii::$app->request->post('structure_id');
        return BaseStructure::find()->select('context_id')->where(['id' => $structureId])->scalar();
    }
}