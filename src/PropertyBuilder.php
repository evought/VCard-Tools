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

interface PropertyBuilder
{
    /**
     * Constructs and returns a Property instance to this builder's
     * specifications.
     * @return Property
     */
    public function build();
    
    /**
     * @return string The name of the property being built.
     */
    public function getName();
    
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
     * Set a parameter for this property.
     * @param string $key The name of the parameter to set.
     * @param array $valueArray The list of values for the parameter.
     * @see PropertyBuilder::pushParameter()
     * @return PropertyBuilder $this
     */
    public function setParameter($key, Array $valueArray);
    
    /**
     * Return the values of assigned parameters.
     * @return array An array of arrays of parameter values, indexed by
     * parameter name.
     */
    public function getParameters();
    
    /**
     * Add a value for the parameter $key against this property.
     * @param string $key The name of the parameter to set.
     * @param string $value The value of the parameter to add.
     * @return PropertyBuilder $this
     */
    public function pushParameter($key, $value);
    
    /**
     * Returns true if-and-only-if this property takes the type parameter.
     * @return bool
     */
    public function isTypeAble();
    
    /**
     * Returns true if-and-only-if this property requires structured values.
     * @return bool
     */
    public function isStructured();
    
    /**
     * Returns true if-and-only-if this property stores external data (files
     * or BLOBs.
     */
    public function isFileProperty();

    /**
     * Parse a line of a VCard text representation and push any resulting
     * properties to the specified VCard.
     * Note that some vcard lines may generate *more than one property*
     * (e.g. categories) in a single parsing pass.
     * @param type $line
     */
    public static function fromVCardLine($line, VCard $vcard);
}