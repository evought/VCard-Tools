<?php
/**
 * A trait for a StructuredPropertyBuilder.
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

trait StructuredPropertyBuilderTrait
{
    use PropertyBuilderTrait;
    
    private $value;
    
    protected function initFields()
    {
        \assert(array_key_exists( 'allowedFields',
                                  $this->getSpecification()->getConstraints()) );
        $this->value = [];
    }
    
    protected function setFieldsFromLine(VCardLine $line)
    {
        /* @var $fields array */
        $fields = $this->fields();
        /* @var $fieldStrs array */
        $fieldStrs = \preg_split(VCardLine::SEMICOLON_SPLIT, $line->getValue());
        
        if (\count($fieldStrs) > \count($fields))
            throw new \DomainException(
                'Field count, ' . \count($fieldStrs)
                . 'is greater than the number of fields defined ('
                . \count($fields) . ') for property '
                . $this->getName() . " : " . $line->getValue() );
        foreach($fieldStrs as $index=>$value)
        {
            if (0 !== \strlen($value))
                $this->setField($fields[$index], \stripcslashes(\trim($value)));
        }
    }

    /**
     * Return the list of allowed fields for this property from the
     * Specification.
     * @return string[]
     */
    public function fields()
    {
        return $this->getSpecification()->getConstraints()['allowedFields'];
    }
    
    public function fieldAllowedValues($field)
    {
        $constraints = $this->getSpecification()->getConstraints();
        if (!array_key_exists('allowedFieldValues', $constraints))
            return null;
        $allowedValues = $constraints['allowedFieldValues'];
        
        if (!array_key_exists($field, $allowedValues))
        {
            return null;
        } else {
            return $allowedValues[$field];
        }
    }

    /**
     * Return the value of the named field, if set.
     * @param string $field The name of the field to look up
     * @return string|null
     */
    public function getField($field)
    {
        \assert(is_array($this->value));
        
        if (array_key_exists($field, $this->value))
            return $this->value[$field];
        else
            return null;
    }
    
    /**
     * Returns true if $field is one of the allowed fields for this property
     * and its value is permitted, throws an exception otherwise.
     * @param string $field The field name to check.
     * @param mixed $value The value to check.
     * @return boolean
     * @throws \DomainException If the field/value is not allowed.
     */
    protected function checkField($field, $value)
    {
        \assert(null !== $field);
        \assert(is_string($field));
        if (!(in_array($field, $this->fields())))
            throw new \DomainException( $field . ' is not an allowed field for '
                                        . $this->getName() );
        
        $allowedValues = $this->fieldAllowedValues($field);
        if (null === $allowedValues) return true;
        
        if (!(in_array($value, $allowedValues)))
            throw new \DomainException( $value . ' is not an allowed value for '
                                        . $field . ' in ' . $this->getName() );
        return true;
    }

    public function setField($field, $value)
    {
        \assert(is_array($this->value));
        $this->checkField($field, $value);
        if (null === $value)
            unset ($this->value[$field]);
        else
            $this->value[$field] = $value;
        return $this;
    }
    
    public function getValue() {return $this->value;}
    
    public function setValue($value)
    {
        \assert(is_array($value));
        $badKeys = \array_diff_key($value, \array_flip($this->fields()));
        if (!empty($badKeys))
            throw new \DomainException(\implode(' ', \array_keys($badKeys))
                                                . ': not in allowed fields for '
                                                . $this->getName() );
        $this->value = $value;
        return $this;
    }

    public function isFieldSet($field)
    {
        \assert(is_array($this->value));
        \assert(null !== $field);
        \assert(is_string($field));
        return array_key_exists($field, $this->value);
    }

    public function unsetField($field)
    {
        \assert(is_array($this->value));
        \assert(null !== $field);
        \assert(is_string($field));
        unset($this->value[$field]);
        return $this;
    }
}