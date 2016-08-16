<?php

namespace DotPlant\EntityStructure\actions;

use DevGroup\AdminUtils\actions\BaseAdminAction;
use DotPlant\EntityStructure\models\BaseStructure;
use DotPlant\EntityStructure\StructureModule;
use yii\base\InvalidConfigException;
use Yii;

/**
 * Class BaseEntityListAction
 *
 * @package DotPlant\EntityStructure\actions
 */
class BaseEntityListAction extends BaseAdminAction
{
    /** @var  BaseStructure */
    public $entityClass;

    /** @var string View file to render */
    public $viewFile = '@DotPlant/EntityStructure/views/default/entity-list';

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
                "The 'entityClass' must extend {class}!",
                ['class' => 'DotPlant\\EntityStructure\\models\\BaseStructure']
            ));
        }
        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function run($id = null, $context_id = null)
    {
        $entityClass = $this->entityClass;
        /** @var BaseStructure $searchModel */
        $searchModel = new $entityClass(['is_active' => '']);
        if (null !== $id) {
            $searchModel->parent_id = (int)$id;
        }
        if (null !== $context_id) {
            $searchModel->context_id = (int)$context_id;
        }
        $params = Yii::$app->request->get();
        $dataProvider = $searchModel->search($params);
        return $this->controller->render(
            $this->viewFile,
            [
                'dataProvider' => $dataProvider,
                'searchModel' => $searchModel,
                'parentId' => $id,
            ]
        );
    }
}
