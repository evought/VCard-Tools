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

class SimplePropertyBuilder
        implements \EVought\vCardTools\PropertyBuilder
{
    private $name;
    private $value;
    private $parameters;
    
    public function __construct($name)
    {
        \assert(null !== $name);
        \assert(is_string($name));
        
        $this->name = $name;
        $this->value = null;
        $this->parameters = [];
    }
    
    public function build() {
        
    }

    public function isFileProperty()
    {
        return false;
    }

    public function isStructured() {
        return false;
    }

    public function isTypeAble() {
        return false;
    }

    public function pushParameter($key, $value)
    {
        \assert(null !== $key);
        \assert(is_string($key));
        \assert(null !== $value);
        
        $this->parameters[$key][] = $value;
        return $this;
    }

    public function setParameter($key, Array $valueArray)
    {
        \assert(null !== $key);
        \assert(is_string($key));        
        $this->parameters = $valueArray;
        return $this;
    }
    
    public function getParameters()
    {
        return $this->parameters;
    }

    public function setValue($value)
    {
        \assert(null !== $value);
        \assert(is_string($value));
        $this->value = $value;
        return $this;
    }

    public static function fromVCardLine($line, VCard $vcard)
    {
        \assert(false, 'Not Implemented.');        
    }

    public function getName()
    {
        return $this->name;
    }

    public function getValue()
    {
        return $this->value;
    }

}