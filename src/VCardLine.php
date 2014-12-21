<?php
/**
 * vCard class for parsing a vCard and/or creating one
 *
 * @link https://github.com/evought/VCard-Tools
 * @author Martins Pilsetnieks, Roberts Bruveris, Eric Vought
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
 * A utility class for a parsed line from a raw VCard file.
 * Components of the line are extracted and stuffed into this structure by
 * the parsing loop and then are interpreted by the appropriate PropertyBuilder
 * class according to the semantics of a particular property.
 * By making this its own type rather than simply using a multi-dimensional
 * hash, we can get better error-checking and can bury the inevitable
 * array_key_exists(..) etc., in this class as well as, potentially, other
 * code useful to translating the structures.
 * As a design principle, this class should be used as a dumb container,
 * putting behavior and semantics into the appropriate Property subclass.
 * It is possible that this class may be useful for an intermediate layer
 * between VCard and other input/output formats as well.
 * 
 * In all cases, groups, names, and parameter names will be canonicalized to
 * all lowercase and any whitespace will be ignored.
 *
 * @author evought
 */
class VCardLine
{
    /**
     * The property group, a sequence of alphanumeric or hyphen characters
     * separated from the property name by a dot.
     * @var string.
     */
    private $group;
    
    /**
     * The property name.
     * @var string
     */
    private $name;
    
    /**
     * An array of parameter values indexed by parameter names.
     * Names without values should be stored in $novalue.
     * Values may themselves be complex.
     * @var array
     */
    private $parameters = [];
    
    /**
     * The value text parsed from the VCard line.
     * @var type 
     */
    private $value;
    
    private $novalue = [];
    
    /**
     * The VCard version we are parsing *from*, which may be needed to tweak
     * interpretations at various stages.
     * Will be in the form {major}.{minor}
     * @var string 
     */
    private $version;
    
    public function __construct($version)
    {
        \assert(null !== $version);
        \assert(is_string($version));
        $this->version = $version;
    }
    
    public function getVersion() {return $this->version;}
    
    public function getGroup() {return $this->group;}

    public function setGroup($group)
    {
        $this->group = \trim(\strtolower($group));
        return $this;
    }
    
    public function getName() {return $this->name;}
    
    public function setName($name)
    {
        $this->name = \trim(\strtolower($name));
        return $this;
    }
    
    public function getParameters()
    {
        \assert(\is_array($this->parameters));
        return $this->parameters;
    }
    
    public function getParameter($parameter)
    {
        \assert(\is_array($this->parameters));
        \assert(null !== $parameter);
        \assert(\is_string($parameter));
        return \array_key_exists($parameter, $this->parameters)
               ? $this->parameters[$parameter] : null;
    }
    
    public function setParameter($parameter, $value)
    {
        \assert(\is_array($this->parameters));
        \assert(null !== $parameter);
        \assert(\is_string($parameter));
        $this->parameters[strtolower($parameter)] = \trim($value);
        return $this;
    }
    
    public function unsetParameter($parameter)
    {
        \assert(\is_array($this->parameters));
        \assert(null !== $parameter);
        \assert(is_string($parameter));
        unset($this->parameters[$parameter]);
        return $this;
    }
    
    public function pushParameter($parameter, $value)
    {
        \assert(\is_array($this->parameters));
        \assert(null !== $parameter);
        \assert(\is_string($parameter));
        $lowerParameter = strtolower($parameter);
        if (!array_key_exists($lowerParameter, $this->parameters))
            $this->parameters[$lowerParameter] = [];
        $this->parameters[$lowerParameter][] = \trim($value);
        return $this;
    }
    
    public function clearParamValues($parameter, Array $values)
    {
        \assert(\is_array($this->parameters));
        \assert(null !== $parameter);
        \assert(\is_string($parameter));
        if (\array_key_exists($parameter, $this->parameters))
        {
            $parameters[$parameter]
                = \array_diff($parameters[$parameter], $values);
        }
        return $this;
    }
    
    public function hasParameter($parameter)
    {
        return \array_key_exists($parameter, $this->parameters);
    }
    
    public function lowercaseParameters(Array $paramNames)
    {
        foreach ($paramNames as $parameter)
        {
            if (\array_key_exists($parameter, $this->parameters))
            {
                $this->parameters[$parameter]
                    = \array_map('strtolower', $this->parameters[$parameter]);
            }
        }
    }
    
    public function getValue() {return $this->value;}
    
    public function setValue($value)
    {
        $this->value = \trim($value);
        return $this;
    }
    
    public function getNoValues() {return $this->novalue;}
    public function pushNoValue($name)
    {
        $this->novalue[] = \strtolower($name);
        return $this;
    }
    
    public function clearNoValues()
    {
        $this->novalue = [];
        return $this;
    }
}
