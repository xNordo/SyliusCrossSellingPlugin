<?php

/*
 * This file has been created by developers from BitBag.
 * Feel free to contact us once you face any issues or want to start
 * You can find more information about us on https://bitbag.io and write us
 * an email on hello@bitbag.io.
 */

declare(strict_types=1);

namespace BitBag\SyliusCrossSellingPlugin\Finder;

use BitBag\SyliusCrossSellingPlugin\Exception\ProductNotFoundException;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\TaxonInterface;

class RelatedProductsByTaxonsFinder extends AbstractRelatedProductsFinder implements RelatedProductsFinderInterface
{
    /**
     * {@inheritDoc}
     */
    public function findRelatedByChannelAndSlug(
        ChannelInterface $channel,
        string $locale,
        string $slug,
        int $maxResults,
        array $excludedProductIds = []
    ): array {
        $product = $this->productRepository->findOneByChannelAndSlug($channel, $locale, $slug);
        if (null === $product) {
            throw new ProductNotFoundException($slug, $channel, $locale);
        }

        $taxons = $this->getTaxons($product);

        $result = [];
        $excludedProductIds[] = $product->getId();

        foreach ($taxons as $taxon) {
            $relatedByTaxon = $this->productRepository->findLatestByChannelAndTaxonCode(
                $channel,
                (string)$taxon->getCode(),
                $maxResults - count($result),
                $excludedProductIds
            );
            $result = array_merge($result, $relatedByTaxon);

            if (count($result) >= $maxResults) {
                return $result;
            }

            $excludedProductIds = array_merge($excludedProductIds, $this->getIds($relatedByTaxon));
        }

        return $result;
    }

    /**
     * @return TaxonInterface[]
     */
    protected function getTaxons(ProductInterface $product): array
    {
        $taxons = [];

        $mainTaxon = $product->getMainTaxon();
        if (null !== $mainTaxon) {
            $taxons[] = $mainTaxon;
        }

        foreach ($product->getTaxons() as $taxon) {
            if (null !== $mainTaxon && $mainTaxon->getId() === $taxon->getId()) {
                continue;
            }

            $taxons[] = $taxon;
        }

        return $taxons;
    }
}
