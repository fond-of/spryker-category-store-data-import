<?php

namespace FondOfSpryker\Zed\CategoryStoreDataImport\Business;

use FondOfSpryker\Zed\CategoryStoreDataImport\Business\Model\CategoryWriterStep;
use FondOfSpryker\Zed\CategoryStoreDataImport\Business\Model\Reader\CategoryReader;
use FondOfSpryker\Zed\CategoryStoreDataImport\CategoryStoreDataImportDependencyProvider;
use FondOfSpryker\Zed\CategoryStoreDataImport\Dependency\Service\CategoryStoreDataImportToUtilTextInterface;
use Spryker\Zed\CategoryDataImport\Business\CategoryDataImportBusinessFactory as SprykerCategoryDataImportBusinessFactory;

/**
 * @method \FondOfSpryker\Zed\CategoryStoreDataImport\CategoryStoreDataImportConfig getConfig()
 */
class CategoryStoreDataImportBusinessFactory extends SprykerCategoryDataImportBusinessFactory
{
    /**
     * @return \Spryker\Zed\DataImport\Business\Model\DataImporterInterface
     */
    public function createCategoryImporter()
    {
        $dataImporter = $this->getCsvDataImporterFromConfig($this->getConfig()->getCategoryDataImporterConfiguration());

        $dataSetStepBroker = $this->createTransactionAwareDataSetStepBroker();
        $dataSetStepBroker
            ->addStep($this->createAddLocalesStep())
            ->addStep($this->createLocalizedAttributesExtractorStep([
                CategoryWriterStep::KEY_NAME,
                CategoryWriterStep::KEY_META_TITLE,
                CategoryWriterStep::KEY_META_DESCRIPTION,
                CategoryWriterStep::KEY_META_KEYWORDS,
            ]))
            ->addStep($this->createCategoryWriterStep());

        $dataImporter
            ->addDataSetStepBroker($dataSetStepBroker);

        return $dataImporter;
    }

    /**
     * @return \FondOfSpryker\Zed\CategoryStoreDataImport\Business\Model\Reader\CategoryReaderInterface
     */
    protected function createCategoryRepository()
    {
        return new CategoryReader();
    }

    /**
     * @return \FondOfSpryker\Zed\CategoryStoreDataImport\Business\Model\CategoryWriterStep
     */
    public function createCategoryWriterStep(): CategoryWriterStep
    {
        return new CategoryWriterStep(
            $this->createCategoryRepository(),
            $this->getUtilText()
        );
    }

    /**
     *
     * @return \FondOfSpryker\Zed\CategoryStoreDataImport\Dependency\Service\CategoryStoreDataImportToUtilTextInterface
     */
    protected function getUtilText(): CategoryStoreDataImportToUtilTextInterface
    {
        return $this->getProvidedDependency(CategoryStoreDataImportDependencyProvider::SERVICE_UTIL_TEXT);
    }
}
