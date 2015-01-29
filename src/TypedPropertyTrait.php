<?php
/**
 * A TypedProperty implementation.
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
 * A trait to store common code for TypedProperty and any derived type which
 * supports the TYPE parameter. Provides methods for accessing set properties,
 * accessing the list of defined TYPEs for this property, and properly
 * outputting a property with TYPEs set.
 */
trait TypedPropertyTrait
{
    /**
     * The list of types set for this Property.
     * @var string[]
     */
    private $types;
    
    /**
     * Copy and set the types from the builder being used to initialize the
     * Property. This method should be called from the constructor of a
     * concrete implementation.
     * @param \EVought\vCardTools\TypedPropertyBuilder $builder The builder
     * being used to initialize this Property.
     */
    protected function setTypesFromBuilder(TypedPropertyBuilder $builder)
    {
        $this->types = $builder->getTypes();
        if (!empty($this->types))
        {
            $this->hasParameters  = true;
            \sort($this->types); // Make comparisons stable
        }
    }
    
    /**
     * Get the types set for this Property.
     * @return string[]
     */
    public function getTypes()
    {
        return $this->types;
    }
    
    /**
     * Return the list of types defined for this Property.
     * If the array is empty, all types are permitted.
     * This is used for X-tended properties for which we do not have a
     * specification and therefore cannot enforce type constraints.
     * @return string[]
     */
    public function getAllowedTypes()
    {
        return $this->getSpecification()->getConstraints()['allowedTypes'];
    }
    
    /**
     * Takes the array of types and turns them into a single string for
     * inclusion in a raw vCard line.
     * @return string
     */
    protected function outputTypes()
    {
        assert(!empty($this->types));
        return 'TYPE=' . \implode(',', \array_map('\strtoupper', $this->types));
    }
}