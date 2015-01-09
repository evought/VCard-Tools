<?php
/**
 * MediaTypePropertyBuiilderTrait.php
 *
 * @link https://github.com/evought/VCard-Tools
 * @author Eric Vought
 * @see RFC 2426, RFC 2425, RFC 6350
 * @license MIT http://opensource.org/licenses/MIT
 */

/*
 * The MIT License
 *
 * Copyright 2015 evought.
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
 * A trait to collect shared code for PropertyBuilders which handle the
 * MEDIATYPE parameter.
 *
 * @author evought
 * @see https://tools.ietf.org/html/rfc6350#section-5.7
 */
trait MediaTypePropertyBuilderTrait
{
    private $mediaType;
    
    /**
     * Sets the media type associated with the URL. Note that this parameter
     * is not often necessary. data URLs encode media-type internally and http
     * URLs fetch the media-type at resolution-time. This is only necessary if
     * the URL scheme does not otherwise provide a means to determine (e.g.
     * ftp).
     * @param string $value
     * @return \EVought\vCardTools\MediaTypePropertyBuilder
     */
    public function setMediaType($value)
    {
        if (null === $value)
        {
            $this->mediaType = null;
            return;
        }
        
        $mediaType = \filter_var(
            $value, FILTER_VALIDATE_REGEXP,
            ['options'=>['regexp'=>  MediaTypePropertyBuilder::MEDIATYPE_REGEX]]
        );
        if (false === $mediaType)
            throw new Exceptions\MalformedParameterException(
                $value . ' is not a valid mediatype.' );
        else
            $this->mediaType = $mediaType;
        return $this;
    }
    
    /**
     * Returns the media-type associated with the URL if-and-only-if such has
     * been explicitly provided. Does _not_ attempt to resolve the media-type
     * from the URL.
     * @return string
     */
    public function getMediaType()
    {
        return $this->mediaType;
    }
    
    protected function setMediaTypeFromLine(VCardLine $line)
    {
        if ($line->hasParameter('mediatype'))
        {
            $mediaTypes = $line->getParameter('mediatype');
            if (count($mediaTypes) > 1)
                throw new Exceptions\MalformedParameterException(
                    'Multiple MEDIATYPE parameters supplied for '
                    . $this->getName() );
            else
                $this->setMediaType ($mediaTypes[0]);
        }
    }
}
