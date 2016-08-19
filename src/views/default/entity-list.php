<?php
/**
 * @var \yii\data\ActiveDataProvider $dataProvider
 * @var  \DotPlant\EntityStructure\models\BaseStructure $searchModel
 * @var int $parentId
 */
use yii\grid\GridView;
use kartik\icons\Icon;
use yii\helpers\Html;
use DotPlant\EntityStructure\StructureModule;
use DevGroup\AdminUtils\Helper;

$this->title = Yii::t('dotplant.entity.structure', 'Entities');
$this->params['breadcrumbs'][] = $this->title;
$buttons = Html::a(
    Icon::show('plus') . '&nbsp'
    . Yii::t('dotplant.entity.structure', 'New entity'),
    ['edit', 'parent_id' => $parentId, 'returnUrl' => Helper::returnUrl()],
    [
        'class' => 'btn btn-success',
    ]);
$gridTpl = <<<HTML
<div class="box-body">
    {summary}
    {items}
</div>
<div class="box-footer">
    <div class="row list-bottom">
        <div class="col-sm-5">
            {pager}
        </div>
        <div class="col-sm-7">
            <div class="btn-group pull-right" style="margin: 20px 0;">
                $buttons
            </div>
        </div>
    </div>
</div>
HTML;
?>
<div class="row">
    <div class="col-sm-12 col-md-12">
        <div class="indreams-pages__list-pages box box-solid">
            <div class="box-header with-border clearfix">
                <h3 class="box-title pull-left">
                    <?= Yii::t('dotplant.entity.structure', 'Entities list') ?>
                </h3>
            </div>
            <?= GridView::widget([
                'id' => 'entities-list',
                'dataProvider' => $dataProvider,
                'filterModel' => $searchModel,
                'layout' => $gridTpl,
                'tableOptions' => [
                    'class' => 'table table-bordered table-hover table-responsive',
                ],
                'columns' => [
                    [
                        'attribute' => 'name',
                        'options' => [
                            'width' => '20%',
                        ],
                    ],
                    [
                        'attribute' => 'title',
                        'options' => [
                            'width' => '20%',
                        ],
                    ],
                    [
                        'attribute' => 'slug',
                        'options' => [
                            'width' => '15%',
                        ],
                    ],
                    [
                        'attribute' => 'is_active',
                        'label' => Yii::t('dotplant.entity.structure', 'Active'),
                        'content' => function ($data) {
                            return Yii::$app->formatter->asBoolean($data->is_active);
                        },
                        'filter' => [
                            0 => Yii::$app->formatter->asBoolean(0),
                            1 => Yii::$app->formatter->asBoolean(1),
                        ],
                    ],
                    [
                        'class' => 'DevGroup\AdminUtils\columns\ActionColumn',
                        'options' => [
                            'width' => '95px',
                        ],
                        'buttons' => [
                            [
                                'url' => 'edit',
                                'icon' => 'pencil',
                                'class' => 'btn-info',
                                'label' => Yii::t('dotplant.entity.structure', 'Edit'),
                            ],
                            [
                                'url' => 'delete',
                                'icon' => 'trash-o',
                                'class' => 'btn-danger',
                                'label' => Yii::t('dotplant.entity.structure', 'Delete'),
                            ],
                        ],
                    ]
                ],
            ]);
            ?>
        </div>
    </div>
</div>
