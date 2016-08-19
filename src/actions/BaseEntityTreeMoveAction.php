<?php

namespace DotPlant\EntityStructure\actions;

use DevGroup\AdminUtils\actions\BaseAdminAction;
use DevGroup\TagDependencyHelper\NamingHelper;
use DotPlant\EntityStructure\models\BaseStructure;
use DotPlant\EntityStructure\models\StructureTranslation;
use DotPlant\EntityStructure\StructureModule;
use Yii;
use yii\base\InvalidConfigException;
use yii\base\InvalidParamException;
use yii\caching\TagDependency;
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
    /** @var string | null  Entity class name */
    public $className = null;

    /** @var string  */
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
        if (true === empty($this->className)) {
            throw new InvalidConfigException(
                Yii::t('dotplant.entity.structure', "The 'className' param must be set!")
            );
        }
        $className = $this->className;
        if (false === is_subclass_of($className, BaseStructure::class)) {
            throw new InvalidConfigException(Yii::t(
                'dotplant.entity.structure',
                "The 'className' must extend 'DotPlant\\EntityStructure\\models\\BaseStructure'!"
            ));
        }
        if (!in_array($this->modelParentIdField, $this->saveAttributes)) {
            $this->saveAttributes[] = $this->modelParentIdField;
        }
    }

    /**
     * @inheritdoc
     */
    public function run($id = null)
    {
        $this->parentId = Yii::$app->request->get('parent_id');
        /** @var BaseStructure $class */
        $class = $this->className;
        if (null === $id
            || null === $this->parentId
            || (null === $model = $class::findOne($id))
            || (null === $parent = $class::findOne($this->parentId))
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
                    NamingHelper::getCommonTag($class)
                );
                return true;
            } else {
                throw new InvalidParamException($model->errors);
            }
        }
    }
}
