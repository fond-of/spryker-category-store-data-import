<?php

namespace FondOfSpryker\Zed\CategoryStoreDataImport;

use Spryker\Zed\CategoryDataImport\CategoryDataImportConfig as SprykerCategoryDataImportConfig;

class CategoryStoreDataImportConfig extends SprykerCategoryDataImportConfig
{
    const IMPORT_TYPE_CATEGORY_WITH_STORE = 'category-with-store';

    /**
     * @return \Generated\Shared\Transfer\DataImporterConfigurationTransfer
     */
    public function getCategoryDataImporterConfiguration()
    {
        return $this->buildImporterConfiguration(
            $this->getDataImportRootPath() . 'category_with_store.csv',
            static::IMPORT_TYPE_CATEGORY_WITH_STORE
        );
    }
}
