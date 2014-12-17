<?php
/**
 * A builder for a StructuredProperty.
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

interface StructuredPropertyBuilder extends PropertyBuilder
{
    /**
     * Lists the defined fields for this structured property.
     * @return Array An array of field names.
     */
    public function fields();
    
    /**
     * Sets the value of the requested field, overriding any existing value.
     * @param string $field The name of the field to set.
     * @param string $value The value to set for the field. If null, the field
     * will be unset.
     * @return StructuredPropertyBuilder $this
     * @throws \DomainException If the requested field is not allowed for this
     * property.
     */
    public function setField($field, $value);
    
    /**
     * Unsets the requested field.
     * @param string $field The field name to unset.
     * @return StructuredPropertyBuilder $this
     */
    public function unsetField($field);
    
    /**
     * Returns true if-and-only-if the requested field has a value.
     * @param string $field The name of the field to test.
     * @return bool
     */
    public function isFieldSet($field);
    
    /**
     * Fetches the contents of a field (if any).
     * @param string $field The name of the field to fetch.
     * @return string The contents of the field, or null.
     * @throws \DomainException If the field is not defined.
     * @see fields()
     */
    public function getField($field);
}