<?php

class KL_Shopello_Helper_Xml extends Mage_Core_Helper_Abstract
{
    public function buildXml($store, $products)
    {
        $doc = new DOMDocument("1.0", "UTF-8");
        $doc->formatOutput = true;

        $productsXml = $doc->createElement("products");
        $productsXml->setAttributeNS("http://www.w3.org/2001/XMLSchema-instance", "xsi:noNamespaceSchemaLocation", "http://schemas.keybroker.com/productfeed_v1_2.xsd");
        $productsXml->setAttribute("currency", $store->getCurrentCurrencyCode());

        foreach ($products as $sku => $data) {
            $productXml = $doc->createElement("product");

            foreach ($data as $elementName => $elementValue) {
                $element = $doc->createElement($elementName);
                $value = $doc->createTextNode($elementValue);

                $element->appendChild($value);

                $productXml->appendChild($element);
            }

            $productsXml->appendChild($productXml);
        }

        $doc->appendChild($productsXml);

        return $doc->saveXML();
    }
}
