<?php

class KL_Shopello_Helper_Cache extends Mage_Core_Helper_Abstract
{
    public function cacheStore()
    {
        $useCache = Mage::app()->useCache("shopello_products");

        if ($useCache === true) {
            $cache = Mage::app()->getCache();
        }
        else {
            $cache = new Zend_Cache_Backend_BlackHole;
        }

        return $cache;
    }

    public function cacheKey($store, $product)
    {
        if ($store instanceof Mage_Core_Model_Store) {
            $storeId = $store->getId();
        }
        else {
            $storeId = $store;
        }

        return join(":", array(
            "shopello_store_product",
            $storeId,
            $product->getId()
        ));
    }

    public function load($store, $product)
    {
        $key = $this->cacheKey($store, $product);
        $cached = $this->cacheStore()->load($key);

        if ($cached) {
            return unserialize($cached);
        }
        else {
            return false;
        }
    }

    public function save($store, $product, $data)
    {
        $key = $this->cacheKey($store, $product);
        $tags = array("SHOPELLO_PRODUCT");
        $cached = serialize($data);

        $this->cacheStore()->save($cached, $key, $tags);

        return true;
    }

    public function remove($store, $product)
    {
        $key = $this->cacheKey($store, $product);
        $this->cacheStore()->remove($key);

        return true;
    }
}
