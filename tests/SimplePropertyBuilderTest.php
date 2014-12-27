<?php
/**
 * Tests for SimplePropertyBuilder.
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
    /**
     * @group default
     * @return PropertySpecification
     */
    public function testConstruct()
    {
        $specification = new PropertySpecification(
                'url',
                PropertySpecification::MULTIPLE_VALUE,
                __NAMESPACE__ . '\SimplePropertyBuilder'
            );
        $builder = $specification->getBuilder();
        $this->assertInstanceOf( 'EVought\vCardTools\SimplePropertyBuilder',
                                    $builder );
        
        return $specification;
    }
    
    /**
     * @group default
     * @depends testConstruct
     */
    public function testSetAndBuild(PropertySpecification $specification)
    {
        $builder = $specification->getBuilder();
        $builder->setValue('http://liquor.cabi.net');
        $property = $builder->build();
        
        $this->assertInstanceOf(__NAMESPACE__ . '\SimpleProperty', $property);
        $this->assertEquals('url', $property->getName());
        $this->assertEquals('http://liquor.cabi.net', $property->getValue());
        $this->assertEmpty($property->getGroup());
        
        return $specification;
    }
    
    /**
     * @group default
     * @depends testSetAndBuild
     */
    public function testSetGroup(PropertySpecification $specification)
    {
        $builder = $specification->getBuilder();
        $builder->setValue('foo')->setGroup('item1');
        $property = $builder->build();
        $this->assertEquals('item1', $property->getGroup());
        
        return $specification;
    }
    
    /**
     * @group default
     * @depends testSetAndBuild
     */
    public function testOutput()
    {
        $specification = new PropertySpecification(
                'fn',
                PropertySpecification::SINGLE_VALUE,
                __NAMESPACE__ . '\SimplePropertyBuilder'
            );
        $builder = $specification->getBuilder();
        $builder->setValue('Mr. Toad');
        $property = $builder->build();
        
        $this->assertEquals('FN:Mr. Toad'."\n", $property->output());
        
        return $specification;
    }
    
    /**
     * @group default
     * @depends testOutput
     */
    public function testOutputWithGroup(PropertySpecification $specification)
    {
        $builder = $specification->getBuilder();
        $builder->setValue('Mr. Toad')->setGroup('agroup');
        $property = $builder->build();
        
        $this->assertEquals('AGROUP.FN:Mr. Toad'."\n", $property->output());
    }
    
    /**
     * @group default
     * @depends testSetAndBuild
     */
    public function testToString(PropertySpecification $specification)
    {
        $specification = new PropertySpecification(
                'fn',
                PropertySpecification::SINGLE_VALUE,
                __NAMESPACE__ . '\SimplePropertyBuilder'
            );
        $builder = $specification->getBuilder();
        $builder->setValue('Mr. Toad');
        $property = $builder->build();
        
        $this->assertEquals('Mr. Toad', (string) $property);
        
        return $specification;
    }
    
    /**
     * @group default
     * @param \EVought\vCardTools\PropertySpecification $specification
     * @depends testSetAndBuild
     */
    public function testSetFromVCardLine(PropertySpecification $specification)
    {
        $vcardLine = new VCardLine('4.0');
        $vcardLine->setGroup('g')->setName('url')->setValue('http://abc.es');
        $builder = $specification->getBuilder();
        $builder->setFromVCardLine($vcardLine);
        
        $this->assertEquals('g', $builder->getGroup());
        $this->assertEquals('http://abc.es', $builder->getValue());
    }
    
    /**
     * @group default
     * @param \EVought\vCardTools\PropertySpecification $specification
     * @depends testSetAndBuild
     */    
    public function testSetFromVCardLineNoGroup(
                PropertySpecification $specification )
    {
        $vcardLine = new VCardLine('4.0');
        $vcardLine->setName('url')->setValue('http://abc.es');
        $builder = $specification->getBuilder();
        $builder->setFromVCardLine($vcardLine);
        
        $this->assertEmpty($builder->getGroup());
        $this->assertEquals('http://abc.es', $builder->getValue());
    }
    
    /**
     * @group default
     * @depends testSetAndBuild
     * @param \EVought\vCardTools\PropertySpecification $specification
     */
    public function testPush(PropertySpecification $specification)
    {
        $container = new PropertyContainerImpl();
        
        $builder = $specification->getBuilder();
        $builder->setValue('http://liquor.cabi.net');
        $property = $builder->push($container);
        
        $this->assertEquals( 'http://liquor.cabi.net',
                                $container->current()->getValue() );
    }
}
