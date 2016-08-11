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
    const TRANSLATION_CATEGORY = 'dotplant.entity.structure';

    /** @var int Default value to be used in all child modules */
    public $defaultPageSize = 10;

    /**
     * @return self Module instance in application
     */
    public static function module()
    {
        $module = Yii::$app->getModule('entityStructure');
        if ($module === null) {
            $module = new self('entityStructure');
        }
        return $module;
    }
}