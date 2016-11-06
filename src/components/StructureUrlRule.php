<?php

namespace DotPlant\EntityStructure\components;

use DevGroup\TagDependencyHelper\LazyCacheTrait;
use DevGroup\TagDependencyHelper\NamingHelper;
use DotPlant\EntityStructure\models\BaseStructure;
use DotPlant\EntityStructure\models\Entity;
use Faker\Provider\Base;
use yii;
use yii\base\Object;
use yii\helpers\ArrayHelper;
use yii\web\Request;
use yii\web\UrlManager;
use yii\web\UrlRuleInterface;

class StructureUrlRule extends Object implements UrlRuleInterface
{
    const ROUTE = 'universal/show';
    const MAIN_PAGE_URL = '~mainpage~';

    protected $allowedUrlParams = ['page', 'sort'];

    /**
     * Parses the given request and returns the corresponding route and parameters.
     *
     * @param UrlManager $manager the URL manager
     * @param Request $request the request component
     *
     * @return array|boolean the parsing result. The route and the parameters are returned as an array.
     * If false, it means this rule cannot be used to parse this path info.
     */
    public function parseRequest($manager, $request)
    {
        // @todo: Add caching
        $pathInfo = $request->getPathInfo();
        if ($pathInfo === '') {
            $pathInfo = self::MAIN_PAGE_URL;
        }
        $cacheKey = "StructureUrlCache:$pathInfo";
        $cached = Yii::$app->cache->get($cacheKey);
        if ($cached === false) {
            $parts = explode('/', preg_replace('#/+#', '/', $pathInfo));
            /** @var BaseStructure $structure */
            $structure = null;
            $lastStructure = null;
            $route = self::ROUTE;
            $routeParams = [];
            foreach ($parts as $index => $slug) {
                $structure = BaseStructure::find()
                    ->select(['id', 'entity_id'])
                    ->where(
                        [
                            // @todo: Use SQL-index
                            BaseStructure::getTranslationTableName() . '.slug' => $slug,
                            BaseStructure::getTranslationTableName() . '.is_active' => true,
                            'context_id' => Yii::$app->multilingual->context_id,
                            'is_deleted' => false,
                            'parent_id' => $structure === null ? null : $structure->id,
                        ]
                    )
                    ->one();
                if ($structure === null) {
                    if (
                        $lastStructure === null
                        || ($entity = Entity::loadModel($lastStructure->entity_id)) === null
                        || count($slugs = array_slice($parts, $index)) < 1
                    ) {
                        return false;
                    }
                    foreach ($entity->route_handlers as $handlerDefinition) {
                        $handler = Yii::createObject($handlerDefinition);
                        $result = $handler->parseUrl($lastStructure->id, $slugs);
                        if ($result['isHandled']) {
                            $routeParams = ArrayHelper::merge(
                                $routeParams,
                                $result['routeParams']
                            );
                            if (isset($result['route'])) {
                                $route = $result['route'];
                            }
                            $slugs = $result['slugs'];
                            if ($result['preventNextHandler'] || count($result['slugs']) === 0) {
                                break;
                            }
                        }
                    }
                    if (count($slugs) > 0) {
                        return false;
                    }
                } else {
                    $lastStructure = $structure;
                }
            }
            $routeParams = ArrayHelper::merge(
                $routeParams,
                [
                    'entities' => [
                        BaseStructure::class => [
                            $lastStructure->id,
                        ]
                    ],
                ]
            );
            foreach ($this->allowedUrlParams as $allowedUrlParam) {
                if ($request->get($allowedUrlParam) !== null) {
                    $routeParams[$allowedUrlParam] = $request->get($allowedUrlParam);
                }
            }
            return [
                $route,
                $routeParams
            ];
        }
        return false;
    }

    /**
     * Creates a URL according to the given route and parameters.
     *
     * @param UrlManager $manager the URL manager
     * @param string $route the route. It should not have slashes at the beginning or the end.
     * @param array $params the parameters
     *
     * @return string|boolean the created URL, or false if this rule cannot be used for creating this URL.
     */
    public function createUrl($manager, $route, $params)
    {
        // @todo: implement all available functional
        if (
            $route !== self::ROUTE
            || !isset($params['entities'][BaseStructure::class])
            || count($params['entities'][BaseStructure::class]) !== 1
        ) {
            return false;
        }
        $languageId = isset($params['languageId']) ? $params['languageId'] : Yii::$app->multilingual->language_id;
        /** @var yii\caching\Cache|LazyCacheTrait $lazyCache */
        $lazyCache = Yii::$app->cache;
        $structureId = $params['entities'][BaseStructure::class];

        $structure = $lazyCache->lazy(
            function() use($structureId, $languageId) {
                return (new yii\db\Query()) // it's released via Query to prevent auto-attaching of language id
                ->select(['url', 'entity_id'])
                    ->from(BaseStructure::tableName())
                    ->where(['id' => $structureId, 'language_id' => $languageId])
                    ->innerJoin(BaseStructure::getTranslationTableName(), 'id = model_id')
                    ->one();
            },
            "createUrl:StructureRow:$languageId:" . implode(',', (array) $structureId),
            86400,
            new yii\caching\TagDependency([
                'tags' => [
                    NamingHelper::getObjectTag(Base::class, $structureId)
                ]
            ])
        );

        if ($structure === false || ($entity = Entity::loadModel($structure['entity_id'])) === null) {
            return false;
        }
        foreach ($entity->route_handlers as $handlerDefinition) {
            $handler = Yii::createObject($handlerDefinition);
            $result = $handler->createUrl($route, $params, $structure['url']);
            if ($result['isHandled']) {
                $structure['url'] = $result['url'];
                if ($result['preventNextHandler']) {
                    break;
                }
            }
        }
        $getParams = [];
        foreach ($this->allowedUrlParams as $allowedUrlParam) {
            if (isset($params[$allowedUrlParam])) {
                $getParams[$allowedUrlParam] = $params[$allowedUrlParam];
            }
        }
        return ($structure['url'] !== self::MAIN_PAGE_URL ? $structure['url'] : '') . (count($getParams) > 0 ? '?' . http_build_query($getParams) : '');
    }
}
