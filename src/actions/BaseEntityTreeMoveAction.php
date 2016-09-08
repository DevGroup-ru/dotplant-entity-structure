<?php

namespace DotPlant\EntityStructure\actions;

use DevGroup\AdminUtils\actions\BaseAdminAction;
use DevGroup\TagDependencyHelper\NamingHelper;
use DotPlant\EntityStructure\models\BaseStructure;
use DotPlant\EntityStructure\models\Entity;
use DotPlant\EntityStructure\models\StructureTranslation;
use Yii;
use yii\base\InvalidConfigException;
use yii\base\InvalidParamException;
use yii\caching\TagDependency;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

/**
 * Class BaseEntityTreeMoveAction Enhancement for default
 * `devgroup\JsTreeWidget\actions\AdjacencyList\TreeNodeMoveAction` for having ability to work with Multilingual and
 * BaseStructure models
 *
 * @package DotPlant\EntityStructure\actions
 */
class BaseEntityTreeMoveAction extends BaseAdminAction
{
    /** @var string */
    public $modelParentIdField = 'parent_id';

    /** @var null | int */
    public $parentId = null;

    /** @var array attributes to validate and save */
    public $saveAttributes = [];

    /**
     * @inheritdoc
     */
    public function init()
    {
        if (!in_array($this->modelParentIdField, $this->saveAttributes)) {
            $this->saveAttributes[] = $this->modelParentIdField;
        }
    }

    /**
     * @inheritdoc
     */
    public function run($id = null)
    {
        $entityId = BaseStructure::getEntityIdForModelId($id);
        /** @var BaseStructure $entityClass */
        $entityClass = Entity::getEntityClassForId($entityId);
        $permissions = $entityClass::getAccessRules();
        if (true === isset($permissions['edit'])) {
            if (false === Yii::$app->user->can($permissions['edit'])) {
                throw new ForbiddenHttpException(Yii::t('yii', 'You are not allowed to perform this action.'));
            }
        }
        $this->parentId = Yii::$app->request->get('parent_id');
        if (null === $id
            || null === $this->parentId
            || (null === $model = $entityClass::findOne($id))
            || (null === $parent = $entityClass::findOne($this->parentId))
        ) {
            throw new NotFoundHttpException;
        }
        /** @var BaseStructure $model */
        if ($model->{$this->modelParentIdField} == $parent->id) {
            return true;
        }
        $model->{$this->modelParentIdField} = $parent->id;
        if (true === $model->validate($this->saveAttributes)) {
            $translations = $model->translations;
            /** @var StructureTranslation $record */
            foreach ($translations as $record) {
                $record->parentContextId = (int)$model->context_id;
                $record->parentParentId = (int)$model->parent_id;
                $record->oldSlug = $record->slug;
            }
            if (true === $model->save()) {
                TagDependency::invalidate(
                    Yii::$app->cache,
                    NamingHelper::getCommonTag(BaseStructure::class)
                );
                return true;
            } else {
                throw new InvalidParamException(implode(',', $model->errors));
            }
        }
    }
}
