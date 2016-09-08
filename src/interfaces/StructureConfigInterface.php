<?php

namespace DotPlant\EntityStructure\interfaces;

/**
 * Interface StructureConfigInterface
 *
 * @package DotPlant\EntityStructure\interfaces
 */
interface StructureConfigInterface
{
    /**
     * Returns string to be user as title for entity edit page
     *
     * @return string
     */
    public function getEditPageTitle();

    /**
     * Returns actions to be injected into whole EntityManageController list
     *
     * @return array
     */
    public static function prepareActionsInjection();

    /**
     * Returns view file to be rendered for entity edit action
     *
     * @return string
     */
    public static function getEditViewFile();

    /**
     * Returns module specific breadcrumbs array
     *
     * @return array
     */
    public static function getModuleBreadCrumbs();

    /**
     * Returns array of additional menu items
     *
     * @return array
     */
    public static function jsTreeContextMenuActions();

    /**
     * Returns array of all jstree context menu actions with showing conditions
     *
     * @param array $default
     * @return array
     */
    public static function getContextMenu($default = []);

    /**
     * @return array
     */
    public function additionalGridButtons();
}
