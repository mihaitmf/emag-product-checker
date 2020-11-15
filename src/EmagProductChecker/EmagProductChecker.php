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
    const CLASSNAME_STOCK = 'label';
    const CLASSNAME_IN_STOCK = 'label label-in_stock';
    const CLASSNAME_LIMITED_STOCK = 'label label-limited_stock';
    const CLASSNAME_LIMITED_STOCK_QUANTITY = 'label label-limited_stock_qty';
    const CLASSNAME_SELLER_PARENT = 'product-highlights-wrapper';
    const CLASSNAME_SELLER = 'inline-block';

    const STOCK_LEVEL_AVAILABLE = 'În stoc';
    const STOCK_LEVEL_LIMITED = 'Stoc limitat';
    const STOCK_LEVEL_LAST_FEW = 'Ultimele';
    const STOCK_LEVEL_LAST_ONE = 'Ultimul';
    const STOCK_LEVEL_PREORDER = 'Precomandă';
    const STOCK_LEVEL_ZERO = 'Stoc epuizat';
    const STOCK_LEVEL_UNAVAILABLE = 'Indisponibil';
    private static $KNOWN_STOCK_LEVELS = [
        self::STOCK_LEVEL_AVAILABLE,
        self::STOCK_LEVEL_LIMITED,
        self::STOCK_LEVEL_LAST_FEW,
        self::STOCK_LEVEL_LAST_ONE,
        self::STOCK_LEVEL_PREORDER,
        self::STOCK_LEVEL_ZERO,
        self::STOCK_LEVEL_UNAVAILABLE,
    ];
    private static $OK_STOCK_LEVELS = [
        self::STOCK_LEVEL_AVAILABLE,
        self::STOCK_LEVEL_LIMITED,
        self::STOCK_LEVEL_LAST_FEW,
        self::STOCK_LEVEL_LAST_ONE,
        self::STOCK_LEVEL_PREORDER,
    ];

    const SELLER_NAME_EMAG = 'eMAG';

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
     * @return EmagProductCheckerResult
     * @throws EmagProductCheckerException
     */
    public function checkProduct($productUrl, $productMaxPrice)
    {
        try {
            $response = $this->httpClient->request('GET', $productUrl);
        } catch (GuzzleException $exception) {
            throw new EmagProductCheckerException(
                sprintf('Could not make get request to product url: %s', $exception->getMessage())
            );
        }

        $productData= $this->parseProductPage((string)$response->getBody());

        $isAvailable = $this->isStockLevelAvailable($productData->getStockLevel())
            && $productData->getPrice() <= $productMaxPrice
            && $productData->getSeller() === self::SELLER_NAME_EMAG;

        return new EmagProductCheckerResult($isAvailable, $productData);
    }

    /**
     * @param string $productPageHtml
     *
     * @return EmagProductData
     * @throws EmagProductCheckerException
     */
    private function parseProductPage($productPageHtml)
    {
//        file_put_contents('test.html',$productPageHtml);
//        $productPageHtml = file_get_contents('prod.html');

        $domDocument = new DomDocument();
        @$domDocument->loadHTML($productPageHtml); // use @ to suppress warnings
        $xPathFinder = new DomXPath($domDocument);

        return new EmagProductData(
            $this->parseProductPrice($xPathFinder),
            $this->parseStockLevel($xPathFinder),
            $this->parseSeller($xPathFinder)
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
            throw new EmagProductCheckerException('Error parsing the product price. Did not find the DOM element.');
        }

        $priceNodeValue = trim($nodes->item(0)->textContent);

        $parsedPrice = (float)str_replace(
            '.', // eliminate "dot" character from price string
            '',
            substr( // get the price as the string before "Lei"
                $priceNodeValue,
                0,
                strpos($priceNodeValue, 'Lei')
            )
        ) / 100; // divide by 100 because the price is in sub-units

        if ($parsedPrice <= 1) {
            throw new EmagProductCheckerException(
                sprintf('Error parsing the product price. Did not find the expected format: %s', $priceNodeValue)
            );
        }

        return $parsedPrice;
    }

    /**
     * @param DOMXPath $xPathFinder
     *
     * @return string
     * @throws EmagProductCheckerException
     */
    private function parseStockLevel(DOMXPath $xPathFinder)
    {
        $xPathExpression = sprintf(
            "//div[contains(concat(' ', normalize-space(@class), ' '), ' %s ')]//span[contains(concat(' ', normalize-space(@class), ' '), ' %s ')]",
            self::CLASSNAME_PRICE_STOCK_PARENT,
            self::CLASSNAME_STOCK
        );
        $nodes = $xPathFinder->query($xPathExpression);

        if ($nodes->length !== 1) {
            throw new EmagProductCheckerException('Error parsing the product stock. Did not find the DOM element.');
        }

        $parsedStockLevel = trim($nodes->item(0)->textContent);

        foreach (self::$KNOWN_STOCK_LEVELS as $knownStockLevel) {
            if ($this->isStockLevel($parsedStockLevel, $knownStockLevel)) {
                return $parsedStockLevel;
            }
        }

        throw new EmagProductCheckerException(
            sprintf('Error parsing the product stock. Did not find a known stock level: %s', $parsedStockLevel)
        );
    }

    /**
     * @param string $parsedStockLevel
     * @param string $knownStockLevel
     *
     * @return bool
     */
    private function isStockLevel($parsedStockLevel, $knownStockLevel)
    {
        return strpos($parsedStockLevel, $knownStockLevel) !== false;
    }

    /**
     * @param string $parsedStockLevel
     *
     * @return bool
     */
    private function isStockLevelAvailable($parsedStockLevel)
    {
        foreach (self::$OK_STOCK_LEVELS as $okStockLevel) {
            if ($this->isStockLevel($parsedStockLevel, $okStockLevel)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param DOMXPath $xPathFinder
     *
     * @return string
     * @throws EmagProductCheckerException
     */
    private function parseSeller(DOMXPath $xPathFinder)
    {
        $xPathExpression = sprintf(
            "//div[contains(concat(' ', normalize-space(@class), ' '), ' %s ')]//div[contains(concat(' ', normalize-space(@class), ' '), ' %s ')]",
            self::CLASSNAME_SELLER_PARENT,
            self::CLASSNAME_SELLER
        );
        $nodes = $xPathFinder->query($xPathExpression);

        if ($nodes->length !== 1) {
            throw new EmagProductCheckerException('Error parsing the product seller. Did not find the DOM element.');
        }

        return trim($nodes->item(0)->textContent);
    }
}
