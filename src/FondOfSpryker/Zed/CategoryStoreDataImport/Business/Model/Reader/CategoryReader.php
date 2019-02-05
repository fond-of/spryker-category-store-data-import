<?php

namespace FondOfSpryker\Zed\CategoryStoreDataImport\Business\Model\Reader;

use ArrayObject;
use FondOfSpyker\Zed\CategoryStoreDataImport\Business\Exception\CategoryByKeyAndStoreNotFoundException;
use Orm\Zed\Category\Persistence\SpyCategoryQuery;
use Spryker\Zed\CategoryDataImport\Business\Model\Reader\CategoryReader as SprykerCategoryReader;

class CategoryReader extends SprykerCategoryReader implements CategoryReaderInterface
{
    const ID_STORE_CATEGORY = 'fk_store';

    /**
     * @var \ArrayObject
     */
    protected $categoryKeys;

    /**
     * @var \ArrayObject
     */
    protected $categoryUrls;

    /**
     * @var \ArrayObject
     */
    protected $storeCategories;

    public function __construct()
    {
        $this->storeCategories = [];
        $this->categoryUrls = new ArrayObject();
    }

    /**
     * @param string $categoryKey
     *
     * @throws \FondOfSpyker\Zed\CategoryStoreDataImport\Business\Exception\CategoryByKeyAndStoreNotFoundException
     *
     * @return int
     */
    public function getIdCategoryNodeByCategoryKeyAndIdStore($categoryKey, $idStore)
    {
        if (count($this->storeCategories) === 0) {
            $this->loadStoreCategories();
        }

        if (!$this->storeCategories[$idStore][$categoryKey]) {
            throw new CategoryByKeyAndStoreNotFoundException(sprintf(
                'Category by key "%s" for the store "%s" not found. Maybe you have a typo in the category key.',
                $categoryKey,
                $idStore
            ));
        }

        return $this->storeCategories[$idStore][$categoryKey][static::ID_CATEGORY_NODE];
    }

    /**
     * @return void
     */
    protected function loadStoreCategories()
    {
        $categoryEntityCollection = SpyCategoryQuery::create()
            ->joinWithNode()
            ->find();

        foreach ($categoryEntityCollection as $categoryEntity) {
            $this->storeCategories[$categoryEntity->getFkStore()][$categoryEntity->getCategoryKey()] = [
                static::ID_CATEGORY => $categoryEntity->getIdCategory(),
                static::ID_CATEGORY_NODE => $categoryEntity->getNodes()->getFirst()->getIdCategoryNode(),
            ];
        }
    }
}
