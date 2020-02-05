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

    /**
     * @param string $categoryKey
     * @param int $idLocale
     *
     * @throws \FondOfSpryker\Zed\CategoryStoreDataImport\Business\Exception\CategoryByKeyAndStoreNotFoundException
     *
     * @return string
     */
    public function getParentUrlByCategoryKeyLocaleAndStore($categoryKey, $idLocale, $idStore);
}
