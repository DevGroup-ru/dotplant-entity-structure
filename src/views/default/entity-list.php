<?php
/**
 * @var \yii\data\ActiveDataProvider $dataProvider
 * @var  \DotPlant\EntityStructure\models\BaseStructureSearch $searchModel
 * @var int $parentId
 */
use yii\grid\GridView;
use yii\helpers\Html;
use devgroup\JsTreeWidget\widgets\TreeWidget;
use \devgroup\JsTreeWidget\helpers\ContextMenuHelper;
use DevGroup\AdminUtils\Helper;
use \DevGroup\AdminUtils\columns\ActionColumn;
use DotPlant\EntityStructure\models\Entity;
use yii\helpers\StringHelper;
use DotPlant\EntityStructure\models\BaseStructure;
use yii\helpers\ArrayHelper;
use DotPlant\EntityStructure\models\BaseStructureSearch;

$this->title = Yii::t('dotplant.entity.structure', 'Entities tree');
$this->params['breadcrumbs'][] = $this->title;
$types = Entity::getIdToClassList();
$links = [];
/**
 * @var  $id
 * @var \DotPlant\EntityStructure\models\BaseStructure $className
 */
foreach ($types as $id => $className) {
    $links[] = Html::a(
        Yii::t($className::TRANSLATION_CATEGORY, StringHelper::basename($className)),
        ['/structure/entity-manage/edit', 'entity_id' => $id, 'parent_id' => $parentId, 'returnUrl' => Helper::returnUrl()]
    );
}
$ul = Html::ul($links, ['encode' => false, 'class' => 'dropdown-menu', 'role' => 'menu']);
$newText = Yii::t('dotplant.entity.structure', 'New');
$gridTpl = <<<HTML
<div class="box-body">
    {summary}
    {items}
</div>
<div class="box-footer">
    <div class="row list-bottom">
        <div class="col-sm-5">
            <div class="btn-group pull-left" style="margin: 20px 0;">
                <div class="btn-group">
                    <button type="button" class="btn btn-success dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                        $newText
                        <span class="caret"></span>
                    </button>
                    $ul
                </div>
            </div>
        </div>
        <div class="col-sm-7">
            {pager}
        </div>
    </div>
</div>
HTML;
?>
<div class="row">
    <div class="col-sm-12 col-md-6">
        <?php
        $contextMenu = BaseStructure::getContextMenu([
            'open' => [
                'label' => 'Open',
                'action' => ContextMenuHelper::actionUrl(
                    ['/structure/entity-manage/index'],
                    ['parent_id', 'context_id', 'id']
                ),
            ],
            'edit' => [
                'label' => 'Edit',
                'action' => ContextMenuHelper::actionUrl(
                    ['/structure/entity-manage/edit']
                ),
            ]
        ]);
        ?>
        <?= TreeWidget::widget([
            'treeDataRoute' => ['/structure/entity-manage/get-tree', 'selected_id' => $parentId],
            'reorderAction' => ['/structure/entity-manage/tree-reorder'],
            'changeParentAction' => ['/structure/entity-manage/tree-parent'],
            'treeType' => TreeWidget::TREE_TYPE_ADJACENCY,
            'contextMenuItems' => $contextMenu,
        ]) ?>
    </div>
    <div class="col-sm-12 col-md-6">
        <div class="entities__list-entities box box-solid">
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
                        'label' => Yii::t('dotplant.entity.structure', 'Name'),
                        'options' => [
                            'width' => '20%',
                        ],
                    ],
                    [
                        'attribute' => 'title',
                        'label' => Yii::t('entity', 'Title'),
                        'options' => [
                            'width' => '20%',
                        ],
                    ],
                    [
                        'attribute' => 'slug',
                        'label' => Yii::t('entity', 'Last url part'),
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
                        'attribute' => 'is_deleted',
                        'label' => Yii::t('dotplant.entity.structure', 'Show deleted?'),
                        'value' => function ($model) {
                            /** @var BaseStructureSearch $model */
                            return $model->isDeleted() === true
                                ? Yii::t('dotplant.entity.structure', 'Deleted')
                                : Yii::t('dotplant.entity.structure', 'Active');
                        },
                        'filter' => [
                            Yii::t('dotplant.entity.structure', 'Show only active'),
                            Yii::t('dotplant.entity.structure', 'Show only deleted')
                        ],
                        'filterInputOptions' => [
                            'class' => 'form-control',
                            'id' => null,
                            'prompt' => Yii::t('dotplant.entity.structure', 'Show all')
                        ]
                    ],
                    [
                        'class' => ActionColumn::class,
                        'options' => [
                            'width' => '120px',
                        ],
                        'buttons' => function ($model, $key, $index, $column) {
                            /** @var BaseStructureSearch $model */
                            $result = [
                                [
                                    'url' => '/structure/entity-manage/edit',
                                    'icon' => 'pencil',
                                    'class' => 'btn-primary',
                                    'label' => Yii::t('dotplant.entity.structure', 'Edit'),
                                    'attrs' => ['entity_id', 'parent_id']
                                ],
                            ];
                            $additional = $model->additionalGridButtons();
                            if (false === empty($additional)) {
                                $result = ArrayHelper::merge($result, $additional);
                            }
                            if ($model->isDeleted() === false) {
                                $result['delete'] = [
                                    'url' => '/structure/entity-manage/delete',
                                    'visible' => false,
                                    'icon' => 'trash-o',
                                    'class' => 'btn-warning',
                                    'label' => Yii::t('dotplant.entity.structure', 'Delete'),
                                    'attrs' => ['entity_id'],
                                    'options' => [
                                        'data-action' => 'delete',
                                        'data-method' => 'post',
                                    ],
                                ];
                            } else {
                                $result['restore'] = [
                                    'url' => '/structure/entity-manage/restore',
                                    'icon' => 'undo',
                                    'class' => 'btn-info',
                                    'label' => Yii::t('dotplant.entity.structure', 'Restore'),
                                    'attrs' => ['entity_id'],
                                ];
                                $result['delete'] = [
                                    'url' => '/structure/entity-manage/delete',
                                    'urlAppend' => ['hard' => 1],
                                    'icon' => 'trash-o',
                                    'class' => 'btn-danger',
                                    'label' => Yii::t('dotplant.entity.structure', 'Delete'),
                                    'attrs' => ['entity_id'],
                                    'options' => [
                                        'data-action' => 'delete',
                                        'data-method' => 'post',
                                    ],
                                ];
                            }
                            return $result;
                        }
                    ]
                ],
            ]) ?>
        </div>
    </div>
</div>
