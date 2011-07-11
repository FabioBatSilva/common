<?php

namespace Doctrine\Tests\Common\Annotations\Fixtures;

use Doctrine\Tests\Common\Annotations\Fixtures\Annotation\AnnotationTargetClass;
use Doctrine\Tests\Common\Annotations\Fixtures\Annotation\MarkedAnnotation;

/**
 * @AnnotationTargetClass("Some data")
 */
class MarkedClassName
{

    /**
     * @MarkedAnnotation("Some data")
     */
    public $foo;
    
    
    /**
     * @MarkedAnnotation("Some data",name="Some name")
     */
    public $name;
    
    /**
     * @MarkedAnnotation("Some data",name="Some name")
     */
    public function someFunction()
    {
        
    }

}