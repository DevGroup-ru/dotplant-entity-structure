<?php

namespace DotPlant\EntityStructure\interfaces;

/**
 * Interface MainEntitySeoInterface
 * @package DotPlant\EntityStructure\interfaces
 */
interface MainEntitySeoInterface
{
    /**
     * @return string
     */
    public function getSeoTitle();

    /**
     * @return string
     */
    public function getSeoMetaDescription();
}
