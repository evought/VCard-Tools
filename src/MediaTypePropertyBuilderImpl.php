<?php
/**
 * MediaTypePropertyBuilderImpl.php
 *
 * @link https://github.com/evought/VCard-Tools
 * @author Eric Vought
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
 * Description of MediaPropertyBuilderImpl
 *
 * @author evought
 */
class MediaTypePropertyBuilderImpl implements MediaTypePropertyBuilder
{
    use SimplePropertyBuilderTrait, MediaTypePropertyBuilderTrait;
    
        /**
     * Initialize a new builder for the property specification.
     * @param PropertySpecification $specification The definition of the
     * target property and its constraints.
     */
    public function __construct(PropertySpecification $specification)
    {
        $this->initBuilder($specification);
        $this->initValue();
        $this->mediaType = null;
    }

    /**
     * Initialize this builder from the parsed components of a VCard format
     * line, copying the value.
     * @param VCardLine $line The pre-parsed line.
     * @return self $this
     */
    public function setFromVCardLine(VCardLine $line)
    {
        $this->setBuilderFromLine($line);
        $this->setMediaTypeFromLine($line);
        $this->setValueFromLine($line);
        return $this;
    }
    
    /**
     * Construct, initialize, and return a SimpleProperty from this builder.
     * @return MediaTypePropertyImpl
     */
    public function build()
    {
        \assert(null !== $this->value);
        return new MediaTypePropertyImpl($this);
    }
}
