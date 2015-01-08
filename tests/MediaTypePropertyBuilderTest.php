<?php
/**
 * MediaTypePropertyBuiilderTest.php
 *
 * @link https://github.com/evought/VCard-Tools
 * @author Eric Vought
 * @see RFC 2426, RFC 2425, RFC 6350
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
 * Description of MediaTypePropertyBuilderTest
 *
 * @author evought
 */
class MediaTypePropertyBuilderTest extends \PHPUnit_Framework_TestCase
{
    protected $specification;
    
    protected $specificationWMediaTypes;
    
     /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->specification = new PropertySpecification(
                'logo',
                PropertySpecification::MULTIPLE_PROPERTY,
                __NAMESPACE__ . '\MediaTypePropertyBuilderImpl',
                PropertySpecification::$cardinalities['Zero To N'],
                ['allowedTypes'=>['work', 'home']]
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
    public function testSetAndBuild()
    {
        $builder = $this->specification->getBuilder();
        $property = $builder->setValue('http://example.com')
                            ->setMediaType('image/jpeg')->build();
        $this->assertEquals('image/jpeg', $builder->getMediaType());
        $this->assertEquals('image/jpeg', $property->getMediaType());
    }
    
    /**
     * @group default
     * @expectedException EVought\vCardTools\Exceptions\MalformedParameterException
     * @expectedExceptionMessage warthog
     */
    public function testSetAndBuildBadMediaType()
    {
        $builder = $this->specification->getBuilder();
        $property = $builder->setValue('http://example.com')
                            ->setMediaType('warthog')->build();
    }
    
    /**
     * @group default
     * @depends testSetAndBuild
     */
    public function testSetFromVCardLine()
    {
        $url = 'https://www.example.com/foo.jpg';
        $vcardLine = new VCardLine('4.0');
        $vcardLine  ->setName('logo')
                    ->setValue($url)
                    ->setParameter('mediatype', ['image/jpeg']);
        $builder = $this->specification->getBuilder();
        $builder->setFromVCardLine($vcardLine);
        
        $this->assertEquals('image/jpeg', $builder->getMediaType());
    }
    
    /**
     * @group default
     * @depends testSetAndBuild
     * @expectedException EVought\vCardTools\Exceptions\MalformedParameterException
     * @expectedExceptionMessage jpeg
     */
    public function testSetFromVCardLineBadMediaType()
    {
        $url = 'https://www.example.com/foo.jpg';
        $vcardLine = new VCardLine('4.0');
        $vcardLine  ->setName('logo')
                    ->setValue($url)
                    ->setParameter('mediatype', ['jpeg']);
        $builder = $this->specification->getBuilder();
        $builder->setFromVCardLine($vcardLine);
    }
    
    /**
     * @group default
     * @depends testSetAndBuild
     */
    public function testOutputMediaType()
    {
        $builder = $this->specification->getBuilder();
        $builder->setValue('http://example.com/logo.jpg')
                ->setMediaType('image/png');
        $property = $builder->build();
        
        $this->assertEquals( 'LOGO;MEDIATYPE=image/png:'
                             . VCard::escape('http://example.com/logo.jpg')
                             . "\n", $property->output() );
    }
}
