<?php

namespace DotPlant\EntityStructure\models;

use DevGroup\DataStructure\behaviors\HasProperties;
use DevGroup\DataStructure\behaviors\PackedJsonAttributes;
use DevGroup\DataStructure\traits\PropertiesTrait;
use DevGroup\Multilingual\behaviors\MultilingualActiveRecord;
use DevGroup\Multilingual\traits\MultilingualTrait;
use DevGroup\TagDependencyHelper\CacheableActiveRecord;
use DevGroup\TagDependencyHelper\TagDependencyTrait;
use DotPlant\EntityStructure\StructureModule;
use yii\data\ActiveDataProvider;
use yii\db\ActiveRecord;
use Yii;
use yii\helpers\ArrayHelper;
use yii\web\NotFoundHttpException;

/**
 * This is the model class for table "{{%structure}}".
 *
 * @property integer $id
 * @property integer $parent_id
 * @property integer $context_id
 * @property integer $entity_id
 * @property integer $expand_in_tree
 * @property integer $sort_order
 * @property integer $is_deleted
 * @property integer $created_at
 * @property integer $created_by
 * @property integer $updated_at
 * @property integer $updated_by
 *
 * @property Entity $entity
 * @property BaseStructure $parent
 * @property BaseStructure[] $children
 * @property StructureTranslation[] $structureTranslations
 */
class BaseStructure extends ActiveRecord
{
    use MultilingualTrait;
    use TagDependencyTrait;
    use PropertiesTrait;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%structure}}';
    }

    protected static function getPageSize()
    {
        return StructureModule::module()->defaultPageSize;
    }

    /**
     * @var string workaround for having one base table for structure and storing additional data such as properties
     * in different tables
     */
    protected static $tablePrefix;

    /**
     * @var array
     */
    private $entitiesMap = [];

    /**
     * Returns Entity id
     *
     * @return int
     * @throws NotFoundHttpException
     */
    public function getEntityId()
    {
        if (false === isset($this->entitiesMap[static::class])) {
            if (false === $entityId = Entity::find()->select('id')->where(['class_name' => static::class])->scalar()) {
                throw new NotFoundHttpException(Yii::t(
                    StructureModule::TRANSLATION_CATEGORY,
                    "Unknown entity '{class}'!",
                    ['class' => static::class]
                ));
            }
            $this->entitiesMap[static::class] = $entityId;
        }
        return (int)$this->entitiesMap[static::class];
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'multilingual' => [
                'class' => MultilingualActiveRecord::class,
                'translationModelClass' => StructureTranslation::class,
                'translationPublishedAttribute' => 'is_active'
            ],
            'CacheableActiveRecord' => [
                'class' => CacheableActiveRecord::class,
            ],
            'PackedJsonAttributes' => [
                'class' => PackedJsonAttributes::class,
            ],
            'properties' => [
                'class' => HasProperties::class,
                'autoFetchProperties' => true,
            ],
        ];
    }

    /**
     * Workaround for EntityTrait::$rules & HasProperties behavior
     *
     * @inheritdoc
     */
    public function getRules()
    {
        return ArrayHelper::merge(
            [
                [
                    [
                        'parent_id',
                        'context_id',
                        'entity_id',
                    ],
                    'required'
                ],
                [
                    [
                        'parent_id',
                        'context_id',
                        'entity_id',
                        'expand_in_tree',
                        'sort_order',
                    ],
                    'integer'
                ],
            ],
            $this->propertiesRules());
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t(StructureModule::TRANSLATION_CATEGORY, 'ID'),
            'parent_id' => Yii::t(StructureModule::TRANSLATION_CATEGORY, 'Parent ID'),
            'context_id' => Yii::t(StructureModule::TRANSLATION_CATEGORY, 'Context ID'),
            'entity_id' => Yii::t(StructureModule::TRANSLATION_CATEGORY, 'Entity ID'),
            'expand_in_tree' => Yii::t(StructureModule::TRANSLATION_CATEGORY, 'Expand In Tree'),
            'sort_order' => Yii::t(StructureModule::TRANSLATION_CATEGORY, 'Sort Order'),
        ];
    }

    public function search($params)
    {
        /* @var $query \yii\db\ActiveQuery */

        $dataProvider = new ActiveDataProvider([
            'query' => $query = static::find(),
            'pagination' => [
                'pageSize' => static::getPageSize(),
            ],
        ]);
        if (null != $this->parent_id) {
            $query->andWhere(['parent_id' => $this->parent_id]);
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
        if (false === $this->load($params)) {
            return $dataProvider;
        }
        $query->andFilterWhere(['id' => $this->id]);
        $translation = new StructureTranslation();
        if (false === $translation->load(static::fetchParams($params, self::class, $translation))) {
            return $dataProvider;
        }
        $query->andFilterWhere(['like', StructureTranslation::tableName() . '.name', $this->name]);
        $query->andFilterWhere(['like', StructureTranslation::tableName() . '.title', $this->title]);
        $query->andFilterWhere(['like', StructureTranslation::tableName() . '.h1', $this->h1]);
        $query->andFilterWhere([StructureTranslation::tableName() . '.is_active' => $this->is_active]);
        return $dataProvider;
    }

    /**
     * @param array $params
     * @param string $fromClass class name
     * @param ActiveRecord $toModel
     * @return array
     */
    public static function fetchParams($params, $fromClass, $toModel)
    {
        if (true === empty($params) || false === class_exists($fromClass) || false === $toModel instanceof ActiveRecord) {
            return [];
        }
        $outParams = [];
        $toClass = get_class($toModel);
        $fromName = array_pop(explode('\\', $fromClass));
        $toName = array_pop(explode('\\', $toClass));
        if (true === isset($params[$fromName])) {
            foreach ($params[$fromName] as $key => $value) {
                if (true === in_array($key, $toModel->attributes())) {
                    $outParams[$toName][$key] = $value;
                }
            }
        }
        return $outParams;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEntity()
    {
        return $this->hasOne(Entity::class, ['id' => 'entity_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStructureTranslations()
    {
        return $this->hasMany(StructureTranslation::class, ['model_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getParent()
    {
        return $this->hasOne(static::class, ['id' => 'parent_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getChildren()
    {
        return $this->hasMany(static::class, ['parent_id' => 'id']);
    }
}