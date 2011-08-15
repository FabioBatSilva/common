<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\Common\Collections;

use Doctrine\Common\Collections\CollectionException;
use Closure, ArrayIterator;

/**
 * An HashMap is a Collection implementation that wraps a regular PHP array.
 *
 * @since  2.2
 * @author Fabio B. Silva <fabio.bat.silva@gmail.com>
 */
class HashMap implements Collection
{
     /**
     * An ArrayCollection containing the entries of this hash-map.
     *
     * @var Doctrine\Common\Collections\ArrayCollection
     */
    private $coll;
    
     /**
     * @var array
     */
    private $_map;
    
    /**
     * @var string 
     */
    private $type;

    /**
     * Initializes a new ArrayCollection.
     *
     * @param array $elements
     */
    public function __construct($type,array $elements = array())
    {
        if(!is_string($type)){
            throw new \InvalidArgumentException("First argument must be a valid class name");
        }

        if(!in_array('Doctrine\Common\Collections\Element', class_implements($type))){
            throw CollectionException::constructTypeError($type);
        }
        
        $this->type = $type;
        $this->coll = new ArrayCollection($elements);
    }

    /** 
     * {@inheritdoc} 
     */
    public function toArray()
    {
        return $this->coll->toArray();
    }

    /** 
     * {@inheritdoc} 
     */
    public function first()
    {
        return $this->coll->first();
    }

    /** 
     * {@inheritdoc} 
     */
    public function last()
    {
        return $this->coll->last();
    }

    /** 
     * {@inheritdoc} 
     */
    public function key()
    {
        return $this->coll->key();
    }
    
    /** 
     * {@inheritdoc} 
     */
    public function next()
    {
        return $this->coll->next();
    }
    
    /** 
     * {@inheritdoc} 
     */
    public function current()
    {
        return $this->coll->current();
    }

    /** 
     * {@inheritdoc} 
     */
    public function remove($key)
    {
        if($key instanceof $this->type){
            $key = $this->indexOf($key);
        }
        
        if (($removed = $this->coll->remove($key)) !== null) {
            unset($this->_map[$key]);
            return $removed;
        }

        return null;
    }

    /** 
     * {@inheritdoc} 
     */
    public function removeElement($element)
    {
        if(!($element instanceof $this->type)){
            throw CollectionException::elementTypeError($this->type, $element);
        }
        
        $hashCode = $element->hashCode();
        
        if (isset($this->_map[$hashCode])) {
            foreach ($this->_map[$hashCode] as $key => $index) {
                if ($element->equals($this->get($index))) {
                    $this->remove($key);
                    return true;
                }
            }
        }
        return false;
    }

    /** 
     * {@inheritdoc} 
     */
    public function offsetExists($offset)
    {
        return $this->coll->offsetExists($offset);
    }

    /** 
     * {@inheritdoc} 
     */
    public function offsetGet($offset)
    {
        return $this->coll->offsetGet($offset);
    }

    /** 
     * {@inheritdoc} 
     */
    public function offsetSet($offset, $value)
    {
        if ( ! isset($offset)) {
            return $this->add($value);
        }
        return $this->set($offset, $value);
    }

    /** 
     * {@inheritdoc} 
     */
    public function offsetUnset($offset)
    {
        return $this->remove($offset);
    }

    /** 
     * {@inheritdoc} 
     */
    public function containsKey($key)
    {
        return $this->coll->containsKey($key);
    }

    /**
     * Checks whether the given element is contained in the collection.
     * Only element values are compared, not keys. The comparison of two elements
     * is strict, that means not only the value but also the type must match.
     * For objects this means reference equality.
     *
     * @param mixed $element
     * @return boolean TRUE if the given element is contained in the collection,
     *          FALSE otherwise.
     */
    public function contains($element)
    {
        if(!($element instanceof $this->type)){
            throw CollectionException::elementTypeError($this->type, $element);
        }
        
        $hashCode = $element->hashCode();
        
        if (isset($this->_map[$hashCode])) {
            foreach ($this->_map[$hashCode] as $index) {
                if ($element->equals($this->get($index))) {
                    return true;
                }
            }
        }
        
        return false;
    }

    /** 
     * {@inheritdoc} 
     */
    public function exists(Closure $p)
    {
        return $this->coll->exists($p);
    }

    /** 
     * {@inheritdoc} 
     */
    public function indexOf($element)
    {
        if(!($element instanceof $this->type)){
            throw CollectionException::elementTypeError($this->type, $element);
        }
        
        $hashCode = $element->hashCode();
        
        if (isset($this->_map[$hashCode])) {
            foreach ($this->_map[$hashCode] as $key => $index) {
                if ($element->equals($this->get($index))) {
                    return $index;
                }
            }
        }
        
        return false;
    }

    /** 
     * {@inheritdoc} 
     */
    public function get($key)
    {
        if($key instanceof $this->type){
            $key = $this->indexOf($key);
        }
        
        return $this->coll->get($key);
    }

    /** 
     * {@inheritdoc} 
     */
    public function getKeys()
    {
        return $this->coll->getKeys();
    }

    /** 
     * {@inheritdoc} 
     */
    public function getValues()
    {
        return $this->coll->getValues();
    }

    /** 
     * {@inheritdoc} 
     */
    public function count()
    {
        return $this->coll->count();
    }

   /** 
     * {@inheritdoc} 
     */
    public function set($key, $value)
    {
        if(!($value instanceof $this->type)){
            throw CollectionException::elementTypeError($this->type, $element);
        }
        
        $this->coll->set($key, $value);
        $this->_map[$value->hashCode()][$key] = $key;
    }

    /** 
     * {@inheritdoc} 
     */
    public function add($value)
    {
        if(!($value instanceof $this->type)){
            throw CollectionException::elementTypeError($value);
        }
        
        $this->coll->add($value);
        $this->coll->last();
        $key = $this->coll->key();
        $this->_map[$value->hashCode()][$key] = $key;
        
        return true;
    }

    /** 
     * {@inheritdoc} 
     */
    public function isEmpty()
    {
        return $this->coll->isEmpty();
    }

    /** 
     * {@inheritdoc} 
     */
    public function getIterator()
    {
        return $this->coll->getIterator();
    }

    /** 
     * {@inheritdoc} 
     */
    public function map(Closure $func)
    {
        return new static($this->type,$this->coll->map($p));
    }

    /** 
     * {@inheritdoc} 
     */
    public function filter(Closure $p)
    {
        return new static($this->type,$this->coll->filter($p));
    }

    /** 
     * {@inheritdoc} 
     */
    public function forAll(Closure $p)
    {
        return $this->coll->forAll($p);
    }

    /** 
     * {@inheritdoc} 
     */
    public function partition(Closure $p)
    {
        $partition           = $this->coll->partition($p);
        list($coll1, $coll2) = $partition;
        return array(new static($this->type,$coll1), new static($this->type,$coll2));
    }

    /** 
     * {@inheritdoc} 
     */
    public function __toString()
    {
        return __CLASS__ . '@' . spl_object_hash($this);
    }

    /** 
     * {@inheritdoc} 
     */
    public function clear()
    {
        $this->_map      = array();
        $this->_elements = array();
    }

    /** 
     * {@inheritdoc} 
     */
    public function slice($offset, $length = null)
    {
        return $this->coll->slice($offset, $length);
    }
}