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

namespace Doctrine\Common\Annotations;

use Closure;
use ReflectionClass;
use Doctrine\Common\Annotations\Annotation\Target;
use Doctrine\Common\Annotations\Annotation\IgnoreAnnotation;

/**
 * A parser for docblock annotations.
 *
 * It is strongly discouraged to change the default annotation parsing process.
 *
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 * @author Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author Jonathan Wage <jonwage@gmail.com>
 * @author Roman Borschel <roman@code-factory.org>
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 * @author Fabio B. Silva <fabio.bat.silva@gmail.com>
 */
final class DocParser
{
    /**
     * An array of all valid tokens for a class name.
     *
     * @var array
     */
    private static $classIdentifiers = array(DocLexer::T_IDENTIFIER, DocLexer::T_TRUE, DocLexer::T_FALSE, DocLexer::T_NULL);

    /**
     * The lexer.
     *
     * @var Doctrine\Common\Annotations\DocLexer
     */
    private $lexer;

    /**
     * Current target context
     *
     * @var string
     */
    private $target;
    /**
     * Current target class
     *
     * @var \ReflectionClass
     */
    private $targetClass;
    
    /**
     * Doc Parser used to collect annotation target
     *
     * @var Doctrine\Common\Annotations\DocParser
     */
    private static $metadataParser;
    
    /**
     * PhpParser used to collect target class metadata
     *
     * @var Doctrine\Common\Annotations\PhpParser
     */
    private static $metadataPhpParser;

    /**
     * Flag to control if the current annotation is nested or not.
     *
     * @var boolean
     */
    private $isNestedAnnotation = false;

    /**
     * Hashmap containing all use-statements that are to be used when parsing
     * the given doc block.
     *
     * @var array
     */
    private $imports = array();

    /**
     * This hashmap is used internally to cache results of class_exists()
     * look-ups.
     *
     * @var array
     */
    private $classExists = array();

    /**
     * Whether annotations that have not been imported should be ignored.
     *
     * @var boolean
     */
    private $ignoreNotImportedAnnotations = false;

    /**
     * An array of default namespaces if operating in simple mode.
     *
     * @var array
     */
    private $namespaces = array();

    /**
     * A list with annotations that are not causing exceptions when not resolved to an annotation class.
     *
     * The names must be the raw names as used in the class, not the fully qualified
     * class names.
     *
     * @var array
     */
    private $ignoredAnnotationNames = array();

    /**
     * @var string
     */
    private $context = '';

    /**
     * Hash-map for caching annotation metadata
     * @var array
     */
    private static $annotationMetadata = array(
        'Doctrine\Common\Annotations\Annotation\Target' => array(
            'default_property' => null,
            'has_constructor'  => true,
            'properties'       => array(),
            'attribute_types'  => array(),
            'targets_literal'  => 'ANNOTATION_CLASS',
            'targets'          => Target::TARGET_CLASS,
            'is_annotation'    => true,
        ),
    );
    
     /**
     * Hash-map for caching target class metadata
     * @var array
     */
    private static $targetClassMetadata = array();

    /**
     * Hash-map for handle types declaration
     *
     * @var array
     */
    private static $typeMap = array(
        'float'     => 'double',
        'bool'      => 'boolean',
        'int'       => 'integer',
    );

    /**
     * Constructs a new DocParser.
     */
    public function __construct()
    {
        $this->lexer = new DocLexer;
    }

    /**
     * Sets the annotation names that are ignored during the parsing process.
     *
     * The names are supposed to be the raw names as used in the class, not the
     * fully qualified class names.
     *
     * @param array $names
     */
    public function setIgnoredAnnotationNames(array $names)
    {
        $this->ignoredAnnotationNames = $names;
    }
    
    /**
     * Signal to the parser to ignore all not imported annotations during the parsing process.
     * 
     * @param boolean $bool 
     */
    public function setIgnoreNotImportedAnnotations($bool)
    {
        $this->ignoreNotImportedAnnotations = (Boolean) $bool;
    }

    /**
     * Sets the default namespaces.
     * 
     * @param array $namespaces
     */
    public function addNamespace($namespace)
    {
        if ($this->imports) {
            throw new \RuntimeException('You must either use addNamespace(), or setImports(), but not both.');
        }
        $this->namespaces[$namespace] = $namespace;
        
        foreach (self::$targetClassMetadata as $class => $metadata) {
            self::$targetClassMetadata[$class]['namespaces'][$namespace] = $namespace;
        }
    }

     /**
     * Sets the default inports.
     * 
     * @param array $namespaces
     */
    public function setImports(array $imports)
    {
        if ($this->namespaces) {
            throw new \RuntimeException('You must either use addNamespace(), or setImports(), but not both.');
        }
        $this->imports = $imports;
        
        foreach (self::$targetClassMetadata as $class => $metadata){
            foreach ($imports as $alias => $fullName){
                self::$targetClassMetadata[$class]['imports'][$alias] = $fullName;
            }
        }
        
    }

     /**
     * Sets current target context as bitmask.
     *
     * @param integer $target
     */
    public function setTarget($target)
    {
        $this->target = $target;
    }
    
    /**
     * Sets current target context as bitmask.
     *
     * @param \ReflectionClass $class
     */
    public function setTargetClass(ReflectionClass $class)
    {
        $this->targetClass = $class;
    }

    /**
     * Parses the given docblock string for annotations.
     *
     * @param string $input The docblock string to parse.
     * @param string $context The parsing context.
     * @return array Array of annotations. If no annotations are found, an empty array is returned.
     */
    public function parse($input, $context = '')
    {
        if (false === $pos = strpos($input, '@')) {
            return array();
        }

        // also parse whatever character is before the @
        if ($pos > 0) {
            $pos -= 1;
        }

        $this->context = $context;
        $this->lexer->setInput(trim(substr($input, $pos), '* /'));
        $this->lexer->moveNext();

        return $this->Annotations();
    }

    /**
     * Attempts to match the given token with the current lookahead token.
     * If they match, updates the lookahead token; otherwise raises a syntax error.
     *
     * @param int Token type.
     * @return bool True if tokens match; false otherwise.
     */
    private function match($token)
    {
        if ( ! $this->lexer->isNextToken($token) ) {
            $this->syntaxError($this->lexer->getLiteral($token));
        }

        return $this->lexer->moveNext();
    }

    /**
     * Attempts to match the current lookahead token with any of the given tokens.
     *
     * If any of them matches, this method updates the lookahead token; otherwise
     * a syntax error is raised.
     *
     * @param array $tokens
     * @return bool
     */
    private function matchAny(array $tokens)
    {
        if ( ! $this->lexer->isNextTokenAny($tokens)) {
            $this->syntaxError(implode(' or ', array_map(array($this->lexer, 'getLiteral'), $tokens)));
        }

        return $this->lexer->moveNext();
    }

    /**
     * Generates a new syntax error.
     *
     * @param string $expected Expected string.
     * @param array $token Optional token.
     * @throws SyntaxException
     */
    private function syntaxError($expected, $token = null)
    {
        if ($token === null) {
            $token = $this->lexer->lookahead;
        }

        $message =  "Expected {$expected}, got ";

        if ($this->lexer->lookahead === null) {
            $message .= 'end of string';
        } else {
            $message .= "'{$token['value']}' at position {$token['position']}";
        }

        if (strlen($this->context)) {
            $message .= ' in ' . $this->context;
        }

        $message .= '.';

        throw AnnotationException::syntaxError($message);
    }

    /**
     * Attempt to check if a class exists or not. This never goes through the PHP autoloading mechanism
     * but uses the {@link AnnotationRegistry} to load classes.
     *
     * @param string $fqcn
     * @return boolean
     */
    private function classExists($fqcn)
    {
        if (isset($this->classExists[$fqcn])) {
            return $this->classExists[$fqcn];
        }

        // first check if the class already exists, maybe loaded through another AnnotationReader
        if (class_exists($fqcn, false)) {
            return $this->classExists[$fqcn] = true;
        }

        // final check, does this class exist?
        return $this->classExists[$fqcn] = AnnotationRegistry::loadAnnotationClass($fqcn);
    }

    /**
     * 
     */
    private function initMetadataParser()
    {
        if(self::$metadataParser === null){
            self::$metadataParser = new self();
            self::$metadataParser->setTarget(Target::TARGET_CLASS);
            self::$metadataParser->setIgnoreNotImportedAnnotations(true);
            self::$metadataParser->setImports(array(
                'target' => 'Doctrine\Common\Annotations\Annotation\Target',
                'ignoreannotation' => 'Doctrine\Common\Annotations\Annotation\IgnoreAnnotation'
            ));
            AnnotationRegistry::registerFile(__DIR__ . '/Annotation/Target.php');
            AnnotationRegistry::registerFile(__DIR__ . '/Annotation/IgnoreAnnotation.php');
        }
    }
    
    /**
     * @param   string $name
     * @return  string 
     */
    private function annotationClassName($name)
    {
        $fullName = null;
        if ('\\' !== $name[0] && !$this->classExists($name)) {
            
            $pos            = strpos($name, '\\');
            $alias          = (false === $pos) ? $name : substr($name, 0, $pos);
            $loweredAlias   = strtolower($alias);
            
            $imports        = $this->imports;
            $namespaces     = $this->namespaces;
                
            if($this->targetClass != null){
                $imports        = array_merge(self::$targetClassMetadata[$this->targetClass->name]['imports'],$imports);
                $namespaces     = array_merge(self::$targetClassMetadata[$this->targetClass->name]['namespaces'],$namespaces);
            }
            
            if (isset($imports[$loweredAlias])){
                if (false !== $pos) {
                    $fullName = $imports[$loweredAlias].substr($name, $pos);
                } else {
                    $fullName = $imports[$loweredAlias];
                }
            }else {
                if($namespaces){
                    foreach ($namespaces as $namespace) {
                        if ($this->classExists($namespace . '\\' . $name)) {
                            $fullName = $namespace . '\\' . $name;
                            break;
                        }
                    }
                }
            }
        }
        if($this->targetClass != null){
            self::$targetClassMetadata[$this->targetClass->name][$name] = $fullName;
        }
        
        return $fullName;
    }
    
    /**
     * Collects parsing metadata for a given class
     *
     * @param ReflectionClass $class
     */
    private function collectTargetClassMetadata()
    {
        if(self::$metadataParser == null){
            $this->initMetadataParser();
        }
        
        if(self::$metadataPhpParser == null){
            self::$metadataPhpParser = new PhpParser();
        }
        
        $namespace = $this->targetClass->getNamespaceName();
        
        $metadata  = array(
            'annotations'   => array(),
            'imports'       => $this->imports,
            'namespaces'    => $this->namespaces,
            'ignored'       => $this->ignoredAnnotationNames,
        );
        
        $metadata[$namespace] = $namespace;
        
        foreach (self::$metadataParser->parse($this->targetClass->getDocComment(), 'class '.$this->targetClass->name) as $annotation) {
            if ($annotation instanceof IgnoreAnnotation){
                foreach ($annotation->names as $annot) {
                    $metadata['ignored'][$annot] = true;
                }
            }
        }
        
        foreach (self::$metadataPhpParser->parseClass($this->targetClass) as $alias => $fullName) {
            $metadata['imports'][$alias] = $fullName;
        }
        
        self::$targetClassMetadata[$this->targetClass->name] = $metadata;
    }
    
    
    /**
     * Collects parsing metadata for a given annotation class
     *
     * @param   string $name        The annotation name
     */
    private function collectAnnotationMetadata($name)
    {
        if(self::$metadataParser == null){
            $this->initMetadataParser();
        }

        $class      = new \ReflectionClass($name);
        $docComment = $class->getDocComment();

        // Sets default values for annotation metadata
        $metadata = array(
            'default_property' => null,
            'has_constructor'  => (null !== $constructor = $class->getConstructor()) && $constructor->getNumberOfParameters() > 0,
            'properties'       => array(),
            'property_types'   => array(),
            'targets_literal'  => null,
            'targets'          => Target::TARGET_ALL,
            'is_annotation'    => false !== strpos($docComment, '@Annotation'),
        );

        // verify that the class is really meant to be an annotation
        if ($metadata['is_annotation']) {
            foreach (self::$metadataParser->parse($docComment, 'class @' . $name) as $annotation) {
                if ($annotation instanceof Target) {
                    $metadata['targets']         = $annotation->targets;
                    $metadata['targets_literal'] = $annotation->literal;
                }
            }

            // if not has a constructor will inject values into public properties
            if (false === $metadata['has_constructor']) {
                // collect all public properties
                foreach ($class->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
                    $metadata['properties'][$property->name] = $property->name;

                    // checks if the property has @var annotation
                    if ((false !== $propertyComment = $property->getDocComment())
                        && false !== strpos($propertyComment, '@var')
                        && preg_match('/@var\s+([^\s]+)/',$propertyComment, $matches)) {
                        // literal type declaration
                        $value = $matches[1];

                        // handle internal type declaration
                        $type = isset(self::$typeMap[$value]) ? self::$typeMap[$value] : $value;

                        // handle the case if the property type is mixed
                        if ('mixed' !== $type) {
                            // Checks if the property has @var array<type> annotation
                            if (false !== $pos = strpos($type, '<')) {
                                $arrayType = substr($type, $pos+1, -1);
                                $type = 'array';

                                if (isset(self::$typeMap[$arrayType])) {
                                    $arrayType = self::$typeMap[$arrayType];
                                }

                                $metadata['attribute_types'][$property->name]['array_type'] = $arrayType;
                            }

                            $metadata['attribute_types'][$property->name]['type']   = $type;
                            $metadata['attribute_types'][$property->name]['value']  = $value;
                        }
                    }
                }

                // choose the first property as default property
                $metadata['default_property'] = reset($metadata['properties']);
            }
        }

        self::$annotationMetadata[$name] = $metadata;
    }

    /**
     * Annotations ::= Annotation {[ "*" ]* [Annotation]}*
     *
     * @return array
     */
    private function Annotations()
    {
        $annotations = array();

        while (null !== $this->lexer->lookahead) {
            if (DocLexer::T_AT !== $this->lexer->lookahead['type']) {
                $this->lexer->moveNext();
                continue;
            }

            // make sure the @ is preceded by non-catchable pattern
            if (null !== $this->lexer->token && $this->lexer->lookahead['position'] === $this->lexer->token['position'] + strlen($this->lexer->token['value'])) {
                $this->lexer->moveNext();
                continue;
            }

            // make sure the @ is followed by either a namespace separator, or
            // an identifier token
            if ((null === $peek = $this->lexer->glimpse())
                || (DocLexer::T_NAMESPACE_SEPARATOR !== $peek['type'] && !in_array($peek['type'], self::$classIdentifiers, true))
                || $peek['position'] !== $this->lexer->lookahead['position'] + 1) {
                $this->lexer->moveNext();
                continue;
            }

            $this->isNestedAnnotation = false;
            if (false !== $annot = $this->Annotation()) {
                $annotations[] = $annot;
            }
        }

        return $annotations;
    }

    /**
     * Annotation     ::= "@" AnnotationName ["(" [Values] ")"]
     * AnnotationName ::= QualifiedName | SimpleName
     * QualifiedName  ::= NameSpacePart "\" {NameSpacePart "\"}* SimpleName
     * NameSpacePart  ::= identifier | null | false | true
     * SimpleName     ::= identifier | null | false | true
     *
     * @return mixed False if it is not a valid annotation.
     */
    private function Annotation()
    {
        $this->match(DocLexer::T_AT);

        // check if we have an annotation
        if ($this->lexer->isNextTokenAny(self::$classIdentifiers)) {
            $this->lexer->moveNext();
            $name = $this->lexer->token['value'];
        } else if ($this->lexer->isNextToken(DocLexer::T_NAMESPACE_SEPARATOR)) {
            $name = '';
        } else {
            $this->syntaxError('namespace separator or identifier');
        }

        while ($this->lexer->lookahead['position'] === $this->lexer->token['position'] + strlen($this->lexer->token['value']) && $this->lexer->isNextToken(DocLexer::T_NAMESPACE_SEPARATOR)) {
            $this->match(DocLexer::T_NAMESPACE_SEPARATOR);
            $this->matchAny(self::$classIdentifiers);
            $name .= '\\'.$this->lexer->token['value'];
        }
        
        // only process names which are not fully qualified, yet
        $originalName  = $name;
        
        if ($this->targetClass != null) {
            // collects the metadata annotation only if there is not yet
            if (!isset(self::$targetClassMetadata[$this->targetClass->name])) {
                $this->collectTargetClassMetadata();
            }
            
            if (isset(self::$targetClassMetadata[$this->targetClass->name]['annotations'][$name])) {
                $name  = self::$targetClassMetadata[$this->targetClass->name]['annotations'][$name];
            }else{
                $name  = $this->annotationClassName($name);
            }
        }else{
            $name  = $this->annotationClassName($name);
        }
        
        if ($name === null) {
            if ($this->ignoreNotImportedAnnotations || isset($this->ignoredAnnotationNames[$name]) || !isset(self::$targetClassMetadata[$this->targetClass->name]['ignored'][$name])){
                return false;
            }
            throw AnnotationException::semanticalError(sprintf('The annotation "@%s" in %s was never imported.', $originalName, $this->context));
        }

        if (!$this->classExists($name)) {
            throw AnnotationException::semanticalError(sprintf('The annotation "@%s" in %s does not exist, or could not be auto-loaded.', $name, $this->context));
        }

        // at this point, $name contains the fully qualified class name of the
        // annotation, and it is also guaranteed that this class exists, and
        // that it is loaded


        // collects the metadata annotation only if there is not yet
        if (!isset(self::$annotationMetadata[$name])) {
            $this->collectAnnotationMetadata($name);
        }

        // verify that the class is really meant to be an annotation and not just any ordinary class
        if (self::$annotationMetadata[$name]['is_annotation'] === false) {
            if (isset($this->ignoredAnnotationNames[$originalName])) {
                return false;
            }

            throw AnnotationException::semanticalError(sprintf('The class "%s" is not annotated with @Annotation. Are you sure this class can be used as annotation? If so, then you need to add @Annotation to the _class_ doc comment of "%s". If it is indeed no annotation, then you need to add @IgnoreAnnotation("%s") to the _class_ doc comment of %s.', $name, $name, $originalName, $this->context));
        }

        //if target is nested annotation
        $target = $this->isNestedAnnotation ? Target::TARGET_ANNOTATION : $this->target;

        // Next will be nested
        $this->isNestedAnnotation = true;

        //if anotation does not support current target
        if (0 === (self::$annotationMetadata[$name]['targets'] & $target) && $target) {
            throw AnnotationException::semanticalError(
                sprintf('Annotation @%s is not allowed to be declared on %s. You may only use this annotation on these code elements: %s.',
                     $originalName, $this->context, self::$annotationMetadata[$name]['targets_literal'])
            );
        }

        $values = array();
        if ($this->lexer->isNextToken(DocLexer::T_OPEN_PARENTHESIS)) {
            $this->match(DocLexer::T_OPEN_PARENTHESIS);

            if ( ! $this->lexer->isNextToken(DocLexer::T_CLOSE_PARENTHESIS)) {
                $values = $this->Values();
            }

            $this->match(DocLexer::T_CLOSE_PARENTHESIS);
        }

        // check if the annotation expects values via the constructor,
        // or directly injected into public properties
        if (self::$annotationMetadata[$name]['has_constructor'] === true) {
            return new $name($values);
        }

        $instance = new $name();
        foreach ($values as $property => $value) {
            if (!isset(self::$annotationMetadata[$name]['properties'][$property])) {
                if ('value' !== $property) {
                    throw AnnotationException::creationError(sprintf('The annotation @%s declared on %s does not have a property named "%s". Available properties: %s', $originalName, $this->context, $property, implode(', ', self::$annotationMetadata[$name]['properties'])));
                }

                // handle the case if the property has no annotations
                if (!$property = self::$annotationMetadata[$name]['default_property']) {
                    throw AnnotationException::creationError(sprintf('The annotation @%s declared on %s does not accept any values, but got %s.', $originalName, $this->context, json_encode($values)));
                }
            }

            // checks if the attribute type matches
            if (null !== $value && isset(self::$annotationMetadata[$name]['attribute_types'][$property])) {
                $type = self::$annotationMetadata[$name]['attribute_types'][$property]['type'];

                if ($type === 'array') {
                    // Handle the case of a single value
                    if (!is_array($value)) {
                        $value = array($value);
                    }

                    // checks if the attribute has array type declaration, such as "array<string>"
                    if (isset(self::$annotationMetadata[$name]['attribute_types'][$property]['array_type'])) {
                        $arrayType = self::$annotationMetadata[$name]['attribute_types'][$property]['array_type'];
                        foreach ($value as $item) {
                            if (gettype($item) !== $arrayType && !$item instanceof $arrayType) {
                                throw AnnotationException::typeError($property, $originalName, $this->context, 'either a(n) '.$arrayType.', or an array of '.$arrayType.'s', $item);
                            }
                        }
                    }
                } elseif (gettype($value) !== $type && !$value instanceof $type) {
                    throw AnnotationException::typeError($property, $originalName, $this->context, 'a(n) '.self::$annotationMetadata[$name]['attribute_types'][$property]['value'], $value);
                }
            }

            $instance->{$property} = $value;
        }

        return $instance;
    }

    /**
     * Values ::= Array | Value {"," Value}*
     *
     * @return array
     */
    private function Values()
    {
        $values = array();

        // Handle the case of a single array as value, i.e. @Foo({....})
        if ($this->lexer->isNextToken(DocLexer::T_OPEN_CURLY_BRACES)) {
            $values['value'] = $this->Value();
            return $values;
        }

        $values[] = $this->Value();

        while ($this->lexer->isNextToken(DocLexer::T_COMMA)) {
            $this->match(DocLexer::T_COMMA);
            $token = $this->lexer->lookahead;
            $value = $this->Value();

            if ( ! is_object($value) && ! is_array($value)) {
                $this->syntaxError('Value', $token);
            }

            $values[] = $value;
        }

        foreach ($values as $k => $value) {
            if (is_object($value) && $value instanceof \stdClass) {
                $values[$value->name] = $value->value;
            } else if ( ! isset($values['value'])){
                $values['value'] = $value;
            } else {
                if ( ! is_array($values['value'])) {
                    $values['value'] = array($values['value']);
                }

                $values['value'][] = $value;
            }

            unset($values[$k]);
        }

        return $values;
    }

    /**
     * Value ::= PlainValue | FieldAssignment
     *
     * @return mixed
     */
    private function Value()
    {
        $peek = $this->lexer->glimpse();

        if (DocLexer::T_EQUALS === $peek['type']) {
            return $this->FieldAssignment();
        }

        return $this->PlainValue();
    }

    /**
     * PlainValue ::= integer | string | float | boolean | Array | Annotation
     *
     * @return mixed
     */
    private function PlainValue()
    {
        if ($this->lexer->isNextToken(DocLexer::T_OPEN_CURLY_BRACES)) {
            return $this->Arrayx();
        }

        if ($this->lexer->isNextToken(DocLexer::T_AT)) {
            return $this->Annotation();
        }

        switch ($this->lexer->lookahead['type']) {
            case DocLexer::T_STRING:
                $this->match(DocLexer::T_STRING);
                return $this->lexer->token['value'];

            case DocLexer::T_INTEGER:
                $this->match(DocLexer::T_INTEGER);
                return (int)$this->lexer->token['value'];

            case DocLexer::T_FLOAT:
                $this->match(DocLexer::T_FLOAT);
                return (float)$this->lexer->token['value'];

            case DocLexer::T_TRUE:
                $this->match(DocLexer::T_TRUE);
                return true;

            case DocLexer::T_FALSE:
                $this->match(DocLexer::T_FALSE);
                return false;

            case DocLexer::T_NULL:
                $this->match(DocLexer::T_NULL);
                return null;

            default:
                $this->syntaxError('PlainValue');
        }
    }

    /**
     * FieldAssignment ::= FieldName "=" PlainValue
     * FieldName ::= identifier
     *
     * @return array
     */
    private function FieldAssignment()
    {
        $this->match(DocLexer::T_IDENTIFIER);
        $fieldName = $this->lexer->token['value'];

        $this->match(DocLexer::T_EQUALS);

        $item = new \stdClass();
        $item->name  = $fieldName;
        $item->value = $this->PlainValue();

        return $item;
    }

    /**
     * Array ::= "{" ArrayEntry {"," ArrayEntry}* [","] "}"
     *
     * @return array
     */
    private function Arrayx()
    {
        $array = $values = array();

        $this->match(DocLexer::T_OPEN_CURLY_BRACES);
        $values[] = $this->ArrayEntry();

        while ($this->lexer->isNextToken(DocLexer::T_COMMA)) {
            $this->match(DocLexer::T_COMMA);

            // optional trailing comma
            if ($this->lexer->isNextToken(DocLexer::T_CLOSE_CURLY_BRACES)) {
                break;
            }

            $values[] = $this->ArrayEntry();
        }

        $this->match(DocLexer::T_CLOSE_CURLY_BRACES);

        foreach ($values as $value) {
            list ($key, $val) = $value;

            if ($key !== null) {
                $array[$key] = $val;
            } else {
                $array[] = $val;
            }
        }

        return $array;
    }

    /**
     * ArrayEntry ::= Value | KeyValuePair
     * KeyValuePair ::= Key "=" PlainValue
     * Key ::= string | integer
     *
     * @return array
     */
    private function ArrayEntry()
    {
        $peek = $this->lexer->glimpse();

        if (DocLexer::T_EQUALS === $peek['type']) {
            $this->match(
                $this->lexer->isNextToken(DocLexer::T_INTEGER) ? DocLexer::T_INTEGER : DocLexer::T_STRING
            );

            $key = $this->lexer->token['value'];
            $this->match(DocLexer::T_EQUALS);

            return array($key, $this->PlainValue());
        }

        return array(null, $this->Value());
    }
}