<?php

class KL_Shopello_Model_Observer
{
    protected function _clearProductCache($product)
    {
        $cacheHelper = Mage::helper('shopello/cache');

        $stores = $product->getStoreIds();

        foreach ($stores as $store) {
            $cacheHelper->remove($store, $product);
        }
    }

    public function updateProduct($observer)
    {
        $event = $observer->getEvent();
        $product = $event->getProduct();

        $this->_clearProductCache($product);
    }

    public function submitQuote($observer)
    {
        $event = $observer->getEvent();
        $quote = $event->getQuote();
        $items = $quote->getAllItems();

        foreach ($items as $item) {
            $this->_clearProductCache($item->getProduct());
        }
    }

    public function cancelOrderItem($observer)
    {
        $event = $observer->getEvent();
        $item = $event->getItem();
        $product = $item->getProduct();

        $this->_clearProductCache($product);
    }
}
