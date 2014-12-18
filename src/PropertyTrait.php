<?php
/**
 * Trait for a minimal Property.
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

trait PropertyTrait
{
    private $name;
    private $hasParameters;
    private $builder;
    
    protected function initProperty(PropertyBuilder $builder)
    {
        $this->name = $builder->getName();
        $this->hasParameters = false;
        $this->builder = $builder;
    }
    
    public function getName()
    {
        return $this->name;
    }
    
    public function __toString()
    {
        $output = $this->outputName();
        if ($this->hasParameters())
            $output .= ';' . $this->outputParameters();
        $output .= ':';
        $output .= $this->outputValue();
        $output .= "\n";
        return $output;
    }
    
    protected function outputName()
    {
        return \strtoupper( $this->getName());
    }
    
    /**
     * Format value for output as part of a raw vcard string.
     * @return string
     */
    
    /**
     * Format parameters for output as part of a raw vcard string.
     * Should only be called if hasParameters() returns true.
     * @return string The string of parameters.
     */
    protected abstract function outputParameters();
    
    /**
     * Returns true if-and-only-if there are named parameters to output.
     */
    protected function hasParameters() {return $this->hasParameters;}
}