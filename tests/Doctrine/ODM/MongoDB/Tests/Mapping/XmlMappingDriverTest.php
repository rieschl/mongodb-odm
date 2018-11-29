<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Mapping;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Mapping\Driver\XmlDriver;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use ReflectionMethod;
use SimpleXmlElement;
use stdClass;
use const DIRECTORY_SEPARATOR;
use function get_class;

class XmlMappingDriverTest extends AbstractMappingDriverTest
{
    protected function _loadDriver()
    {
        return new XmlDriver(__DIR__ . DIRECTORY_SEPARATOR . 'xml');
    }

    public function testSetShardKeyOptionsByAttributes()
    {
        $class   = new ClassMetadata(stdClass::class);
        $driver  = $this->_loadDriver();
        $element = new SimpleXmlElement('<shard-key unique="true" numInitialChunks="4096"><key name="_id"/></shard-key>');

        /** @uses XmlDriver::setShardKey */
        $m = new ReflectionMethod(get_class($driver), 'setShardKey');
        $m->setAccessible(true);
        $m->invoke($driver, $class, $element);

        $this->assertTrue($class->isSharded());
        $shardKey = $class->getShardKey();
        $this->assertSame(['unique' => true, 'numInitialChunks' => 4096], $shardKey['options']);
        $this->assertSame(['_id' => 1], $shardKey['keys']);
    }

    public function testInvalidMappingFileTriggersException() : void
    {
        $className     = InvalidMappingDocument::class;
        $mappingDriver = $this->_loadDriver();

        $class = new ClassMetadata($className);

        $this->expectException(MappingException::class);
        $this->expectExceptionMessageRegExp("#Element '\{http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping\}field', attribute 'id': The attribute 'id' is not allowed.#");

        $mappingDriver->loadMetadataForClass($className, $class);
    }
}
