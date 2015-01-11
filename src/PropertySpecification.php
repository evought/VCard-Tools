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
    const SINGLE_PROPERTY = 1;
    
    /**
     * The property is allowed multiple instances.
     */
    const MULTIPLE_PROPERTY = 2;
    
    /**
     * The property allows multiple values separated by commas as a shortcut
     * to creating multiple instances (e.g. CATEGORIES).
     */
    const COMMA_PROPERTY = 3;
    
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
     * @see getCardinality() to contrast cardinality and multiplicity.
     * @see allowsMultipleProperties() Predicate to query this value.
     * @see allowsCommaProperties() Predicate to query this value.
     * @see requiresSingleProperty() Predicate to query this value.
     */
    private $multiplicity;
    
    /**
     * The cardinality according to RFC6350.
     * @see getCardinality() for additional description.
     * @see $multiplicity
     * @var string One of the values in $cardinalities
     */
    private $cardinality;
    
    /**
     * The permissible values for $cardinality and getCardinality().
     * @see getCardinality()
     * @var string[]
     */
    public static $cardinalities = [ 'Exactly One'=>'1', 'Zero or One'=>'*1',
                                     'One To N'=>'1*', 'Zero To N'=>'*'];
    
    /**
     * Create a PropertySpecification.
     * @param string $name The name of the property (lowercase).
     * @param int $multiplicity One of SINGLE_PROPERTY, MULTIPLE_PROPERTY,
     * COMMA_PROPERTY.
     * @param string $builderClass The name of the PropertyBuilder class used
     * to create Properties according to this specification.
     * The constructor of the PropertyBuilder will be passed this specification
     * as its only argument.
     * @param $cardinality string One of the values in $cardinalities.
     * @param array $constraints An array with keys and values defining
     * additional constraints on the parameters and value of the Property.
     * Interpreted by the PropertyBuilder subtype.
     * @see cardinalities
     * @see getCardinality() For description of $cardinality values.
     */
    public function __construct( $name, $multiplicity, $builderClass,
                                 $cardinality,
                                 Array $constraints = [] )
    {
        \assert(!(empty($name)));
        \assert(is_string($name));
        \assert(self::SINGLE_PROPERTY <= $multiplicity);
        \assert(self::COMMA_PROPERTY >= $multiplicity);
        \assert(!empty($builderClass));
        \assert(is_string($builderClass));
        
        $this->name = $name;
        $this->multiplicity = $multiplicity;
        $this->builderClass = $builderClass;
        \assert(\in_array($cardinality, self::$cardinalities));
        $this->cardinality = $cardinality;
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
     * instance of the property (multiplicity).
     * @return bool
     * @see getCardinality() to contrast cardinality and multiplicity
     */
    public function requiresSingleProperty()
    {
        return ($this->multiplicity === self::SINGLE_PROPERTY);
    }
    
    /**
     * Returns true if-and-only-if this specification allows multiple instances
     * of the property.
     * @return bool
     * @see getCardinality() to contrast cardinality and multiplicity
     */
    public function allowsMultipleProperties()
    {
        return ($this->multiplicity !== self::SINGLE_PROPERTY);
    }
    
    /**
     * Returns true if-and-only-if this specification allows multiple values
     * of the property separated by commas as a shortcut to creating multiple
     * instances of the property (e.g. CATEGORIES). This is an example of
     * multiplicity of properties.
     * @see getCardinality() to contrast cardinality and multiplicity
     * @return bool
     */
    public function allowsCommaProperties()
    {
        return ($this->multiplicity === self::COMMA_PROPERTY);
    }
    
    /**
     * Get the cardinality according to RFC6350. This is subtly different from 
     * *multiplicity* as used in this API. The spec uses cardinality to refer
     * to the number of logical values a property may have in a single VCard.
     * A property, however, may use the ALTID parameter to represent a
     * single logical value in multiple Property instances (see Section 5.4).
     * This results in a property such as N which has a cardinality of '*1'
     * (zero or one value) having a multiplicity in this API of
     * MULTIPLE_INSTANCE. As multiplicity determines how many values are set and
     * returned (array v. Property) and cardinality determines constraints such
     * as which parameters are allowed in what combinations, both attributes
     * must be tracked, especially when we deal with export to a database or
     * external format.
     * @see https://tools.ietf.org/html/rfc6350#section-5.4 for the explanation
     * of ALTID in the standard, including examples.
     * @see https://github.com/evought/VCard-Tools/issues/73 VCard-Tools issue
     * resulting in the creation of this enumeration.
     * @see AllowsMultipleProperties() contrast cardinality and multiplicity
     * @see $multiplicty
     * @return string One of the values in $cardinalities
     */
    public function getCardinality()
    {
        \assert(in_array($this->cardinality, self::$cardinalities));
        return $this->cardinality;
    }
    
    /**
     * Return true if-and-only-if the cardinality of this property allows more
     * than one logical value. NOTE: This function can return false and the
     * property may still be represented by more than one _Property instance_
     * (multiplicity) as long as they represent only one *logical value*
     * (cardinality). This function is primarily used to determine what
     * parameters are permitted for certain properties based on their specified
     * cardinality.
     * @see getCardinality() For an explanation of the differences between
     * cardinality and multiplicity in VCards.
     * @see requiresSingleProperty() to determine the number of Property
     * *instances* permitted for the property defined by this specification. 
     * @return type
     */
    public function isCardinalityToN()
    {
        return in_array( $this->cardinality,
                         [ self::$cardinalities['One To N'],
                           self::$cardinalities['Zero To N'] ] );
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
