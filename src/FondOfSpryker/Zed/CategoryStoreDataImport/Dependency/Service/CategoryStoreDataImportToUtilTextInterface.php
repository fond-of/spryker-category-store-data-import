<?php

namespace FondOfSpryker\Zed\CategoryStoreDataImport\Dependency\Service;

interface CategoryStoreDataImportToUtilTextInterface
{
    /**
     * @param string $value
     *
     * @return string
     */
    public function generateSlug(string $value): string;
}