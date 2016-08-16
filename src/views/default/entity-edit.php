<?php

/**
 * @var $this \yii\web\View
 * @var \DotPlant\EntityStructure\models\BaseStructure $model
 */
use DotPlant\EntityStructure\StructureModule;
use DevGroup\Multilingual\models\Context;
use dmstr\widgets\Alert;
use yii\helpers\ArrayHelper;

$this->title = empty($model->id)
    ? Yii::t(StructureModule::TRANSLATION_CATEGORY, 'New entity')
    : Yii::t(StructureModule::TRANSLATION_CATEGORY, 'Edit entity #{id}', ['id' => $model->id]);

$this->params['breadcrumbs'][] = [
    'url' => ['index'],
    'label' => Yii::t(StructureModule::TRANSLATION_CATEGORY, 'Entities management')
];
$this->params['breadcrumbs'][] = $this->title;
$contexts = ArrayHelper::map(Context::find()->all(), 'id', 'name');

$form = \yii\bootstrap\ActiveForm::begin([
    'id' => 'page-form',
//    'options' => [
//        'enctype' => 'multipart/form-data'
//    ]
]);
?>
<?= Alert::widget() ?>
<?= $form->field($model, 'entity_id', ['template' => '{input}'])->hiddenInput(['value' => $model->entity_id]) ?>
    <div class="nav-tabs-custom">
        <ul class="nav nav-tabs">
            <li class="active">
                <a href="#page-data" data-toggle="tab" aria-expanded="true">
                    <?= Yii::t(StructureModule::TRANSLATION_CATEGORY, 'Main options') ?>
                </a>
            </li>
            <?php if (false === $model->isNewRecord) : ?>
                <li class="">
                    <a href="#page-properties" data-toggle="tab" aria-expanded="false">
                        <?= Yii::t(StructureModule::TRANSLATION_CATEGORY, 'Entity properties') ?>
                    </a>
                </li>
            <?php endif; ?>
        </ul>
        <div class="tab-content">
            <div class="tab-pane active" id="page-data">
                <div class="col-sm-12 col-md-6">
                    <?= $form->field($model, 'parent_id') ?>
                </div>
                <div class="col-sm-12 col-md-6">
                    <div class="row">
                        <div class="col-sm-4">
                            <?= $form->field($model, 'expand_in_tree')->checkbox() ?>
                        </div>
                        <div class="col-sm-4">
                            <?php
                            $cDDOptions = [];
                            if (null !== $model->parent) {
                                foreach ($contexts as $id => $name) {
                                    if ($id != $model->parent->context_id) {
                                        $cDDOptions['options'][$id] = ['disabled' => true];
                                    }
                                }
                            }
                            ?>
                            <?= $form->field($model, 'context_id')->dropDownList($contexts, $cDDOptions) ?>
                        </div>
                        <div class="col-sm-4">
                            <?= $form->field($model, 'sort_order') ?>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-12">
                        <?= DevGroup\Multilingual\widgets\MultilingualFormTabs::widget([
                            'model' => $model,
                            'childView' => '@DotPlant/EntityStructure/views/default/multilingual-part.php',
                            'form' => $form,
                        ]) ?>
                    </div>
                </div>
            </div>
            <div class="tab-pane" id="page-properties">
                <?= \DevGroup\DataStructure\widgets\PropertiesForm::widget([
                    'model' => $model,
                    'form' => $form,
                ]) ?>
            </div>
            <div class="row">
                <div class="col-sm-12">
                    <div class="btn-group pull-right" role="group" aria-label="Edit buttons">
                        <button type="submit" class="btn btn-success pull-right">
                            <?= Yii::t(StructureModule::TRANSLATION_CATEGORY, 'Save') ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php $form::end(); ?>