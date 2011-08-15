<?php

namespace Doctrine\Tests\Common\Collections\Fixtures;

use Doctrine\Common\Collections\Element;

class CollectionElementObject implements Element
{

    public $id;
    
    public $value;

    public function __construct($id = null,$value = null)
    {
        $this->id       = $id;
        $this->value    = $value;
    }

    /**
     * {@inheritdoc} 
     */
    public function equals($obj)
    {
        if ($obj == $this) {
            return true;
        }

        if ($obj instanceof self) {
            if ($this->id == $obj->id) {
                return true;
            }
        }


        return false;
    }

    /**
     * {@inheritdoc} 
     */
    public function hashCode()
    {
        return md5($this->id);
    }

}