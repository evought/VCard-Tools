<?php
/**
 * WeakRefStub.php
 * @author Eric Vought evought@pobox.com 2015-01-21
 * @copyright Eric Vought 2014, Some rights reserved.
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
 * A stub which can be used if \WeakRef is not available. WeakRefStub always
 * throws away its referent and therefore never impedes garbage collection.
 * Better to use the WeakRef PECL extension if you have it.
 * Falling back to WeakRefStub is the equivalent of a cache-less strategy,
 * is therefore logic-neutral and won't actually *hurt* performance.
 *
 * @author evought
 */
class WeakRefStub
{
    public function __construct($ob = null) {}
    public function get() {return null;}
    public function set($ob) {}
}
