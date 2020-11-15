<?php

namespace Notifier\EmagProductChecker;

use DOMDocument;
use DOMXPath;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;

class EmagProductChecker
{
    const CLASSNAME_PRICE_STOCK_PARENT = 'product-highlight product-page-pricing';
    const CLASSNAME_PRICE = 'product-new-price';
    const CLASSNAME_IN_STOCK = 'label label-in_stock';
    const CLASSNAME_SELLER_PARENT = 'product-highlights-wrapper';
    const CLASSNAME_SELLER = 'inline-block';

    /** @var ClientInterface */
    private $httpClient;

    public function __construct(ClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * @param string $productUrl
     * @param int $productMaxPrice
     *
     * @return bool
     * @throws EmagProductCheckerException
     */
    public function checkProduct($productUrl, $productMaxPrice)
    {
        try {
            $response = $this->httpClient->request('GET', $productUrl);
        } catch (GuzzleException $exception) {
            throw new EmagProductCheckerException(
                sprintf(
                    'Could not make get request to product url: %s',
                    $exception->getMessage()
                )
            );
        }

        $parsedProduct= $this->parseProductPage((string)$response->getBody());

        if ($parsedProduct->isInStock()
            && $parsedProduct->isSoldByEmag()
            && $parsedProduct->getPrice() <= $productMaxPrice
        ) {
            return true;
        }

        return false;
    }

    /**
     * @param string $productPageHtml
     *
     * @return ParsedProductData
     * @throws EmagProductCheckerException
     */
    private function parseProductPage($productPageHtml)
    {
        $domDocument = new DomDocument();
        @$domDocument->loadHTML($productPageHtml); // use @ to suppress warnings
        $xPathFinder = new DomXPath($domDocument);

        return new ParsedProductData(
            $this->parseProductPrice($xPathFinder),
            $this->parseIsProductInStock($xPathFinder),
            $this->parseIsSoldByEmag($xPathFinder)
        );
    }

    /**
     * @param DOMXPath $xPathFinder
     *
     * @return float
     * @throws EmagProductCheckerException
     */
    private function parseProductPrice(DOMXPath $xPathFinder)
    {
        $xPathExpression = sprintf(
            "//div[contains(concat(' ', normalize-space(@class), ' '), ' %s ')]//*[contains(concat(' ', normalize-space(@class), ' '), ' %s ')]",
            self::CLASSNAME_PRICE_STOCK_PARENT,
            self::CLASSNAME_PRICE
        );
        $nodes = $xPathFinder->query($xPathExpression);

        if ($nodes->length !== 1) {
            throw new EmagProductCheckerException('Could not parse the product price (did not find the element)');
        }

        $priceNodeValue = trim($nodes->item(0)->textContent);

        $parsedPrice = (float)str_replace('.', '', substr($priceNodeValue, 0, strpos($priceNodeValue, 'Lei'))) / 100;
        if ($parsedPrice <= 0) {
            throw new EmagProductCheckerException('Could not parse the product price (did not find the expected format)');
        }

        return $parsedPrice;
    }

    /**
     * @param DOMXPath $xPathFinder
     *
     * @return bool
     */
    private function parseIsProductInStock(DOMXPath $xPathFinder)
    {
        $xPathExpression = sprintf(
            "//div[contains(concat(' ', normalize-space(@class), ' '), ' %s ')]//*[contains(concat(' ', normalize-space(@class), ' '), ' %s ')]",
            self::CLASSNAME_PRICE_STOCK_PARENT,
            self::CLASSNAME_IN_STOCK
        );
        $nodes = $xPathFinder->query($xPathExpression);

        return $nodes->length === 1;
    }

    /**
     * @param DOMXPath $xPathFinder
     *
     * @return bool
     */
    private function parseIsSoldByEmag(DOMXPath $xPathFinder)
    {
        $xPathExpression = sprintf(
            "//div[contains(concat(' ', normalize-space(@class), ' '), ' %s ')]//div[contains(concat(' ', normalize-space(@class), ' '), ' %s ')]",
            self::CLASSNAME_SELLER_PARENT,
            self::CLASSNAME_SELLER
        );
        $nodes = $xPathFinder->query($xPathExpression);

        return ($nodes->length === 1) && (trim($nodes->item(0)->textContent) === 'eMAG');
    }
}
