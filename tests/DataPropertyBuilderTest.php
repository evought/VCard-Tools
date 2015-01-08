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
    /**
     * @group default
     * @return \EVought\vCardTools\PropertySpecification
     */
    public function testConstruct()
    {
        $specification = new PropertySpecification(
                'logo',
                PropertySpecification::MULTIPLE_PROPERTY,
                __NAMESPACE__ . '\DataPropertyBuilder',
                PropertySpecification::$cardinalities['Zero To N'],
                ['allowedTypes'=>['work', 'home']]
            );
        $builder = $specification->getBuilder();
        $this->assertInstanceOf( 'EVought\vCardTools\TypedPropertyBuilder',
                                    $builder );
        $this->assertInstanceOf( 'EVought\vCardTools\DataPropertyBuilder',
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
        
        return $specification;
    }

    /**
     * @group default
     * @depends testSetAndBuild
     */
    public function testCompareValue(PropertySpecification $specification)
    {
        $builder = $specification->getBuilder();
        $property1 = $builder->setValue('http://foo')->build();
        $property2 = $builder->setValue('http://bar')->build();
        $property3 = $builder->setValue('http://baz')->build();
        $property4 = $builder->setValue('http://baz')->build();
        
        $this->assertEquals(1, $property1->compareValue($property1, $property2));
        $this->assertEquals(-1, $property1->compareValue($property2, $property3));
        $this->assertEquals(0, $property1->compareValue($property3, $property4));
        
        return $specification;
    }
    
    /**
     * @group default
     * @depends testSetAndBuild
     * @expectedException \DomainException
     */
    public function testBadURL(PropertySpecification $specification)
    {
        $builder = $specification->getBuilder();
        $builder->setValue('@NOT@URL');
    }
    
    /**
     * @group default
     * @depends testSetAndBuild
     */
    public function testOutputJustValue(PropertySpecification $specification)
    {
        $builder = $specification->getBuilder();
        $builder->setValue('http://example.com/logo.jpg');
        $property = $builder->build();
        
        $this->assertEquals( 'LOGO:'
                             . VCard::escape('http://example.com/logo.jpg')
                             . "\n", $property->output() );
    }
    
    /**
     * @group default
     * @depends testSetAndBuild
     */
    public function testOutputOneType(PropertySpecification $specification)
    {
        $builder = $specification->getBuilder();
        $builder->setValue('http://example.com/logo.jpg')
                ->addType('work');
        $property = $builder->build();
        
        $this->assertEquals( 'LOGO;TYPE=WORK:'
                             . VCard::escape('http://example.com/logo.jpg')
                             . "\n", $property->output() );
    }
    
    /**
     * @group default
     * @depends testSetAndBuild
     */
    public function testOutputMediaTypeAndType(PropertySpecification $specification)
    {
        $builder = $specification->getBuilder();
        $builder->setValue('http://example.com/logo.jpg')
                ->setMediaType('image/png')
                ->addType('home'); // Who has a home logo?
        $property = $builder->build();
        
        // NOTE: Sensitive to output order.
        $this->assertEquals( 'LOGO;TYPE=HOME;MEDIATYPE=image/png:'
                             . VCard::escape('http://example.com/logo.jpg')
                             . "\n", $property->output() );
    }
    
    /**
     * @group default
     * @depends testSetAndBuild
     */
    public function testToString(PropertySpecification $specification)
    {
        $builder = $specification->getBuilder();
        $builder->setValue('http://example.com/logo.jpg');
        $property = $builder->build();
        
        $this->assertEquals('http://example.com/logo.jpg', (string) $property);
    }
    
    /**
     * @group default
     * @param \EVought\vCardTools\PropertySpecification $specification
     * @depends testSetAndBuild
     */
    public function testSetFromVCardLine(PropertySpecification $specification)
    {
        $google = 'https://www.google.com/logos/doodles/2014/holidays-2014-day-1-5194759324827648.2-hp.gif';
        $vcardLine = new VCardLine('4.0');
        $vcardLine  ->setGroup('google')
                    ->setName('logo')
                    ->setValue($google)
                    ->setParameter('type', ['work']);
        $builder = $specification->getBuilder();
        $builder->setFromVCardLine($vcardLine);
        
        $this->assertEquals('google', $builder->getGroup());
        $this->assertEquals($google, $builder->getValue());
        $this->assertEquals(['work'], $builder->getTypes());
    }
    
    /**
     * @group default
     * @param \EVought\vCardTools\PropertySpecification $specification
     * @depends testSetAndBuild
     */
    public function testSetFromVCardLine30Binary(PropertySpecification $specification)
    {
        $data = 'GIF000000000000000000000000000000000000';
        $vcardLine = new VCardLine('3.0');
        $vcardLine  ->setGroup('')
                    ->setName('logo')
                    ->setValue($data)
                    ->setParameter('type', ['work'])
                    ->setParameter('mediatype', ['image/gif']);
        $builder = $specification->getBuilder();
        $builder->setFromVCardLine($vcardLine);

        $this->assertEquals(['work'], $builder->getTypes());

        $uri = $builder->getValue();
        $this->assertTrue(\DataUri::isParsable($uri));
        
        /* @var \DataUri $dataUri */
        $dataUri = null;
        $this->assertTrue(\DataUri::tryParse($uri, $dataUri));
        
        $this->assertEquals('image/gif', $dataUri->getMediaType());
        $this->assertEquals(\DataUri::ENCODING_BASE64, $dataUri->getEncoding());
        
        $decodedData = '';
        $this->assertTrue($dataUri->tryDecodeData($decodedData));
        $this->assertEquals($data, $decodedData);
    }
    
    /**
     * @group default
     * @param \EVought\vCardTools\PropertySpecification $specification
     * @depends testSetAndBuild
     * @expectedException \DomainException
     */
    public function testSetFromVCardLineBadUrl(
                                    PropertySpecification $specification )
    {
        $vcardLine = new VCardLine('4.0');
        $vcardLine  ->setName('logo')
                    ->setValue('<urf#--;purple')
                    ->setParameter('type', ['work']);
        $builder = $specification->getBuilder();
        $builder->setFromVCardLine($vcardLine);
    }
}
