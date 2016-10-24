<?php

namespace DotPlant\EntityStructure\commands;

use DotPlant\EntityStructure\models\BaseStructure;
use yii\console\Controller;
use yii\db\Query;

/**
 * Class StructureController
 * @package DotPlant\EntityStructure\commands
 */
class StructureController extends Controller
{

    /**
     * @var array
     */
    private $elements = [];

    /**
     * Check and regenerate compiled urls
     */
    public function actionRegenerateSlugs()
    {
        $this->elements = (new Query())
            ->select(['id', 'parent_id'])
            ->from(BaseStructure::tableName())
            ->orderBy(['parent_id' => SORT_ASC])
            ->indexBy('id')
            ->all();

        foreach ($this->elements as &$element) {
            if ((int)$element['parent_id'] === 0) {
                $this->setTreeUrl($element);
            }
        }
    }

    /**
     * @param $currentRow
     */
    private function setTreeUrl(&$currentRow)
    {
        $translations = (new Query)
            ->select(['model_id', 'language_id', 'slug', 'url'])
            ->from(BaseStructure::getTranslationTableName())
            ->where(['model_id' => $currentRow['id']])
            ->indexBy('language_id')
            ->all();
        foreach ($translations as $languageId => $translation) {
            $currentRow['translations'] = [];
            try {
                $url = (int)$currentRow['parent_id'] > 0
                    ? $this->elements[$currentRow['parent_id']]['translations'][$languageId] . '/' . $translation['slug']
                    : $translation['slug'];
                if ($url != $translation['url']) {
                    echo "Ok: {$currentRow['id']}\n";
                    BaseStructure::getDb()->createCommand()->update(
                        BaseStructure::getTranslationTableName(),
                        ['url' => $url],
                        ['model_id' => $currentRow['id'], 'language_id' => $languageId]
                    )->execute();
                }
                $currentRow['translations'][$languageId] = $url;
            } catch (\Exception $e) {
                // do nothing
            }
        }

        foreach ($this->elements as &$row) {
            if ((int)$row['parent_id'] === (int)$currentRow['id']) {
                $this->setTreeUrl($row);
            }
        }

    }

}
