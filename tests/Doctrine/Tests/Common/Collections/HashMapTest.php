<?php

namespace Doctrine\Tests\Common\Collections;

use Doctrine\Tests\Common\Collections\Fixtures\CollectionElementObject;

class HashMapTest extends \Doctrine\Tests\DoctrineTestCase
{

    /**
     * @var \Doctrine\Common\Collections\HashMap
     */
    private $_coll;

    protected function setUp()
    {
        $this->_coll = new \Doctrine\Common\Collections\HashMap('Doctrine\Tests\Common\Collections\Fixtures\CollectionElementObject');
    }

    public function testIssetAndUnset()
    {
        $this->assertFalse(isset($this->_coll[0]));
        $this->_coll->add(new CollectionElementObject('testing'));
        $this->assertTrue(isset($this->_coll[0]));
        unset($this->_coll[0]);
        $this->assertFalse(isset($this->_coll[0]));
    }
    
    public function testToString()
    {
        $this->_coll->add(new CollectionElementObject('testing'));
        $this->assertTrue(is_string((string) $this->_coll));
    }

    public function testRemovingNonExistentEntryReturnsNull()
    {
        $this->assertEquals(null, $this->_coll->remove(new CollectionElementObject('testing_does_not_exist')));
    }
    
    public function testExists()
    {
        $this->_coll->add(new CollectionElementObject('one'));
        $this->_coll->add(new CollectionElementObject('two'));
        $exists = $this->_coll->exists(function($k, $e) { return $e->id == "one"; });
        $this->assertTrue($exists);
        $exists = $this->_coll->exists(function($k, $e) { return $e->id == "other"; });
        $this->assertFalse($exists);
    }

  
    public function testContains()
    {
        $this->_coll[0] = new CollectionElementObject('one');
        $this->_coll[1] = new CollectionElementObject('two');
        
        $this->assertTrue($this->_coll->contains(new CollectionElementObject('two')));
        $this->assertFalse($this->_coll->contains(new CollectionElementObject('teste')));
    }
    
    
    public function testAdd()
    {
        for ($i = 0; $i < 9; $i++) {
            $this->_coll[$i]            = new CollectionElementObject($i,time());
        }
        
        for ($i = 0; $i < 9; $i++) {
            $this->assertTrue($this->_coll->contains(new CollectionElementObject($i)));
        }
    }
    

}