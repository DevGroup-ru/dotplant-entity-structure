<?php

namespace DotPlant\EntityStructure\models;

use DotPlant\EntityStructure\StructureModule;
use yii\db\ActiveRecord;
use Yii;

/**
 * This is the model class for table "{{%entity}}".
 *
 * @property integer $id
 * @property string $name
 * @property string $class_name
 *
 * @property BaseStructure[] $structure
 */
class Entity extends ActiveRecord
{
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
    public function rules()
    {
        return [
            [['name', 'class_name'], 'required'],
            [['name'], 'string', 'max' => 100],
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
            'id' => Yii::t(StructureModule::TRANSLATION_CATEGORY, 'ID'),
            'name' => Yii::t(StructureModule::TRANSLATION_CATEGORY, 'Name'),
            'class_name' => Yii::t(StructureModule::TRANSLATION_CATEGORY, 'Class Name'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStructure()
    {
        return $this->hasMany(BaseStructure::class, ['entity_id' => 'id']);
    }
}