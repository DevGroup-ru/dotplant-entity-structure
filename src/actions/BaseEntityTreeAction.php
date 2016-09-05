<?php

namespace DotPlant\EntityStructure\actions;

use DevGroup\Multilingual\models\Context;
use DevGroup\TagDependencyHelper\NamingHelper;
use DotPlant\EntityStructure\models\BaseStructure;
use DotPlant\EntityStructure\models\Entity;
use Yii;
use yii\base\Action;
use yii\base\InvalidConfigException;
use yii\caching\TagDependency;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;
use yii\web\Response;

/**
 * Class BaseEntityTreeAction
 * Works with multilingual and provides ability to keep nodes closed by default and lazy load children via ajax
 *
 * @package DotPlant\EntityStructure\actions
 */
class BaseEntityTreeAction extends Action
{

    public $className = null;

    public $modelIdAttribute = 'id';

    public $modelLabelAttribute = 'name';

    public $modelParentAttribute = 'parent_id';

    public $varyByTypeAttribute = null;

    public $queryParentAttribute = 'id';

    public $querySortOrder = 'sort_order';

    public $querySelectedAttribute = 'selected_id';

    public $contextIdAttribute = 'context_id';

    public $showHiddenInTree = null;
    /**
     * Additional conditions for retrieving tree(ie. don't display nodes marked as deleted)
     * @var array
     */
    public $whereCondition = [];

    /**
     * Cache key prefix. Should be unique if you have multiple actions with different $whereCondition
     * @var string
     */
    public $cacheKey = 'FullTree';

    /**
     * Cache lifetime for the full tree
     * @var int
     */
    public $cacheLifeTime = 86400;

    /**
     * @throws InvalidConfigException
     */
    public function init()
    {
        if (true === empty($this->className)) {
            throw new InvalidConfigException("Model name should be set in controller actions");
        }
        if (false === class_exists($this->className)) {
            throw new InvalidConfigException("Model class does not exists");
        }
    }

    public function run()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        /** @var BaseStructure | string $class */
        $class = $this->className;
        if (null === $current_selected_id = Yii::$app->request->get($this->querySelectedAttribute)) {
            $current_selected_id = Yii::$app->request->get($this->queryParentAttribute);
        }
        $entityId = Entity::getEntityIdForClass($class);
        $cacheKey = "AdjacencyFullTreeData:{$this->cacheKey}:{$class}:{$this->querySortOrder}:{$entityId}"
            . Yii::$app->multilingual->language_id . $this->showHiddenInTree;

        if (false === $result = Yii::$app->cache->get($cacheKey)) {
            $contexts = ArrayHelper::map(Context::find()->all(), 'id', 'name');
            /** @var ActiveQuery $query */
            $query = $class::find()
                ->where(['entity_id' => $entityId])
                ->orderBy([
                    $this->contextIdAttribute => SORT_ASC,
                    $this->querySortOrder => SORT_ASC
                ]);

            if (count($this->whereCondition) > 0) {
                $query = $query->where($this->whereCondition);
            }

            if (null === $rows = $query->asArray()->all()) {
                return [];
            }

            $result = [];
            $hidden = [];
            $roots = $class::find()->select(['id'])->where(['is_deleted' => 1])->column();
            $hidden += $roots;
            foreach ($roots as $id) {
                self::treeGoDown($id, $rows, $hidden);
            }
            foreach ($rows as $row) {
                $item = [];
                if (true === in_array($row[$this->modelIdAttribute], $hidden)) {
                    if (true === $this->showHiddenInTree) {
                        $item['state'] = ['disabled' => true];
                    } else {
                        continue;
                    }
                }
                $text = ($row[$this->modelParentAttribute] > 0)
                    ? $row['defaultTranslation'][$this->modelLabelAttribute]
                    : ($row['defaultTranslation'][$this->modelLabelAttribute]
                        . " ({$contexts[$row[$this->contextIdAttribute]]})");
                $item += [
                    'id' => $row[$this->modelIdAttribute],
                    'parent' => ($row[$this->modelParentAttribute] > 0) ? $row[$this->modelParentAttribute] : '#',
                    'text' => $text,
                    'a_attr' => [
                        'data-id' => $row[$this->modelIdAttribute],
                        'data-parent_id' => $row[$this->modelParentAttribute],
                        'data-context_id' => $row[$this->contextIdAttribute],
                    ],
                ];
                if (null !== $this->varyByTypeAttribute) {
                    $item['type'] = $row[$this->varyByTypeAttribute];
                }

                $result[$row[$this->modelIdAttribute]] = $item;
            }

            Yii::$app->cache->set(
                $cacheKey,
                $result,
                86400,
                new TagDependency([
                    'tags' => [
                        NamingHelper::getCommonTag($class),
                    ],
                ])
            );
        }

        if (array_key_exists($current_selected_id, $result)) {
            $result[$current_selected_id] = array_merge(
                $result[$current_selected_id],
                ['state' => ['opened' => true, 'selected' => true]]
            );
        }

        return array_values($result);
    }

    /**
     * Recursively finds all children of node with given id
     *
     * @param $id
     * @param $data
     * @param $hidden
     */
    private static function treeGoDown($id, $data, &$hidden)
    {
        foreach ($data as $row) {
            if ($row['parent_id'] == $id) {
                if (false === in_array($row['id'], $hidden)) {
                    $hidden[] = $row['id'];
                }
                self::treeGoDown($row['id'], $data, $hidden);
            }
        }
    }
}
