<?php

namespace DotPlant\EntityStructure\interfaces;

/**
 * Interface AdditionalRouteHandlerInterface
 * @package DotPlant\EntityStructure\interfaces
 */
interface AdditionalRouteHandlerInterface
{
    /**
     * @param int $structureId
     * @param string[] $slugs
     * @return array in the next format
     *  [
     *      'isHandled' => true, // is handler processed successfully?
     *      'preventNextHandler' => false, // prevent next handler executing
     *      'route' => 'store/goods/show', // new action route
     *      'routeParams' => [], // additional route params
     *      'slugs' => ['filter-value1', 'filter-value2'], // leftover slugs
     *  ]
     */
    public function parse($structureId, $slugs);
}
