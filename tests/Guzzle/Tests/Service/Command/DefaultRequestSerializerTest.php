<?php

namespace Guzzle\Tests\Service\Command;

use Guzzle\Service\Command\DefaultRequestSerializer;
use Guzzle\Http\Message\EntityEnclosingRequest;
use Guzzle\Service\Client;
use Guzzle\Service\Description\Operation;
use Guzzle\Service\Description\Parameter;
use Guzzle\Service\Command\LocationVisitor\Request\HeaderVisitor;

/**
 * @covers Guzzle\Service\Command\DefaultRequestSerializer
 */
class DefaultRequestSerializerTest extends \Guzzle\Tests\GuzzleTestCase
{
    /**
     * @var EntityEnclosingRequest
     */
    protected $request;

    /**
     * @var \Guzzle\Service\Command\AbstractCommand
     */
    protected $command;

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var DefaultRequestSerializer
     */
    protected $serializer;

    /**
     * @var Operation
     */
    protected $operation;

    public function setUp()
    {
        $this->serializer = DefaultRequestSerializer::getInstance();
        $this->client = new Client('http://foo.com/baz');
        $this->operation = new Operation(array('httpMethod' => 'POST'));
        $this->command = $this->getMockBuilder('Guzzle\Service\Command\AbstractCommand')
            ->setConstructorArgs(array(array(), $this->operation))
            ->getMockForAbstractClass();
        $this->command->setClient($this->client);
    }

    public function testAllowsCustomVisitor()
    {
        $this->serializer->addVisitor('custom', new HeaderVisitor());
        $this->command['test'] = '123';
        $this->operation->addParam(new Parameter(array('name' => 'test', 'location' => 'custom')));
        $request = $this->serializer->prepare($this->command);
        $this->assertEquals('123', (string) $request->getHeader('test'));
    }

    public function testUsesRelativePath()
    {
        $this->operation->setUri('bar');
        $request = $this->serializer->prepare($this->command);
        $this->assertEquals('http://foo.com/baz/bar', (string) $request->getUrl());
    }

    public function testUsesRelativePathWithUriLocations()
    {
        $this->command['test'] = '123';
        $this->operation->setUri('bar/{test}');
        $this->operation->addParam(new Parameter(array('name' => 'test', 'location' => 'uri')));
        $request = $this->serializer->prepare($this->command);
        $this->assertEquals('http://foo.com/baz/bar/123', (string) $request->getUrl());
    }

    public function testConstructorAddsVisitors()
    {
        $serializer = new DefaultRequestSerializer(array());
        $this->assertEmpty($this->readAttribute($serializer, 'visitors'));
    }

    public function testMixedParams()
    {
        $this->operation->setUri('bar{?limit,fields}');
        $this->operation->addParam(new Parameter(array(
            'name' => 'limit',
            'location' => 'uri',
            'required' => false,
        )));
        $this->operation->addParam(new Parameter(array(
            'name' => 'fields',
            'location' => 'uri',
            'required' => true,
        )));

        $this->command['fields'] = array('id', 'name');

        $request = $this->serializer->prepare($this->command);
        $this->assertEquals('http://foo.com/baz/bar?fields='.urlencode('id,name'), (string) $request->getUrl());
    }
}
