<?php

namespace Doctrine\Tests\Common\Annotations\Fixtures;

use Doctrine\Tests\Common\Annotations\Fixtures\Annotation\AnnotationTargetClass;

/**
 * @AnnotationTargetClass("Some data")
 */
class MarkedClassNameWithInvalidProperty
{
    
    /**
     * @AnnotationTargetClass("Bar")
     */
    public $foo;
}