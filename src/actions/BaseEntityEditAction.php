<?php

namespace DotPlant\EntityStructure\actions;

use DevGroup\AdminUtils\actions\BaseAdminAction;
use DevGroup\DataStructure\behaviors\HasProperties;
use DevGroup\DataStructure\traits\PropertiesTrait;
use DevGroup\Multilingual\behaviors\MultilingualActiveRecord;
use DevGroup\TagDependencyHelper\TagDependencyTrait;
use DotPlant\EntityStructure\models\BaseStructure;
use DotPlant\EntityStructure\models\Entity;
use Yii;
use yii\helpers\StringHelper;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

/**
 * Class BaseEntityEditAction
 *
 * @package DotPlant\EntityStructure\actions
 */
class BaseEntityEditAction extends BaseAdminAction
{
    /**
     * @inheritdoc
     */
    public function run($id = null, $parent_id = null, $entity_id)
    {
        /** @var BaseStructure $entityClass */
        $entityClass = Entity::getEntityClassForId($entity_id);
        $permissions = $entityClass::getAccessRules();
        $entityName = StringHelper::basename($entityClass);
        $entitySelectorPrefix = strtolower($entityName);
        $user = Yii::$app->user;
        if (true === isset($permissions['view'])) {
            if (false === $user->can($permissions['view'])) {
                throw new ForbiddenHttpException(Yii::t('yii', 'You are not allowed to perform this action.'));
            }
        }
        /**
         * @var BaseStructure|HasProperties|PropertiesTrait|TagDependencyTrait|MultilingualActiveRecord $structureModel
         */
        $structureModel = $entityClass::loadModel(
            $id,
            true,
            true,
            86400,
            new NotFoundHttpException(Yii::t('dotplant.entity.structure', '{model} not found!',
                ['model' => Yii::t($entityClass::TRANSLATION_CATEGORY, $entityName)]
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
        $canSave = true;
        if (true === isset($permissions['edit']) && false === $user->can($permissions['edit'])) {
            $canSave = false;
        }
        if (false === empty($post)) {
            if (false === $canSave) {
                throw new ForbiddenHttpException(Yii::t('yii', 'You are not allowed to perform this action.'));
            }
            if (true === $structureModel->load($post)) {
                $translationClass = Yii::createObject($structureModel->getTranslationModelClassName());
                $translations = Yii::$app->request->post($translationClass->formName(), []);
                $translationClass = null;
                unset($translationClass);

                foreach ($translations as $language => $data) {
                    $data['parentContextId'] = (int)$structureModel->context_id;
                    $data['parentParentId'] = (int)$structureModel->parent_id;
                    $structureModel->translate($language)->oldSlug = $structureModel->translate($language)->slug;
                    foreach ($data as $attribute => $translation) {
                        $structureModel->translate($language)->$attribute = $translation;
                    }
                }
                if (true === $structureModel->validate()) {
                    if (true === $structureModel->save(false)) {
                        Yii::$app->session->setFlash('success',
                            Yii::t('dotplant.entity.structure', '{model} successfully saved!',
                                ['model' => Yii::t($entityClass::TRANSLATION_CATEGORY, $entityName)]
                            )
                        );
                        if (true === $refresh) {
                            return $this->controller->refresh();
                        } else {
                            return $this->controller->redirect([
                                'edit',
                                'id' => $structureModel->id,
                                'entity_id' => $structureModel->entity_id
                            ]);
                        }
                    } else {
                        Yii::$app->session->setFlash('error',
                            Yii::t('dotplant.entity.structure', 'An error occurred while saving {model}!',
                                ['model' => Yii::t($entityClass::TRANSLATION_CATEGORY, $entityName)]
                            )
                        );
                    }
                } else {
                    Yii::$app->session->setFlash('warning', Yii::t(
                        'dotplant.entity.structure',
                        'Please verify that all fields are filled correctly!'
                    ));
                }
            }
        }
        return $this->controller->render(
            $entityClass::getEditViewFile(),
            [
                'model' => $structureModel,
                'canSave' => $canSave,
                'entitySelectorPrefix' => $entitySelectorPrefix,
            ]
        );
    }
}
