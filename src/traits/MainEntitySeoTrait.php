<?php

namespace DotPlant\EntityStructure\traits;

trait MainEntitySeoTrait
{
    /**
     * @inheritdoc
     */
    public function getSeoTitle()
    {
        return $this->defaultTranslation->title;
    }

    /**
     * @inheritdoc
     */
    public function getSeoMetaDescription()
    {
        return $this->defaultTranslation->meta_description;
    }
}
