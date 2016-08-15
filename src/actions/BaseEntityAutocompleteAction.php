<?php

namespace DotPlant\EntityStructure\actions;

use DevGroup\AdminUtils\actions\BaseAdminAction;
use DotPlant\EntityStructure\models\BaseStructure;
use DotPlant\EntityStructure\models\StructureTranslation;
use DotPlant\EntityStructure\StructureModule;
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
            throw new NotFoundHttpException(Yii::t(StructureModule::TRANSLATION_CATEGORY, 'Page not found'));
        }
        if (false === empty($this->searchFields)) {
            $columns = StructureTranslation::getTableSchema()->columnNames;
            $notFound = array_diff($this->searchFields, $columns);
            if (false === empty($notFound)) {
                throw new InvalidConfigException(
                    Yii::t(
                        StructureModule::TRANSLATION_CATEGORY,
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
        if (null !== $q) {
            $query = new Query;
            $query->select('id, name AS text')
                ->from(BaseStructure::tableName())
                ->innerJoin(StructureTranslation::tableName(), 'id = model_id')
                ->where($this->prepareCondition($q))
                ->andWhere(['language_id' => Yii::$app->multilingual->language_id])
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
        $condition = count($this->searchFields) > 1 ? array_unshift($condition, 'or') : $condition;
        return $condition;
    }
}