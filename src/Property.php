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
     * Return the value of the preference parameter for this property.
     * PREF is defined for any property which can have multiple values and is
     * undefined otherwise.
     * @param bool $default If true and no PREF parameter specified, this
     * method will return the preference value indicating the least preferred.
     * Passing false for $default is necessary to determine whether an explicit
     * PREF was provided.
     * @return int
     */
    public function getPref($default = true);
    
    /**
     * A sort-function suitable for use with \usort() or \uasort() which
     * compares the PREF parameter.
     * @param Property $a
     * @param Property $b
     * @return int -1, 0, or 1 if $b sorts less than, equal to, or greater than
     * $a.
     * @see \usort()
     * @see \uasort()
     * @see getPref()
     * @see compareValue Alternative comparison.
     */
    public function comparePref(Property $a, Property $b);

    /**
     * A sort-function suitable for use with \usort() or \uasort() which
     * compares property *values*.
     * @param Property $a
     * @param Property $b
     * @return int -1, 0, or 1 if $b sorts less than, equal to, or greater than
     * $a.
     * @see \usort()
     * @see \uasort()
     * @see comparePref() Alternative comparison.
     */
    public function compareValue(Property $a, Property $b);

        /**
     * A sort-function suitable for use with \usort() or \uasort() which
     * compares PREF parameters first, then property *values*.
     * @param Property $a
     * @param Property $b
     * @return int -1, 0, or 1 if $b sorts less than, equal to, or greater than
     * $a.
     * @see \usort()
     * @see \uasort()
     * @see comparePref() First comparison.
     * @see compareValue() Second comparison.
     */
    public function comparePrefThenValue(Property $a, Property $b);
    
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
