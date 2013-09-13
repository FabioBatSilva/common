<?php

namespace Doctrine\Tests\Common\Persistence\Mapping;

use Doctrine\Tests\DoctrineTestCase;
use Doctrine\Tests\Mocks\ClassMetadataMock;
use Doctrine\Common\Persistence\Mapping\CompiledClassMetadataFactory;

class CompiledClassMetadataFactoryTest extends DoctrineTestCase
{
    /**
     * @var \Doctrine\Common\Persistence\Mapping\ClassMetadataFactory
     */
    private $baseCmf;

    /**
     * @var \Doctrine\Common\Persistence\Mapping\CompiledClassMetadataFactory
     */
    private $cmf;

    /**
     * @var Doctrine\Tests\Common\Persistence\Mapping\CompiledClassMetadata
     */
    private $metadata;

    /**
     * @var string
     */
    private $directory;

    public function setUp()
    {
        $driver          = $this->getMock('Doctrine\Common\Persistence\Mapping\Driver\MappingDriver');
        $this->metadata  = new CompiledClassMetadata();
        $this->baseCmf   = new TestClassMetadataFactory($driver, $this->metadata);
        $this->cmf       = new CompiledClassMetadataFactory($this->baseCmf);
        $this->directory = sys_get_temp_dir() . "/doctrine_metadata_". uniqid();

        $this->assertTrue(mkdir($this->directory));
        $this->cmf->setDirectory($this->directory);
    }

    public function tearDown()
    {
        foreach (glob("$this->directory/*") as $file) {
            unlink($file);
        }
    }

    public function testGetMetadataFor()
    {
        $this->metadata->isReadOnly = true;
        $this->metadata->namespace  = __NAMESPACE__;
        $this->metadata->name       = __NAMESPACE__ . '\CompiledUser';
        $this->metadata->reflClass  = new \ReflectionClass($this->metadata->name);
        $this->metadata->fieldNames = array(
            'foo',
            'bar'
        );

        $metadata = $this->cmf->getMetadataFor($this->metadata->name);

        $this->assertInstanceOf('Doctrine\Common\Persistence\Mapping\ClassMetadata', $metadata);
        $this->assertTrue($this->cmf->hasMetadataFor($this->metadata->name));

        $this->assertNull($metadata->reflClass);
        $this->assertNull($metadata->getPrototype());
        $this->assertEquals($this->metadata->name, $metadata->name);
        $this->assertEquals($this->metadata->namespace, $metadata->namespace);
        $this->assertEquals($this->metadata->fieldNames, $metadata->fieldNames);
        $this->assertEquals($this->metadata->isReadOnly, $metadata->isReadOnly);
    }
}


class CompiledUser
{

}

class CompiledClassMetadata extends ClassMetadataMock
{
    public $name;

    public $namespace;

    public $type = 1;

    public $fieldNames = array();

    public $reflClass;

    public $isReadOnly = false;

    private $prototype;

    public function __sleep()
    {
        return array('name', 'namespace', 'type', 'fieldNames', 'isReadOnly');
    }

    public function getPrototype()
    {
        return $this->prototype;
    }
}
