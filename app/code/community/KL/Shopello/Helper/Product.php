<?php

class KL_Shopello_Helper_Product extends Mage_Core_Helper_Abstract
{
    public function active($product)
    {
        return (int)$product->isVisibleInCatalog();
    }

    public function manufacturer($product)
    {
        return $product->getAttributeText("manufacturer");
    }

    public function modelName($product)
    {
        return $product->getName();
    }

    public function description($product)
    {
        return strip_tags($product->getShortDescription());
    }

    public function categoryFields($product)
    {
        $categoryFields = array();
        $catIds = $product->getCategoryIds();
        if (is_array($catIds) && isset($catIds[0])) {
            $categoryFields["category"] = Mage::getModel('catalog/category')->load($catIds[0])->getName();
        }
        return $categoryFields;
    }

    public function priceWithTax($product)
    {
        return $product->getPrice();
    }

    public function productUrl($product)
    {
        return $product->getProductUrl();
    }

    public function quantityForSale($product)
    {
        return (int)Mage::getModel('cataloginventory/stock_item')->loadByProduct($product)->getQty();
    }

    public function imageUrl($product)
    {
        $image = '';

        if ($product->getImage() !== 'no_selection') {
            $image = Mage::getModel('catalog/product_media_config')
                ->getMediaUrl($product->getImage());
        }

        return $image;
    }

    public function shippingCost($product)
    {
        $collection = Mage::getModel('matrixrate/mysql4_carrier_matrixrate_collection')
            ->setWebsiteFilter(Mage::app()->getStore()->getId());
        $collection->getSelect()->where('condition_from_value <= ' . $this->priceWithTax($product));
        $collection->getSelect()->where('condition_to_value >= ' . $this->priceWithTax($product));
        $collection->getSelect()->order('price');
        $collection->getSelect()->limit(1);

        $collection->load();
        $items = $collection->getData();
        if (is_array($items) && isset($items[0]) && isset($items[0]['price'])) {
            return $items[0]['price'];
        } else {
            return 0;
        }
    }

    public function deliveryTime()
    {
        return Mage::getStoreConfig('shopello/general/delivery_time');
    }


    public function feedData($product)
    {
        $data = array(
            "id" => $product->getId(),
            "sku" => $product->getSku(),
            "productName" => $this->modelName($product),
            "productUrl" => $this->productUrl($product),
            "imageUrl" => $this->imageUrl($product),
            "price" => $this->priceWithTax($product),
            "description" => $this->description($product),
            "brand" => $this->manufacturer($product),
            "previousPrice" => '',
            "currency" => Mage::app()->getStore()->getCurrentCurrencyCode(),
            "shippingCost" => $this->shippingCost($product),
            "deliveryTime" => $this->deliveryTime(),
            "availability" => $this->quantityForSale($product),
        );

        $categoryFields = $this->categoryFields($product);

        /**
         * Make sure it's an array
         */
        if (!is_array($categoryFields)) {
            $categoryFields = array();
        }

        if (floatval($product->getFinalPrice()) !== floatval($this->priceWithTax($product))) {
            $data["discount"] = $product->getFinalPrice();
        }

        $data = array_merge($data, $categoryFields);

        return $data;
    }
}
