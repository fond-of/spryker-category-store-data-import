<?php

namespace FondOfSpryker\Zed\CategoryStoreDataImport\Communication\Plugin;

use FondOfSpryker\Zed\CategoryStoreDataImport\CategoryStoreDataImportConfig;

use Spryker\Zed\CategoryDataImport\Communication\Plugin\CategoryDataImportPlugin as SprykerCategoryDataImportPlugin;

class CategoryStoreDataImportPlugin extends SprykerCategoryDataImportPlugin
{
    /**
     * @return string
     */
    public function getImportType()
    {
        return CategoryStoreDataImportConfig::IMPORT_TYPE_CATEGORY_WITH_STORE;
    }
}
