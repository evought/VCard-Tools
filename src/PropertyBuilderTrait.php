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
    
    /**
     * The VALUE parameter setting, or null if it has not explicitly been set.
     * @var string|null 
     */
    private $valueType = null;
    
    /**
     * The default VALUE parameter for this property.
     * @var string
     */
    private $valueTypeDefault = null;
    
    /**
     * The list of permitted VALUE parameter settings for this property.
     * @var string[]
     */
    private $allowedValueTypes = null;
    
    /**
     * @see http://www.iana.org/assignments/vcard-elements/vcard-elements.xhtml#value-data-types
     * @var string[]
     */
    protected static $allValueTypes
        = [ 'text', 'uri', 'date', 'time', 'date-time', 'date-and-or-time',
            'timestamp', 'boolean', 'integer', 'float', 'utc-offset',
            'language-tag' ];
    
    public function pushTo(PropertyContainer $container)
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
     * Set the VALUE parameter for this property. VALUE describes the content
     * and format of the property value. If VALUE is not explicitly set,
     * a property-specific default will be used.
     * In many properties, resetting VALUE to text will allow free-form
     * information to be set.
     * @throws Exceptions\MalformedParameterException If an attempt is made to
     * set a VALUE which is not permitted for the target Property.
     * @param string $valueType
     * @return self $this
     * @see PropertyBuilder::setValueType()
     */
    public function setValueType($valueType)
    {
        if ($this->checkValueType($valueType) === false)
            throw new Exceptions\MalformedParameterException(
                'Value type: ' . $valueType . ' not permitted for '
                . $this->getName() );
        $this->valueType = $valueType;
        return $this;
    }
    
    protected function checkValueType($valueType)
    {
        \assert(null !== $valueType);
        \assert(\is_string($valueType), \print_r($valueType, true));
        $allowedTypes = $this->getAllowedValueTypes();
        if (\in_array($valueType, $allowedTypes))
            return true;
        else
            return false;
    }

    /**
     * Return the VALUE param setting for this property, if it has been set.
     * @return string|null The VALUE parameter contents, or null.
     */
    public function getValueType()
    {
        return $this->valueType;
    }
    
    /**
     * Return the property-specific default for the VALUE parameter.
     * @return string The default VALUE parameter.
     */
    public function getValueTypeDefault()
    {
        return $this->valueTypeDefault;
    }
    
    /**
     * Return the property-specific list of allowed VALUE parameter settings.
     * @return string[] an array containing permitted values.
     */
    public function getAllowedValueTypes()
    {
        return $this->allowedValueTypes;
    }
    
    /**
     * If the Specification does not provide a valueTypeDefault, provide a
     * default default. Override in a concrete type to provide specific
     * behaviors for categories of properties.
     * @return string
     */
    protected function getDefaultValueTypeDefault()
    {
        return 'text';
    }
    
    /**
     * Set the PREF parameter.
     * @param int $value 1 <= $pref <= 100 
     * @return self $this
     */
    public function setPref($value)
    {
        $this->pref= (int) $value;
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
    
    /**
     * Initialize core values of the Builder.
     * Should be the first initialization performed in a concrete type.
     * @param PropertySpecification $specification
     */
    protected function initBuilder($specification)
    {
        \assert(null !== $specification);
        
        $this->specification = $specification;
        
        $constraints = $specification->getConstraints();
        if (array_key_exists('allowedValueTypes', $constraints))
        {
            $this->allowedValueTypes = $constraints['allowedValueTypes'];
        } else {
            $this->allowedValueTypes = self::$allValueTypes;
        }
        
        if (array_key_exists('valueTypeDefault', $constraints))
        {
            $this->valueTypeDefault = $constraints['valueTypeDefault'];
        } else {
            $this->valueTypeDefault = $this->getDefaultValueTypeDefault();
        }
    }
    
    protected function setBuilderFromLine(VCardLine $vcardLine)
    {
        \assert($this->getName() === $vcardLine->getName());
        $this->group = empty($vcardLine->getGroup())
                        ? null : $vcardLine->getGroup();
        if ($vcardLine->hasParameter('pref'))
        {
            if (!($this->getSpecification()->isCardinalityToN()))
                throw new \DomainException(
                    'PREF not allowed for *1 or 1 cardinality: '
                    . $this->getName ());
            $this->pref = $vcardLine->getParameter('pref');
        }
        if ($vcardLine->hasParameter('value'))
        {
            $valueTypes = $vcardLine->getParameter('value');
            if (count($valueTypes) != 1)
                throw new Exceptions\MalformedParameterException(
                    'Muliple VALUE parameters provided for : '
                    . $this->getName() );
            $this->setValueType($valueTypes[0]);
        }
        return $this;
    }
}