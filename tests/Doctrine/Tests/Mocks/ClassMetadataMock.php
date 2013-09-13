<?php

namespace Doctrine\Tests\Mocks;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;

class ClassMetadataMock implements ClassMetadata
{
    public function getAssociationMappedByTargetField($assocName){}
    public function getAssociationNames(){}
    public function getAssociationTargetClass($assocName){}
    public function getFieldNames(){}
    public function getIdentifier(){}
    public function getIdentifierFieldNames(){}
    public function getIdentifierValues($object){}
    public function getName(){}
    public function getReflectionClass(){}
    public function getTypeOfField($fieldName){}
    public function hasAssociation($fieldName){}
    public function hasField($fieldName){}
    public function isAssociationInverseSide($assocName){}
    public function isCollectionValuedAssociation($fieldName){}
    public function isIdentifier($fieldName){}
    public function isSingleValuedAssociation($fieldName){}
}