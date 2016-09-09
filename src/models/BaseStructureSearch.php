<?php

namespace DotPlant\EntityStructure\models;

use DevGroup\AdminUtils\traits\FetchModels;
use DevGroup\Entity\traits\BaseActionsInfoTrait;
use DevGroup\Entity\traits\EntityTrait;
use DevGroup\Entity\traits\SoftDeleteTrait;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQuery;

/**
 * Class BaseStructureSearch
 * @package DotPlant\EntityStructure\models
 */
class BaseStructureSearch extends BaseStructure
{
    use EntityTrait;
    use SoftDeleteTrait;
    use BaseActionsInfoTrait;
    use FetchModels;

    /**
     * Finds models
     *
     * @param $params
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        /* @var $query ActiveQuery */

        $dataProvider = new ActiveDataProvider([
            'query' => $query = static::find(),
            'pagination' => [
                'pageSize' => static::getPageSize(),
            ],
        ]);
        if (null != $this->parent_id) {
            $query->andWhere(['parent_id' => $this->parent_id]);
        }
        if (null != $this->context_id) {
            $query->andWhere(['context_id' => $this->context_id]);
        }
        $dataProvider->sort->attributes['name'] = [
            'asc' => [StructureTranslation::tableName() . '.name' => SORT_ASC],
            'desc' => [StructureTranslation::tableName() . '.name' => SORT_DESC],
        ];
        $dataProvider->sort->attributes['title'] = [
            'asc' => [StructureTranslation::tableName() . '.title' => SORT_ASC],
            'desc' => [StructureTranslation::tableName() . '.title' => SORT_DESC],
        ];
        $dataProvider->sort->attributes['is_active'] = [
            'asc' => [StructureTranslation::tableName() . '.is_active' => SORT_ASC],
            'desc' => [StructureTranslation::tableName() . '.is_active' => SORT_DESC],
        ];
        $dataProvider->sort->attributes['slug'] = [
            'asc' => [StructureTranslation::tableName() . '.slug' => SORT_ASC],
            'desc' => [StructureTranslation::tableName() . '.slug' => SORT_DESC],
        ];
        if (false === $this->load($params)) {
            return $dataProvider;
        }
        $query->andFilterWhere(['id' => $this->id]);
        $query->andFilterWhere(['is_deleted' => $this->is_deleted]);
        $translation = new StructureTranslation();
        if (false === $translation->load(static::fetchParams($params, static::class, $translation))) {
            return $dataProvider;
        }
        $query->andFilterWhere(['like', StructureTranslation::tableName() . '.name', $this->name]);
        $query->andFilterWhere(['like', StructureTranslation::tableName() . '.title', $this->title]);
        $query->andFilterWhere(['like', StructureTranslation::tableName() . '.h1', $this->h1]);
        $query->andFilterWhere(['like', StructureTranslation::tableName() . '.slug', $this->slug]);
        $query->andFilterWhere([StructureTranslation::tableName() . '.is_active' => $this->is_active]);
        return $dataProvider;
    }


}