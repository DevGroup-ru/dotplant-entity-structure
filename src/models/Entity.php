<?php

namespace DotPlant\EntityStructure\models;

use DevGroup\TagDependencyHelper\CacheableActiveRecord;
use DevGroup\TagDependencyHelper\TagDependencyTrait;
use DotPlant\EntityStructure\StructureModule;
use yii\base\InvalidParamException;
use yii\db\ActiveRecord;
use Yii;

/**
 * This is the model class for table "{{%entity}}".
 *
 * @property integer $id
 * @property string $name
 * @property string $route
 * @property string $class_name
 *
 * @property BaseStructure[] $structure
 */
class Entity extends ActiveRecord
{
    use TagDependencyTrait;
    /** @var array */
    private static $classMap = [];

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
        if (false === isset(self::$classMap[$className])) {
            if (false === $id = self::find()->select('id')->where(['class_name' => $className])->scalar()) {
                throw new InvalidParamException(Yii::t(
                    'dotplant.entity.structure',
                    'Unknown entity class \'{class}\'.',
                    ['class' => $className]
                ));
            }
            self::$classMap[$className] = $id;
        }
        return self::$classMap[$className];
    }
}