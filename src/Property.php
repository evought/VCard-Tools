<?php
/**
 * Class for representing a Property of a VCard.
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
 * Minimal interface for a VCard property. Instances of Properties are created
 * by the appropriate Builder class and are not instantiated directly.
 */
interface Property
{
    /**
     * Return the PropertySpecification defining this Property.
     * @return PropertySpecification
     */
    public function getSpecification();
    
    /**
     * Return the RFC 6350 VCard Property Name (e.g. adr) this property
     * represents.
     * @return string
     */
    public function getName();
    
    /**
     * Return the property group associated with this property.
     * @return string
     * @see https://tools.ietf.org/html/rfc6350#section-3.3
     */
    public function getGroup();
    
    /**
     * Return the value of this property. Value may be simple or structured
     * as dependendent on the property name and type.
     * @return mixed The property value.
     */
    public function getValue();
    
    /**
     * Convert the value of this property to a string. This will produce
     * a *human-readable* representation of the value, taking advantage of
     * any appropriate parameter hints for the presentation of that value
     * (e.g. LABEL) but will not include the parameters themselves.
     * Contrast output(), which produces a *machine-readable* representation.
     * @see output()
     * @return string
     */
    public function __toString();
    
    /**
     * Format this property appropriately for inclusion as a line in a raw
     * VCard file. The output will not have a trailing line-break.
     * @return string
     */
    public function output();
}
