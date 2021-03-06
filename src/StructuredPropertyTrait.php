<?php
/**
 * A trait for a StructuredProperty.
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

trait StructuredPropertyTrait
{
    use SimplePropertyTrait;
    
    public function getField($field)
    {
        \assert(is_array($this->value));
        if (array_key_exists($field, $this->value))
           return $this->value[$field];
        else
            return null;
    }
    
    public function getAllowedFields()
    {
        \assert(\array_key_exists( 'allowedFields',
                $this->getSpecification()->getConstraints()) );
        return $this->getSpecification()->getConstraints()['allowedFields'];
    }
    
    public function compareValue(Property $a, Property $b)
    {
        // FIXME: unicode compare and take LANGUAGE into account.
        if ((string) $a == (string) $b)
            return 0;
        elseif ((string) $a < (string) $b)
            return -1;
        else
            return 1;    
    }
    
    protected function outputValue()
    {
        $fieldStrings = [];
        foreach ($this->getAllowedFields() as $field)
        {
            $fieldStrings[] = array_key_exists($field, $this->value)
                              ? $this->value[$field] : '';
        }
        return \implode(';', $fieldStrings);
    }
    
    public function __toString()
    {
        $fieldStrings = [];
        foreach ($this->getAllowedFields() as $field)
        {
            if (array_key_exists($field, $this->value))
                $fieldStrings[] = $this->value[$field];
        }
        return \implode(' ', $fieldStrings);
    }
}