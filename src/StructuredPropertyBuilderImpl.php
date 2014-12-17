<?php
/**
 * A concrete StructuredPropertyBuilder.
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
 * Description of StructuredPropertyBuilderImpl
 *
 * @author evought
 */
class StructuredPropertyBuilderImpl implements StructuredPropertyBuilder
{
    use PropertyBuilderTrait;
    
    private $allowedFields;
    private $value;
    
    public function __construct($name, Array $allowedFields)
    {
        $this->initName($name);

        \assert(!empty($allowedFields));
        $this->allowedFields = $allowedFields;
        $this->value = [];
    }
    
    /**
     * 
     * @return \EVought\vCardTools\StructuredProperty
     */
    public function build()
    {
        \assert(null !== $this->value);
        \assert(null !== $this->name);
        \assert(is_array($this->value));
        return new StructuredPropertyImpl($this);
    }

    public function fields()
    {
        return $this->allowedFields;
    }

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
     * and throws an exception otherwise.
     * @param type $field The field name to check.
     * @return boolean
     * @throws \DomainException If the field is not allowed.
     */
    protected function checkField($field)
    {
        \assert(null !== $field);
        \assert(is_string($field));
        if (!(in_array($field, $this->allowedFields)))
            throw new \DomainException( $field . ' is not an allowed field for '
                                        . $this->name );
        return true;
    }

    public function setField($field, $value)
    {
        \assert(is_array($this->value));
        $this->checkField($field);
        if (null === $value)
            unset ($this->value[$field]);
        else
            $this->value[$field] = $value;
    }
    
    public function getValue() {return $this->value;}
    
    public function setValue($value)
    {
        $badKeys = \array_diff_key($value, \array_flip($this->allowedFields));
        if (!empty($badKeys))
            throw new \DomainException(\implode(' ', $badKeys)
                                                . ': not an allowed fields for '
                                                . $this->name );
        $this->value = $value;
        return $this;
    }

    public static function fromVCardLine($line, VCard $vcard)
    {
        assert(false, 'Not Implemented.');
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
