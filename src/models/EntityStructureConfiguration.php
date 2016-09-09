<?php

namespace DotPlant\EntityStructure\models;

use DevGroup\ExtensionsManager\models\BaseConfigurationModel;
use DotPlant\EntityStructure\components\StructureUrlRule;
use DotPlant\EntityStructure\StructureModule;
use Yii;

/**
 * Class EntityStructureConfiguration
 *
 * @package DotPlant\EntityStructure\models
 */
class EntityStructureConfiguration extends BaseConfigurationModel
{
    public function getModuleClassName()
    {
        return StructureModule::className();
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
            'defaultPageSize' => Yii::t('dotplant.entity.structure', 'Default Items Per Page'),
            'showHiddenInTree' => Yii::t('dotplant.entity.structure', 'Show Hidden Records In Tree'),
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
                        'dotplant.entity.structure' => [
                            'class' => 'yii\i18n\PhpMessageSource',
                            'basePath' => dirname(__DIR__) . DIRECTORY_SEPARATOR . 'messages',
                        ]
                    ]
                ],
                'urlManager' => [
                    'rules' => [
                        [
                            'class' => StructureUrlRule::class,
                        ],
                    ],
                ],
            ],
            'modules' => [
                'structure' => [
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
