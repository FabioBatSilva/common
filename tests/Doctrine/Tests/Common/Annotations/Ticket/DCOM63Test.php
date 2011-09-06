<?php

namespace Doctrine\Tests\Common\Annotations\Ticket;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\FileCacheReader;

use Doctrine\Tests\Common\Annotations\Ticket\DCOM63Person;
use Doctrine\Tests\Common\Annotations\Ticket\DCOM63Employee;

/**
 * @group
 */
class DCOM63Test extends \PHPUnit_Framework_TestCase
{
 
    private static $cacheDir;

    /**
     * @return  Doctrine\Common\Annotations\FileCacheReader  
     */
    protected function getReader()
    {
        if (!is_dir(self::$cacheDir)) {
           mkdir(self::$cacheDir);
        }
        
        return new FileCacheReader(new AnnotationReader(), self::$cacheDir, true);
    }
    
    static function setUpBeforeClass()
    {
        self::$cacheDir = __DIR__ . "/tmp/annotations_dcom63";
    }
    
    /**
     * @runInSeparateProcess
     */
    public function testSetDefaultRoutePattern()
    {
        $this->changeRoutePattern("/person");
        $this->assertEquals(self::getClassFileContent("DCOM63Person"), self::getTplContent("/person"));
    }
    
    /**
     * @runInSeparateProcess
     * @depends testSetDefaultRoutePattern
     */
    public function testReadPropertyAnnotations()
    {
        include __DIR__ .'/DCOM63Person.php';
        include __DIR__ .'/DCOM63Employee.php';
        
        $property   = new \ReflectionProperty(__NAMESPACE__. "\DCOM63Employee","route");
        $annots     = $this->getReader()->getPropertyAnnotations($property);
        $content    = self::getClassFileContent("DCOM63Person");
        $tpl        = self::getTplContent("/person");
        
        $this->assertEquals($content,$tpl);
        $this->assertEquals(sizeof($annots),1);
        $this->assertEquals($annots[0]->pattern,"/person");
    }
    
    
    /**
     * @runInSeparateProcess
     * @depends testReadPropertyAnnotations
     */
    public function testSetOtherRoutePattern()
    {
        $this->changeRoutePattern("/employee");
        $this->assertEquals(self::getClassFileContent("DCOM63Person"), self::getTplContent("/employee"));
    }
    
    
    /**
     * @runInSeparateProcess
     * @depends testSetOtherRoutePattern
     */
    public function testIssue()
    {
        include __DIR__ .'/DCOM63Person.php';
        include __DIR__ .'/DCOM63Employee.php';
        
        $property   = new \ReflectionProperty(__NAMESPACE__. "\DCOM63Employee","route");
        $annots     = $this->getReader()->getPropertyAnnotations($property);
        $content    = self::getClassFileContent("DCOM63Person");
        $tpl        = self::getTplContent("/employee");
        
        $this->assertEquals($content,$tpl);
        $this->assertEquals(sizeof($annots),1);
        $this->assertEquals($annots[0]->pattern,"/employee");
    }
    
    
    
    
    
    private static function changeRoutePattern($pattern)
    {
        $content    = self::getTplContent($pattern);
        $filename   = self::getClassFileName("DCOM63Person");
        file_put_contents($filename, $content);
    }
    
    private static function getClassFileContent($class)
    {
        return file_get_contents(self::getClassFileName($class));
    }
    
    private static function getClassFileName($class)
    {
        $class = new \ReflectionClass(__NAMESPACE__. '\\' . $class);
        return $class->getFileName();
    }
    
    private static function getTplContent($pattern)
    {
        return str_replace("<pattern/>", $pattern, self::CLASS_TPL);
    }


    const CLASS_TPL = '<?php

namespace Doctrine\Tests\Common\Annotations\Ticket;

abstract class DCOM63Person
{
    /** @Doctrine\Tests\Common\Annotations\Fixtures\Annotation\Route("<pattern/>") */
    public $route;
}
';
    
    
}