<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\ProductNew\Plugin\Elasticsearch\Query;

use Elastica\Query;
use Elastica\Query\AbstractQuery;
use Elastica\Query\BoolQuery;
use Elastica\Query\Nested;
use Elastica\Query\Term;
use Generated\Shared\Search\PageIndexMap;
use Generated\Shared\Transfer\SearchContextTransfer;
use Spryker\Client\Kernel\AbstractPlugin;
use Spryker\Client\ProductLabel\Plugin\ProductLabelFacetConfigTransferBuilderPlugin;
use Spryker\Client\Search\Dependency\Plugin\QueryInterface;
use Spryker\Client\SearchExtension\Dependency\Plugin\SearchContextAwareQueryInterface;

/**
 * @method \Spryker\Client\ProductNew\ProductNewFactory getFactory()
 */
class NewProductsQueryPlugin extends AbstractPlugin implements QueryInterface, SearchContextAwareQueryInterface
{
    /**
     * @var string
     */
    protected const SOURCE_IDENTIFIER = 'page';

    /**
     * @var \Elastica\Query
     */
    protected $query;

    /**
     * @var \Generated\Shared\Transfer\SearchContextTransfer
     */
    protected $searchContextTransfer;

    public function __construct()
    {
        $this->query = $this->createSearchQuery();
    }

    /**
     * {@inheritDoc}
     * - Returns a query object for new products search.
     *
     * @api
     *
     * @return \Elastica\Query
     */
    public function getSearchQuery()
    {
        return $this->query;
    }

    /**
     * {@inheritDoc}
     * - Defines a context for new products search.
     *
     * @api
     *
     * @return \Generated\Shared\Transfer\SearchContextTransfer
     */
    public function getSearchContext(): SearchContextTransfer
    {
        if (!$this->hasSearchContext()) {
            $this->setupDefaultSearchContext();
        }

        return $this->searchContextTransfer;
    }

    /**
     * {@inheritDoc}
     * - Sets a context for new products search.
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\SearchContextTransfer $searchContextTransfer
     *
     * @return void
     */
    public function setSearchContext(SearchContextTransfer $searchContextTransfer): void
    {
        $this->searchContextTransfer = $searchContextTransfer;
    }

    /**
     * @return void
     */
    protected function setupDefaultSearchContext(): void
    {
        $searchContextTransfer = new SearchContextTransfer();
        $searchContextTransfer->setSourceIdentifier(static::SOURCE_IDENTIFIER);

        $this->searchContextTransfer = $searchContextTransfer;
    }

    /**
     * @return \Elastica\Query
     */
    protected function createSearchQuery()
    {
        $newProductsFilter = $this->createNewProductsFilter();

        $boolQuery = new BoolQuery();
        $boolQuery->addFilter($newProductsFilter);

        return $this->createQuery($boolQuery);
    }

    /**
     * @return \Elastica\Query\Nested
     */
    protected function createNewProductsFilter()
    {
        $newProductsQuery = $this->createNewProductsQuery();

        $newProductsFilter = new Nested();
        $newProductsFilter
            ->setQuery($newProductsQuery)
            ->setPath(PageIndexMap::STRING_FACET);

        return $newProductsFilter;
    }

    /**
     * @return \Elastica\Query\BoolQuery
     */
    protected function createNewProductsQuery()
    {
        $localeName = $this->getFactory()
            ->getLocaleClient()
            ->getCurrentLocale();
        $storeName = $this->getFactory()
            ->getStoreClient()
            ->getCurrentStore()
            ->getNameOrFail();

        $labelName = $this->getFactory()
            ->getConfig()
            ->getLabelNewName();

        $storageProductLabelTransfer = $this->getFactory()
            ->getProductLabelStorageClient()
            ->findLabelByName($labelName, $localeName, $storeName);

        $labelId = $storageProductLabelTransfer ? (int)$storageProductLabelTransfer->getIdProductLabel() : 0;

        $stringFacetFieldFilter = $this->createStringFacetFieldFilter(ProductLabelFacetConfigTransferBuilderPlugin::NAME);
        $stringFacetValueFilter = $this->createStringFacetValueFilter($labelId);

        $newProductsBoolQuery = new BoolQuery();
        $newProductsBoolQuery
            ->addFilter($stringFacetFieldFilter)
            ->addFilter($stringFacetValueFilter);

        return $newProductsBoolQuery;
    }

    /**
     * @param string $fieldName
     *
     * @return \Elastica\Query\Term
     */
    protected function createStringFacetFieldFilter($fieldName)
    {
        $termQuery = new Term();
        $termQuery->setTerm(PageIndexMap::STRING_FACET_FACET_NAME, $fieldName);

        return $termQuery;
    }

    /**
     * @param int $idProductLabel
     *
     * @return \Elastica\Query\Term
     */
    protected function createStringFacetValueFilter($idProductLabel)
    {
        $termQuery = new Term();
        $termQuery->setTerm(PageIndexMap::STRING_FACET_FACET_VALUE, $idProductLabel);

        return $termQuery;
    }

    /**
     * @param \Elastica\Query\AbstractQuery $abstractQuery
     *
     * @return \Elastica\Query
     */
    protected function createQuery(AbstractQuery $abstractQuery)
    {
        $query = new Query();
        $query
            ->setQuery($abstractQuery)
            ->setSource([PageIndexMap::SEARCH_RESULT_DATA]);

        return $query;
    }

    /**
     * @return bool
     */
    protected function hasSearchContext(): bool
    {
        return (bool)$this->searchContextTransfer;
    }
}
