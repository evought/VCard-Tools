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
                PropertySpecification::MULTIPLE_PROPERTY,
                __NAMESPACE__ . '\SimplePropertyBuilder',
                PropertySpecification::$cardinalities['Zero To N']
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
    public function testSetPref(PropertySpecification $specification)
    {
        $builder = $specification->getBuilder();
        $builder->setValue('foo')->setPref(35);
        $property = $builder->build();
        $this->assertEquals(35, $property->getPref());
        $this->assertEquals(35, $property->getPref(true));
        $this->assertEquals(35, $property->getPref(false));
        
        return $specification;
    }
    
    /**
     * @group default
     * @depends testSetAndBuild
     */
    public function testGetPrefDefault(PropertySpecification $specification)
    {
        $builder = $specification->getBuilder();
        $property = $builder->setValue('foo')->build();
        $this->assertEquals(100, $property->getPref());
        $this->assertEquals(100, $property->getPref(true));
        $this->assertNull($property->getPref(false));
        
        return $specification;
    }
    
    /**
     * @group default
     * @depends testGetPrefDefault
     */
    public function testComparePref(PropertySpecification $specification)
    {
        $builder = $specification->getBuilder()->setValue('foo');
        $property1 = $builder->build();
        $property2 = $builder->setPref(1)->build();
        $property3 = $builder->setPref(23)->build();
        $property4 = $builder->setPref(23)->build();
        
        $this->assertEquals(1, $property1->comparePref($property1, $property2));
        $this->assertEquals(-1, $property1->comparePref($property2, $property3));
        $this->assertEquals(0, $property1->comparePref($property3, $property4));
        
        return $specification;
    }
    
    /**
     * @group default
     * @depends testSetAndBuild
     */
    public function testCompareValue(PropertySpecification $specification)
    {
        $builder = $specification->getBuilder();
        $property1 = $builder->setValue('foo')->build();
        $property2 = $builder->setValue('bar')->build();
        $property3 = $builder->setValue('baz')->build();
        $property4 = $builder->setValue('baz')->build();
        
        $this->assertEquals(1, $property1->compareValue($property1, $property2));
        $this->assertEquals(-1, $property1->compareValue($property2, $property3));
        $this->assertEquals(0, $property1->compareValue($property3, $property4));
        
        return $specification;
    }
    
    /**
     * @group default
     * @depends testComparePref
     * @depends testCompareValue
     */
    public function testComparePrefAndValue(PropertySpecification $specification)
    {
        $builder = $specification->getBuilder();
        $property1 = $builder->setValue('foo')->build();
        $property2 = $builder->setValue('bar')->setPref(1)->build();
        $property3 = $builder->setValue('baz')->build();
        $property4 = $builder->setValue('baz')->setPref(2)->build();
        
        $this->assertEquals( 1,
                $property1->comparePrefThenValue($property1, $property2) );
        $this->assertEquals(-1,
                $property1->comparePrefThenValue($property2, $property3) );
        $this->assertEquals(-1,
                $property1->comparePrefThenValue($property3, $property4) );
        $this->assertEquals(-1,
                $property1->comparePrefThenValue($property4, $property1) );
        
        return $specification;
    }
    
    /**
     * @group default
     * @depends testComparePref
     */
    public function testUSortPref(PropertySpecification $specification)
    {
        $builder = $specification->getBuilder()->setValue('foo');
        $property1 = $builder->build();
        $property2 = $builder->setPref(1)->build();
        $property3 = $builder->setPref(23)->build();
        $property4 = $builder->setPref(23)->build();
        
        $list = [$property1, $property2, $property3, $property4];
        
        \usort($list, [$property1, 'comparePref']);
        $this->assertEquals($property2, $list[0]);
        $this->assertEquals($property1, $list[3]);
        $this->assertEquals(23, $list[1]->getPref());
        $this->assertEquals(23, $list[2]->getPref());
    }
    
    /**
     * @group default
     * @depends testSetAndBuild
     */
    public function testOutput()
    {
        $specification = new PropertySpecification(
                'fn',
                PropertySpecification::SINGLE_PROPERTY,
                __NAMESPACE__ . '\SimplePropertyBuilder',
                PropertySpecification::$cardinalities['Exactly One']
            );
        $builder = $specification->getBuilder()->setValue('Mr. Toad');
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
                PropertySpecification::SINGLE_PROPERTY,
                __NAMESPACE__ . '\SimplePropertyBuilder',
                PropertySpecification::$cardinalities['Exactly One']
            );
        $builder = $specification->getBuilder()->setValue('Mr. Toad');
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
        
        return $specification;
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
        
        return $specification;
    }
    
    /**
     * @group default
     * @param \EVought\vCardTools\PropertySpecification $specification
     * @depends testSetFromVCardLine
     */
    public function testSetFromVCardLinePref(PropertySpecification $specification)
    {
        $vcardLine = new VCardLine('4.0');
        $vcardLine->setName('url')->setValue('http://abc.es')
                ->setParameter('pref', 1);
        $builder = $specification->getBuilder();
        $builder->setFromVCardLine($vcardLine);
        
        $this->assertEquals(1, $builder->getPref());
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
        $property = $builder->pushTo($container);
        
        $this->assertEquals( 'http://liquor.cabi.net',
                                $container->current()->getValue() );
    }
}
