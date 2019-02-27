<?php

namespace FondOfSpryker\Zed\CategoryStoreDataImport\Business\Model\Reader;

use FondOfSpryker\Zed\CategoryStoreDataImport\Business\Exception\CategoryByKeyAndStoreNotFoundException;
use Orm\Zed\Category\Persistence\SpyCategory;
use Orm\Zed\Category\Persistence\SpyCategoryNode;
use Orm\Zed\Category\Persistence\SpyCategoryQuery;
use Orm\Zed\Url\Persistence\SpyUrlQuery;
use Spryker\Shared\Log\LoggerTrait;
use Spryker\Zed\CategoryDataImport\Business\Model\Reader\CategoryReader as SprykerCategoryReader;

class CategoryReader extends SprykerCategoryReader implements CategoryReaderInterface
{
    use LoggerTrait;

    const ID_STORE = 'fk_store';

    /**
     * @var array
     */
    protected $categoryKeys;

    /**
     * @var array
     */
    protected $categoryUrls;

    /**
     * CategoryReader constructor.
     */
    public function __construct()
    {
        $this->categoryKeys = [];
        $this->categoryUrls = [];
    }

    /**
     * @param \Orm\Zed\Category\Persistence\SpyCategory $categoryEntity
     * @param \Orm\Zed\Category\Persistence\SpyCategoryNode $categoryNodeEntity
     *
     * @return void
     */
    public function addCategory(SpyCategory $categoryEntity, SpyCategoryNode $categoryNodeEntity)
    {
        $this->categoryKeys[$categoryEntity->getFkStore()][$categoryEntity->getCategoryKey()] = [
            static::ID_CATEGORY => $categoryEntity->getIdCategory(),
            static::ID_CATEGORY_NODE => $categoryNodeEntity->getIdCategoryNode(),
        ];

        $urls = [];
        $categoryNodeEntityCollection = $categoryEntity->getNodes();
        foreach ($categoryNodeEntityCollection as $categoryNode) {
            foreach ($categoryNode->getSpyUrls() as $urlEntity) {
                $urls[] = [
                    static::ID_LOCALE => $urlEntity->getFkLocale(),
                    static::ID_STORE => $urlEntity->getFkStore(),
                    static::URL => $urlEntity->getUrl(),
                ];
            }
        }

        $this->categoryUrls[$categoryEntity->getFkStore()][$categoryEntity->getCategoryKey()] = $urls;
    }

    /**
     * @param string $categoryKey
     *
     * @throws \FondOfSpryker\Zed\CategoryStoreDataImport\Business\Exception\CategoryByKeyAndStoreNotFoundException
     *
     * @return int
     */
    public function getIdCategoryNodeByCategoryKeyAndIdStore($categoryKey, $idStore)
    {
        if (count($this->categoryKeys) === 0) {
            $this->loadCategoryKeys();
        }
        
        if (!array_key_exists($idStore, $this->categoryKeys) || !array_key_exists($categoryKey, $this->categoryKeys[$idStore])) {
            throw new CategoryByKeyAndStoreNotFoundException(sprintf(
                'Category by key "%s" for the store "%s" not found. Maybe you have a typo in the category key.',
                $categoryKey,
                $idStore
            ));
        }

        return $this->categoryKeys[$idStore][$categoryKey][static::ID_CATEGORY_NODE];
    }

    /**
     *
     * @return void
     */
    protected function loadCategoryKeys()
    {
        $categoryEntityCollection = SpyCategoryQuery::create()
            ->joinWithNode()
            ->find();
        
        foreach ($categoryEntityCollection as $categoryEntity) {
            $this->categoryKeys[$categoryEntity->getFkStore()][$categoryEntity->getCategoryKey()] = [
                static::ID_CATEGORY => $categoryEntity->getIdCategory(),
                static::ID_CATEGORY_NODE => $categoryEntity->getNodes()->getFirst()->getIdCategoryNode(),
            ];
        }
    }

    /**
     * @param string $categoryKey
     * @param int $idLocale
     *
     * @throws \FondOfSpryker\Zed\CategoryStoreDataImport\Business\Exception\CategoryByKeyAndStoreNotFoundException
     *
     * @return string
     */
    public function getParentUrlByCategoryKeyLocaleAndStore($categoryKey, $idLocale, $idStore)
    {
        if (count($this->categoryUrls) === 0) {
            $this->loadCategoryUrls();
        }

        if (!array_key_exists($idStore, $this->categoryUrls) || !array_key_exists($categoryKey, $this->categoryUrls[$idStore])) {
            throw new CategoryByKeyAndStoreNotFoundException(sprintf(
                'Category url key "%s" for the category and store was not found. Maybe you have a typo in the category key.',
                $categoryKey
            ));
        }

        foreach ($this->categoryUrls[$idStore][$categoryKey] as $categoryUrl) {
            if ($categoryUrl[static::ID_LOCALE] === $idLocale) {
                return $categoryUrl[static::URL];
            }
        }

        throw new CategoryByKeyAndStoreNotFoundException(sprintf(
            'Category url key "%s" for idLocale "%s" and idStore "%s" not found.',
            $categoryKey,
            $idLocale,
            $idStore
        ));
    }

    /**
     * @return void
     */
    protected function loadCategoryUrls()
    {
        $urlEntityCollection = SpyUrlQuery::create()->filterByFkResourceCategorynode(null, Criteria::ISNOTNULL)->find();

        foreach ($urlEntityCollection as $urlEntity) {
            $categoryEntity = $urlEntity->getSpyCategoryNode()->getCategory();
            if (!array_key_exists($categoryEntity->getFkStore(), $this->categoryUrls) || !array_key_exists($categoryEntity->getCategoryKey(), $this->categoryUrls[$categoryEntity->getFkStore()])) {
                $this->categoryUrls[$categoryEntity->getFkStore()][$categoryEntity->getCategoryKey()] = [];
            }
            $this->categoryUrls[$categoryEntity->getFkStore()][$categoryEntity->getCategoryKey()][] = [
                static::ID_LOCALE => $urlEntity->getFkLocale(),
                static::ID_STORE => $urlEntity->getFkStore(),
                static::URL => $urlEntity->getUrl(),
            ];
        }
    }
}
