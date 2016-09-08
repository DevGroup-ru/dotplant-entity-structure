<?php

namespace DotPlant\EntityStructure\actions;

use devgroup\JsTreeWidget\actions\AdjacencyList\TreeNodesReorderAction;
use DevGroup\TagDependencyHelper\NamingHelper;
use DotPlant\EntityStructure\models\BaseStructure;
use DotPlant\EntityStructure\models\Entity;
use Yii;
use yii\caching\TagDependency;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;

/**
 * Class BaseEntityTreeReorderAction
 *
 * @package DotPlant\EntityStructure\actions
 */
class BaseEntityTreeReorderAction extends TreeNodesReorderAction
{
    /**
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     */
    public function init()
    {
        $id = Yii::$app->request->post('id');
        $entityId = BaseStructure::getEntityIdForModelId($id);
        /** @var BaseStructure $entityClass */
        $entityClass = Entity::getEntityClassForId($entityId);
        $permissions = $entityClass::getAccessRules();
        if (true === isset($permissions['edit']) && false === Yii::$app->user->can($permissions['edit'])) {
            throw new ForbiddenHttpException(Yii::t(
                'yii', 'You are not allowed to perform this action.'
            ));
        }
        $this->className = $entityClass;
        $this->sortOrder = Yii::$app->request->post('order');
        if (empty($this->sortOrder)) {
            throw new BadRequestHttpException;
        }
    }

    /**
     * @inheritdoc
     */
    protected function afterRun()
    {
        TagDependency::invalidate(
            Yii::$app->cache,
            NamingHelper::getCommonTag(BaseStructure::class)
        );
    }
}
