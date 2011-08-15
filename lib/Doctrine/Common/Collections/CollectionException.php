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

use Doctrine\Common\CommonException;

/**
 * Exception class for collections
 *
 * @since   2.2
 * @author Fabio B. Silva <fabio.bat.silva@gmail.com>
 */
class CollectionException extends CommonException
{

    /**
     * Creates a new CollectionException.
     * 
     * @param   string $type
     * @return  CollectionException
     */
    public static function constructTypeError($type)
    {
        return new self(sprintf('[Construct Type Error] %s must be a subclass of Doctrine\Common\Collections\Element', $type));
    }
    
    
    /**
     * Creates a new CollectionException.
     * 
     * @param   string $expected
     * @param   mixed $actual
     * @return  CollectionException 
     */
    public static function elementTypeError($expected, $actual)
    {
        return new self(sprintf(
                '[Element Type Error] %s is not a instance of %s', 
                is_object($actual)?get_class($actual):gettype($actual),$expected));
    }

}