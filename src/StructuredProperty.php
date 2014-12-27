<?php
/**
 * A Property with structured fields.
 * @author Eric Vought <evought@pobox.com>
 * @copyright Eric Vought 2014, Some rights reserved.
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
 * A Property which has a structured (associative array) for its value
 * component. A StructuredProperty provides access to defined subfields.
 * Property::getValue will return an associative array.
 * Property::output will format subfields for output according to RFC6350,
 * and toString will format the subfields with spaces.
 * @see StructuredPropertyBuilder
 */
interface StructuredProperty extends Property
{   
    /**
     * Fetches the contents of a field (if any).
     * 
     * @param string $field The name of the field to fetch.
     * @return string|null
     * @throws \DomainException If the field is not defined.
     * @see getAllowedFields()
     */
    public function getField($field);
    
    /**
     * Returns the list of fields specified for this property.
     * 
     * @return string[]
     */
    public function getAllowedFields();
}