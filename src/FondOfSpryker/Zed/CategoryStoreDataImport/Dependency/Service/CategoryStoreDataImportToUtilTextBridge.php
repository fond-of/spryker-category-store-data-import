<?php

namespace FondOfSpryker\Zed\CategoryStoreDataImport\Dependency\Service;

class CategoryStoreDataImportToUtilTextBridge implements CategoryStoreDataImportToUtilTextInterface
{
    /**
     * @var \Spryker\Service\UtilText\UtilTextServiceInterface
     */
    protected $utilTextService;

    /**
     * @param \Spryker\Service\UtilText\UtilTextServiceInterface $utilTextService
     */
    public function __construct($utilTextService)
    {
        $this->utilTextService = $utilTextService;
    }

    /**
     * @param string $value
     *
     * @return string
     */
    public function generateSlug(string $value): string
    {
        return $this->utilTextService->generateSlug($value);
    }
}
