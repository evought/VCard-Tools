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
        $this->parameters[$parameter] = $value;
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
        $this->parameters[$parameter][] = $value;
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
    
    /**
     * Prepare a parameter value for storage:
     * * Strip extra whitespace.
     * * Strip double quotes, if present.
     * * Unescape escaped characters.
     * @param string $rawValue The raw value to strip.
     * @return string The prepared value.
     */
    protected function stripParamValue($rawValue)
    {
        $value = \trim($rawValue);
        if (\strpos($value, '"', 0))
        {
            $value = \substr($value, 1, -1);
        }
        return VCard::unescape($value);
    }
    
    /**
     * Helper for parsing raw vCard text. Parse the parameter names/values from
     * an array of raw parameters and store them in this strucure.
     * At this stage, we are not processing the parameter values or doing much
     * checking on whether allowed or disallowed.
     * Rather, we are gathering the parameter values for the specific properties
     * to interpret.
     * Parameter names are canonicalized to lowercase.
     * Parameter values are stripped of quotes and NSWSP if necessary, and
     * unescaped.
     * We do do some checking on parameters whose definition has changed between
     * versions to canonicalize them. For example, bare TYPEs are aggregated
     * for 2.1 cards and the PREF TYPE is turned into a PREF parameter.
     * If we otherwise have malformed parameters (no value), then we throw
     * an exception.
     * @param array $rawParams The array of parameter strings (delimiter
     * already removed) from the VCard.
     * @return VCardLine $this
     * @throws \DomainException For certain malformed parameter conditions.
     */
    public function parseParameters(Array $rawParams)
    {
        if (empty($rawParams))
	    return $this;

	// Parameters are split into (key, value) pairs
	
	foreach ($rawParams as $paramStr)
	{
            if (empty($paramStr))
                throw new \DomainException(
                    'Empty or malformed parameter in property: '
                    . $this->name
                    . '; check colons, semi-colons, and unmatched quotes.');
            
            // We should not need to worry about escaping/quoting with respect
            // to the first equals, directly following the parameter name, as
            // parameter names are only alpha-numeric and hyphen.
            // There may be other, quoted equals-signs in the value, but we
            // don't care about them at this point.
	    $param = \explode('=', $paramStr, 2);
            $paramName = \trim(\strtolower($param[0]));
            if (\count($param) == 1)
                $this->novalue[] = $paramName;
            else
                $this->pushParameter( $paramName,
                                      $this->stripParamValue($param[1]) );         
	}
        
        if (!empty($this->novalue))
        {
            if ($this->version === '2.1')
            {
                if ($this->hasParameter('type'))
                    $this->parameters['type'] =
                        \array_merge( $this->parameters['type'],
                            $this->novalues );
                else
                    $this->parameters['type'] = $this->novalue;
                unset($this->novalue);
            } else {
                throw new \DomainException(
                    'One or more parameters do not have values and version '
                    . ' is not 2.1: '
                    . \implode(',', $this->novalue ) );
                unset($this->novalue);
            }
        }
        
        $this->lowercaseParameters(['type', 'encoding', 'value']);
        
        if ( $this->hasParameter('type')
             && in_array('pref', $this->getParameter('type')) )
        {
            // PREF was allowed as a type in 3.0
            // NOTE: if PREF was specified bare in 2.1, it will have already
            // been moved into TYPE
            if ( $this->getVersion() === '3.0'
                 || $this->getVersion() === '2.1' )
            {
                if (!($this->hasParameter('pref')))
                    $this->setParameter('pref', '1');
                $this->clearParamValues('type', ['pref']);
            } else {
                throw new \DomainException(
                    'PREF is given as TYPE for ' . $this->getName()
                    . ' and VERSION is not 2.1 or 3.0' );
            }
        }
        return $this;
    }
}
