<?php

namespace DotPlant\EntityStructure\models;


use DevGroup\Entity\traits\EntityTrait;
use DevGroup\Entity\traits\SeoTrait;
use yii\db\ActiveRecord;

/**
 * Class StructureTranslation
 *
 * @property $model_id
 * @property $language_id
 * @property $name
 * @property $title
 * @property $h1
 * @property $breadcrumbs_label
 * @property $meta_description
 * @property $slug
 * @property $url
 * @property $is_active
 * @property $packed_json_content
 * @property $packed_json_providers
 *
 * @package DotPlant\EntityStructure\models
 */
class StructureTranslation extends ActiveRecord
{
    use EntityTrait;
    use SeoTrait;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%structure_translation}}';
    }

    protected $rules = [
        ['name', 'string', 'max' => 255],
        ['name', 'required'],
    ];

    //TODO $url
}