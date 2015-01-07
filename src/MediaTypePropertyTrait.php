<?php
/**
 * MediaTypePropertyTrait.php
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
 * A trait to collect shared code for handling the MEDIATYPE parameter.
 *
 * @see MediaTypeProperty
 * @author evought
 */
trait MediaTypePropertyTrait
{
    private $mediaType;
    
     /**
     * Initialize the value of the MEDIATYPE parameter from the Builder.
     * @return type
     */
    protected function setMediaTypeFromBuilder(MediaTypePropertyBuilder $builder)
    {
        $this->mediaType = $builder->getMediaType();
        if (!empty($this->mediaType)) $this->hasParameters = true;
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

    /**
     * Output the MEDIATYPE parameter.
     * @return string
     */
    protected function outputMediaType()
    {
        assert(!empty($this->mediaType));
        return 'MEDIATYPE=' . $this->getMediaType();
    }
}
