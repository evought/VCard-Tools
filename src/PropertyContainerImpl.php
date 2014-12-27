<?php
/**
 * A minimal PropertyContainer implementation.
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
 * A minimal implementation of a PropertyContainer.
 *
 * @author evought
 */
class PropertyContainerImpl implements PropertyContainer
{
    /**
     * The internal Property storage.
     * @var Properties[]
     */
    private $properties = [];
    
    public function count()
    {
        return count($this->properties);
    }
    
    /**
     * Get the Property at the current iterator position.
     * @return Property
     * @see Iterator::current()
     */
    public function current()
    {
        return current($this->properties);
    }

    public function key()
    {
        return null;
    }

    public function next()
    {
        return next($this->properties);
    }

    /**
     * 
     * @param Property|PropertyContainer $items,...
     * @return self $this
     */
    public function push($items)
    {
        $items = func_get_args();
        foreach ($items as $item)
        {
            if ($item instanceof PropertyContainer)
            {
                foreach ($item as $property)
                {
                    $this->properties[] = $property;
                }
            } else {
                \assert($item instanceof Property);
                $this->properties[] = $item;
            }
        }
        return $this;
    }

    public function rewind()
    {
        reset($this->properties);
        return $this;
    }

    public function valid()
    {
        return $this->current() !== false;
    }

    public function clear()
    {
        $this->properties = [];
    }

}
