<?php

namespace DotPlant\EntityStructure\models;


use DevGroup\Entity\traits\EntityTrait;
use DevGroup\Entity\traits\SeoTrait;
use DotPlant\EntityStructure\StructureModule;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use Yii;
use yii\db\Expression;
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

    /** @var int field to detect necessity of url recompiling */
    public $parentParentId;

    /** @var int field to detect necessity of url recompiling */
    public $parentContextId;

    /** @var string field to detect necessity of url recompiling */
    public $oldSlug;

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
        ['url', 'validateUrl', 'skipOnEmpty' => false, 'skipOnError' => false],
    ];

    protected function getAttributeLabels()
    {
        return [
            'is_active' => Yii::t('dotplant.entity.structure', 'Is Active'),
            'name' => Yii::t('dotplant.entity.structure', 'Name'),
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
     * Validates url to be unique among other in one context and in one language range
     *
     * @param $attribute
     * @param $params
     */
    public function validateUrl($attribute, $params)
    {
        $parentParentId = (null === $this->structure) ? 0 : (int)$this->structure->parent_id;
        $parentContextId = (null === $this->structure) ? 0 : (int)$this->structure->context_id;
        if (
            $this->slug != $this->oldSlug
            || $this->parentParentId !== $parentParentId
            || $this->parentContextId !== $parentContextId
        ) {
            $url = $this->compileUrl();
            $query = self::find()
                ->select('url')
                ->innerJoin(
                    BaseStructure::tableName(),
                    'model_id = ' . BaseStructure::tableName() . '.id'
                )
                ->where([
                    'language_id' => $this->language_id,
                    'context_id' => $this->parentContextId,
                    'url' => $url
                ]);
            if (false === $this->isNewRecord) {
                $query->andWhere(['not', ['model_id' => $this->model_id]]);
            }
            $isset = $query->scalar();
            if (false !== $isset) {
                $this->addError('slug', Yii::t(
                    'dotplant.entity.structure',
                    'Value \'{value}\' already in use, please, choose another!',
                    ['value' => $this->slug]
                ));
            }
            $this->url = $url;
        }
    }

    /**
     * Compiles url
     *
     * @return string
     */
    private function compileUrl()
    {
        if (false === empty($this->parentParentId)) {
            $parentData = self::find()
                ->select(['slug', 'url'])
                ->where(['model_id' => $this->parentParentId, 'language_id' => $this->language_id])
                ->asArray(true)
                ->one();
            $parentSlug = empty($parentData['url'])
                ? (empty($parentData['slug']) ? '' : trim($parentData['slug'], '/'))
                : trim($parentData['url'], '/');
            $url = empty($parentSlug)
                ? '/' . trim($this->slug, '/')
                : '/' . $parentSlug . '/' . trim($this->slug, '/');
        } else {
            $url = '/' . trim($this->slug, '/');
        }
        return $url;
    }

    /**
     * @inheritdoc
     */
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);
        if (
            false === $insert
            && (false === empty($changedAttributes['slug']) || false === empty($changedAttributes['url']))
        ) {
            $next = (new Query)
                ->from(BaseStructure::tableName())
                ->select('id')
                ->where(['parent_id' => $this->model_id])
                ->column();
            while (false === empty($next)) {
                StructureTranslation::updateAll(
                    ['url' => new Expression("CONCAT('{$this->url}', TRIM(LEADING '{$changedAttributes['url']}' FROM url))")],
                    ['model_id' => $next, 'language_id' => $this->language_id]
                );
                $next = (new Query)
                    ->from(BaseStructure::tableName())
                    ->select('id')
                    ->where(['parent_id' => $next])
                    ->column();
            }
        }
    }
}
