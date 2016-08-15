<?php

namespace DotPlant\EntityStructure\models;

use DevGroup\ExtensionsManager\models\BaseConfigurationModel;
use DotPlant\EntityStructure\StructureModule;
use Yii;

/**
 * Class EntityStructureConfiguration
 *
 * @package DotPlant\EntityStructure\models
 */
class EntityStructureConfiguration extends BaseConfigurationModel
{
    /**
     * @param array $config
     */
    public function __construct($config = [])
    {
        $attributes = [
            'defaultPageSize',
            'showHiddenInTree'
        ];
        parent::__construct($attributes, $config);
        /** @var StructureModule $module */
        $module = StructureModule::module();
        $this->defaultPageSize = $module->defaultPageSize;
        $this->showHiddenInTree = $module->showHiddenInTree;
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['defaultPageSize'], 'integer'],
            [['showHiddenInTree'], 'boolean']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'defaultPageSize' => Yii::t(StructureModule::TRANSLATION_CATEGORY, 'Default Items Per Page'),
            'showHiddenInTree' => Yii::t(StructureModule::TRANSLATION_CATEGORY, 'Show Hidden Records In Tree'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function webApplicationAttributes()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function consoleApplicationAttributes()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function commonApplicationAttributes()
    {
        return [
            'components' => [
                'i18n' => [
                    'translations' => [
                        StructureModule::TRANSLATION_CATEGORY => [
                            'class' => 'yii\i18n\PhpMessageSource',
                            'basePath' => dirname(__DIR__) . DIRECTORY_SEPARATOR . 'messages',
                        ]
                    ]
                ],
            ],
            'modules' => [
                'entityStructure' => [
                    'class' => StructureModule::class,
                    'defaultPageSize' => $this->defaultPageSize,
                    'showHiddenInTree' => (bool)$this->showHiddenInTree
                ]
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function appParams()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function aliases()
    {
        return [
            '@DotPlant/EntityStructure' => realpath(dirname(__DIR__)),
        ];
    }
}
