<?php

namespace DotPlant\EntityStructure;

use yii\base\Module;
use Yii;

/**
 * Class StructureModule
 *
 * @package DotPlant\EntityStructure
 */
class StructureModule extends Module
{
    /** @var int Default value to be used in all child modules */
    public $defaultPageSize = 10;

    /**
     * Show hidden records in tree
     *
     * @var bool
     */
    public $showHiddenInTree = false;

    /**
     * @return self Module instance in application
     */
    public static function module()
    {
        $module = Yii::$app->getModule('entityStructure');
        if ($module === null) {
            $module = Yii::createObject(self::class, ['entityStructure']);
        }
        return $module;
    }
}