<?php

namespace DotPlant\EntityStructure\models;


use DevGroup\Entity\traits\EntityTrait;
use DevGroup\Entity\traits\SeoTrait;
use DotPlant\EntityStructure\StructureModule;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use Yii;
use yii\db\Query;

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
 * @property BaseStructure $structure
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
        return '{{%dotplant_structure_translation}}';
    }

    protected $rules = [
        [['is_active'], 'integer'],
        ['name', 'string', 'max' => 255],
        ['name', 'required'],
    ];

    protected function getAttributeLabels()
    {
        return [
            'is_active' => Yii::t(StructureModule::TRANSLATION_CATEGORY, 'Is Active'),
            'name' => Yii::t(StructureModule::TRANSLATION_CATEGORY, 'Name'),
        ];
    }

    /**
     * @return ActiveQuery
     */
    public function getStructure()
    {
        return $this->hasOne(BaseStructure::class, ['id' => 'model_id']);
    }

    /**
     * @inheritdoc
     */
    public function beforeSave($insert)
    {
        if (true === parent::beforeSave($insert)) {
            $parentStructData = (new Query())
                ->from(BaseStructure::tableName())
                ->select(['parent_id', 'context_id'])
                ->where(['id' => $this->model_id])
                ->one();
            if (true === isset($parentStructData['parent_id'], $parentStructData['context_id'])) {
                $parentData = self::find()
                    ->select(['slug', 'url'])
                    ->where(['model_id' => $parentStructData['parent_id'], 'language_id' => $this->language_id])
                    ->asArray(true)
                    ->one();
                $parentSlug = empty($parentData['url'])
                    ? (empty($parentData['slug']) ? '' : trim($parentData['slug'], '/'))
                    : trim($parentData['url'], '/');
                $url = empty($parentSlug)
                    ? '/' . trim($this->slug, '/')
                    : '/' . $parentSlug . '/' . trim($this->slug, '/');
                $check = self::find()
                    ->select('id')
                    ->innerJoin(
                        BaseStructure::tableName(),
                        'model_id=' . BaseStructure::tableName() . '.id'
                    )
                    ->where([
                        'context_id' => $parentStructData['context_id'],
                        'language_id' => $this->language_id,
                        'url' => $url
                    ])
                    ->scalar();
                if (false !== $check) {
                    $this->addError('slug', Yii::t(
                        StructureModule::TRANSLATION_CATEGORY,
                        'Value \'{value}\' already in use, please, choose another!',
                        ['value' => $this->slug]
                    ));
                    return false;
                }
                $this->url = $url;
            }
            return true;
        } else {
            return false;
        }
    }


}