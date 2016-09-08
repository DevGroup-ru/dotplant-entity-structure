<?php

namespace DotPlant\EntityStructure\interfaces;

/**
 * Interface PermissionsInterface
 *
 * @package DotPlant\EntityStructure\interfaces
 */
interface PermissionsInterface
{
    /**
     * Returns array of applicable access rules for current BaseStructure heir type
     * should be like:
     *  [
     *      'view' => 'permission-name-to-view',
     *      'edit' => 'permission-name-to-edit',
     *      'delete' => 'permission-name-to-delete',
     *      'extra-permission1' => 'extra-permission1-name'
     *  ]
     *
     * If you need to have an extra permissions then you likely need to create your own Action to perform custom actions
     *
     * @return array
     */
    public static function getAccessRules();
}