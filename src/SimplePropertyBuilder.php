<?php
/**
 * Class for constructing/modifying a SimpleProperty.
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
 * A PropertyBuilder for a SimpleProperty. SimplePropertyBuilder provides the
 * ability to set simple string types on its target property.
 */
class SimplePropertyBuilder
        implements \EVought\vCardTools\PropertyBuilder
{
    use SimplePropertyBuilderTrait;
    
    /**
     * Initialize a new builder for the property specification.
     * @param PropertySpecification $specification The definition of the
     * target property and its constraints.
     */
    public function __construct(PropertySpecification $specification)
    {
        $this->initBuilder($specification);
        $this->initValue();
    }
    
    /**
     * Initialize this builder from the parsed components of a VCard format
     * line, copying the value.
     * @param VCardLine $line The pre-parsed line.
     * @return self $this
     */
    public function setFromVCardLine(VCardLine $line)
    {
        $this->setBuilderFromLine($line);
        $this->setValueFromLine($line);
        return $this;
    }
    
    /**
     * Construct, initialize, and return a SimpleProperty from this builder.
     * @return SimpleProperty
     */
    public function build()
    {
        \assert(null !== $this->value);
        return new SimpleProperty($this);
    }
}