<?php

namespace FondOfSpryker\Zed\CategoryStoreDataImport\Business\Model\Reader;

use Spryker\Zed\CategoryDataImport\Business\Model\Reader\CategoryReaderInterface  as SprykerCategoryReaderInterface;
use Orm\Zed\Category\Persistence\SpyCategory;
use Orm\Zed\Category\Persistence\SpyCategoryNode;

interface CategoryReaderInterface extends SprykerCategoryReaderInterface
{
    /**
     * @param $categoryKey
     * @param $idStore
     *
     * @return int
     */
    public function getIdCategoryNodeByCategoryKeyAndIdStore($categoryKey, $idStore);
}
