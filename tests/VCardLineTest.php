<?php
/**
 * Tests for VCardLine.
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
 * Tests for VCardLine
 *
 * @author evought
 */
class VCardLineTest extends \PHPUnit_Framework_TestCase
{
    public function testConstruct()
    {
        $vcardLine = new VCardLine('4.0');
        $this->assertInstanceOf(__NAMESPACE__ . '\VCardLine', $vcardLine);
        $this->assertEquals('4.0', $vcardLine->getVersion());
    }
    
    /**
     * @depends testConstruct
     */
    public function testSetName()
    {
        $vcardLine = new VCardLine('4.0');
        $vcardLine->setName('foo');
        
        $this->assertEquals('foo', $vcardLine->getName());
    }
    
    /**
     * @depends testConstruct
     */
    public function testSetGroup()
    {
        $vcardLine = new VCardLine('4.0');
        $vcardLine->setGroup('foo');
        
        $this->assertEquals('foo', $vcardLine->getGroup());
    }
    
    /**
     * @depends testConstruct
     */
    public function testSetParameter()
    {
        $vcardLine = new VCardLine('4.0');
        $vcardLine->setParameter('foo', 'bar');
        
        $this->assertEquals('bar', $vcardLine->getParameter('foo'));
    }

    /**
     * @depends testConstruct
     */
    public function testUnsetParameter()
    {
        $vcardLine = new VCardLine('4.0');
        $vcardLine->setParameter('foo', 'bar')->unsetParameter('foo');
        
        $this->assertEmpty($vcardLine->getParameter('foo'));
    }
    
    /**
     * @depends testSetParameter
     */
    public function testPushParameter()
    {
        $vcardLine = new VCardLine('4.0');
        $vcardLine->pushParameter('breakfast', 'spam');
        $vcardLine->pushParameter('breakfast', 'eggs');
        
        $this->assertCount(2, $vcardLine->getParameter('breakfast'));
        $this->assertContains('spam', $vcardLine->getParameter('breakfast'));
        $this->assertContains('eggs', $vcardLine->getParameter('breakfast'));
    }
    
    /**
     * @depends testSetParameter
     */
    public function testClearParamValues()
    {
        $vcardLine = new VCardLine('4.0');
        $vcardLine->setParameter('breakfast', ['spam', 'eggs', 'spam']);
        $vcardLine->clearParamValues('breakfast', ['spam', 'herring']);
        
        $this->assertEquals(['eggs'], $vcardLine->getParameter('breakfast'));
    }

    /**
     * @depends testSetParameter
     */
    public function testHasParameter()
    {
        $vcardLine = new VCardLine('4.0');
        
        $this->assertFalse($vcardLine->hasParameter('foo'));
        $vcardLine->setParameter('foo', 'bar');
        $this->assertTrue($vcardLine->hasParameter('foo'));
    }
}
