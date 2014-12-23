<?php

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
 * Description of TypedStructuredPropertyBuilderTest
 *
 * @author evought
 */
class TypedStructuredPropertyBuilderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @group default
     * @return \EVought\vCardTools\PropertySpecification
     */
    public function testConstruct()
    {
        $specification = new PropertySpecification(
                'adr',
                PropertySpecification::MULTIPLE_VALUE,
                __NAMESPACE__ . '\TypedStructuredPropertyBuilder',
                [
                    'allowedTypes'=>['work', 'home'],
                    'allowedFields'=>['Locality', 'Region']
                ]
            );
        $builder = $specification->getBuilder();
        $this->assertInstanceOf( 'EVought\vCardTools\TypedPropertyBuilder',
            $builder );
        $this->assertInstanceOf( 'EVought\vCardTools\StructuredPropertyBuilder',
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
        $builder->setValue(['Locality'=>'Manhattan', 'Region'=>'Kansas'])
                ->setTypes(['work']);
        
        $property = $builder->build();
        
        $this->assertInstanceOf('EVought\vCardTools\TypedProperty', $property);
        $this->assertInstanceOf( 'EVought\vCardTools\StructuredProperty',
                                    $property );
        
        $this->assertEquals('adr', $property->getName());
        $this->assertEquals( ['Locality'=>'Manhattan', 'Region'=>'Kansas'],
                                $property->getValue() );
        $this->assertEquals(['work'], $property->getTypes());
        
        return $specification;
    }
    
    /**
     * @group default
     * @depends testSetAndBuild
     */
    public function testToString(PropertySpecification $specification)
    {
        $builder = $specification->getBuilder();
        $builder->setValue(['Locality'=>'value1', 'Region'=>'value2'])
                ->addType('home');
        $property = $builder->build();
        
        $this->assertEquals( 'ADR;TYPE=HOME:value1;value2'."\n",
                                (string) $property );
    }
    
    /**
     * @group default
     * @param \EVought\vCardTools\PropertySpecification $specification
     * @depends testSetAndBuild
     */
    public function testSetFromVCardLine(PropertySpecification $specification)
    {
        $vcardLine = new VCardLine('4.0');
        $vcardLine  ->setGroup('glurg')
                    ->setName('adr')
                    ->setValue('value1;value2')
                    ->setParameter('type', ['work']);
        $builder = $specification->getBuilder();
        $builder->setFromVCardLine($vcardLine);
        
        $this->assertEquals('glurg', $builder->getGroup());
        $this->assertEquals(['work'], $builder->getTypes());
        $this->assertEquals( ['Locality'=>'value1', 'Region'=>'value2'],
                               $builder->getValue() );
    }
}
