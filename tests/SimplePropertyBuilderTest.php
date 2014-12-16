<?php
/**
 * Tests for SinglePropertyBuilder.
 * @author Eric Vought <evought@pobox.com>
 * @copyright Eric Vought 2014, Some rights reserved.
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

class SimplePropertyBuilderTest extends \PHPUnit_Framework_TestCase
{
    public function testConstruct()
    {
        $builder = new SimplePropertyBuilder('url');
        $this->assertInstanceOf( 'EVought\vCardTools\SimplePropertyBuilder',
                                    $builder );
    }
    
    /**
     * @depends testConstruct
     */
    public function testSetAndBuild()
    {
        $builder = new SimplePropertyBuilder('url');
        $builder->setValue('http://liquor.cabi.net');
        $property = $builder->build();
        
        $this->assertInstanceOf('EVought\vCardTools\SimpleProperty', $property);
        $this->assertEquals('url', $property->getName());
        $this->assertEquals('http://liquor.cabi.net', $property->getValue());
        $this->assertEmpty($property->getParameters());
    }
    
    /**
     * @depends testConstruct
     * @expectedException \DomainException
     */
    public function testsSetTypeFails()
    {
        $builder = new SimplePropertyBuilder('tel');
        $builder->setParameter('type', ['work']);
    }
}