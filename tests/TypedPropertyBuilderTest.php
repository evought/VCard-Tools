<?php
/**
 * Tests for TypedPropertyBuilder.
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

class TypedPropertyBuilderTest extends \PHPUnit_Framework_TestCase
{
    public function testConstruct()
    {
        $builder = new TypedPropertyBuilderImpl('tel', ['work', 'home', 'cell']);
        $this->assertInstanceOf( 'EVought\vCardTools\TypedPropertyBuilder',
                                    $builder );
    }
    
    /**
     * @depends testConstruct
     */
    public function testSetAndBuild()
    {
        $builder = new TypedPropertyBuilderImpl('tel', ['work', 'home', 'cell']);
        $builder->setValue('999-555-1212');
        $builder->setTypes(['work']);
        $builder->addType('cell');
        
        /* @var $property TypedProperty */
        $property = $builder->build();
        
        $this->assertInstanceOf('EVought\vCardTools\TypedProperty', $property);
        $this->assertEquals('tel', $property->getName());
        $this->assertEquals('999-555-1212', $property->getValue());
        $this->assertCount(2, $property->getTypes());
        $this->assertContains('work', $property->getTypes());
        $this->assertContains('cell', $property->getTypes());
    }
    
    /**
     * @depends testSetAndBuild
     */
    public function testDuplicateTypes()
    {
        $builder = new TypedPropertyBuilderImpl('tel', ['work', 'home', 'cell']);
        $builder->setValue('999-555-1212');
        $builder->addType('cell');
        $builder->addType('cell');
        
        /* @var $property TypedProperty */
        $property = $builder->build();
        
        $this->assertCount(1, $property->getTypes());
        $this->assertContains('cell', $property->getTypes());
    }
    
    /**
     * @depends testSetAndBuild
     * @expectedException \DomainException
     */
    public function testAddInvalidType()
    {
        $builder = new TypedPropertyBuilderImpl('tel', ['work', 'home', 'cell']);
        $builder->setValue('999-555-1212');
        $builder->addType('skadgamagoozie');
    }
    
    /**
     * @depends testSetAndBuild
     * @expectedException \DomainException
     */
    public function testSetInvalidType()
    {
        $builder = new TypedPropertyBuilderImpl('tel', ['work', 'home', 'cell']);
        $builder->setValue('999-555-1212');
        $builder->setTypes(['skadgamagoozie']);
    }
}