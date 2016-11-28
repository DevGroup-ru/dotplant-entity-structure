<?php

namespace DotPlant\EntityStructure\models;

use DevGroup\DataStructure\behaviors\HasProperties;
use DevGroup\DataStructure\traits\PropertiesTrait;
use DevGroup\Multilingual\behaviors\MultilingualActiveRecord;
use DevGroup\Multilingual\traits\MultilingualTrait;
use DevGroup\TagDependencyHelper\CacheableActiveRecord;
use DevGroup\TagDependencyHelper\NamingHelper;
use DevGroup\TagDependencyHelper\TagDependencyTrait;
use DotPlant\EntityStructure\interfaces\MainEntitySeoInterface;
use DotPlant\EntityStructure\interfaces\PermissionsInterface;
use DotPlant\EntityStructure\interfaces\StructureConfigInterface;
use DotPlant\EntityStructure\StructureModule;
use DotPlant\EntityStructure\traits\MainEntitySeoTrait;
use yii\caching\TagDependency;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

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
 * @property StructureTranslation $defaultTranslation
 * @property StructureTranslation[] $translations
 */
class BaseStructure extends ActiveRecord implements PermissionsInterface, StructureConfigInterface, MainEntitySeoInterface
{
    use MultilingualTrait;
    use TagDependencyTrait;
    use PropertiesTrait;
    use MainEntitySeoTrait;

    /** string this is for base actions translation */
    const TRANSLATION_CATEGORY = 'dotplant.entity.structure';

    //protected $entityConfiguration = [];

    /**
     * View file to be used for render in the `BaseEntityEditAction`
     * can be overwritten in heirs
     *
     * @var string
     */
    protected static $editViewFile = '@DotPlant/EntityStructure/views/default/entity-edit';

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
     * Array of actions to inject
     */
    protected static $injectionActions = [];

    /**
     * Returns Entity id
     *
     * @return int
     * @throws \Exception
     */
    public function getEntityId()
    {
        return Entity::getEntityIdForClass(static::class);
    }


//
//    /**
//     * @param $record
//     */
//    public function setEntityConfiguration($record)
//    {
//        $this->entityConfiguration = $record;
//    }
//
//    public function getEntityConfiguration()
//    {
//        return $this->entityConfiguration;
//    }

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
     * @inheritdoc
     */
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);
        TagDependency::invalidate(
            $this->getTagDependencyCacheComponent(),
            NamingHelper::getCommonTag(self::class)
        );
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


    /**
     * @return array
     */
    public function getParentsIds()
    {
        $cacheKey = 'BaseStructure:parentsIds:' . $this->id;
        if (false === $result = Yii::$app->cache->get($cacheKey)) {
            $result = [];
            $parent_id = $this->parent_id;
            while ($parent_id > 0) {
                $result[] = (int) $parent_id;
                $parent_id = self::find()->select('parent_id')->where(['id' => $parent_id])->scalar();
                if ($parent_id === false) {
                    break;
                }
            }
            $result = array_reverse($result);
            \Yii::$app->cache->set(
                $cacheKey,
                $result,
                86400,
                new TagDependency(
                    [
                        'tags' => $this->objectCompositeTag(),
                    ]
                )
            );
        }
        return $result;
    }

    /**
     * @inheritdoc
     */
    public function getEditPageTitle()
    {
        return (true === $this->getIsNewRecord())
            ? Yii::t('dotplant.entity.structure', 'New entity')
            : Yii::t('dotplant.entity.structure', 'Edit {title}', ['title' => $this->name]);
    }

    /**
     * @param $modelId
     * @return false|null|string
     */
    public static function getEntityIdForModelId($modelId)
    {
        return self::find()->select('entity_id')->where(['id' => $modelId])->scalar();
    }

    /**
     * @inheritdoc
     */
    public static function jsTreeContextMenuActions()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public static function getAccessRules()
    {
        return [];
    }

    /**
     * @return array
     */
    public function additionalGridButtons()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public static function getEditViewFile()
    {
        return static::$editViewFile;
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%dotplant_structure}}';
    }

    /**
     * Переопределяем базовый метод `BaseActiveRecord` чтобы в момент построения GridView
     * все получаемые модели были не объектами текущего класса, а конктретного класса наследника
     *
     * @inheritdoc
     */
    public static function instantiate($row)
    {
        $entityId = (int)$row['entity_id'];
        $className = Entity::getEntityClassForId($entityId);
        return Yii::createObject($className);
    }

    /**
     * @inheritdoc
     */
    public static function getModuleBreadCrumbs()
    {
        return [
            [
                'url' => ['/structure/entity-manage/index'],
                'label' => Yii::t('dotplant.entity.structure', 'Entities management')
            ]
        ];
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
    public static function getContextMenu($default = [])
    {
        $contextMenu = $default;
        /** @var BaseStructure $className */
        foreach (Entity::getIdToClassList() as $className) {
            $contextMenu = ArrayHelper::merge($contextMenu, $className::jsTreeContextMenuActions());
        }
        return $contextMenu;
    }


    /**
     * @return array
     */
    public static function prepareActionsInjection()
    {
        $injectionActions = [];
        /** @var BaseStructure $className */
        foreach (Entity::getIdToClassList() as $className) {
            $injectionActions = ArrayHelper::merge($injectionActions, $className::$injectionActions);
        }
        return $injectionActions;
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
     * @inheritdoc
     */
    public function getSeoBreadcrumbs()
    {
        $breadcrumbs = [
            [
                'label' => !empty($this->defaultTranslation->breadcrumbs_label)
                    ? $this->defaultTranslation->breadcrumbs_label
                    : $this->defaultTranslation->name,
                'url' => Url::toRoute(
                    [
                        '/universal/show',
                        'entities' => [
                            self::class => [$this->id],
                        ]
                    ]
                ),
            ],
        ];
        $modelId = $this->parent_id;
        while ($modelId !== null) {
            $model = static::findOne($modelId);
            if ($model != null) {
                $breadcrumbs[] = [
                    'label' => !empty($model->defaultTranslation->breadcrumbs_label)
                        ? $model->defaultTranslation->breadcrumbs_label
                        : $model->defaultTranslation->name,
                    'url' => Url::toRoute(
                        [
                            '/universal/show',
                            'entities' => [
                                self::class => [$model->id],
                            ]
                        ]
                    ),
                ];
                $modelId = $model->parent_id;
            } else {
                $modelId = null;
            }
        }
        return array_reverse($breadcrumbs);
    }
}
