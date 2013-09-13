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
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\Common\Persistence\Mapping;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;

/**
 * Compiled class metadata factory
 *
 * @since   2.5
 * @author  Fabio B. Silva <fabio.bat.silva@gmail.com>
 */
class CompiledClassMetadataFactory implements ClassMetadataFactory
{
    /**
     * @var \Doctrine\Common\Persistence\Mapping\ClassMetadataFactory
     */
    private $factory;

    /**
     * @var string
     */
    private $namespace = '\__GC__\DoctrineMapping';

    /**
     * The directory that contains metadata classes.
     *
     * @var string
     */
    private $directory;

    /**
     * @param \Doctrine\Common\Persistence\Mapping\ClassMetadataFactory $factory
     */
    public function __construct(ClassMetadataFactory $factory)
    {
        $this->factory = $factory;
    }

    public function getNamespace()
    {
        return $this->namespace;
    }

    public function setNamespace($namespace)
    {
        $this->namespace = $namespace;
    }

    public function getDirectory()
    {
        return $this->directory;
    }

    public function setDirectory($directory)
    {
        $this->directory = $directory;
    }

    /**
     * @param string $metadata
     *
     * @return string
     */
    public function generate(ClassMetadata $metadata, $metadataClassName)
    {
        $compiler = new ClassMetadataCompiler($metadata, $metadataClassName);
        $filename = $compiler->generate($this->directory);

        include $filename;


        echo file_get_contents($filename);

        return $filename;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getAllMetadata()
    {
        return $this->factory->getAllMetadata();
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadataFor($className)
    {

        $metadataClassName = $this->namespace . '\\' . $className . 'ClassMetadata';

        if ( ! class_exists($metadataClassName, false)) {
            $this->generate($this->factory->getMetadataFor($className), $metadataClassName);
        }

        return new $metadataClassName();
    }

    /**
     * {@inheritdoc}
     */
    public function hasMetadataFor($className)
    {
        return $this->factory->hasMetadataFor($className);
    }

    /**
     * {@inheritdoc}
     */
    public function isTransient($className)
    {
        return $this->factory->isTransient($className);
    }

    /**
     * {@inheritdoc}
     */
    public function setMetadataFor($className, $class)
    {
        return $this->factory->setMetadataFor($className, $class);
    }
}
