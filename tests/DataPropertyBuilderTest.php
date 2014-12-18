<?php
/**
 * Tests for DataPropertyBuilder/DataProperty.
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
 * Tests for DataPropertyBuilder/DataProperty.
 *
 * @author evought
 */
class DataPropertyBuilderTest extends \PHPUnit_Framework_TestCase
{
    public function testConstruct()
    {
        $builder = new DataPropertyBuilder('logo', ['work', 'home']);
        $this->assertInstanceOf( 'EVought\vCardTools\TypedPropertyBuilder',
                                    $builder );
        $this->assertInstanceOf( 'EVought\vCardTools\DataPropertyBuilder',
                                    $builder );
    }
    
     /**
     * @depends testConstruct
     */
    public function testSetAndBuild()
    {
        $builder = new DataPropertyBuilder('logo', ['work', 'home']);
        $builder->setValue('http://example.com/logo.jpg')
                ->setMediaType('image/jpeg')
                ->setTypes(['work']);
        
        $property = $builder->build();
        
        $this->assertInstanceOf('EVought\vCardTools\TypedProperty', $property);
        $this->assertInstanceOf('EVought\vCardTools\DataProperty', $property);
        
        $this->assertEquals('logo', $property->getName());
        $this->assertEquals( 'http://example.com/logo.jpg',
                             $property->getValue() );
        $this->assertEquals('image/jpeg', $property->getMediaType());
        $this->assertEquals(['work'], $property->getTypes());
    }

    /**
     * @depends testSetAndBuild
     * @expectedException \DomainException
     */
    public function testBadURL()
    {
        $builder = new DataPropertyBuilder('logo', ['work', 'home']);
        $builder->setValue('@NOT@URL');
    }
    
    /**
     * @depends testSetAndBuild
     * @expectedException \DomainException
     */
    public function testMediaType()
    {
        $builder = new DataPropertyBuilder('logo', ['work', 'home']);
        $builder->setValue('Edsel');
    }
}
