<?php

class KL_Shopello_ProductsController extends Mage_Core_Controller_Front_Action
{
    public function indexAction()
    {
        $params = $this->getRequest()->getParams();
        $secret = Mage::getStoreConfig('shopello/general/secret_key');

        if (empty($params["secret_key"]) || $params["secret_key"] != $secret) {
            $this->getResponse()->setHttpResponseCode(403);
            $this->getResponse()->setBody("Access denied.");
            return;
        }
        $store = Mage::app()->getStore();

        /**
         * Determines if this is HTTPS request
         */
        $isSecure = $this->getRequest()->isSecure();

        /**
         * Set the store code
         */
        $storeCode = Mage::app()->getStore()->getCode();

        /**
         * Where to output the XML file
         */
        if($isSecure) {
            $filename = Mage::getBaseDir() . '/var/' . 'shopello-https-' . $storeCode . '-' . $secret . '.xml';
        } else {
            $filename = Mage::getBaseDir() . '/var/' . 'shopello-' . $storeCode . '-' . $secret . '.xml';
        }

        /**
         * Default action is NOT to generate the file
         */
        $generateFile = false;

        /**
         * Check if we should force it
         */
        if (isset($params["force"]) && $params["force"] == '1') {
            $generateFile = true;
        } else {
            /**
             * Force generate if the file is missing
             */
            if (!file_exists($filename)) {
                $generateFile = true;
            } else {
                $generateFile = false;
            }
        }

        /**
         * Generate the file
         */
        if ($generateFile) {

            $productHelper  = Mage::helper('shopello/product');
            $xmlHelper      = Mage::helper('shopello/xml');
            $cacheHelper    = Mage::helper('shopello/cache');

            $products = Mage::getModel('catalog/product')
                            ->getCollection()
                            ->addAttributeToFilter(
                                'status',
                                array('eq' => Mage_Catalog_Model_Product_Status::STATUS_ENABLED)
                            )
                            ->addAttributeToSelect(array(
                                "name",
                                "manufacturer",
                                "short_description",
                                "price",
                                "image"
                            ))
                            ->addAttributeToFilter('visibility', 4)
                            ->addStoreFilter($store->getId())
                            ->addFieldToFilter('type_id', array('neq' => 'bundle'))
                            ->addFinalPrice();

            $productEntries = array();

            foreach ($products as $product) {
                $cachedProductData = $cacheHelper->load($store, $product);
                $cachedProductData = false;

                if ($cachedProductData) {
                    $productEntries[] = $cachedProductData;
                }
                else {
                    $productData = $productHelper->feedData($product);
                    $productEntries[] = $productData;
                    $cacheHelper->save($store, $product, $productData);
                }
            }
            /*
             * Since we cache the product data, then we would make dynamic HTTPS check.
             * We don't want HTTPS to get cached and be rendered in the future for HTTP calls.
             */
            if ($isSecure) {
                foreach($productEntries as $index => $productEntry) {
                    $productEntries[$index]['productUrl'] = str_replace('http:', 'https:', $productEntry['productUrl']);
                    $productEntries[$index]['imageUrl'] = str_replace('http:', 'https:', $productEntry['imageUrl']);
                }
            }

            $xml = $xmlHelper->buildXml($store, $productEntries);

            /**
             * Save on disk
             */
            file_put_contents($filename, (string)$xml);
        } else {
            $xml = file_get_contents($filename);
        }

        $this->getResponse()->setHeader('Content-Type', 'application/xml; charset=UTF-8');
        $this->getResponse()->setBody($xml);

    }
}
