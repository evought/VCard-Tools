<?php
/**
 * MediaTypeProperty.php
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
 * An interface for handling properties which accept MEDIATYPE parameters in
 * VCard 4.0. Any Property which may have a URI/URL as a value may have an
 * optional MediaType. In particular, if the media-type is determined during
 * the URL resolution process, there is no need for a MEDIATYPE parameter.
 * VCard 3.0 buried media-types in the TYPE parameter.
 * Such embedded media-types *should* be discovered and made accessible through
 * this interface.
 * @author evought
 */
interface MediaTypeProperty
{
    /**
     * Returns the media-type associated with the URL if-and-only-if such has
     * been explicitly provided. Does _not_ attempt to resolve the media-type
     * from the URL.
     * @return string
     */
    public function getMediaType();
}
