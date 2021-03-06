<?php

namespace DotPlant\EntityStructure\actions;

use DevGroup\Multilingual\models\Context;
use DevGroup\TagDependencyHelper\NamingHelper;
use DotPlant\EntityStructure\models\BaseStructure;
use DotPlant\EntityStructure\models\Entity;
use Yii;
use yii\base\Action;
use yii\caching\TagDependency;
use yii\db\ActiveQuery;
use yii\db\Query;
use yii\helpers\ArrayHelper;
use yii\web\ForbiddenHttpException;
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
     * @param null $id
     * @param null $contextId
     *
     * @return array
     * @throws ForbiddenHttpException
     */
    public function run($id = null, $contextId = null)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        if (false === Yii::$app->user->can('dotplant-structure-view')) {
            throw new ForbiddenHttpException(Yii::t(
                'yii', 'You are not allowed to perform this action.'
            ));
        }
        /** @var BaseStructure | string $class */
        if (null === $current_selected_id = Yii::$app->request->get($this->querySelectedAttribute)) {
            $current_selected_id = Yii::$app->request->get($this->queryParentAttribute);
        }
        $checked = Yii::$app->request->get('checked', '');
        $cacheKey = "AdjacencyFullTreeData:{$this->cacheKey}:{$this->querySortOrder}:{$id}:{$contextId}"
            . Yii::$app->multilingual->language_id . $this->showHiddenInTree . $checked;
        if (false === empty($checked)) {
            $checked = explode(',', $checked);
        } else {
            $checked = [];
        }
        if (false === $result = Yii::$app->cache->get($cacheKey)) {
            $contexts = call_user_func([Yii::$app->multilingual->modelsMap['Context'], 'getListData']);
            /** @var ActiveQuery $query */
            $parentId = ('#' == $id) ? null : $id;
            $query = BaseStructure::find()
                ->where(['parent_id' => $parentId])
                ->orderBy([
                    $this->contextIdAttribute => SORT_ASC,
                    $this->querySortOrder => SORT_ASC
                ]);
            if (count($this->whereCondition) > 0) {
                $query = $query->where($this->whereCondition);
            }
            if ($contextId != 'all') {
                $query->andWhere([$this->contextIdAttribute => $contextId]);
            }

            $entityId = (null !== $this->className) ? Entity::getEntityIdForClass($this->className) : null;
            if (null === $rows = $query->asArray()->all()) {
                return [];
            }
            $result = [];
            $hidden = [];
            $roots = BaseStructure::find()->select(['id'])->where(['is_deleted' => 1])->column();
            $hidden += $roots;
            foreach ($roots as $id) {
                self::treeGoDown($id, $rows, $hidden);
            }
            foreach ($rows as $row) {
                if ((int)$row['expand_in_tree'] === 1) {
                    $c = (new Query())
                        ->from(BaseStructure::tableName())
                        ->where(['parent_id' => $row['id']])
                        ->select('id')
                        ->count();
                    $children = $c > 0;
                } else {
                    $children = false;
                }
                $item = [];
                if (true === in_array($row[$this->modelIdAttribute], $hidden)) {
                    if (true === $this->showHiddenInTree) {
                        self::setState($item, ['disabled' => true]);
                    } else {
                        continue;
                    }
                }
                $text = ($row[$this->modelParentAttribute] > 0)
                    ? $row['defaultTranslation'][$this->modelLabelAttribute]
                    : ($row['defaultTranslation'][$this->modelLabelAttribute]
//                        . " ({$contexts[$row[$this->contextIdAttribute]]})"
                    );
                $item += [
                    'id' => $row[$this->modelIdAttribute],
                    'parent' => ($row[$this->modelParentAttribute] > 0) ? $row[$this->modelParentAttribute] : '#',
                    'text' => $text,
                    'children' => $children,
                    'a_attr' => [
                        'data-id' => $row[$this->modelIdAttribute],
                        'data-parent_id' => $row[$this->modelParentAttribute],
                        'data-context_id' => $row[$this->contextIdAttribute],
                        'data-entity_id' => $row['entity_id'],
                    ],
                ];
                if (null !== $entityId && $entityId != $row['entity_id']) {
                    self::setState($item, ['checkbox_disabled' => true]);
                }
                if (true === in_array($row[$this->modelIdAttribute], $checked)) {
                    self::setState($item, ['opened' => true, 'checked' => true, 'selected' => true]);
                }
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
                        NamingHelper::getCommonTag(BaseStructure::class),
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
     * @param array $item
     * @param array $value
     */
    private static function setState(&$item, $value)
    {
        if (true === isset($item['state'])) {
            $item['state'] = array_merge($item['state'], $value);
        } else {
            $item['state'] = $value;
        }
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
