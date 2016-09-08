<?php

namespace DotPlant\EntityStructure\actions;

use DevGroup\AdminUtils\actions\BaseAdminAction;
use DevGroup\Entity\traits\SoftDeleteTrait;
use DevGroup\TagDependencyHelper\NamingHelper;
use DotPlant\EntityStructure\models\BaseStructure;
use DotPlant\EntityStructure\models\Entity;
use yii\base\InvalidCallException;
use yii\base\InvalidConfigException;
use Yii;
use yii\caching\TagDependency;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

/**
 * Class BaseEntityRestoreAction
 *
 * @package DotPlant\EntityStructure\actions
 */
class BaseEntityRestoreAction extends BaseAdminAction
{
    /**
     * @inheritdoc
     */
    public function run($id = null, $returnUrl = '', $entity_id)
    {
        $entityClass = Entity::getEntityClassForId($entity_id);
        $permissions = $entityClass::getAccessRules();
        if (true === isset($permissions['delete'])) {
            if (false === Yii::$app->user->can($permissions['delete'])) {
                throw new ForbiddenHttpException(Yii::t('yii', 'You are not allowed to perform this action.'));
            }
        }
        if (false === method_exists($entityClass, 'restore')) {
            throw new InvalidCallException(Yii::t(
                'dotplant.entity.structure',
                "The 'entityClass' must use 'DevGroup\\Entity\\traits\\SoftDeleteTrait'!"
            ));
        }
        /** @var BaseStructure | SoftDeleteTrait $model */
        $model = $entityClass::loadModel(
            $id,
            true,
            true,
            86400,
            new NotFoundHttpException(Yii::t(
                'dotplant.entity.structure',
                'Record with id {id} not found!',
                ['id' => $id]
            ))
        );
        if (true === $model->restore()) {
            Yii::$app->session->setFlash('info', Yii::t(
                'dotplant.entity.structure',
                'Record has been successfully restored.'
            ));
        } else {
            Yii::$app->session->setFlash('warning', Yii::t(
                'dotplant.entity.structure',
                'An error occurred Item has not been restored.'
            ));
        }
        TagDependency::invalidate(
            Yii::$app->cache,
            NamingHelper::getCommonTag(BaseStructure::class)
        );
        $returnUrl = empty($this->redirectUrl)
            ? (empty($returnUrl) ? 'index' : $returnUrl)
            : $this->redirectUrl;
        return $this->controller->redirect($returnUrl);
    }
}
