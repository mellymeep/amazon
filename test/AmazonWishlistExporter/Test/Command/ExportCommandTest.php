<?php

namespace AmazonWishlistExporter\Test\Command;

use AmazonWishlistExporter\Command\ExportCommand;
use AmazonWishlistExporter\Crawler\AmazonCrawler;

class ExportCommandTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $clientMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $loggerMock;

    /**
     * @var string
     */
    private $responseContentPage1Fixture;

    /**
     * @var string
     */
    private $responseContentPage2Fixture;

    /**
     * @var string
     */
    private $responseContentPage3Fixture;

    protected function setUp()
    {
        $this->clientMock = $this->getMock('\\GuzzleHttp\\ClientInterface');
        $this->loggerMock = $this->getMock('\\Psr\Log\LoggerInterface');

        $this->responseContentPage1Fixture = <<<RESPONSE
        <span class="profile-layout-aid-top">
    <span class="a-size-extra-large stable clip-text">
      <span>
        Wishlist Title
      </span>
    </span>
</span>
<div id="item_1">
    <a id="itemName_1" href="/product-1">Product 1</a>
    <div id="itemPrice_1">$ 1.99</div>
    <div id="itemImage_1"><img src="test://product-1.png" /></div>
</div>
<div id="item_2">
    <a id="itemName_2" href="/product-2">Product 2</a>
    <div id="itemPrice_2">$ 2.99</div>
    <div id="itemImage_2"><img src="test://product-2.png" /></div>
</div>
<div id="item_3">
    <a id="itemName_3" href="/product-3">Product 3</a>
    <div id="itemPrice_3">$ 3.99</div>
    <div id="itemImage_3"><img src="test://product-3.png" /></div>
</div>
RESPONSE;

        $this->responseContentPage2Fixture = <<<RESPONSE
        <span class="profile-layout-aid-top">
    <span class="a-size-extra-large stable clip-text">
      <span>
        Wishlist Title
      </span>
    </span>
</span>
<div id="item_4">
    <a id="itemName_4" href="/product-4">Product 4</a>
    <div id="itemPrice_4">$ 4.99</div>
    <div id="itemImage_4"><img src="test://product-4.png" /></div>
</div>
<div id="item_5">
    <a id="itemName_5" href="/product-5">Product 5</a>
    <div id="itemPrice_5">$ 5.99</div>
    <div id="itemImage_5"><img src="test://product-5.png" /></div>
</div>
<div id="item_6">
    <a id="itemName_6" href="/product-6">Product 6</a>
    <div id="itemPrice_6">$ 6.99</div>
    <div id="itemImage_6"><img src="test://product-6.png" /></div>
</div>
RESPONSE;

        $this->responseContentPage3Fixture = <<<RESPONSE
<div id="item_7">
    <a id="itemName_7" href="/product-7">Product 7</a>
    <div id="itemPrice_7">$ 7.99</div>
    <div id="itemImage_7"><img src="test://product-7.png" /></div>
</div>
<div id="item_8">
    <a id="itemName_8" href="/product-8">Product 8</a>
    <div id="itemPrice_8">$ 8.99</div>
    <div id="itemImage_8"><img src="test://product-8.png" /></div>
</div>
<div id="item_9">
    <a id="itemName_9" href="/product-9">Product 9</a>
    <div id="itemPrice_9">$ 9.99</div>
    <div id="itemImage_9"><img src="test://product-9.png" /></div>
</div>
RESPONSE;

        $this->unexpectedResponse = <<<RESPONSE
<div id="bad-bad-server">
    <h1>No donut for you...</h1>
</div>
RESPONSE;
    }

    public function testFetchTitle()
    {
        $this->setExpectedException('\\InvalidArgumentException');
        $crawler = new AmazonCrawler($this->clientMock, $this->loggerMock, 'ABC123', 'XX');
        $this->assertEquals('Wishlist Title', $crawler->crawlTitle());
    }

    public function testExecuteWithInvalidArguments()
    {
        $this->setExpectedException('\\InvalidArgumentException');
        $crawler = new AmazonCrawler($this->clientMock, $this->loggerMock, 'ABC123', 'XX');
        $crawler->crawlItems();

    }

    public function testExecuteWithInvalidWishlistId()
    {
        $responseMock = $this->getMock('\\GuzzleHttp\\Message\\ResponseInterface');
        $responseMock
            ->expects($this->once())
            ->method('getStatusCode')
            ->will($this->returnValue(404));

        $this->clientMock
            ->expects($this->once())
            ->method('get')
            ->will($this->returnValue($responseMock));

        // TODO: Check how to check multiple calls with different parameters
        $this->loggerMock
            ->expects($this->exactly(3))
            ->method('info')
            ->withAnyParameters();

        $crawler = new AmazonCrawler($this->clientMock, $this->loggerMock, 'ABC123', 'DE');
        $rows = $crawler->crawlItems();

        $this->assertEmpty($rows);
    }

    public function testExecuteWithNotPublicWishlistOrUnexpectedContent()
    {
        $responseMock = $this->getMock('\\GuzzleHttp\\Message\\ResponseInterface');
        $responseMock
            ->expects($this->once())
            ->method('getStatusCode')
            ->will($this->returnValue(200));

        $responseMock
            ->expects($this->exactly(1))
            ->method('getBody')
            ->will($this->returnValue($this->unexpectedResponse));

        $this->clientMock
            ->expects($this->once())
            ->method('get')
            ->will($this->returnValue($responseMock));

        // TODO: Check how to check multiple calls with different parameters
        $this->loggerMock
            ->expects($this->exactly(3))
            ->method('log')
            ->withAnyParameters();

        $crawler = new AmazonCrawler($this->clientMock, $this->loggerMock, 'ABC123', 'UK');
        $rows = $crawler->crawlItems();

        $this->assertEmpty($rows);
    }

    public function testExecuteOnUsWithSinglePageOfItems()
    {
        $responseMock = $this->getMock('\\GuzzleHttp\\Message\\ResponseInterface');
        $responseMock
            ->expects($this->exactly(2))
            ->method('getStatusCode')
            ->will($this->returnValue(200));

        $responseMock
            ->expects($this->exactly(2))
            ->method('getBody')
            ->will($this->returnValue($this->responseContentPage1Fixture));

        $this->clientMock
            ->expects($this->exactly(2))
            ->method('get')
            ->will($this->returnValue($responseMock));

        $this->loggerMock
            ->expects($this->exactly(4))
            ->method('log')
            ->withAnyParameters();


        $crawler = new AmazonCrawler($this->clientMock, $this->loggerMock, 'ABC123', 'US');
        $rows = $crawler->crawlItems();


        $expectedResult = [
            ['name' => 'Product 1', 'price' => 1.99, 'url' => 'http://www.amazon.com/product-1', 'image' => 'test://product-1.png'],
            ['name' => 'Product 2', 'price' => 2.99, 'url' => 'http://www.amazon.com/product-2', 'image' => 'test://product-2.png'],
            ['name' => 'Product 3', 'price' => 3.99, 'url' => 'http://www.amazon.com/product-3', 'image' => 'test://product-3.png'],
        ];

        $this->assertEquals($expectedResult, $rows);
    }

    public function testExecuteOnUkWithSinglePageOfItems()
    {
        $responseMock = $this->getMock('\\GuzzleHttp\\Message\\ResponseInterface');
        $responseMock
            ->expects($this->exactly(2))
            ->method('getStatusCode')
            ->will($this->returnValue(200));

        $responseMock
            ->expects($this->exactly(2))
            ->method('getBody')
            ->will($this->returnValue($this->responseContentPage1Fixture));

        $this->clientMock
            ->expects($this->exactly(2))
            ->method('get')
            ->will($this->returnValue($responseMock));

        $this->loggerMock
            ->expects($this->exactly(4))
            ->method('log')
            ->withAnyParameters();


        $crawler = new AmazonCrawler($this->clientMock, $this->loggerMock, 'ABC123', 'UK');
        $result = $crawler->crawlItems();

        $expectedResult = [
            ['name' => 'Product 1', 'price' => 1.99, 'url' => 'http://www.amazon.co.uk/product-1', 'image' => 'test://product-1.png'],
            ['name' => 'Product 2', 'price' => 2.99, 'url' => 'http://www.amazon.co.uk/product-2', 'image' => 'test://product-2.png'],
            ['name' => 'Product 3', 'price' => 3.99, 'url' => 'http://www.amazon.co.uk/product-3', 'image' => 'test://product-3.png'],
        ];

        $this->assertEquals($expectedResult, $result);
    }

    public function testExecuteOnUsWithMultiplePagesOfItems()
    {
        $responseMock = $this->getMock('\\GuzzleHttp\\Message\\ResponseInterface');
        $responseMock
            ->expects($this->exactly(4))
            ->method('getStatusCode')
            ->will($this->returnValue(200));

        $responseMock
            ->expects($this->exactly(4))
            ->method('getBody')
            ->will($this->onConsecutiveCalls(
                $this->responseContentPage1Fixture,
                $this->responseContentPage2Fixture,
                $this->responseContentPage3Fixture,
                $this->responseContentPage3Fixture
            ));

        $this->clientMock
            ->expects($this->exactly(4))
            ->method('get')
            ->will($this->returnValue($responseMock));

        $this->loggerMock
            ->expects($this->exactly(6))
            ->method('log')
            ->withAnyParameters();
        $crawler = new AmazonCrawler($this->clientMock, $this->loggerMock, 'ABC123', 'US');
        $result = $crawler->crawlItems();

        $expectedResult = [
            ['name' => 'Product 1', 'price' => 1.99, 'url' => 'http://www.amazon.com/product-1', 'image' => 'test://product-1.png'],
            ['name' => 'Product 2', 'price' => 2.99, 'url' => 'http://www.amazon.com/product-2', 'image' => 'test://product-2.png'],
            ['name' => 'Product 3', 'price' => 3.99, 'url' => 'http://www.amazon.com/product-3', 'image' => 'test://product-3.png'],
            ['name' => 'Product 4', 'price' => 4.99, 'url' => 'http://www.amazon.com/product-4', 'image' => 'test://product-4.png'],
            ['name' => 'Product 5', 'price' => 5.99, 'url' => 'http://www.amazon.com/product-5', 'image' => 'test://product-5.png'],
            ['name' => 'Product 6', 'price' => 6.99, 'url' => 'http://www.amazon.com/product-6', 'image' => 'test://product-6.png'],
            ['name' => 'Product 7', 'price' => 7.99, 'url' => 'http://www.amazon.com/product-7', 'image' => 'test://product-7.png'],
            ['name' => 'Product 8', 'price' => 8.99, 'url' => 'http://www.amazon.com/product-8', 'image' => 'test://product-8.png'],
            ['name' => 'Product 9', 'price' => 9.99, 'url' => 'http://www.amazon.com/product-9', 'image' => 'test://product-9.png'],
        ];

        $this->assertEquals($expectedResult, $result);
    }

    public function testExecuteOnUkWithMultiplePagesOfItems()
    {
        $responseMock = $this->getMock('\\GuzzleHttp\\Message\\ResponseInterface');
        $responseMock
            ->expects($this->exactly(4))
            ->method('getStatusCode')
            ->will($this->returnValue(200));

        $responseMock
            ->expects($this->exactly(4))
            ->method('getBody')
            ->will($this->onConsecutiveCalls(
                $this->responseContentPage1Fixture,
                $this->responseContentPage2Fixture,
                $this->responseContentPage3Fixture,
                $this->responseContentPage3Fixture
            ));

        $this->clientMock
            ->expects($this->exactly(4))
            ->method('get')
            ->will($this->returnValue($responseMock));

        $this->loggerMock
            ->expects($this->exactly(6))
            ->method('log')
            ->withAnyParameters();


        $crawler = new AmazonCrawler($this->clientMock, $this->loggerMock, 'ABC123', 'UK');
        $result = $crawler->crawlItems();
        $expectedResult = [
            ['name' => 'Product 1', 'price' => 1.99, 'url' => 'http://www.amazon.co.uk/product-1', 'image' => 'test://product-1.png'],
            ['name' => 'Product 2', 'price' => 2.99, 'url' => 'http://www.amazon.co.uk/product-2', 'image' => 'test://product-2.png'],
            ['name' => 'Product 3', 'price' => 3.99, 'url' => 'http://www.amazon.co.uk/product-3', 'image' => 'test://product-3.png'],
            ['name' => 'Product 4', 'price' => 4.99, 'url' => 'http://www.amazon.co.uk/product-4', 'image' => 'test://product-4.png'],
            ['name' => 'Product 5', 'price' => 5.99, 'url' => 'http://www.amazon.co.uk/product-5', 'image' => 'test://product-5.png'],
            ['name' => 'Product 6', 'price' => 6.99, 'url' => 'http://www.amazon.co.uk/product-6', 'image' => 'test://product-6.png'],
            ['name' => 'Product 7', 'price' => 7.99, 'url' => 'http://www.amazon.co.uk/product-7', 'image' => 'test://product-7.png'],
            ['name' => 'Product 8', 'price' => 8.99, 'url' => 'http://www.amazon.co.uk/product-8', 'image' => 'test://product-8.png'],
            ['name' => 'Product 9', 'price' => 9.99, 'url' => 'http://www.amazon.co.uk/product-9', 'image' => 'test://product-9.png'],
        ];

        $this->assertEquals($expectedResult, $result);
    }
}