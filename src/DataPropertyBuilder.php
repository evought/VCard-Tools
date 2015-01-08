<?php
/**
 * A PropertyBuilder for a DataProperty.
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
 * A PropertyBuilder for a DataProperty.
 *
 * @author evought
 */
class DataPropertyBuilder
    implements TypedPropertyBuilder, MediaTypePropertyBuilder
{
    use PropertyBuilderTrait, TypedPropertyBuilderTrait,
        MediaTypePropertyBuilderTrait;
    
    private $value;
    
    public function __construct(PropertySpecification $specification)
    {
        $this->initBuilder($specification);
        $this->initTypes();
        $this->value = null;
        $this->mediaType = null;
    }
    
    public function setFromVCardLine(VCardLine $line)
    {
        $this->setBuilderFromLine($line);
        $this->setTypesFromLine($line);
        $this->setMediaTypeFromLine($line);
        if ( (($line->getVersion() === '3.0') || ($line->getVersion() === '2.1'))
             && ($this->getValueType() !== 'uri' ) )
        {
            $uri = new \DataUri( $this->getMediaType(),
                                 $line->getValue(),
                                 \DataUri::ENCODING_BASE64 );
            $this->setValue($uri->toString());
            $this->setMediaType(null);
        } else {
            $this->setValue(\stripcslashes($line->getValue()));
        }
        return $this;
    }
    
    public function build()
    {
        \assert(null !== $this->value);
        return new DataProperty($this);
    }

    public function getValue()
    {
        return $this->value;
    }

    /**
     * Sets the value of the property. Expected to be a URL.
     * @param string $value The URL value to set.
     * @return \EVought\vCardTools\DataPropertyBuilder
     * @throws \DomainException If the URL value is not well-formed.
     * @see https://tools.ietf.org/html/rfc6350#section-5.7
     */
    public function setValue($value)
    {
        \assert(null !== $value);
        
        $url = \filter_var($value, \FILTER_VALIDATE_URL);
        if ((false === $url) && (false == \DataUri::isParsable($value)))
            throw new \DomainException($value . ' is not a valid url.');
        else
            $this->value = $value;
        return $this;
    }
}
