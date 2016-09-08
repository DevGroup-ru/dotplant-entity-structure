<?php

namespace DotPlant\EntityStructure\actions;

use DevGroup\AdminUtils\actions\BaseAdminAction;
use DevGroup\TagDependencyHelper\NamingHelper;
use DotPlant\EntityStructure\models\BaseStructure;
use DotPlant\EntityStructure\models\Entity;
use Yii;
use yii\caching\TagDependency;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

/**
 * Class BaseEntityDeleteAction
 *
 * @package DotPlant\EntityStructure\actions
 */
class BaseEntityDeleteAction extends BaseAdminAction
{
    /**
     * @inheritdoc
     */
    public function run($id, $returnUrl = '', $hard = null, $entity_id)
    {
        $entityClass = Entity::getEntityClassForId($entity_id);
        $permissions = $entityClass::getAccessRules();
        if (true === isset($permissions['delete'])) {
            if (false === Yii::$app->user->can($permissions['delete'])) {
                throw new ForbiddenHttpException(Yii::t('yii', 'You are not allowed to perform this action.'));
            }
        }
        /** @var BaseStructure $model */
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
        if (method_exists($model, 'hardDelete')) {
            if ((int)$hard === 1) {
                if (false !== $model->hardDelete()) {
                    Yii::$app->session->setFlash(
                        'info',
                        Yii::t('dotplant.entity.structure', 'Record has been successfully deleted.')
                    );
                } else {
                    Yii::$app->session->setFlash(
                        'warning',
                        Yii::t('dotplant.entity.structure', 'An error occurred while deleting record.')
                    );
                }
            } else {
                if (false === $model->delete() && true === $model->isDeleted()) {
                    Yii::$app->session->setFlash(
                        'info',
                        Yii::t('dotplant.entity.structure', 'Record has been successfully hidden.')
                    );
                } else {
                    Yii::$app->session->setFlash('warning', Yii::t(
                        'dotplant.entity.structure',
                        'An error occurred while attempting to hide record.'
                    ));
                }
            }
        } else {
            if (false !== $model->delete()) {
                Yii::$app->session->setFlash(
                    'info',
                    Yii::t('dotplant.entity.structure', 'Record has been successfully deleted.')
                );
            } else {
                Yii::$app->session->setFlash(
                    'warning',
                    Yii::t('dotplant.entity.structure', 'An error occurred while deleting record.')
                );
            }
        }
        TagDependency::invalidate(
            Yii::$app->cache,
            NamingHelper::getCommonTag(BaseStructure::class)
        );
        $returnUrl = empty($returnUrl) ? 'index' : $returnUrl;
        return $this->controller->redirect($returnUrl);
    }
}