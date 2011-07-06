<?php

namespace Doctrine\Tests\Common\Annotations\Fixtures;

use Doctrine\Tests\Common\Annotations\Fixtures\Annotation\AnnotationTargetClass;

/**
 * @AnnotationTargetClass("Some data")
 */
class MarkedClassNameWithInvalidMethod
{
    
    /**
     * @AnnotationTargetClass("functionName")
     */
    public function functionName($param)
    {
        
    }
}