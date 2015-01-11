<?php

/**
 * PropertySpecificationTest.php
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
 * Tests for PropertySpecification
 *
 * @author evought
 */
class PropertySpecificationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PropertySpecification
     */
    protected $specification;
    
    /**
     * @var PropertySpecification
     */
    protected $singleSpec;

    /**
     * @var PropertySpecification
     */
    protected $commaSpec;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->specification = new PropertySpecification(
                'property',
                PropertySpecification::MULTIPLE_PROPERTY,
                __NAMESPACE__ . '\SimplePropertyBuilder',
                PropertySpecification::$cardinalities['Zero To N'],
                ['constraintName' => 'constraintValue']
            );
        
        $this->singleSpec = new PropertySpecification(
                'single',
                PropertySpecification::SINGLE_PROPERTY,
                __NAMESPACE__ . '\SimplePropertyBuilder',
                PropertySpecification::$cardinalities['Exactly One']
            );
        
        $this->commaSpec = new PropertySpecification(
                'comma',
                PropertySpecification::COMMA_PROPERTY,
                __NAMESPACE__ . '\SimplePropertyBuilder',
                PropertySpecification::$cardinalities['Zero To N']
            );
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
        
    }

    /**
     * @group default
     */
    public function testGetName()
    {
        $this->assertEquals('property', $this->specification->getName());
    }
    
    /**
     * @group default
     */
    public function testRequiresSingleProperty()
    {
        $this->assertFalse($this->specification->requiresSingleProperty());
        $this->assertTrue($this->singleSpec->requiresSingleProperty());
        $this->assertFalse($this->commaSpec->requiresSingleProperty());
    }
    
    /**
     * @group default
     */
    public function testAllowsMultipleProperties()
    {
        $this->assertTrue($this->specification->allowsMultipleProperties());
        $this->assertFalse($this->singleSpec->allowsMultipleProperties());
        $this->assertTrue($this->commaSpec->allowsMultipleProperties());
    }
    
    /**
     * @group default
     */
    public function testAllowsCommaProperties()
    {
        $this->assertFalse($this->specification->allowsCommaProperties());
        $this->assertFalse($this->singleSpec->allowsCommaProperties());
        $this->assertTrue($this->commaSpec->allowsCommaProperties());
    }
    
    /**
     * @group default
     */
    public function testGetCardinality()
    {
        $this->assertEquals( PropertySpecification::$cardinalities['Zero To N'],
                                $this->specification->getCardinality() );
        $this->assertEquals(
            PropertySpecification::$cardinalities['Exactly One'],
            $this->singleSpec->getCardinality() );
    }
    
    /**
     * @group default
     */
    public function testIsCardinalityToN()
    {
        $this->assertTrue($this->specification->isCardinalityToN());
        $this->assertFalse($this->singleSpec->isCardinalityToN());
    }
    
    /**
     * @group default
     */
    public function testGetConstraints()
    {
        $this->assertEquals( ['constraintName' => 'constraintValue'],
                             $this->specification->getConstraints() );
        $this->assertEmpty($this->singleSpec->getConstraints());
    }
    
    /**
     * @group default
     */
    public function testGetBuilder()
    {
        $builder = $this->specification->getBuilder();
        $this->assertInstanceOf( __NAMESPACE__ . '\SimplePropertyBuilder',
                                    $builder );
    }
}
