<?php
/**
 * A property which takes a simple value.
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
 * A SimpleProperty is a Property with a simple scalar value (represented as
 * a string).
 */
class SimpleProperty implements \EVought\vCardTools\Property
{
    use SimplePropertyTrait;

    /**
     * Create a new SimpleProperty from the PropertyBuilder.
     * @param \EVought\vCardTools\PropertyBuilder $builder The builder being
     * used to initialize this property.
     */
    public function __construct(SimplePropertyBuilder $builder)
    {
        $this->initProperty($builder);
        $this->setValueFromBuilder($builder);
    }

    /**
     * Format the parameters appropriate for inclusion in a VCard text line.
     * As SimpleProperty does not take parameters, output is empty.
     * @return string
     */
    protected function outputParameters() {return '';}
}