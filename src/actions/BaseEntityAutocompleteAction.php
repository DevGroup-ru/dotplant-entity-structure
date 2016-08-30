<?php

namespace DotPlant\EntityStructure\actions;

use DevGroup\AdminUtils\actions\BaseAdminAction;
use DotPlant\EntityStructure\models\BaseStructure;
use DotPlant\EntityStructure\models\Entity;
use DotPlant\EntityStructure\models\StructureTranslation;
use yii\base\InvalidConfigException;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\db\Query;
use Yii;

/**
 * Class BaseEntityAutocompleteAction
 * @package DotPlant\EntityStructure\actions
 */
class BaseEntityAutocompleteAction extends BaseAdminAction
{
    /** @var  BaseStructure */
    public $entityClass;

    /**
     * @var array fields to search against
     */
    public $searchFields;

    /**
     * @var array default search fields
     */
    private $defaultFields = ['title', 'name', 'h1', 'breadcrumbs_label', 'slug'];

    /**
     * @inheritdoc
     */
    public function init()
    {
        if (false === Yii::$app->request->isAjax) {
            throw new NotFoundHttpException(Yii::t('dotplant.entity.structure', 'Page not found'));
        }
        if (true === empty($this->entityClass)) {
            throw new InvalidConfigException(
                Yii::t('dotplant.entity.structure', "The 'entityClass' param must be set!")
            );
        }
        $entityClass = $this->entityClass;
        if (false === is_subclass_of($entityClass, BaseStructure::class)) {
            throw new InvalidConfigException(Yii::t(
                'dotplant.entity.structure',
                "The 'entityClass' must extend 'DotPlant\\EntityStructure\\models\\BaseStructure'!"
            ));
        }
        if (false === empty($this->searchFields)) {
            $columns = StructureTranslation::getTableSchema()->columnNames;
            $notFound = array_diff($this->searchFields, $columns);
            if (false === empty($notFound)) {
                throw new InvalidConfigException(
                    Yii::t(
                        'dotplant.entity.structure',
                        'The following columns \'{columns}\' are not found in database table!',
                        ['columns' => implode(', ', $notFound)]
                    )
                );
            }
        } else {
            $this->searchFields = $this->defaultFields;
        }
        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function run($q = null, $id = null)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $out = ['results' => ['id' => '', 'text' => '']];
        $entityId = Entity::getEntityIdForClass($this->entityClass);
        if (null !== $q) {
            $query = new Query;
            $query->select('id, name AS text')
                ->from(BaseStructure::tableName())
                ->innerJoin(StructureTranslation::tableName(), 'id = model_id')
                ->where($this->prepareCondition($q))
                ->andWhere([
                    'language_id' => Yii::$app->multilingual->language_id,
                    'entity_id' => $entityId
                ])
                ->limit(20);
            $command = $query->createCommand();
            $data = $command->queryAll();
            $out['results'] = array_values($data);
        } elseif ($id > 0) {
            $out['results'] = ['id' => $id, 'text' => BaseStructure::find($id)->name];
        }
        return $out;
    }

    /**
     * Prepares query condition
     *
     * @param $q
     * @return array|int
     */
    private function prepareCondition($q)
    {
        $condition = [];
        foreach ($this->searchFields as $field) {
            $condition[] = ['like', $field, $q];
        }
        if (count($this->searchFields) > 1) {
            array_unshift($condition, 'or');
        }
        return $condition;
    }
}