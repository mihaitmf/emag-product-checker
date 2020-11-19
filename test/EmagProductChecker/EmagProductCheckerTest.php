<?php

namespace ProductChecker\Tests\EmagProductChecker;

use GuzzleHttp\ClientInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use ProductChecker\EmagProductChecker\EmagProductChecker;
use ProductChecker\EmagProductChecker\EmagProductCheckerException;
use Psr\Http\Message\ResponseInterface;

class EmagProductCheckerTest extends TestCase
{
    private const PRODUCT_URL = 'http://any-url.com/product/page';
    private const HTML_FILES_PATH = __DIR__ . DIRECTORY_SEPARATOR . 'product-pages' . DIRECTORY_SEPARATOR;

    private EmagProductChecker $productChecker;

    /** @var ClientInterface|MockObject */
    private $httpClientMock;

    /** @var ResponseInterface|Stub */
    private $responseStub;

    protected function setUp(): void
    {
        $this->httpClientMock = $this->createMock(ClientInterface::class);
        $this->productChecker = new EmagProductChecker($this->httpClientMock);

        $this->responseStub = $this->createStub(ResponseInterface::class);
    }

    /**
     * @dataProvider providerProductPages
     */
    public function testCheckProductSuccess(
        string $productPageHtml,
        int $productMaxPrice,
        bool $expectedIsAvailable,
        float $expectedProductPrice,
        string $expectedProductStockLevel,
        string $expectedProductSeller
    ): void {
        $this->responseStub->method('getBody')->willReturn($productPageHtml);

        $this->httpClientMock->expects($this->once())->method('request')
            ->with('GET', self::PRODUCT_URL, EmagProductChecker::REQUEST_OPTIONS_HEADERS)
            ->willReturn($this->responseStub);

        $actualResult = $this->productChecker->checkProduct(self::PRODUCT_URL, $productMaxPrice);

        $this->assertSame($expectedIsAvailable, $actualResult->isAvailable(), 'Different result for "isAvailable"');
        $this->assertSame($expectedProductPrice, $actualResult->getProductData()->getPrice(), 'Different result for product price');
        $this->assertSame($expectedProductStockLevel, $actualResult->getProductData()->getStockLevel(), 'Different result for product stock level');
        $this->assertSame($expectedProductSeller, $actualResult->getProductData()->getSeller(), 'Different result for product seller');
    }

    public function providerProductPages()
    {
        return [
            'stock ok, seller Emag, price ok' => [
                file_get_contents(self::HTML_FILES_PATH . 'stock-ok-seller-emag.html'),
                1900,
                true,
                1899.99,
                'În stoc',
                'eMAG',
            ],
            'stock ok, seller Emag, price high' => [
                file_get_contents(self::HTML_FILES_PATH . 'stock-ok-seller-emag.html'),
                1800,
                false,
                1899.99,
                'În stoc',
                'eMAG',
            ],
            'stock ok, seller other, price ok' => [
                file_get_contents(self::HTML_FILES_PATH . 'stock-ok-seller-other.html'),
                100,
                false,
                99.22,
                'În stoc',
                'Paraduck',
            ],
            'stock ok, seller other, price high' => [
                file_get_contents(self::HTML_FILES_PATH . 'stock-ok-seller-other.html'),
                99,
                false,
                99.22,
                'În stoc',
                'Paraduck',
            ],
            'stock limited, seller other, price ok' => [
                file_get_contents(self::HTML_FILES_PATH . 'stock-limited-seller-other.html'),
                700,
                false,
                650.0,
                'Stoc limitat',
                'Reinas Siempre',
            ],
            'stock limited, seller other, price high' => [
                file_get_contents(self::HTML_FILES_PATH . 'stock-limited-seller-other.html'),
                600,
                false,
                650.0,
                'Stoc limitat',
                'Reinas Siempre',
            ],
            'stock last few, seller Emag, price ok' => [
                file_get_contents(self::HTML_FILES_PATH . 'stock-last-few-seller-emag.html'),
                345,
                true,
                344.99,
                'Ultimele 3 produse',
                'eMAG',
            ],
            'stock last few, seller Emag, price high' => [
                file_get_contents(self::HTML_FILES_PATH . 'stock-last-few-seller-emag.html'),
                344,
                false,
                344.99,
                'Ultimele 3 produse',
                'eMAG',
            ],
            'stock last few, seller other, price ok' => [
                file_get_contents(self::HTML_FILES_PATH . 'stock-last-few-seller-other.html'),
                100,
                false,
                71.40,
                'Ultimele 2 produse',
                'RaftOnline',
            ],
            'stock last few, seller other, price high' => [
                file_get_contents(self::HTML_FILES_PATH . 'stock-last-few-seller-other.html'),
                70,
                false,
                71.40,
                'Ultimele 2 produse',
                'RaftOnline',
            ],
            'stock last one, seller Emag, price ok' => [
                file_get_contents(self::HTML_FILES_PATH . 'stock-last-one-seller-emag.html'),
                300,
                true,
                239.99,
                'Ultimul produs in stoc',
                'eMAG',
            ],
            'stock last one, seller Emag, price high' => [
                file_get_contents(self::HTML_FILES_PATH . 'stock-last-one-seller-emag.html'),
                200,
                false,
                239.99,
                'Ultimul produs in stoc',
                'eMAG',
            ],
            'stock last one, seller other, price ok' => [
                file_get_contents(self::HTML_FILES_PATH . 'stock-last-one-seller-other.html'),
                10,
                false,
                7.72,
                'Ultimul produs in stoc',
                'Alliance Computers',
            ],
            'stock last one, seller other, price high' => [
                file_get_contents(self::HTML_FILES_PATH . 'stock-last-one-seller-other.html'),
                7,
                false,
                7.72,
                'Ultimul produs in stoc',
                'Alliance Computers',
            ],
            'stock preorder, seller Emag, price ok' => [
                file_get_contents(self::HTML_FILES_PATH . 'stock-preorder-seller-emag.html'),
                300,
                true,
                299.99,
                'Precomandă',
                'eMAG',
            ],
            'stock preorder, seller Emag, price high' => [
                file_get_contents(self::HTML_FILES_PATH . 'stock-preorder-seller-emag.html'),
                250,
                false,
                299.99,
                'Precomandă',
                'eMAG',
            ],
            'stock zero, seller Emag, price ok' => [
                file_get_contents(self::HTML_FILES_PATH . 'stock-zero-seller-emag.html'),
                300,
                false,
                229.99,
                'Stoc epuizat',
                'eMAG',
            ],
            'stock zero, seller Emag, price high' => [
                file_get_contents(self::HTML_FILES_PATH . 'stock-zero-seller-emag.html'),
                200,
                false,
                229.99,
                'Stoc epuizat',
                'eMAG',
            ],
            'stock zero, seller other, price ok' => [
                file_get_contents(self::HTML_FILES_PATH . 'stock-zero-seller-other.html'),
                5000,
                false,
                4399.0,
                'Stoc epuizat',
                'VIGAFON',
            ],
            'stock zero, seller other, price high' => [
                file_get_contents(self::HTML_FILES_PATH . 'stock-zero-seller-other.html'),
                3000,
                false,
                4399.0,
                'Stoc epuizat',
                'VIGAFON',
            ],
        ];
    }

    public function testCheckProductFailWhenStockIsUnavailable(): void {
        $productMaxPrice = 300;
        $productPageHtml = file_get_contents(self::HTML_FILES_PATH . 'stock-unavailable-seller-emag.html');
        $this->responseStub->method('getBody')->willReturn($productPageHtml);

        $this->httpClientMock->expects($this->once())->method('request')
            ->with('GET', self::PRODUCT_URL, EmagProductChecker::REQUEST_OPTIONS_HEADERS)
            ->willReturn($this->responseStub);

        $this->expectException(EmagProductCheckerException::class);
        $this->expectExceptionMessage('Error parsing the product price. Did not find the DOM element.');

        $this->productChecker->checkProduct(self::PRODUCT_URL, $productMaxPrice);
    }

    /**
     * @dataProvider providerStockMissing
     */
    public function testCheckProductFailWhenStockIsMissing(string $productPageHtml): void {
        $productMaxPrice = 3000;
        $this->responseStub->method('getBody')->willReturn($productPageHtml);

        $this->httpClientMock->expects($this->once())->method('request')
            ->with('GET', self::PRODUCT_URL, EmagProductChecker::REQUEST_OPTIONS_HEADERS)
            ->willReturn($this->responseStub);

        $this->expectException(EmagProductCheckerException::class);
        $this->expectExceptionMessage('Error parsing the product stock. Did not find the DOM element.');

        $this->productChecker->checkProduct(self::PRODUCT_URL, $productMaxPrice);
    }

    public function providerStockMissing()
    {
        return [
            'stock zero and missing' => [
                file_get_contents(self::HTML_FILES_PATH . 'missing-stock-zero.html'),
            ],
            'stock ok but missing ("see more offers" page)' => [
                file_get_contents(self::HTML_FILES_PATH . 'missing-stock-ok.html'),
            ],
        ];
    }
}
