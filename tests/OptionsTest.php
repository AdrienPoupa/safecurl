<?php

use fin1te\SafeCurl\Options;

class OptionsTest extends \PHPUnit\Framework\TestCase
{
    private $options;

    protected function setUp(): void
    {
        $this->options = new Options();
    }

    public function testFollowlocation()
    {
        $this->assertFalse($this->options->getFollowLocation());

        $this->options->enableFollowLocation();

        $this->assertTrue($this->options->getFollowLocation());

        $this->options->disableFollowLocation();

        $this->assertFalse($this->options->getFollowLocation());
    }

    public function testFollowlocationLimit()
    {
        $this->assertEquals(0, $this->options->getFollowLocationLimit());

        $this->options->setFollowLocationLimit(10);

        $this->assertEquals(10, $this->options->getFollowLocationLimit());
    }

    public function dataForFollowlocationLimit()
    {
        return array(
            array(-1),
            array('"é"é"é'),
            array(null),
        );
    }

    /**
     * @dataProvider dataForFollowlocationLimit
     */
    public function testFollowlocationLimitException($limit)
    {
        $this->expectException(\fin1te\SafeCurl\Exception\InvalidOptionException::class);
        $this->expectExceptionMessage('Provided limit');

        $this->options->setFollowLocationLimit($limit);
    }

    public function testSendCredentials()
    {
        $this->assertFalse($this->options->getSendCredentials());

        $this->options->enableSendCredentials();

        $this->assertTrue($this->options->getSendCredentials());

        $this->options->disableSendCredentials();

        $this->assertFalse($this->options->getSendCredentials());
    }

    public function testPinDns()
    {
        $this->assertFalse($this->options->getPinDns());

        $this->options->enablePinDns();

        $this->assertTrue($this->options->getPinDns());

        $this->options->disablePinDns();

        $this->assertFalse($this->options->getPinDns());
    }

    public function testInListEmptyValue()
    {
        $this->assertTrue($this->options->isInList('whitelist', 'ip', ''));
        $this->assertFalse($this->options->isInList('whitelist', 'port', ''));
        $this->assertTrue($this->options->isInList('whitelist', 'domain', ''));
        $this->assertFalse($this->options->isInList('whitelist', 'scheme', ''));

        $this->assertFalse($this->options->isInList('blacklist', 'ip', ''));
        $this->assertFalse($this->options->isInList('blacklist', 'port', ''));
        $this->assertFalse($this->options->isInList('blacklist', 'domain', ''));
        $this->assertFalse($this->options->isInList('blacklist', 'scheme', ''));
    }

    public function testInListDomainRegex()
    {
        $this->options->addToList('whitelist', 'domain', '(.*)\.fin1te\.net');

        $this->assertFalse($this->options->isInList('whitelist', 'domain', ''));
        $this->assertFalse($this->options->isInList('whitelist', 'domain', 'fin1te.net'));
        $this->assertFalse($this->options->isInList('whitelist', 'domain', 'superfin1te.net'));
        $this->assertTrue($this->options->isInList('whitelist', 'domain', 'www.fin1te.net'));
    }

    public function testInListBadList()
    {
        $this->expectException(\fin1te\SafeCurl\Exception\InvalidOptionException::class);
        $this->expectExceptionMessage('Provided list "noo" must be "whitelist" or "blacklist"');

        $this->options->isInList('noo', 'domain', '');
    }

    public function testInListBadType()
    {
        $this->expectException(\fin1te\SafeCurl\Exception\InvalidOptionException::class);
        $this->expectExceptionMessage('Provided type "noo" must be "ip", "port", "domain" or "scheme"');

        $this->options->isInList('whitelist', 'noo', '');
    }

    public function testGetListWithoutType()
    {
        $list = $this->options->getList('whitelist');

        $this->assertCount(4, $list);
        $this->assertArrayHasKey('ip', $list);
        $this->assertArrayHasKey('port', $list);
        $this->assertArrayHasKey('domain', $list);
        $this->assertArrayHasKey('scheme', $list);

        $list = $this->options->getList('blacklist');

        $this->assertCount(4, $list);
        $this->assertArrayHasKey('ip', $list);
        $this->assertArrayHasKey('port', $list);
        $this->assertArrayHasKey('domain', $list);
        $this->assertArrayHasKey('scheme', $list);
    }

    public function testGetListWhitelistWithType()
    {
        $this->options->addToList('whitelist', 'ip', '0.0.0.0');
        $list = $this->options->getList('whitelist', 'ip');

        $this->assertCount(1, $list);
        $this->assertArrayHasKey(0, $list);
        $this->assertEquals('0.0.0.0', $list[0]);

        $list = $this->options->getList('whitelist', 'port');

        $this->assertCount(3, $list);
        $this->assertEquals('80', $list[0]);
        $this->assertEquals('443', $list[1]);
        $this->assertEquals('8080', $list[2]);

        $this->options->addToList('whitelist', 'domain', '(.*)\.fin1te\.net');
        $list = $this->options->getList('whitelist', 'domain');

        $this->assertCount(1, $list);
        $this->assertEquals('(.*)\.fin1te\.net', $list[0]);

        $list = $this->options->getList('whitelist', 'scheme');

        $this->assertCount(2, $list);
        $this->assertEquals('http', $list[0]);
        $this->assertEquals('https', $list[1]);
    }

    public function testGetListBlacklistWithType()
    {
        $list = $this->options->getList('blacklist', 'ip');

        $this->assertCount(15, $list);
        $this->assertEquals('0.0.0.0/8', $list[0]);

        $this->options->addToList('blacklist', 'port', '8080');
        $list = $this->options->getList('blacklist', 'port');

        $this->assertCount(1, $list);
        $this->assertEquals('8080', $list[0]);

        $this->options->addToList('blacklist', 'domain', '(.*)\.fin1te\.net');
        $list = $this->options->getList('blacklist', 'domain');

        $this->assertCount(1, $list);
        $this->assertEquals('(.*)\.fin1te\.net', $list[0]);

        $this->options->addToList('blacklist', 'scheme', 'ftp');
        $list = $this->options->getList('blacklist', 'scheme');

        $this->assertCount(1, $list);
        $this->assertEquals('ftp', $list[0]);
    }

    public function testGetListBadList()
    {
        $this->expectException(\fin1te\SafeCurl\Exception\InvalidOptionException::class);
        $this->expectExceptionMessage('Provided list "noo" must be "whitelist" or "blacklist"');

        $this->options->getList('noo');
    }

    public function testGetListBadType()
    {
        $this->expectException(\fin1te\SafeCurl\Exception\InvalidOptionException::class);
        $this->expectExceptionMessage('Provided type "noo" must be "ip", "port", "domain" or "scheme"');

        $this->options->getList('whitelist', 'noo');
    }

    public function testSetList()
    {
        $this->options->setList('whitelist', array('ip' => array('0.0.0.0')));

        $this->assertEquals(array('0.0.0.0'), $this->options->getList('whitelist', 'ip'));

        $this->options->setList('blacklist', array(22), 'port');

        $this->assertEquals(array(22), $this->options->getList('blacklist', 'port'));
    }

    public function testSetListBadList()
    {
        $this->expectException(\fin1te\SafeCurl\Exception\InvalidOptionException::class);
        $this->expectExceptionMessage('Provided list "noo" must be "whitelist" or "blacklist"');

        $this->options->setList('noo', array());
    }

    public function testSetListBadValue()
    {
        $this->expectException(\fin1te\SafeCurl\Exception\InvalidOptionException::class);
        $this->expectExceptionMessage('Provided values must be an array, "integer" given');

        $this->options->setList('whitelist', 12);
    }

    public function testSetListBadType()
    {
        $this->expectException(\fin1te\SafeCurl\Exception\InvalidOptionException::class);
        $this->expectExceptionMessage('Provided type "noo" must be "ip", "port", "domain" or "scheme"');

        $this->options->setList('whitelist', array(), 'noo');
    }

    public function testSetListBadTypeValue()
    {
        $this->expectException(\fin1te\SafeCurl\Exception\InvalidOptionException::class);
        $this->expectExceptionMessage('Provided type "noo" must be "ip", "port", "domain" or "scheme"');

        $this->options->setList('whitelist', array('noo' => 'oops'));
    }

    public function testAddToListBadList()
    {
        $this->expectException(\fin1te\SafeCurl\Exception\InvalidOptionException::class);
        $this->expectExceptionMessage('Provided list "noo" must be "whitelist" or "blacklist"');

        $this->options->addToList('noo', 'noo', 'noo');
    }

    public function testAddToListBadType()
    {
        $this->expectException(\fin1te\SafeCurl\Exception\InvalidOptionException::class);
        $this->expectExceptionMessage('Provided type "noo" must be "ip", "port", "domain" or "scheme"');

        $this->options->addToList('whitelist', 'noo', 'noo');
    }

    public function testAddToListBadValue()
    {
        $this->expectException(\fin1te\SafeCurl\Exception\InvalidOptionException::class);
        $this->expectExceptionMessage('Provided values cannot be empty');

        $this->options->addToList('whitelist', 'ip', null);
    }

    public function testRemoveFromListBadList()
    {
        $this->expectException(\fin1te\SafeCurl\Exception\InvalidOptionException::class);
        $this->expectExceptionMessage('Provided list "noo" must be "whitelist" or "blacklist"');

        $this->options->removeFromList('noo', 'noo', 'noo');
    }

    public function testRemoveFromListBadType()
    {
        $this->expectException(\fin1te\SafeCurl\Exception\InvalidOptionException::class);
        $this->expectExceptionMessage('Provided type "noo" must be "ip", "port", "domain" or "scheme"');

        $this->options->removeFromList('whitelist', 'noo', 'noo');
    }

    public function testRemoveFromListBadValue()
    {
        $this->expectException(\fin1te\SafeCurl\Exception\InvalidOptionException::class);
        $this->expectExceptionMessage('Provided values cannot be empty');

        $this->options->removeFromList('whitelist', 'ip', null);
    }

    public function testRemoveFromList()
    {
        // remove not an array
        $this->options->addToList('blacklist', 'port', '8080');
        $list = $this->options->getList('blacklist', 'port');

        $this->assertCount(1, $list);
        $this->assertEquals('8080', $list[0]);

        $this->options->removeFromList('blacklist', 'port', '8080');
        $list = $this->options->getList('blacklist', 'port');

        $this->assertCount(0, $list);

        // remove using an array
        $this->options->addToList('blacklist', 'scheme', 'ftp');
        $list = $this->options->getList('blacklist', 'scheme');

        $this->assertCount(1, $list);
        $this->assertEquals('ftp', $list[0]);

        $this->options->removeFromList('blacklist', 'scheme', array('ftp'));
        $list = $this->options->getList('blacklist', 'scheme');

        $this->assertCount(0, $list);
    }
}
