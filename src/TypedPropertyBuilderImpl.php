<?php
/**
 * A builder for TypedPropertyImpl.
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
 * A concrete TypedPropertyBuilder.
 */
class TypedPropertyBuilderImpl implements TypedPropertyBuilder
{
    use SimplePropertyBuilderTrait, TypedPropertyBuilderTrait;
    
    /**
     * Construct a new builder.
     * @param \EVought\vCardTools\PropertySpecification $specification
     * 'allowedTypes' shall be a key in getConstraints() containing an array
     * of permitted types.
     */
    public function __construct(PropertySpecification $specification)
    {
        $this->initBuilder($specification);
        $this->initValue();
        $this->initTypes();
    }

    public function setFromVCardLine(VCardLine $line)
    {
        $this->setBuilderFromLine($line);
        $this->setValueFromLine($line);
        $this->setTypesFromLine($line);
        return $this;
    }

    public function build()
    {
        return new TypedPropertyImpl($this);
    }
}