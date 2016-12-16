<?php

namespace DotPlant\EntityStructure\helpers;

use yii\data\Pagination;

/**
 * Class PaginationHelper
 * @package DotPlant\EntityStructure\helpers
 */
class PaginationHelper
{
    /**
     * @var array
     */
    protected static $defaultParams = [
        'firstPageLabel' => false,
        'firstPageCssClass' => 'first',
        'lastPageLabel' => false,
        'lastPageCssClass' => 'last',
        'prevPageLabel' => '&lt;',
        'prevPageCssClass' => 'prev',
        'nextPageLabel' => '&gt;',
        'nextPageCssClass' => 'next',
        'activePageCssClass' => 'active',
        'disabledPageCssClass' => 'disabled',
        'maxButtonCount' => 5,
    ];

    /**
     * @var Pagination
     */
    protected static $pagination;

    /**
     * @var array
     */
    protected static $params;

    /**
     * Get a list of pages
     * @param Pagination $pagination
     * @param array $params
     * @return array
     */
    public static function getItems($pagination, $params = [])
    {
        $pageCount = $pagination->getPageCount();
        if ($pageCount < 2) {
            return [];
        }
        static::$pagination = $pagination;
        $items = [];
        $currentPage = $pagination->getPage();
        static::$params = array_merge(
            static::$defaultParams,
            $params
        );
        // first page
        $firstPageLabel = static::$params['firstPageLabel'] === true ? '1' : static::$params['firstPageLabel'];
        if ($firstPageLabel !== false) {
            $items[] = static::buildPageItem($firstPageLabel, 0, static::$params['firstPageCssClass'], $currentPage <= 0, false);
        }
        // prev page
        if (static::$params['prevPageLabel'] !== false) {
            if (($page = $currentPage - 1) < 0) {
                $page = 0;
            }
            $items[] = static::buildPageItem(static::$params['prevPageLabel'], $page, static::$params['prevPageCssClass'], $currentPage <= 0, false);
        }
        // internal pages
        list($beginPage, $endPage) = static::getPageRange($pagination, static::$params);
        for ($i = $beginPage; $i <= $endPage; ++$i) {
            $items[] = static::buildPageItem($i + 1, $i, null, false, $i == $currentPage);
        }
        // next page
        if (static::$params['nextPageLabel'] !== false) {
            if (($page = $currentPage + 1) >= $pageCount - 1) {
                $page = $pageCount - 1;
            }
            $items[] = static::buildPageItem(static::$params['nextPageLabel'], $page, static::$params['nextPageCssClass'], $currentPage >= $pageCount - 1, false);
        }
        // last page
        $lastPageLabel = static::$params['lastPageLabel'] === true ? $pageCount : static::$params['lastPageLabel'];
        if ($lastPageLabel !== false) {
            $items[] = static::buildPageItem($lastPageLabel, $pageCount - 1, static::$params['lastPageCssClass'], $currentPage >= $pageCount - 1, false);
        }
        return $items;
    }

    /**
     * Get a pagination range
     * @return array
     */
    protected static function getPageRange()
    {
        $currentPage = static::$pagination->getPage();
        $pageCount = static::$pagination->getPageCount();
        $beginPage = max(0, $currentPage - (int) (static::$params['maxButtonCount'] / 2));
        if (($endPage = $beginPage + static::$params['maxButtonCount'] - 1) >= $pageCount) {
            $endPage = $pageCount - 1;
            $beginPage = max(0, $endPage - static::$params['maxButtonCount'] + 1);
        }
        return [$beginPage, $endPage];
    }

    /**
     * Build a page link array
     * @param string $label
     * @param int $page
     * @param string $class
     * @param boolean $disabled
     * @param boolean $active
     * @return array
     */
    protected static function buildPageItem($label, $page, $class, $disabled, $active)
    {
        $classes = [$class];
        if ($active) {
            $classes[] = static::$params['activePageCssClass'];
        }
        if ($disabled) {
            $classes[] = static::$params['disabledPageCssClass'];
        }
        return [
            'label' => $label,
            'url' => static::$pagination->createUrl($page),
            'class' => implode(' ', $classes),
        ];
    }
}
