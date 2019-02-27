<?php

namespace FondOfSpryker\Zed\CategoryStoreDataImport\Business\Model\Reader;

use Spryker\Zed\CategoryDataImport\Business\Model\Reader\CategoryReaderInterface  as SprykerCategoryReaderInterface;

interface CategoryReaderInterface extends SprykerCategoryReaderInterface
{
    /**
     * @param string $categoryKey
     * @param int $idStore
     *
     * @return int
     */
    public function getIdCategoryNodeByCategoryKeyAndIdStore($categoryKey, $idStore);
}
