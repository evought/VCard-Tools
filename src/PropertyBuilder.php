<?php
/**
 * Class for constructing/modifying a Property of a VCard.
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
 * Minimal interface for a PropertyBuilder.
 * PropertyBuilders are utilities for creating and returning instances of
 * Properties according to a specification.
 * Each Builder subtype creates a Property according to the rules for that
 * Property type.
 */
interface PropertyBuilder
{
    /**
     * Constructs and returns a Property instance to this builder's
     * specifications.
     * @return Property
     */
    public function build();
    
    /**
     * Builds the Property and adds it to $container.
     * @param \EVought\vCardTools\PropertyContainer $container The container
     * to add the Property to.
     * @return self $this
     */
    public function push(PropertyContainer $container);
    
    /**
     * @return string The name of the property being built.
     */
    public function getName();
    
    /**
     * Set the property group associated with this property.
     * @param string $group The group name to set.
     * @return PropertyBuilder $this
     * @see https://tools.ietf.org/html/rfc6350#section-3.3
     */
    public function setGroup($group);
    
    /**
     * Get the property group associated with this property (if any).
     * @return string
     * @see https://tools.ietf.org/html/rfc6350#section-3.3
     */
    public function getGroup();
    
    /**
     * Set the value of this property. Value may be simple or strucured
     * according to the specification for this property.
     * @param mixed $value
     * @return PropertyBuilder $this
     */
    public function setValue($value);
    
    /**
     * Returns the value of the property being built.
     * @return mixed may be simple or structured depending on the property.
     */
    public function getValue();
    
    /**
     * Returns the PropertySpecification defining the property being built.
     * @return PropertySpecification
     */
    public function getSpecification();
    
    /**
     * Read a VCardLine and extract values for this property.
     * @return VCardLine $this
     * @throws \DomainException If any of the components found in $line violate
     * constraints defined for this property.
     */
    public function setFromVCardLine(VCardLine $line);
    
}