<?php
/**
 * Class for defining the specification for a Property.
 *
 * @link https://github.com/evought/VCard-Tools
 * @author Eric Vought
 * @see RFC 2426, RFC 2425, RFC 6350
 * @license MIT http://opensource.org/licenses/MIT
 */

/*
 * The MIT License
 *
 * Copyright 2014 evought.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace EVought\vCardTools;

/**
 * Class for defining the specification for a Property.
 * Creates mapping to PropertyBuilder subclasses.
 *
 * @author evought
 */
class PropertySpecification
{
    /**
     * The property is allowed at most one instance.
     */
    const SINGLE_VALUE = 1;
    
    /**
     * The property is allowed multiple instances.
     */
    const MULTIPLE_VALUE = 2;
    
    /**
     * The property allows multiple values separated by commas as a shortcut
     * to creating multiple instances (e.g. CATEGORIES).
     */
    const COMMA_VALUE = 3;
    
    /**
     * The name of the VCard property (lowercase)
     * @var string
     */
    private $name;
    
    /**
     * The class of the PropertyBuilder which should be used to create
     * Properties according to this specification.
     * @var type 
     */
    private $builderClass;
    
    /**
     * An array of keys and values further constraining property values and
     * parameters. Interpreted by the PropertyBuilder subtype.
     * @var Array
     */
    private $builderConstraints;
    
    /**
     * The number of instances of this property allowed, as specified by the
     * _VALUE constants.
     * @var int
     */
    private $multiplicity;
    
    /**
     * Create a PropertySpecification.
     * @param string $name The name of the property (lowercase).
     * @param int $multiplicity One of SINGLE_VALE, MULTIPLE_VALUE, COMMA_VALUE.
     * @param string $builderClass The name of the PropertyBuilder class used
     * to create Properties according to this specification.
     * The constructor of the PropertyBuilder will be passed this specification
     * as its only argument. 
     * @param array $constraints An array with keys and values defining
     * additional constraints on the parameters and value of the Property.
     * Interpreted by the PropertyBuilder subtype.
     */
    public function __construct( $name, $multiplicity, $builderClass,
                                 Array $constraints = [] )
    {
        \assert(!(empty($name)));
        \assert(is_string($name));
        \assert(self::SINGLE_VALUE <= $multiplicity);
        \assert(self::COMMA_VALUE >= $multiplicity);
        \assert(!empty($builderClass));
        \assert(is_string($builderClass));
        
        $this->name = $name;
        $this->multiplicity = $multiplicity;
        $this->builderClass = $builderClass;
        $this->builderConstraints = $constraints;
    }
    
    /**
     * Return the name of the property this specifies (lowercase).
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
    
    /**
     * Return true if-and-only-if this specification allows at most one
     * instance of the property.
     * @return bool
     */
    public function requiresSingleValue()
    {
        return ($this->multiplicity === self::SINGLE_VALUE);
    }
    
    /**
     * Returns true if-and-only-if this specification allows multiple instances
     * of the property.
     * @return bool
     */
    public function allowsMultipleValues()
    {
        return ($this->multiplicity !== self::SINGLE_VALUE);
    }
    
    /**
     * Returns true if-and-only-if this specification allows multiple values
     * of the property separated by commas as a shortcut to creating multiple
     * instances of the property (e.g. CATEGORIES).
     * @return bool
     */
    public function allowsCommaValues()
    {
        return ($this->multiplicity === self::COMMA_VALUE);
    }
    
    /**
     * Returns an array of keys and values placing additional constraints on
     * the parameters and values of the property.
     * Interpreted by the PropertyBuilder subtype.
     * @return Array
     */
    public function getConstraints()
    {
        return $this->builderConstraints;
    }
    
    /**
     * Creates and returns a new PropertyBuilder instance of the appropriate
     * class which may be used to create values of the specified property.
     * This specification will be passed to the PropertyBuilder.
     * @return \EVought\vCardTools\classname
     */
    public function getBuilder()
    {
        $classname = $this->builderClass;
        $builder = new $classname($this);
        \assert($builder instanceof \EVought\vCardTools\PropertyBuilder);
        return $builder;
    }
}
