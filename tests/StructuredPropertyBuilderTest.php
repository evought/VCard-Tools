<?php
/**
 * Tests for StructuredPropertyBuilder/StructuredPropertyBuilderImpl.
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

/**
 * Tests for StructuredPropertyBuilder/StructuredPropertyBuilderImpl, and
 * StructuredProperty/StructuredPropertyImpl.
 *
 * @author evought
 */
class StructuredPropertyBuilderTest extends \PHPUnit_Framework_TestCase
{
    public function testConstruct()
    {
        $specification = new PropertySpecification(
                'adr',
                PropertySpecification::MULTIPLE_VALUE,
                __NAMESPACE__ . '\StructuredPropertyBuilderImpl',
                ['allowedFields'=>['StreetAddress', 'Locality', 'Region']]
            );
        $builder = $specification->getBuilder();
        $this->assertInstanceOf( 'EVought\vCardTools\StructuredPropertyBuilder',
                                    $builder );
    }
    
    /**
     * @depends testConstruct
     */
    public function testSetAndBuild()
    {
        $fields = [
                    'StreetAddress'=>'K St. NW',
                    'Locality'=>'Washington',
                    'Region' => 'DC'
                  ];
        $specification = new PropertySpecification(
                'adr',
                PropertySpecification::MULTIPLE_VALUE,
                __NAMESPACE__ . '\StructuredPropertyBuilderImpl',
                ['allowedFields'=>['StreetAddress', 'Locality', 'Region']]
            );
        $builder1 = $specification->getBuilder();
        $builder1->setField('StreetAddress', $fields['StreetAddress']);
        $property = $builder1->build();
        
        $this->assertInstanceOf('EVought\vCardTools\StructuredProperty', $property);
        
        $this->assertEquals('adr', $property->getName());
        $this->assertEquals( $fields['StreetAddress'],
                                $property->getField('StreetAddress') );
        $this->assertNotEmpty($property->getValue());
        $this->assertCount(1, $property->getValue());
        $this->assertEquals( $fields['StreetAddress'],
                                $property->getValue()['StreetAddress'] );
        
        
        $builder2 = $specification->getBuilder();
        $builder2->setValue($fields);
                $property = $builder2->build();
        
        $this->assertInstanceOf('EVought\vCardTools\StructuredProperty', $property);
        
        $this->assertEquals('adr', $property->getName());
        $this->assertEquals($fields, $property->getValue());

        $this->assertEquals( $fields['StreetAddress'],
                                $property->getField('StreetAddress') );
        $this->assertEquals( $fields['Locality'],
                                $property->getField('Locality') );
        $this->assertEquals( $fields['Region'],
                                $property->getField('Region') );
    }
    
    /**
     * @depends testConstruct
     * @expectedException \DomainException
     */
    public function testSetInvalidField()
    {
        $specification = new PropertySpecification(
                'foo',
                PropertySpecification::MULTIPLE_VALUE,
                __NAMESPACE__ . '\StructuredPropertyBuilderImpl',
                ['allowedFields'=>['Bar', 'Baz']]
            );
        $builder = $specification->getBuilder();
        $builder->setField('Bozo', 'Whatever');
    }
    
    /**
     * @depends testConstruct
     * @expectedException \DomainException
     */
    public function testSetInvalidFieldViaValue()
    {
        $specification = new PropertySpecification(
                'foo',
                PropertySpecification::MULTIPLE_VALUE,
                __NAMESPACE__ . '\StructuredPropertyBuilderImpl',
                ['allowedFields'=>['Bar', 'Baz']]
            );
        $builder = $specification->getBuilder();
        $builder->setValue(['Bozo'=>'Whatever']);
    }
    
    /**
     * @depends testConstruct
     */
    public function testToString()
    {
        $specification = new PropertySpecification(
                'struct',
                PropertySpecification::MULTIPLE_VALUE,
                __NAMESPACE__ . '\StructuredPropertyBuilderImpl',
                ['allowedFields'=>['field1', 'field2']]
            );
        $builder = $specification->getBuilder();
        $builder->setValue(['field1'=>'value1', 'field2'=>'value2']);
        $property = $builder->build();
        
        $this->assertEquals('STRUCT:value1;value2'."\n", (string) $property);
    }
}
