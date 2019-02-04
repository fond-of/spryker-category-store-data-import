<?php

namespace FondOfSpryker\Zed\CategoryStoreDataImport\Business\Model;

use Exception;
use FondOfSpryker\Zed\CategoryStoreDataImport\Business\Model\Reader\CategoryReaderInterface;
use FondOfSpryker\Zed\CategoryStoreDataImport\Dependency\Service\CategoryStoreDataImportToUtilTextInterface;
use Orm\Zed\Category\Persistence\SpyCategory;
use Orm\Zed\Category\Persistence\SpyCategoryAttribute;
use Orm\Zed\Category\Persistence\SpyCategoryNode;
use Orm\Zed\Category\Persistence\SpyCategoryNodeQuery;
use Orm\Zed\Category\Persistence\SpyCategoryQuery;
use Orm\Zed\Store\Persistence\SpyStoreQuery;
use Orm\Zed\Url\Persistence\SpyUrlQuery;
use Propel\Runtime\ActiveQuery\Criteria;
use Spryker\Shared\Kernel\Store;
use Spryker\Zed\Category\Dependency\CategoryEvents;
use Spryker\Zed\CategoryDataImport\Business\Model\CategoryWriterStep as SprykerCategoryWriterStep;
use Spryker\Zed\DataImport\Business\Model\DataImportStep\AddLocalesStep;
use Spryker\Zed\DataImport\Business\Model\DataSet\DataSetInterface;
use Spryker\Zed\Url\Dependency\UrlEvents;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CategoryWriterStep extends SprykerCategoryWriterStep
{
    const KEY_STORE = 'store';

    /**
     * @var \FondOfSpryker\Zed\CategoryStoreDataImport\Dependency\Service\CategoryStoreDataImportToUtilTextInterface
     */
    protected $categoryStoreDataImportToUtilText;

    /**
     * @var \FondOfSpryker\Zed\CategoryStoreDataImport\Business\Model\Reader\CategoryReaderInterface
     */
    protected $categoryReader;

    /**
     * CategoryWriterStep constructor.
     *
     * @param \FondOfSpryker\Zed\CategoryStoreDataImport\Business\Model\Reader\CategoryReaderInterface $categoryReader
     * @param \FondOfSpryker\Zed\CategoryStoreDataImport\Business\Model\CategoryDataImportToUtilTextInterface $categoryStoreDataImportToUtilText
     */
    public function __construct(
        CategoryReaderInterface $categoryReader,
        CategoryStoreDataImportToUtilTextInterface $categoryStoreDataImportToUtilText
    ) {
        $this->categoryReader = $categoryReader;
        $this->categoryStoreDataImportToUtilText = $categoryStoreDataImportToUtilText;
    }

    /**
     * @param \Spryker\Zed\DataImport\Business\Model\DataSet\DataSetInterface $dataSet
     *
     * @return \Orm\Zed\Category\Persistence\SpyCategory
     */
    protected function findOrCreateCategory(DataSetInterface $dataSet)
    {
        $idStore = $this->getIdStore($dataSet[static::KEY_STORE]);

        $categoryEntity = SpyCategoryQuery::create()
            ->filterByCategoryKey($dataSet[static::KEY_CATEGORY_KEY])
            ->filterByFkStore(null, Criteria::ISNULL)
            ->_or()
            ->filterByFkStore($idStore)
            ->findOneOrCreate();

        $categoryEntity->fromArray($dataSet->getArrayCopy());

        if (!empty($dataSet[static::KEY_TEMPLATE_NAME])) {
            $categoryTemplateEntity = $this->getCategoryTemplate($dataSet);
            $categoryEntity->setFkCategoryTemplate($categoryTemplateEntity->getIdCategoryTemplate());
        }

        $categoryEntity->setFkStore($idStore);

        if ($categoryEntity->isNew() || $categoryEntity->isModified()) {
            $categoryEntity->save();
        }
        
        return $categoryEntity;
    }

    /**
     * @param \Orm\Zed\Category\Persistence\SpyCategory $categoryEntity
     * @param \Spryker\Zed\DataImport\Business\Model\DataSet\DataSetInterface $dataSet
     *
     * @return \Orm\Zed\Category\Persistence\SpyCategoryNode
     */
    protected function findOrCreateNode(SpyCategory $categoryEntity, DataSetInterface $dataSet)
    {
        $idStore = $this->getIdStore($dataSet[static::KEY_STORE]);
        $categoryNodeEntity = SpyCategoryNodeQuery::create()
            ->filterByCategory($categoryEntity)
            ->findOneOrCreate();

        if (!empty($dataSet[static::KEY_PARENT_CATEGORY_KEY]) && !empty($dataSet[static::KEY_PARENT_CATEGORY_KEY])) {
            $idParentCategoryNode = $this->categoryReader->getIdCategoryNodeByCategoryKeyAndIdStore(
                $dataSet[static::KEY_PARENT_CATEGORY_KEY],
                $idStore
            );

            $categoryNodeEntity->setFkParentCategoryNode($idParentCategoryNode);
        }

        $categoryNodeEntity->fromArray($dataSet->getArrayCopy());

        if ($categoryNodeEntity->isNew() || $categoryNodeEntity->isModified()) {
            $categoryNodeEntity->save();
        }

        $this->addToClosureTable($categoryNodeEntity);
        $this->addPublishEvents(CategoryEvents::CATEGORY_NODE_PUBLISH, $categoryNodeEntity->getIdCategoryNode());

        foreach ($categoryEntity->getAttributes() as $categoryAttributesEntity) {
            $urlPathParts = $this->getUrlPathParts($dataSet, $categoryNodeEntity, $categoryAttributesEntity);

            if ($categoryNodeEntity->getIsRoot()) {
                $this->addPublishEvents(
                    CategoryEvents::CATEGORY_TREE_PUBLISH,
                    $categoryNodeEntity->getIdCategoryNode()
                );
            }

            $url = '/' . implode('/', $this->convertUrlPathParts($urlPathParts));

            $urlEntity = SpyUrlQuery::create()
                ->filterByFkLocale($categoryAttributesEntity->getFkLocale())
                ->filterByFkResourceCategorynode($categoryNodeEntity->getIdCategoryNode())
                ->findOneOrCreate();

            $urlEntity
                ->setUrl($url)
                ->setFkStore($idStore);

            if ($urlEntity->isNew() || $urlEntity->isModified()) {
                $urlEntity->save();
                $this->addPublishEvents(UrlEvents::URL_PUBLISH, $urlEntity->getIdUrl());
            }
        }

        return $categoryNodeEntity;
    }

    /**
     * @param \Spryker\Zed\DataImport\Business\Model\DataSet\DataSetInterface $dataSet
     * @param \Orm\Zed\Category\Persistence\SpyCategoryNode $categoryNodeEntity
     * @param \Orm\Zed\Category\Persistence\SpyCategoryAttribute $categoryAttributesEntity
     *
     * @return array
     */
    protected function getUrlPathParts(
        DataSetInterface $dataSet,
        SpyCategoryNode $categoryNodeEntity,
        SpyCategoryAttribute $categoryAttributesEntity
    ): array {
        $idLocale = $categoryAttributesEntity->getFkLocale();
        $languageIdentifier = $this->getLanguageIdentifier($idLocale, $dataSet);
        $urlPathParts = [$languageIdentifier];
        if (!$categoryNodeEntity->getIsRoot()) {
            $parentUrl = $this->categoryReader->getParentUrl(
                $dataSet[static::KEY_PARENT_CATEGORY_KEY],
                $idLocale
            );
            $urlPathParts = explode('/', ltrim($parentUrl, '/'));
            $urlPathParts[] = $categoryAttributesEntity->getName();
        }
        return $urlPathParts;
    }

    /**
     * @param int $idLocale
     * @param \Spryker\Zed\DataImport\Business\Model\DataSet\DataSetInterface $dataSet
     *
     * @throws \Exception
     *
     * @return string
     */
    protected function getLanguageIdentifier($idLocale, DataSetInterface $dataSet): string
    {
        $allowedLocales = $this->getAllowedLocales();
        foreach ($dataSet[AddLocalesStep::KEY_LOCALES] as $localeName => $localeId) {
            if ($idLocale !== $localeId) {
                continue;
            }
            $key = \array_search($localeName, $allowedLocales, true);
            if ($key !== false) {
                return $key;
            }
        }
        throw new Exception(sprintf('Could not extract language identifier for idLocale "%s"', $idLocale));
    }

    /**
     * @return array
     */
    protected function getAllowedLocales(): array
    {
        $store = Store::getInstance();
        $storeNames = $store->getAllowedStores();
        $allowedLocales = [];
        foreach ($storeNames as $storeName) {
            $locales = $store->getLocalesPerStore($storeName);
            $allowedLocales = \array_merge($allowedLocales, $locales);
        }
        return $allowedLocales;
    }

    /**
     * @param array $urlPathParts
     *
     * @return array
     */
    protected function convertUrlPathParts(array $urlPathParts): array
    {
        $slugGenerator = $this->categoryStoreDataImportToUtilText;
        $convertCallback = function ($value) use ($slugGenerator) {
            return $slugGenerator->generateSlug($value);
        };
        $urlPathParts = array_map($convertCallback, $urlPathParts);
        return $urlPathParts;
    }

    /**
     * @param string $name
     *
     * @return \Orm\Zed\Store\Persistence\SpyStore
     */
    protected function getIdStore(string $name)
    {
        /** @var \Orm\Zed\Store\Persistence\SpyStore $storeEntity */
        $storeEntity = SpyStoreQuery::create()
            ->filterByName($name)
            ->findOneOrCreate();

        return $storeEntity->getPrimaryKey();
    }
}
