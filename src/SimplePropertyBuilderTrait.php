<?php
/**
 * A Trait for a SimplePropertyBuilder.
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
 * A Trait to organize shared code for Properties having a simple scalar value.
 */
trait SimplePropertyBuilderTrait
{
    use PropertyBuilderTrait;

    /**
     * The value of the property. For SimpleProperty, the value is a string.
     * @var string
     */
    private $value;

    /**
     * Initialize the value.
     */
    protected function initValue() {$this->value = null;}
    
    /**
     * This method will extract the value component from a pre-parsed line of
     * raw VCard text.
     * @param \EVought\vCardTools\VCardLine $line A line of raw VCard text
     * which has already been parsed into its component structures.
     * @return SimpleProperty $this
     */
    protected function setValueFromLine(VCardLine $line)
    {
        $this->value = \stripcslashes($line->getValue());
        return $this;
    }
    
    /**
     * Set the value component of this PropertyBuilder.
     * @param string $value Not null.
     * @return SimpleProperty $this
     */
    public function setValue($value)
    {
        \assert(null !== $value);
        \assert(is_string($value));
        $this->value = $value;
        return $this;
    }

    /**
     * Get the value component of this Property.
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }
}