<?php

namespace Doctrine\Tests\Mocks;

use Doctrine\Common\Persistence\Mapping\ClassMetadataFactory;

class ClassMetadataFactoryMock implements ClassMetadataFactory
{
    private $metadata;

    public function __construct(array $metadata)
    {
        $this->metadata = $metadata;
    }

    public function getAllMetadata()
    {
        return $this->metadata;
    }

    public function getMetadataFor($className)
    {
        if (isset($this->metadata[$className])) {
            return $this->metadata[$className];
        }

        return null;
    }

    public function hasMetadataFor($className)
    {
        return isset($this->metadata[$className]);
    }

    public function isTransient($className)
    {
        return ! $this->hasMetadataFor($className);
    }

    public function setMetadataFor($className, $class)
    {
        $this->metadata[$className] = $class;
    }
}