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

/**
 * A trait to organize shared code for Property implementations.
 * Provides standard handling for the PropertySpecification, name, group,
 * common output structure, and common properties which should be applicable
 * to all Properties. Putting this code in a trait allows combination of
 * sub-interfaces to simulate multiple inheritance.
 */
trait PropertyTrait
{
    /**
     * The PropertySpecification defining this property type.
     * @var PropertySpecification
     */
    private $specification;
    
    /**
     * The property group for this property.
     * @var string
     */
    private $group;
    
    /**
     * True if-and-only-if this Property has parameters to output.
     * @var bool
     */
    private $hasParameters;
    
    private $valueType = null;
    
    private $valueTypeDefault = null;
    
    /**
     * The PREF parameter for this property (if specified).
     * @var int
     */
    private $pref;
    
    /**
     * Initialize core Property from PropertyBuilder
     * @param \EVought\vCardTools\PropertyBuilder $builder
     */
    protected function initProperty(PropertyBuilder $builder)
    {
        $this->specification = $builder->getSpecification();
        $this->group = $builder->getGroup();
        $this->hasParameters = false;
        
        $this->valueType = $builder->getValueType();
        $this->valueTypeDefault = $builder->getValueTypeDefault();
        
        $this->pref = $builder->getPref();
        
        if ((null !== $this->pref) || (null !== $this->valueType))
            $this->hasParameters = true;
    }
    
    /**
     * Return the specification for this property.
     * @see Property::getSpecification.
     * @return PropertySpecification
     */
    public function getSpecification() {return $this->specification;}
    
    /**
     * Convenience method to get the name of the property from the
     * specification.
     * @see Property::getName()
     * @return string
     */
    public function getName()
    {
        return $this->specification->getName();
    }
    
    /**
     * Get the property group.
     * @see Property::getGroup()
     * @return string
     */
    public function getGroup()
    {
        return $this->group;
    }
    
    /**
     * Return the VALUE param setting for this property, if set.
     * The VALUE parameter describes the contents and format of the property
     * value. If VALUE was not explicitly set, a default for this property will
     * be returned.
     * @param bool $subDefault If true, automatically substitute a
     * property-specific default if none has been explicitly set.
     * Use false to retrieve only an explicitly-set VALUE for re-exporting this
     * property.
     * @see Property::getValueType()
     */
    public function getValueType($subDefault = true)
    {
        if (null !== $this->valueType)
            return $this->valueType;
        elseif (true === $subDefault)
            return $this->valueTypeDefault;
        
        return null;
    }
    
    /**
     * Return the value of the preference parameter for this property.
     * PREF is defined for any property which can have multiple values and is
     * undefined otherwise.
     * @param bool $default If true and no PREF parameter specified, this
     * method will return the preference value indicating the least preferred.
     * Passing false for $default is necessary to determine whether an explicit
     * PREF was provided.
     * @return int
     * @see Property::getPref()
     */
    public function getPref($default = true)
    {
        if ($default === true)
            return ($this->pref === null) ? 100 : $this->pref;
        else
            return $this->pref;
    }
    
    /**
     * A sort-function suitable for use with \usort() or \uasort() which
     * compares the PREF parameter.
     * @param Property $a
     * @param Property $b
     * @return int -1, 0, or 1 if $a sorts less than, equal to, or greater than
     * $b.
     * @see Property::comparePref()
     */
    public function comparePref(Property $a, Property $b)
    {
        if ($a->getPref() == $b->getPref())
            return 0;
        elseif ($a->getPref() < $b->getPref())
            return -1;
        else
            return 1;
    }

    /**
     * A sort-function suitable for use with \usort() or \uasort() which
     * compares PREF parameters first, then property *values*.
     * @param Property $a
     * @param Property $b
     * @return int -1, 0, or 1 if $a sorts less than, equal to, or greater than
     * $b.
     * @see Property::comparePrefThenValue()
     */
    public function comparePrefThenValue(Property $a, Property $b)
    {
        $prefCompare = $this->comparePref($a, $b);
        if ($prefCompare === 0)
            return $this->compareValue($a, $b);
        else
            return $prefCompare;
    }
    
    /**
     * Return a human-readable representation of the property's *value*.
     * @see Property::toString()
     * @return string
     */
    public function __toString()
    {
        return $this->getValue();
    }
    
    /**
     * Return a machine-readable representation of the entire Property,
     * including parameters.
     * @see Property::output()
     * @return string
     */
    public function output()
    {
        $output = $this->outputName();
        if ($this->hasParameters())
            $output .= ';' . $this->outputParameters();
        $output .= ':';
        $output .= $this->outputValue();
        $output .= "\n";
        return $output;
    }

    /**
     * Format the property name for output as part of a raw vcard line.
     * @return string
     */
    protected function outputName()
    {
        $groupPart = empty($this->group) ? '' : \strtoupper($this->group . '.');
        return \strtoupper($groupPart . $this->getName());
    }
    
    /**
     * Format parameters for output as part of a raw vcard string.
     * Should only be called if hasParameters() returns true.
     * @return string The string of parameters.
     * @see output()
     */
    protected function outputParameters()
    {
        $params = '';
        if (null !== $this->pref)
            $params .= 'PREF='.$this->pref;
        if (null !== $this->valueType)
            $params .= 'VALUE='.$this->getValueType();
        return $params;
    }
    
    /**
     * Returns true if-and-only-if there are named parameters to output.
     * @return bool
     */
    protected function hasParameters() {return $this->hasParameters;}
}