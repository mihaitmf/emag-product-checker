<?php

namespace ProductChecker\EmagProductChecker;

use DOMDocument;
use DOMXPath;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;

class EmagProductChecker
{
    private const CLASSNAME_PRICE_STOCK_PARENT = 'product-highlight product-page-pricing';
    private const CLASSNAME_PRICE = 'product-new-price';
    private const CLASSNAME_STOCK = 'label';
    private const CLASSNAME_IN_STOCK = 'label label-in_stock';
    private const CLASSNAME_LIMITED_STOCK = 'label label-limited_stock';
    private const CLASSNAME_LIMITED_STOCK_QUANTITY = 'label label-limited_stock_qty';
    private const CLASSNAME_SELLER_PARENT = 'product-highlights-wrapper';
    private const CLASSNAME_SELLER = 'inline-block';

    private const STOCK_LEVEL_AVAILABLE = 'În stoc';
    private const STOCK_LEVEL_LIMITED = 'Stoc limitat';
    private const STOCK_LEVEL_LAST_FEW = 'Ultimele';
    private const STOCK_LEVEL_LAST_ONE = 'Ultimul';
    private const STOCK_LEVEL_PREORDER = 'Precomandă';
    private const STOCK_LEVEL_ZERO = 'Stoc epuizat';
    private const STOCK_LEVEL_UNAVAILABLE = 'Indisponibil';
    private const KNOWN_STOCK_LEVELS = [
        self::STOCK_LEVEL_AVAILABLE,
        self::STOCK_LEVEL_LIMITED,
        self::STOCK_LEVEL_LAST_FEW,
        self::STOCK_LEVEL_LAST_ONE,
        self::STOCK_LEVEL_PREORDER,
        self::STOCK_LEVEL_ZERO,
        self::STOCK_LEVEL_UNAVAILABLE,
    ];
    private const OK_STOCK_LEVELS = [
        self::STOCK_LEVEL_AVAILABLE,
        self::STOCK_LEVEL_LIMITED,
        self::STOCK_LEVEL_LAST_FEW,
        self::STOCK_LEVEL_LAST_ONE,
        self::STOCK_LEVEL_PREORDER,
    ];

    private const SELLER_NAME_EMAG = 'eMAG';

    private ClientInterface $httpClient;

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
    public function checkProduct(string $productUrl, int $productMaxPrice): EmagProductCheckerResult
    {
        $response = $this->makeGetRequest($productUrl);

        $productData = $this->parseProductPage((string)$response->getBody());

        $isAvailable = $this->isStockLevelAvailable($productData->getStockLevel())
            && $productData->getPrice() <= $productMaxPrice
            && $productData->getSeller() === self::SELLER_NAME_EMAG;

        return new EmagProductCheckerResult($isAvailable, $productData);
    }

    /**
     * @param string $productUrl
     *
     * @return ResponseInterface
     * @throws EmagProductCheckerException
     */
    private function makeGetRequest(string $productUrl): ResponseInterface
    {
        try {
            return $this->httpClient->request(
                'GET',
                $productUrl,
                [
                    'headers' => [
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 6.3; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/86.0.4240.183 Safari/537.36',
                    ],
                ]
            );
        } catch (GuzzleException $exception) {
            throw new EmagProductCheckerException(
                sprintf('Could not make GET request to product url: %s', $exception->getMessage())
            );
        }
    }

    /**
     * @param string $productPageHtml
     *
     * @return EmagProductData
     * @throws EmagProductCheckerException
     */
    private function parseProductPage(string $productPageHtml): EmagProductData
    {
//        file_put_contents('test.html', $productPageHtml);
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
    private function parseProductPrice(DOMXPath $xPathFinder): float
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
    private function parseStockLevel(DOMXPath $xPathFinder): string
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

        foreach (self::KNOWN_STOCK_LEVELS as $knownStockLevel) {
            if ($this->isStockLevel($parsedStockLevel, $knownStockLevel)) {
                return $parsedStockLevel;
            }
        }

        throw new EmagProductCheckerException(
            sprintf('Error parsing the product stock. Did not find a known stock level: %s', $parsedStockLevel)
        );
    }

    private function isStockLevel(string $parsedStockLevel, string $knownStockLevel): bool
    {
        return strpos($parsedStockLevel, $knownStockLevel) !== false;
    }

    private function isStockLevelAvailable(string $parsedStockLevel): bool
    {
        foreach (self::OK_STOCK_LEVELS as $okStockLevel) {
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
    private function parseSeller(DOMXPath $xPathFinder): string
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
