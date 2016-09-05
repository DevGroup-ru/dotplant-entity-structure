<?php

namespace DotPlant\EntityStructure\assets;

use yii\web\AssetBundle;
use yii\web\JqueryAsset;

/**
 * Class ContextBehaviorAsset
 *
 * @package DotPlant\EntityStructure\assets
 */
class ContextBehaviorAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public $sourcePath = '@DotPlant/EntityStructure/assets/dist';

    /**
     * @inheritdoc
     */
    public $js = [
        'js/context-change.js'
    ];

    /**
     * @inheritdoc
     */
    public $depends = [
        JqueryAsset::class,
    ];
}