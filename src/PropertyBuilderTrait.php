<?php
/**
 * A trait for a PropertyBuilder.
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

trait PropertyBuilderTrait
{
    /**
     * The PropertySpecification defining the property being built.
     * @var PropertySpecification
     */
    private $specification;
    
    /**
     * The property group associated with this property.
     * @var string
     */
    private $group;
    
    /**
     *The PREF parameter value, if specified.
     * @var int|null
     */
    private $pref = null;
    
    public function push(PropertyContainer $container)
    {
        $property = $this->build();
        $container->push($property);
        return $this;
    }
    
    public function getSpecification() {return $this->specification;}
    
    public function getName()
    {
        return $this->specification->getName();
    }
    
    public function setGroup($group)
    {
        \assert(\is_string($group));
        $this->group = $group;
        return $this;
    }
    
    public function getGroup()
    {
        return $this->group;
    }
    
    /**
     * Set the PREF parameter.
     * @param int $value 1 <= $pref <= 100 
     * @return self $this
     */
    public function setPref($value)
    {
        \assert(is_int($value));
        $this->pref= $value;
        return $this;
    }
    
    /**
     * Get the value of the PREF parameter, or null if none specified. PREF is
     * only defined for Properties which can have more than one value.
     * @return int|null In the range 1 to 100.
     */
    public function getPref()
    {
        return $this->pref;
    }
    
    protected function initBuilder($specification)
    {
        \assert(null !== $specification);
        
        $this->specification = $specification;
    }
    
    protected function setBuilderFromLine(VCardLine $vcardLine)
    {
        \assert($this->getName() === $vcardLine->getName());
        $this->group = empty($vcardLine->getGroup())
                        ? null : $vcardLine->getGroup();
        if ($vcardLine->hasParameter('pref'))
        {
            if ($this->getSpecification()->requiresSingleValue())
                throw new \DomainException(
                    'PREF not allowed for single value property '
                    . $this->getName ());
            $this->pref = $vcardLine->getParameter('pref');
        }
        return $this;
    }
}