<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Wishlist\Model\PrivateData\Section;

use Magento\Customer\Model\PrivateData\Section\SectionSourceInterface;

/**
 * Wishlist section
 */
class Wishlist implements SectionSourceInterface
{
    /**
     * @var string
     */
    const SIDEBAR_ITEMS_NUMBER = 3;

    /**
     * @var \Magento\Wishlist\Helper\Data
     */
    protected $wishlistHelper;

    /** @var \Magento\Catalog\Model\Product\Image\View */
    protected $productImageView;

    /**
     * @param \Magento\Wishlist\Helper\Data $wishlistHelper
     */
    public function __construct(
        \Magento\Wishlist\Helper\Data $wishlistHelper,
        \Magento\Wishlist\Block\Customer\Sidebar $block,
        \Magento\Catalog\Model\Product\Image\View $productImageView
    ) {
        $this->wishlistHelper = $wishlistHelper;
        $this->productImageView = $productImageView;
        $this->block = $block;
    }

    /**
     * {@inheritdoc}
     */
    public function getData()
    {
        $counter = $this->getCounter();
        return [
            'counter' => $counter,
            'items' => $counter ? $this->getItems() : [],
        ];
    }

    /**
     * @return string
     */
    protected function getCounter()
    {
        return $this->createCounter($this->getItemCount());
    }

    /**
     * Count items in wishlist
     *
     * @return int
     */
    protected function getItemCount()
    {
        return $this->wishlistHelper->getItemCount();
    }

    /**
     * Create button label based on wishlist item quantity
     *
     * @param int $count
     * @return \Magento\Framework\Phrase|null
     */
    protected function createCounter($count)
    {
        if ($count > 1) {
            return __('%1 items', $count);
        } elseif ($count == 1) {
            return __('1 item');
        }
        return null;
    }

    /**
     * Get wishlist items
     *
     * @return array
     */
    protected function getItems()
    {
        $collection = $this->wishlistHelper->getWishlistItemCollection();
        $collection->clear()->setPageSize(self::SIDEBAR_ITEMS_NUMBER)
            ->setInStockFilter(true)->setOrder('added_at');
        $items = [];
        foreach ($collection as $wishlistItem) {
            /** @var \Magento\Catalog\Model\Product $product */
            $product = $wishlistItem->getProduct();
            $this->productImageView->init($product, 'wishlist_sidebar_block', 'Magento_Catalog');
            $items[] = [
                'image' => [
                    'src' => $this->productImageView->getUrl(),
                    'alt' => $this->productImageView->getLabel(),
                    'width' => $this->productImageView->getWidth(),
                    'height' => $this->productImageView->getHeight(),
                ],
                'product_url' => $this->block->getProductUrl($wishlistItem),
                'product_name' => $product->getName(),
                'product_price' => $this->block->getProductPriceHtml(
                    $product,
                    \Magento\Catalog\Pricing\Price\ConfiguredPriceInterface::CONFIGURED_PRICE_CODE,
                    \Magento\Framework\Pricing\Render::ZONE_ITEM_LIST,
                    ['item' => $wishlistItem]
                ),
                'product_is_saleable_and_visible' => $product->isSaleable() && $product->isVisibleInSiteVisibility(),
                'product_has_required_options' => $product->getTypeInstance()->hasRequiredOptions($product),
                'add_to_cart_params' => $this->wishlistHelper->getAddToCartParams($wishlistItem),
                'delete_item_params' => $this->wishlistHelper->getRemoveParams($wishlistItem),
            ];
        }
        return $items;
    }
}
