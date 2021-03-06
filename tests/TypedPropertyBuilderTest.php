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
    /**
     * @group default
     * @return \EVought\vCardTools\PropertySpecification
     */
    public function testConstruct()
    {
        $specification = new PropertySpecification(
                'tel',
                PropertySpecification::MULTIPLE_PROPERTY,
                __NAMESPACE__ . '\TypedPropertyBuilderImpl',
                PropertySpecification::$cardinalities['Zero To N'],
                ['allowedTypes'=>['work', 'home', 'cell', 'voice']]
            );
        $builder = $specification->getBuilder();
        $this->assertInstanceOf( 'EVought\vCardTools\TypedPropertyBuilder',
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
        $builder->setValue('999-555-1212')
                ->setTypes(['work'])
                ->addType('cell');
        
        $property = $builder->build();
        
        $this->assertInstanceOf('EVought\vCardTools\TypedProperty', $property);
        $this->assertEquals('tel', $property->getName());
        $this->assertEquals('999-555-1212', $property->getValue());
        $this->assertCount(2, $property->getTypes());
        $this->assertContains('work', $property->getTypes());
        $this->assertContains('cell', $property->getTypes());
        
        return $specification;
    }
    
    /**
     * @group default
     * @depends testSetAndBuild
     */
    public function testDuplicateTypes(PropertySpecification $specification)
    {
        $builder = $specification->getBuilder();
        $builder->setValue('999-555-1212')
                ->addType('cell')
                ->addType('cell');
        
        /* @var $property TypedProperty */
        $property = $builder->build();
        
        $this->assertCount(1, $property->getTypes());
        $this->assertContains('cell', $property->getTypes());
    }
    
    /**
     * @group default
     * @depends testSetAndBuild
     * @expectedException \DomainException
     */
    public function testAddInvalidType(PropertySpecification $specification)
    {
        $builder = $specification->getBuilder();
        $builder->setValue('999-555-1212')
                ->addType('skadgamagoozie');
    }
    
    /**
     * @group default
     * @depends testSetAndBuild
     * @expectedException \DomainException
     */
    public function testSetInvalidType(PropertySpecification $specification)
    {
        $builder = $specification->getBuilder();
        $builder->setValue('999-555-1212')
                ->setTypes(['skadgamagoozie']);
    }
    
    /**
     * @group default
     * @depends testSetAndBuild
     */
    public function testOutputNoTypes(PropertySpecification $specification)
    {
        $builder = $specification->getBuilder();
        $builder->setValue('1-800-PHP-KING');
        $property = $builder->build();
        
        $this->assertEquals('TEL:1-800-PHP-KING'."\n", $property->output());
    }
    
    /**
     * @group default
     * @depends testSetAndBuild
     */
    public function testOutputOneType(PropertySpecification $specification)
    {
        $builder = $specification->getBuilder();
        $builder->setValue('1-800-PHP-KING')
                ->addType('work');
        $property = $builder->build();
        
        $this->assertEquals( 'TEL;TYPE=WORK:1-800-PHP-KING'."\n",
                $property->output() );
    }
    
    /**
     * @group default
     * @depends testSetAndBuild
     */
    public function testOutputTwoTypes(PropertySpecification $specification)
    {
        $builder = $specification->getBuilder();
        $builder->setValue('1-800-PHP-KING')
                ->addType('work')->addType('voice');
        $property = $builder->build();
        
        $this->assertEquals( 'TEL;TYPE=VOICE,WORK:1-800-PHP-KING'."\n",
                             $property->output() );
    }

    /**
     * @group default
     * @depends testSetAndBuild
     */
    public function testToString(PropertySpecification $specification)
    {
        $builder = $specification->getBuilder();
        $builder->setValue('1-800-PHP-KING');
        $property = $builder->build();
        
        $this->assertEquals('1-800-PHP-KING', (string) $property);
    }
    
    /**
     * @group default
     * @param \EVought\vCardTools\PropertySpecification $specification
     * @depends testSetAndBuild
     */
    public function testSetFromVCardLine(PropertySpecification $specification)
    {
        $vcardLine = new VCardLine('4.0');
        $vcardLine  ->setName('tel')->setValue('+1-888-GET-PAID')
                    ->pushParameter('type', 'work');
        
        /* @var PropertyBuilder $builder */
        $builder = $specification->getBuilder();
        $builder->setFromVCardLine($vcardLine);
        
        $this->assertEquals('+1-888-GET-PAID', $builder->getValue());
        $this->assertEquals(['work'], $builder->getTypes());
    }
    
    /**
     * @group default
     * @param \EVought\vCardTools\PropertySpecification $specification
     * @depends testSetAndBuild
     */
    public function testSetFromVCardLineNoTypes(
            PropertySpecification $specification )
    {
        $vcardLine = new VCardLine('4.0');
        $vcardLine  ->setName('tel')->setValue('+1-888-GET-PAID');
        
        /* @var PropertyBuilder $builder */
        $builder = $specification->getBuilder();
        $builder->setFromVCardLine($vcardLine);
        
        $this->assertEquals('+1-888-GET-PAID', $builder->getValue());
        $this->assertEmpty($builder->getTypes());
    }
    
    /**
     * @group default
     * @depends testSetAndBuild
     */
    public function testEmptyTypesPermitsAll()
    {
        $specification = new PropertySpecification(
                'tel',
                PropertySpecification::MULTIPLE_PROPERTY,
                __NAMESPACE__ . '\TypedPropertyBuilderImpl',
                PropertySpecification::$cardinalities['Zero To N'],
                ['allowedTypes'=>[]]
            );
        $builder = $specification->getBuilder();
        $builder->setValue('999-555-1212')
                ->addType('skadgamagoozie');
        $property = $builder->build();
        $this->assertContains('skadgamagoozie', $property->getTypes());
    }
}