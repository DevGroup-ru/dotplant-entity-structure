<?php

namespace DotPlant\EntityStructure\models;

use DevGroup\DataStructure\behaviors\PackedJsonAttributes;
use DevGroup\TagDependencyHelper\CacheableActiveRecord;
use DevGroup\TagDependencyHelper\TagDependencyTrait;
use yii\base\InvalidParamException;
use yii\caching\TagDependency;
use yii\db\ActiveRecord;
use Yii;

/**
 * This is the model class for table "{{%dotplant_entity}}".
 *
 * @property integer $id
 * @property string $name
 * @property string $route
 * @property string $class_name
 * @property string $tree_icon
 * @property string $packed_json_route_handlers
 * @property array $route_handlers
 *
 * @property BaseStructure[] $structure
 */
class Entity extends ActiveRecord
{
    use TagDependencyTrait;

    /** @var array */
    private static $entitiesMap = [];

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%dotplant_entity}}';
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'CacheableActiveRecord' => [
                'class' => CacheableActiveRecord::class,
            ],
            'packedJsonAttributes' => [
                'class' => PackedJsonAttributes::class,
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name', 'class_name'], 'required'],
            [['name'], 'string', 'max' => 100],
            [['route'], 'string', 'max' => 100],
            [['class_name'], 'string', 'max' => 255],
            [
                ['id'],
                'exist',
                'skipOnError' => true,
                'targetClass' => BaseStructure::class,
                'targetAttribute' => ['id' => 'entity_id']
            ],
            [['packed_json_route_handlers'], 'default', 'value' => '[]'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('dotplant.entity.structure', 'ID'),
            'name' => Yii::t('dotplant.entity.structure', 'Name'),
            'route' => Yii::t('dotplant.entity.structure', 'Route'),
            'class_name' => Yii::t('dotplant.entity.structure', 'Class Name'),
            'tree_icon' => Yii::t('dotplant.entity.structure', 'Tree icon'),
            'packed_json_route_handlers' => Yii::t('dotplant.entity.structure', 'Route handlers'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStructure()
    {
        return $this->hasMany(BaseStructure::class, ['entity_id' => 'id']);
    }

    /**
     * Returns Entity id according to given Entity class name
     *
     * @param $className
     * @return mixed
     */
    public static function getEntityIdForClass($className)
    {
        self::entitiesMap();
        if (false === isset(self::$entitiesMap[$className])) {
            throw new InvalidParamException(Yii::t(
                'dotplant.entity.structure',
                'Unknown entity class \'{class}\'.',
                ['class' => $className]
            ));
        }
        return (int)self::$entitiesMap[$className]['id'];
    }

    /**
     * @param $id
     * @return mixed
     */
    public static function getEntityClassForId($id)
    {
        self::entitiesMap();
        $class = null;
        foreach (self::$entitiesMap as $className => $itemData) {
            if ($itemData['id'] == $id) {
                $class = $className;
                break;
            }
        }
        if (null === $class) {
            throw new InvalidParamException(Yii::t(
                'dotplant.entity.structure',
                'Invalid entity id #{id}',
                ['id' => $id]
            ));
        }
        return $class;
    }

    /**
     * @return array
     */
    public static function getIdToClassList()
    {
        self::entitiesMap();
        $idToClass = Yii::$app->cache->get('Structure:IdToClassList');
        if (false === $idToClass) {
            $idToClass = [];
            foreach (self::$entitiesMap as $className => $data) {
                $idToClass[$data['id']] = $className;
            }
            Yii::$app->cache->set(
                'Structure:IdToClassList',
                $idToClass,
                86400,
                new TagDependency(['tags' => self::commonTag()])
            );
        }
        return $idToClass;
    }

    /**
     * @return array|mixed|\yii\db\ActiveRecord[]
     */
    public static function entitiesMap()
    {
        if (true === empty(self::$entitiesMap)) {
            self::$entitiesMap = Yii::$app->cache->get('Structure:EntitiesMap');
            if (false === self::$entitiesMap) {
                self::$entitiesMap = Entity::find()->asArray()->indexBy('class_name')->all();
                Yii::$app->cache->set(
                    'Structure:EntitiesMap',
                    self::$entitiesMap,
                    86400,
                    new TagDependency(['tags' => self::commonTag()])
                );
            }
        }
        return self::$entitiesMap;
    }
}
