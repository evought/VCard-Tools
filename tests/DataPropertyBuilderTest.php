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
                PropertySpecification::MULTIPLE_VALUE,
                __NAMESPACE__ . '\DataPropertyBuilder',
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
     * @expectedException \DomainException
     */
    public function testMediaType(PropertySpecification $specification)
    {
        $builder = $specification->getBuilder();
        $builder->setValue('Edsel');
    }
    
    /**
     * @group default
     * @depends testSetAndBuild
     */
    public function testToStringJustValue(PropertySpecification $specification)
    {
        $builder = $specification->getBuilder();
        $builder->setValue('http://example.com/logo.jpg');
        $property = $builder->build();
        
        $this->assertEquals( 'LOGO:'
                             . VCard::escape('http://example.com/logo.jpg')
                             . "\n", (string) $property );
    }
    
    /**
     * @group default
     * @depends testSetAndBuild
     */
    public function testToStringOneType(PropertySpecification $specification)
    {
        $builder = $specification->getBuilder();
        $builder->setValue('http://example.com/logo.jpg')
                ->addType('work');
        $property = $builder->build();
        
        $this->assertEquals( 'LOGO;TYPE=WORK:'
                             . VCard::escape('http://example.com/logo.jpg')
                             . "\n", (string) $property );
    }
    
    /**
     * @group default
     * @depends testSetAndBuild
     */
    public function testToStringMediaType(PropertySpecification $specification)
    {
        $builder = $specification->getBuilder();
        $builder->setValue('http://example.com/logo.jpg')
                ->setMediaType('image/png');
        $property = $builder->build();
        
        $this->assertEquals( 'LOGO;MEDIATYPE=image/png:'
                             . VCard::escape('http://example.com/logo.jpg')
                             . "\n", (string) $property );
    }
    
    /**
     * @group default
     * @depends testSetAndBuild
     */
    public function testToStringMediaTypeAndType(PropertySpecification $specification)
    {
        $builder = $specification->getBuilder();
        $builder->setValue('http://example.com/logo.jpg')
                ->setMediaType('image/png')
                ->addType('home'); // Who has a home logo?
        $property = $builder->build();
        
        // NOTE: Sensitive to output order.
        $this->assertEquals( 'LOGO;TYPE=HOME;MEDIATYPE=image/png:'
                             . VCard::escape('http://example.com/logo.jpg')
                             . "\n", (string) $property );
    }
}
