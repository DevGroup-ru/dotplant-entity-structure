<?php

namespace DotPlant\EntityStructure\actions;

use DevGroup\DataStructure\behaviors\HasProperties;
use DevGroup\DataStructure\traits\PropertiesTrait;
use DevGroup\Multilingual\behaviors\MultilingualActiveRecord;
use DevGroup\TagDependencyHelper\TagDependencyTrait;
use DotPlant\EntityStructure\models\BaseStructure;
use DotPlant\EntityStructure\StructureModule;
use yii\base\Action;
use Yii;
use yii\base\InvalidConfigException;
use yii\helpers\StringHelper;
use yii\web\NotFoundHttpException;

/**
 * Class BaseEntityEditAction
 * @package DotPlant\EntityStructure\actions
 */
class BaseEntityEditAction extends Action
{
    /** @var  BaseStructure | HasProperties | TagDependencyTrait */
    public $entityClass;

    /**
     * View file to render
     *
     * @var string
     */
    public $viewFile = '@DotPlant/EntityStructure/views/default/entity-edit';

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
    public function run($id = null, $parent_id = null)
    {
        $entityClass = $this->entityClass;
        $entityName = StringHelper::basename($entityClass);
        /**
         * @var BaseStructure | HasProperties | PropertiesTrait | TagDependencyTrait | MultilingualActiveRecord $structureModel
         */
        $structureModel = $entityClass::loadModel(
            $id,
            true,
            true,
            86400,
            new NotFoundHttpException(Yii::t(StructureModule::TRANSLATION_CATEGORY, '{model} not found!',
                ['model' => Yii::t(StructureModule::TRANSLATION_CATEGORY, $entityName)]
            ))
        );
        $refresh = !$structureModel->isNewRecord;
        if (false === $structureModel->isNewRecord) {
            $structureModel->translations;
        } else {
            $structureModel->loadDefaultValues();
            if (null !== $parent_id) {
                $structureModel->parent_id = $parent_id;
            }
        }
        $structureModel->autoSaveProperties = true;
        $post = Yii::$app->request->post();
        $structureModel->entity_id = $structureModel->getEntityId();
        if (false === empty($post)) {
            if (true === $structureModel->load($post)) {
                foreach (Yii::$app->request->post('StructureTranslation', []) as $language => $data) {
                    foreach ($data as $attribute => $translation) {
                        $structureModel->translate($language)->$attribute = $translation;
                    }
                }
                if (true === $structureModel->save()) {
                    Yii::$app->session->setFlash('success',
                        Yii::t(StructureModule::TRANSLATION_CATEGORY, '{model} successfully saved!',
                            ['model' => Yii::t(StructureModule::TRANSLATION_CATEGORY, $entityName)]
                        )
                    );
                    if (true === $refresh) {
                        return $this->controller->refresh();
                    } else {
                        return $this->controller->redirect(['pages-manage/edit', 'id' => $structureModel->id]);
                    }
                } else {
                    Yii::$app->session->setFlash('error',
                        Yii::t(StructureModule::TRANSLATION_CATEGORY, 'An error occurred while saving {model}!',
                            ['model' => Yii::t(StructureModule::TRANSLATION_CATEGORY, $entityName)]
                        )
                    );
                }
            }
        }
        return $this->controller->render(
            $this->viewFile,
            [
                'model' => $structureModel,
            ]
        );
    }
}