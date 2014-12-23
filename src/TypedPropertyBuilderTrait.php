<?php
/**
 * A trait for a TypedPropertyBuilder.
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
 * A trait to contain reusable code for TypePropertyBuilders.
 */
trait TypedPropertyBuilderTrait
{
    private $types;
    
    protected function initTypes()
    {
        $this->types = [];
        \assert(array_key_exists( 'allowedTypes',
            $this->getSpecification()->getConstraints()) );
    }
    
    protected function setTypesFromLine(VCardLine $line)
    {
        if (!empty($line->getParameter('type')))
            $this->setTypes($line->getParameter('type'));
    }


    public function addType($type)
    {
        $this->checkType($type);
        if (!(in_array($type, $this->types)))
            $this->types[] = $type;
        return $this;
    }

    protected function checkType($type)
    {
        \assert(null !== $type);
        \assert(is_string($type));
        if (!(in_array($type, $this->getAllowedTypes())))
            throw new \DomainException( $type . ' is not an allowed type for '
                                        . $this->getName() );
        return true;
    }
    
    public function getTypes()
    {
        return $this->types;
    }

    public function setTypes(Array $types)
    {
        $this->types = array_filter($types, [$this, 'checkType']);
        return $this;
    }
    
    public function getAllowedTypes()
    {
        return $this->getSpecification()->getConstraints()['allowedTypes'];
    }
}
