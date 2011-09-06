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
            rmdir(self::$cacheDir);
        }
        return new FileCacheReader(new AnnotationReader(), self::$cacheDir, true);
    }
    
    static function setUpBeforeClass()
    {
        if(self::$cacheDir == null){
            self::$cacheDir = sys_get_temp_dir() . "/annotations_dcom63_". uniqid();
            self::$cacheDir = "/Users/fabio/backup/temp/";
        }
        
        foreach (glob(self::$cacheDir.'/*.php') as $file) {
            unlink($file);
        }
    }
    
    
    
    /**
     * @runInSeparateProcess
     */
    public function testSetDefaultRoutePattern()
    {
        $this->changeRoutePattern("/person");
        $this->assertEquals($this->getClassFileContent("DCOM63Person"), $this->getTplContent("/person"));
    }
    
    
    /**
     * @runInSeparateProcess
     * @depends testSetDefaultRoutePattern
     */
    public function testReadPropertyAnnotations()
    {
        include 'DCOM63Person.php';
        include 'DCOM63Employee.php';
        
        $property   = new \ReflectionProperty(__NAMESPACE__. "\DCOM63Employee","route");
        $annots     = $this->getReader()->getPropertyAnnotations($property);
        
        $this->assertEquals(sizeof($annots),1);
        $this->assertEquals($annots[0]->pattern,"/person");
        $this->assertEquals($this->getClassFileContent("DCOM63Person"), $this->getTplContent("/person"));
    }
    
    
    /**
     * @runInSeparateProcess
     * @depends testReadPropertyAnnotations
     */
    public function testSetOtherRoutePattern()
    {
        $this->changeRoutePattern("/employee");
        $this->assertEquals($this->getClassFileContent("DCOM63Person"), $this->getTplContent("/employee"));
    }
    
    
    /**
     * @runInSeparateProcess
     * @depends testSetOtherRoutePattern
     */
    public function testIssue()
    {
        include 'DCOM63Person.php';
        include 'DCOM63Employee.php';
        
        $property   = new \ReflectionProperty(__NAMESPACE__. "\DCOM63Employee","route");
        $annots     = $this->getReader()->getPropertyAnnotations($property);
        
        $this->assertEquals(sizeof($annots),1);
        $this->assertEquals($annots[0]->pattern,"/employee");
        $this->assertEquals($this->getClassFileContent("DCOM63Person"), $this->getTplContent("/employee"));
    }
    
    
    
    private function changeRoutePattern($pattern)
    {
        $content    = $this->getTplContent($pattern);
        $filename   = $this->getClassFileName("DCOM63Person");
        
        file_put_contents($filename, $content);
    }
    
    private function getClassFileContent($class)
    {
        $filename = $this->getClassFileName($class);
        return file_get_contents($filename);
    }
    
    private function getClassFileName($class)
    {
        $class = new \ReflectionClass(__NAMESPACE__. '\\' . $class);
        return $class->getFileName();
    }
    
    private function getTplContent($pattern)
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