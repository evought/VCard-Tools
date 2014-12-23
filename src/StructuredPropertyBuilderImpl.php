<?php
/**
 * A concrete StructuredPropertyBuilder.
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
 * Description of StructuredPropertyBuilderImpl
 *
 * @author evought
 */
class StructuredPropertyBuilderImpl implements StructuredPropertyBuilder
{
    use StructuredPropertyBuilderTrait;
    
    /**
     * Create a new builder from the given PropertySpecification. 
     * @param \EVought\vCardTools\PropertySpecification $specification
     * 'allowedFields' shall be defined by getConstraints() to be a list
     * of fields permitted for this property.
     */
    public function __construct(PropertySpecification $specification)
    {
        $this->initBuilder($specification);
        $this->initFields();
    }
    
    public function setFromVCardLine(VCardLine $line)
    {
        $this->setBuilderFromLine($line);
        $this->setFieldsFromLine($line);
        return $this;
    }
    
    /**
     * 
     * @return \EVought\vCardTools\StructuredProperty
     */
    public function build()
    {
        \assert(null !== $this->value);
        \assert(is_array($this->value));
        return new StructuredPropertyImpl($this);
    }
}
