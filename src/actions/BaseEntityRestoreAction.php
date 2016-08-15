<?php

namespace DotPlant\EntityStructure\actions;

use DevGroup\AdminUtils\actions\BaseAdminAction;
use DotPlant\EntityStructure\models\BaseStructure;
use DotPlant\EntityStructure\StructureModule;
use yii\base\InvalidConfigException;
use Yii;
use yii\web\NotFoundHttpException;

/**
 * Class BaseEntityRestoreAction
 *
 * @package DotPlant\EntityStructure\actions
 */
class BaseEntityRestoreAction extends BaseAdminAction
{
    /** @var  BaseStructure */
    public $entityClass;

    /** @var  array custom route to redirect to */
    public $redirectUrl;

    /**
     * @inheritdoc
     */
    public function init()
    {
        if (true === empty($this->entityClass)) {
            throw new InvalidConfigException(
                Yii::t(StructureModule::TRANSLATION_CATEGORY, "The 'entityClass' param must be set!")
            );
        }
        $entityClass = $this->entityClass;
        if (false === is_subclass_of($entityClass, BaseStructure::class)) {
            throw new InvalidConfigException(Yii::t(
                StructureModule::TRANSLATION_CATEGORY,
                "The 'entityClass' must extend 'DotPlant\\EntityStructure\\models\\BaseStructure'!"
            ));
        }
        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function run($id = null, $returnUrl = '')
    {
        $entityClass = $this->entityClass;
        /** @var BaseStructure $model */
        $model = $entityClass::loadModel(
            $id,
            true,
            true,
            86400,
            new NotFoundHttpException(Yii::t(
                StructureModule::TRANSLATION_CATEGORY,
                'Record with id {id} not found!',
                ['id' => $id]
            ))
        );
        if (true === $model->restore()) {
            Yii::$app->session->setFlash('info', Yii::t(
                StructureModule::TRANSLATION_CATEGORY,
                'Record has been successfully restored.'
            ));
        } else {
            Yii::$app->session->setFlash('warning', Yii::t(
                StructureModule::TRANSLATION_CATEGORY,
                'An error occurred Item has not been restored.'
            ));
        }
        $returnUrl = empty($this->redirectUrl)
            ? (empty($returnUrl) ? 'index' : $returnUrl)
            : $this->redirectUrl;
        return $this->controller->redirect($returnUrl);
    }
}