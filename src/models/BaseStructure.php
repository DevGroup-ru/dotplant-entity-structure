<?php

namespace DotPlant\EntityStructure\models;

use DevGroup\DataStructure\behaviors\HasProperties;
use DevGroup\DataStructure\traits\PropertiesTrait;
use DevGroup\Multilingual\behaviors\MultilingualActiveRecord;
use DevGroup\Multilingual\traits\MultilingualTrait;
use DevGroup\TagDependencyHelper\CacheableActiveRecord;
use DevGroup\TagDependencyHelper\TagDependencyTrait;
use DotPlant\EntityStructure\StructureModule;
use yii\caching\TagDependency;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQuery;
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

    /** string this is for base actions translation */
    const TRANSLATION_CATEGORY = 'dotplant.entity.structure';

    protected $entityConfiguration = [];

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%dotplant_structure}}';
    }

    /**
     * Allows to configure count per page listed records separately for each DotPlant Entity
     *
     * @return int
     */
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
    protected static $entitiesMap;

    /**
     * Returns Entity id
     *
     * @return int
     * @throws NotFoundHttpException
     */
    public function getEntityId()
    {
        self::entitiesMap();
        if (false === isset(self::$entitiesMap[static::class])) {

            throw new \Exception(Yii::t(
                'dotplant.entity.structure',
                "Unknown entity '{class}'!",
                ['class' => static::class]
            ));
        }
        return (int) self::$entitiesMap[static::class]['id'];
    }

    public static function entitiesMap()
    {
        if (self::$entitiesMap === null) {
            self::$entitiesMap = Yii::$app->cache->get('Structure:EntitiesMap');
            if (self::$entitiesMap === false) {
                self::$entitiesMap = Entity::find()->asArray()->indexBy('class_name')->all();
                Yii::$app->cache->set(
                    'Structure:EntitiesMap',
                    self::$entitiesMap,
                    86400,
                    new TagDependency(['tags'=>Entity::commonTag()])
                );
            }
        }
        return self::$entitiesMap;
    }

    /**
     * Table inheritance pattern here.
     * @inheritdoc
     */
    public static function instantiate($row)
    {
        $entityId = (int) $row['entity_id'];

        foreach (self::entitiesMap() as $record) {
            if ($entityId === (int) $record['id']) {
                $class = Yii::createObject($record['class_name']);
                $class->setEntityConfiguration($record);
                return $class;
            }
        }


        return Yii::createObject(self::class);
    }

    public function setEntityConfiguration($record)
    {
        $this->entityConfiguration = $record;
    }

    public function getEntityConfiguration()
    {
        return $this->entityConfiguration;
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
            $this->propertiesRules()
        );
    }

    /**
     * @inheritdoc
     */
    protected function getAttributeLabels()
    {
        return [
            'id' => Yii::t('dotplant.entity.structure', 'ID'),
            'parent_id' => Yii::t('dotplant.entity.structure', 'Parent ID'),
            'context_id' => Yii::t('dotplant.entity.structure', 'Context ID'),
            'entity_id' => Yii::t('dotplant.entity.structure', 'Entity ID'),
            'expand_in_tree' => Yii::t('dotplant.entity.structure', 'Expand In Tree'),
            'sort_order' => Yii::t('dotplant.entity.structure', 'Sort Order'),
        ];
    }

    /**
     * Override safe attributes to include translation attributes
     * @return array
     */
    public function safeAttributes()
    {
        $t = Yii::createObject($this->getTranslationModelClassName());
        return ArrayHelper::merge(parent::safeAttributes(), $t->safeAttributes());
    }

    /**
     * Override for filtering in grid
     * @param string $attribute
     *
     * @return bool
     */
    public function isAttributeActive($attribute)
    {
        return in_array($attribute, $this->safeAttributes());
    }

    /**
     * @param ActiveQuery $query
     *
     * @return ActiveQuery
     */
    public static function applyDefaultScope($query)
    {
        return $query;
    }

    /**
     * @inheritdoc
     */
    public function beforeSave($insert)
    {
        if (true === parent::beforeSave($insert)) {
            //jstree change parent action
            if (null !== $this->parent_id && 0 != $this->parent_id) {
                if ($this->context_id != $this->parent->context_id) {
                    $this->context_id = $this->parent->context_id;
                }
            }
            return true;
        } else {
            return false;
        }
    }

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

    /**
     * Workaround to have ability use Model::load() method instead assigning values from request by hand
     *
     * @param array $params
     * @param string $fromClass class name
     * @param ActiveRecord $toModel
     * @return array
     */
    public static function fetchParams($params, $fromClass, $toModel)
    {
        if (true === empty($params)
            || false === class_exists($fromClass)
            || false === $toModel instanceof ActiveRecord
        ) {
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
     * @return ActiveQuery
     */
    public function getEntity()
    {
        return $this->hasOne(Entity::class, ['id' => 'entity_id']);
    }

    /**
     * @return ActiveQuery
     */
    public function getParent()
    {
        return $this->hasOne(static::class, ['id' => 'parent_id']);
    }

    /**
     * @return ActiveQuery
     */
    public function getChildren()
    {
        return $this->hasMany(static::class, ['parent_id' => 'id']);
    }
}