<?php
/**
 * Interface for a container of Properties.
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
 * Interface for a basic container to which Properties may be stored to,
 * retrieved from, and iterated over.
 * @author evought
 */
interface PropertyContainer extends \Iterator, \Countable
{
    /**
     * Add one or more properties to this container.
     * If this container defines a property as requiring at most one value,
     * then the new value will overwrite any previous value, otherwise, it will
     * be added to the existing values.
     * For any argument which is a PropertyContainer, unpack and push its
     * Properties.
     * @param Property|PropertyContainer $items,...
     * @return self $this
     */
    public function push($items);
    
    /**
     * Get the Property at the current iterator position.
     * @return Property
     * @see Iterator::current()
     */
    public function current();
    
    /**
     * Empty this container.
     * @return self $this
     */
    public function clear();
}
