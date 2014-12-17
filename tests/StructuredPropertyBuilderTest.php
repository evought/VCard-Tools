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
        $builder = new StructuredPropertyBuilderImpl( 'adr',
                            [ 'StreetAddress', 'Locality', 'Region',
                                'PostalCode', 'Country' ] );
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
        $builder = new StructuredPropertyBuilderImpl( 'adr',
                            [ 'StreetAddress', 'Locality', 'Region',
                                'PostalCode', 'Country' ] );
        $builder ->setField('StreetAddress', $fields['StreetAddress']);
        $property = $builder->build();
        
        $this->assertInstanceOf('EVought\vCardTools\StructuredProperty', $property);
        
        $this->assertEquals('adr', $property->getName());
        $this->assertEquals( $fields['StreetAddress'],
                                $property->getField('StreetAddress') );
        $this->assertNotEmpty($property->getValue());
        $this->assertCount(1, $property->getValue());
        $this->assertEquals( $fields['StreetAddress'],
                                $property->getValue()['StreetAddress'] );
        
        
        $builder = new StructuredPropertyBuilderImpl( 'adr',
                            [ 'StreetAddress', 'Locality', 'Region',
                                'PostalCode', 'Country' ] );
        $builder->setValue($fields);
                $property = $builder->build();
        
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
        $builder = new StructuredPropertyBuilderImpl('foo', ['Bar', 'Baz']);
        $builder->setField('Bozo', 'Whatever');
    }
    
    /**
     * @depends testConstruct
     * @expectedException \DomainException
     */
    public function testSetInvalidFieldViaValue()
    {
        $builder = new StructuredPropertyBuilderImpl('foo', ['Bar', 'Baz']);
        $builder->setValue(['Bozo'=>'Whatever']);
    }
}
