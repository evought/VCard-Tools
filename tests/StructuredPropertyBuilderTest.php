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
    /**
     * @group default
     */
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
        return $specification;
    }
    
    /**
     * @group default
     * @depends testConstruct
     */
    public function testSetAndBuild(PropertySpecification $specification)
    {
        $fields = [
                    'StreetAddress'=>'K St. NW',
                    'Locality'=>'Washington',
                    'Region' => 'DC'
                  ];
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
        
        return $specification;
    }
    
    /**
     * @group default
     * @depends testSetAndBuild
     */
    public function testCompareValue(PropertySpecification $specification)
    {
        $builder = $specification->getBuilder();
        $property1 = $builder->setField('Locality', 'Level 1')->build();
        $property2 = $builder->setField('Locality', 'Level 9')->build();
        $property3 = $builder->setField('Locality', 'Level 9')->build();
        $property4 = $builder->setField('Locality', 'Cleveland')->build();
        
        $this->assertEquals(-1, $property1->compareValue($property1, $property2));
        $this->assertEquals(0, $property1->compareValue($property2, $property3));
        $this->assertEquals(1, $property1->compareValue($property3, $property4));
        
        return $specification;
    }
    
    /**
     * @group default
     * @depends testConstruct
     * @expectedException \DomainException
     */
    public function testSetInvalidField(PropertySpecification $specification)
    {
        $builder = $specification->getBuilder();
        $builder->setField('Bozo', 'Whatever');

        return $specification;
    }
    
    /**
     * @group default
     * @depends testConstruct
     * @expectedException \DomainException
     */
    public function testSetInvalidFieldViaValue(
                                        PropertySpecification $specification )
    {
        $builder = $specification->getBuilder();
        $builder->setValue(['Bozo'=>'Whatever']);
        
        return $specification;
    }
    
    /**
     * @group default
     * @depends testConstruct
     */
    public function testOutput(PropertySpecification $specification)
    {
        /* @var $builder StructuredPropertyBuilder */
        $builder = $specification->getBuilder();
        $builder->setField('StreetAddress', 'value1')
                ->setField('Locality', 'value2')
                ->setField('Region', 'value3');
        $property = $builder->build();
        
        $this->assertEquals( 'ADR:value1;value2;value3'."\n",
                                $property->output() );
        return $specification;
    }
    
    /**
     * @group default
     * @depends testConstruct
     */
    public function testOutputNoFields(PropertySpecification $specification)
    {
        /* @var $builder StructuredPropertyBuilder */
        $builder = $specification->getBuilder();
        $property = $builder->build();
        
        $this->assertEquals('ADR:;;'."\n", $property->output());
        return $specification;
    }
    
    /**
     * @group default
     * @depends testConstruct
     */
    public function testToString(PropertySpecification $specification)
    {
        /* @var $builder StructuredPropertyBuilder */
        $builder = $specification->getBuilder();
        $builder->setField('StreetAddress', 'value1')
                ->setField('Locality', 'value2')
                ->setField('Region', 'value3');
        $property = $builder->build();
        
        $this->assertEquals( 'value1 value2 value3', (string) $property );
        return $specification;
    }
    
    public function valueStringProducer()
    {
        // [valueString, fields]
        return [
                'Basic' =>
                    [
                        '2000 S Eads St;Arlington;VA',
                            [
                                'StreetAddress' =>'2000 S Eads St',
                                'Locality'      =>'Arlington',
                                'Region'        =>'VA'
                            ]
                    ],
                'Escaped' =>
                    [
                        'Escaped \; Semicolon St.;Geekville;NC',
                            [
                                'StreetAddress' =>'Escaped ; Semicolon St.',
                                'Locality'      =>'Geekville',
                                'Region'        =>'NC'
                            ]
                    ],
                'Escaped Backslash' =>
                    [
                        'Punctuation Trail;\\\\;VT',
                            [
                                'StreetAddress' =>'Punctuation Trail',
                                'Locality'      =>'\\',
                                'Region'        =>'VT'
                            ]
                    ]
        ];
    }

    /**
     * @group default
     * @param string $valueString
     * @param array $fields An array of fieldname => values.
     * @param \EVought\vCardTools\PropertySpecification $specification
     * @depends testSetAndBuild
     * @dataProvider valueStringProducer
     */
    public function testSetFromVCardLine(
        $valueString, Array $fields, PropertySpecification $specification )
    {
        $vcardLine = new VCardLine('4.0');
        $vcardLine  ->setName('adr')
                    ->setValue($valueString);
        
        /* @var StructuredPropertyBuilder $builder */
        $builder = $specification->getBuilder();
        $builder->setFromVCardLine($vcardLine);
        
        $this->assertEquals($fields, $builder->getValue());
    }

    
    /**
     * @group default
     * @param \EVought\vCardTools\PropertySpecification $specification
     * @depends testSetAndBuild
     * @expectedException \DomainException
     */
    public function testSetFromVCardLineNoFields(
                                        PropertySpecification $specification )
    {
        $vcardLine = new VCardLine('4.0');
        $vcardLine  ->setName('adr')->setValue('');
        
        /* @var StructuredPropertyBuilder $builder */
        $builder = $specification->getBuilder();
        $builder->setFromVCardLine($vcardLine);
    }
    
    /**
     * @group default
     * @param \EVought\vCardTools\PropertySpecification $specification
     * @depends testSetAndBuild
     * @expectedException \DomainException
     */
    public function testSetFromVCardLineTooFewFields(
                                        PropertySpecification $specification )
    {
        $vcardLine = new VCardLine('4.0');
        $vcardLine  ->setName('adr')->setValue(';');
        
        /* @var StructuredPropertyBuilder $builder */
        $builder = $specification->getBuilder();
        $builder->setFromVCardLine($vcardLine);
    }
    
    /**
     * @group default
     * @param \EVought\vCardTools\PropertySpecification $specification
     * @depends testSetAndBuild
     * @expectedException \DomainException
     */
    public function testSetFromVCardLineTooManyFields(
                                        PropertySpecification $specification )
    {
        $vcardLine = new VCardLine('4.0');
        $vcardLine  ->setName('adr')->setValue(';;;;;');
        
        /* @var StructuredPropertyBuilder $builder */
        $builder = $specification->getBuilder();
        $builder->setFromVCardLine($vcardLine);
    }
}
