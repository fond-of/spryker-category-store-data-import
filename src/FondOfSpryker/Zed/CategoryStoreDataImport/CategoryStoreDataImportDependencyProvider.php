<?php

namespace FondOfSpryker\Zed\CategoryStoreDataImport;

use FondOfSpryker\Zed\CategoryStoreDataImport\Dependency\Service\CategoryStoreDataImportToUtilTextBridge;
use Spryker\Zed\CategoryDataImport\CategoryDataImportDependencyProvider as SprykerCategoryDataImportDependencyProvider;
use Spryker\Zed\Kernel\Container;

class CategoryStoreDataImportDependencyProvider extends SprykerCategoryDataImportDependencyProvider
{
    public const SERVICE_UTIL_TEXT = 'SERVICE_UTIL_TEXT';

    /**
     * @param \Spryker\Zed\Kernel\Container $container
     *
     * @return \Spryker\Zed\Kernel\Container
     */
     public function provideBusinessLayerDependencies(Container $container)
    {
        $container = parent::provideBusinessLayerDependencies($container);

        $container[self::SERVICE_UTIL_TEXT] = function (Container $container) {
            return new CategoryStoreDataImportToUtilTextBridge($container->getLocator()->utilText()->service());
        };

        return $container;
    }
}
