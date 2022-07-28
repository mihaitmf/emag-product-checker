<?php

namespace ProductChecker\SummerWellProductChecker;

use DOMDocument;
use DOMXPath;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;

class SummerWellProductChecker
{
    public const REQUEST_OPTIONS_HEADERS = [
        'headers' => [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 6.3; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/86.0.4240.183 Safari/537.36',
        ],
    ];

    private const CLASSNAME_PRICE_STOCK_PARENT = 'ticket__info';
    private const CLASSNAME_PRICE = 'ticket__price';
    private const CLASSNAME_STOCK = 'btn btn--primary ticket__add-to-cart-btn';

    private const STOCK_LEVEL_UNAVAILABLE = [
        'Out of stock',
        'Stoc epuizat',
    ];

    private ClientInterface $httpClient;

    public function __construct(ClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * @param string $productUrl
     *
     * @return SummerWellProductCheckerResult
     * @throws SummerWellProductCheckerException
     */
    public function checkProduct(string $productUrl): SummerWellProductCheckerResult
    {
        $response = $this->makeGetRequest($productUrl);

        $productData = $this->parseProductPage((string)$response->getBody());

        $isAvailable = $this->isStockLevelAvailable($productData->getStockLevel());

        return new SummerWellProductCheckerResult($isAvailable, $productData);
    }

    /**
     * @param string $productUrl
     *
     * @return ResponseInterface
     * @throws SummerWellProductCheckerException
     */
    private function makeGetRequest(string $productUrl): ResponseInterface
    {
        try {
            return $this->httpClient->request('GET', $productUrl,self::REQUEST_OPTIONS_HEADERS);
        } catch (GuzzleException $exception) {
            throw new SummerWellProductCheckerException(
                sprintf('Could not make GET request to product url: %s', $exception->getMessage())
            );
        }
    }

    /**
     * @param string $productPageHtml
     *
     * @return SummerWellProductData
     * @throws SummerWellProductCheckerException
     */
    private function parseProductPage(string $productPageHtml): SummerWellProductData
    {
//        file_put_contents('test.html', $productPageHtml); exit;
//        $productPageHtml = file_get_contents('test.html');

        $domDocument = new DomDocument();
        @$domDocument->loadHTML($productPageHtml); // use @ to suppress warnings
        $xPathFinder = new DomXPath($domDocument);

        return new SummerWellProductData(
            $this->parseStockLevel($xPathFinder),
            $this->parseProductPrice($xPathFinder),
        );
    }

    /**
     * @param DOMXPath $xPathFinder
     *
     * @return string
     * @throws SummerWellProductCheckerException
     */
    private function parseStockLevel(DOMXPath $xPathFinder): string
    {
        $xPathExpression = sprintf(
            "//div[contains(concat(' ', normalize-space(@class), ' '), ' %s ')]//button[contains(concat(' ', normalize-space(@class), ' '), ' %s ')]",
            self::CLASSNAME_PRICE_STOCK_PARENT,
            self::CLASSNAME_STOCK
        );
        $nodes = $xPathFinder->query($xPathExpression);

        if ($nodes->length !== 1) {
            throw new SummerWellProductCheckerException('Error parsing the product stock. Did not find the DOM element.');
        }

        return trim($nodes->item(0)->textContent);
    }

    private function isStockLevelAvailable(string $parsedStockLevel): bool
    {
        return !in_array($parsedStockLevel, self::STOCK_LEVEL_UNAVAILABLE);
    }

    /**
     * @param DOMXPath $xPathFinder
     *
     * @return float
     * @throws SummerWellProductCheckerException
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
            throw new SummerWellProductCheckerException('Error parsing the product price. Did not find the DOM element.');
        }

        $priceNodeValue = trim($nodes->item(0)->textContent);

        $parsedPrice = (float)substr( // get the price as the string before "lei"
            $priceNodeValue,
            0,
            strpos($priceNodeValue, 'lei')
        );

        if ($parsedPrice <= 1) {
            throw new SummerWellProductCheckerException(
                sprintf('Error parsing the product price. Did not find the expected format: %s', $priceNodeValue)
            );
        }

        return $parsedPrice;
    }
}
