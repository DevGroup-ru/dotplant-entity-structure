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
     * Check and regenerate compiled urls
     */
    public function actionRegenerateSlugs()
    {
        $tree = (new Query())
            ->select(['id', 'parent_id'])
            ->from(BaseStructure::tableName())
            ->orderBy(['parent_id' => SORT_ASC])
            ->indexBy('id')
            ->all();
        foreach ($tree as $id => $row) {
            $translations = (new Query)
                ->select(['model_id', 'language_id', 'slug', 'url'])
                ->from(BaseStructure::getTranslationTableName())
                ->where(['model_id' => $id])
                ->indexBy('language_id')
                ->all();
            foreach ($translations as $languageId => $translation) {
                $tree[$id]['translations'] = [];
                try {
                    $url = (int)$row['parent_id'] > 0
                        ? $tree[$row['parent_id']]['translations'][$languageId] . '/' . $translation['slug']
                        : $translation['slug'];
                    if ($url != $translation['url']) {
                        echo "Ok: {$id}\n";
                        BaseStructure::getDb()->createCommand()->update(
                            BaseStructure::getTranslationTableName(),
                            ['url' => $url],
                            ['model_id' => $id, 'language_id' => $languageId]
                        );
                    }
                    $tree[$id]['translations'][$languageId] = $url;
                } catch (\Exception $e) {
                    // do nothing
                }

            }
        }
    }
}
