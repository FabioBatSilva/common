<?php

namespace Doctrine\Tests\Common\Collections;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\HashMap;
use Doctrine\Tests\Common\Collections\Fixtures\CollectionElementObject; 

class PerformanceTest extends \PHPUnit_Framework_TestCase
{
    

    public function testAddArrayCollection()
    {
        $coll = new ArrayCollection();

        $time = microtime(true);
        for ($i=0,$c=50000; $i<$c; $i++) {
            $coll->add(new CollectionElementObject($i, "Value $i"));
        }
        $time = microtime(true) - $time;

        $this->printResults('ArrayCollection#add', $time, $c);
    }
    
    
    public function testAddHashMap()
    {
        $coll = new HashMap('Doctrine\Tests\Common\Collections\Fixtures\CollectionElementObject');

        $time = microtime(true);
        for ($i=0,$c=50000; $i<$c; $i++) {
            $coll->add(new CollectionElementObject($i, "Value $i"));
        }
        $time = microtime(true) - $time;

        $this->printResults('HashMap#add', $time, $c);
    }
    
    public function testExistsArrayCollection()
    {
        $item = null;
        $coll = new ArrayCollection();
        $obj  = new CollectionElementObject(259);
        for ($i=0,$c=50000; $i<$c; $i++) {
            $coll->add(new CollectionElementObject($i, "Value $i"));
        }
        
        $time = microtime(true);
        foreach ($coll as $value) {
            if($obj->id == $value->id){
                $item = $value;
                break;
            }
        }
        $time = microtime(true) - $time;

        $this->printResults('ArrayCollection#get', $time, $c);
    }
    
    
    public function testExistsHashMap()
    {
        $item = null;
        $coll = new HashMap('Doctrine\Tests\Common\Collections\Fixtures\CollectionElementObject');
        $obj  = new CollectionElementObject(259);
        for ($i=0,$c=50000; $i<$c; $i++) {
            $coll->add(new CollectionElementObject($i, "Value $i"));
        }
        
        $time = microtime(true);
        $item = $coll->get($obj);
        $time = microtime(true) - $time;

        $this->printResults('HashMap#get', $time, $c);
    }

   

    private function printResults($test, $time, $iterations)
    {
        if (0 == $iterations) {
            throw new \InvalidArgumentException('$iterations cannot be zero.');
        }

        $title = $test." results:\n";
        $iterationsText = sprintf("Iterations:         %d\n", $iterations);
        $totalTime      = sprintf("Total Time:         %.3f s\n", $time);
        $iterationTime  = sprintf("Time per iteration: %.3f ms\n", $time/$iterations * 1000);

        $max = max(strlen($title), strlen($iterationTime)) - 1;

        echo "\n".str_repeat('-', $max)."\n";
        echo $title;
        echo str_repeat('=', $max)."\n";
        echo $iterationsText;
        echo $totalTime;
        echo $iterationTime;
        echo str_repeat('-', $max)."\n";
    }
}