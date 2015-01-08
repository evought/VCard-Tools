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
    /**
     * @group default
     */
    public function testConstruct()
    {
        $vcardLine = new VCardLine('4.0');
        $this->assertInstanceOf(__NAMESPACE__ . '\VCardLine', $vcardLine);
        $this->assertEquals('4.0', $vcardLine->getVersion());
    }
    
    /**
     * @group default
     * @depends testConstruct
     */
    public function testSetName()
    {
        $vcardLine = new VCardLine('4.0');
        $vcardLine->setName('foo');
        
        $this->assertEquals('foo', $vcardLine->getName());
    }
    
    /**
     * @group default
     * @depends testConstruct
     */
    public function testSetGroup()
    {
        $vcardLine = new VCardLine('4.0');
        $vcardLine->setGroup('foo');
        
        $this->assertEquals('foo', $vcardLine->getGroup());
    }
    
    /**
     * @group default
     * @depends testConstruct
     */
    public function testSetParameter()
    {
        $vcardLine = new VCardLine('4.0');
        $vcardLine->setParameter('foo', 'bar');
        
        $this->assertEquals('bar', $vcardLine->getParameter('foo'));
    }

    /**
     * @group default
     * @depends testConstruct
     */
    public function testUnsetParameter()
    {
        $vcardLine = new VCardLine('4.0');
        $vcardLine->setParameter('foo', 'bar')->unsetParameter('foo');
        
        $this->assertEmpty($vcardLine->getParameter('foo'));
    }
    
    /**
     * @group default
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
     * @group default
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
     * @group default
     * @depends testSetParameter
     */
    public function testHasParameter()
    {
        $vcardLine = new VCardLine('4.0');
        
        $this->assertFalse($vcardLine->hasParameter('foo'));
        $vcardLine->setParameter('foo', 'bar');
        $this->assertTrue($vcardLine->hasParameter('foo'));
    }

    /**
     * @group default
     * @depends testSetParameter
     * @depends testHasParameter
     */
    public function testLowercaseParameters()
    {
        $vcardLine = new VCardLine('4.0');
        $vcardLine  ->setParameter('name1', ['VALUE1', 'Value2'])
                    ->setParameter('name2', ['VALUE1'])
                    ->lowercaseParameters(['name1', 'name3']);
        
        $this->assertEquals( ['value1', 'value2'],
                                $vcardLine->getParameter('name1') );
        $this->assertEquals( ['VALUE1'], $vcardLine->getParameter('name2'));
        $this->assertFalse($vcardLine->hasParameter('name3'));
    }
    
    /**
     * @group default
     * @depends testConstruct
     */
    public function testSetValue()
    {
        $vcardLine = new VCardLine('4.0');
        $vcardLine->setValue('Rumplestilskin');
        
        $this->assertEquals('Rumplestilskin', $vcardLine->getValue());
    }
    
    /**
     * @group default
     * @depends testConstruct
     */
    public function testParseParametersEmpty()
    {
        $vcardLine = new VCardLine('4.0');
        $vcardLine->parseParameters([]);
        
        $this->assertEmpty($vcardLine->getParameters());
    }
    
    /**
     * @group default
     * @depends testConstruct
     * @expectedException EVought\vCardTools\Exceptions\MalformedParameterException
     */
    public function testParseParametersMalformed()
    {
        $vcardLine = new VCardLine('4.0');
        $vcardLine->parseParameters(['']);
        
        $this->assertEmpty($vcardLine->getParameters());
    }
    
    /**
     * @group default
     * @depends testParseParametersEmpty
     * @expectedException EVought\vCardTools\Exceptions\MalformedParameterException
     */
    public function testParseParametersNoValue40()
    {
        $vcardLine = new VCardLine('4.0');
        $vcardLine->parseParameters(['foo']);
    }

    /**
     * @group default
     * @depends testParseParametersEmpty
     * @expectedException EVought\vCardTools\Exceptions\MalformedParameterException
     */
    public function testParseParametersNoValue30()
    {
        $vcardLine = new VCardLine('3.0');
        $vcardLine->parseParameters(['foo']);
    }

    /**
     * @group default
     * @group vcard21
     * @depends testParseParametersEmpty
     */
    public function testParseParametersNoValue21()
    {
        $vcardLine = new VCardLine('2.1');
        $vcardLine->parseParameters(['foo']);
        
        $this->assertEquals(['foo'], $vcardLine->getParameter('type'));
    }
    
    /**
     * @group default
     * @depends testParseParametersEmpty
     * @expectedException EVought\vCardTools\Exceptions\IllegalParameterValueException
     */
    public function testParseParametersPrefType40()
    {
        $vcardLine = new VCardLine('4.0');
        $vcardLine->parseParameters(['TYPE=PREF']);
    }
    
    /**
     * @group default
     * @depends testParseParametersEmpty
     */
    public function testParseParametersPrefType30()
    {
        $vcardLine = new VCardLine('3.0');
        $vcardLine->parseParameters(['TYPE=PREF']);
        
        // PREF TYPE should be moved to PREF parameter.
        $this->assertEquals(['1'], $vcardLine->getParameter('pref'));
    }
    
    /**
     * @group default
     * @depends testParseParametersEmpty
     */
    public function testParseParametersPrefType21()
    {
        $vcardLine = new VCardLine('2.1');
        $vcardLine->parseParameters(['TYPE=PREF']);
        
        // PREF TYPE should be moved to PREF parameter.
        $this->assertEquals(['1'], $vcardLine->getParameter('pref'));
    }
    
    /**
     * @group default
     * @depends testParseParametersEmpty
     */
    public function testParseParametersNameValue()
    {
        $vcardLine = new VCardLine('4.0');
        $vcardLine->parseParameters(['foo=bar']);
        $this->assertEquals(['bar'], $vcardLine->getParameter('foo'));
    }
    
    /**
     * @group default
     * @depends testParseParametersEmpty
     */
    public function testParseParametersTwoNames()
    {
        $vcardLine = new VCardLine('4.0');
        $vcardLine->parseParameters(['foo=bar','baz=bozo']);
        $this->assertEquals(['bar'], $vcardLine->getParameter('foo'));
        $this->assertEquals(['bozo'], $vcardLine->getParameter('baz'));
    }
    
    /**
     * @group default
     * @depends testParseParametersEmpty
     */
    public function testParseParametersTwoValues()
    {
        $vcardLine = new VCardLine('4.0');
        $vcardLine->parseParameters(['foo=bar','foo=baz']);
        $this->assertCount(2, $vcardLine->getParameter('foo'));
        $this->assertContains('bar', $vcardLine->getParameter('foo'));
        $this->assertContains('baz', $vcardLine->getParameter('foo'));
    }

    public function parameterProvider()
    {
        // paramText, parameters
        return [
                    'Trivial' =>
                        ['foo=bar',             ['foo'=>['bar']]],
                    'Remove NSWSP around name 1' =>
                        [" foo\t=bar",          ['foo'=>['bar']]],
                    'Remove NSWSP around name 2' =>
                        ['foo = bar',           ['foo'=>['bar']]],
                    'Remove double quotes' =>
                        ['foo="bar"',           ['foo'=>['bar']]],
                    'Remove NSWSP around quotes' =>
                        ["\tfoo = \t \"bar\"",  ['foo'=>['bar']]],
                    'Strip cslashes in value, nl & backslash' =>
                        ['foo=line1\n\\\\line2',['foo'=>["line1\n\\line2"]]],
                    'Quoted punction is safe' =>
                        ['foo=":=;,"',         ['foo'=>[':=;,']]]
        ];
    }
    
    /**
     * @group default
     * @depends testParseParametersNameValue
     * @dataProvider parameterProvider
     * @param string $paramText Parameter text to parse.
     * @param array $parameters Expected value of $parameters.
     */
    public function testParseParamValue($paramText, $parameters)
    {
        $vcardLine = new VCardLine('4.0');
        $vcardLine->parseParameters([$paramText]);
        
        $this->assertEquals($parameters, $vcardLine->getParameters());
    }
    
    public function lineProvider()
    {
        return [
                    'FN no parameters' =>
                        ['FN:William Blakely',
                            [
                                'group'         =>'',
                                'name'          =>'fn',
                                'parameters'    =>[],
                                'value'         =>'William Blakely'
                            ]
                        ],
                    'N LANGUAGE and VALUE' =>
                        ['N;LANGUAGE=en/us;VALUE=TEXT:Shmoe;Joe;;;;',
                            [
                                'group'         =>'',
                                'name'          =>'n',
                                'parameters'    =>[ 'language'=>['en/us'],
                                                    'value'=>['text']],
                                'value'         =>'Shmoe;Joe;;;;'
                            ]
                        ],
                    'ADR' =>
                        ['ADR:xtended;pobox;street;locality;region;postal',
                            [
                                'group'         =>'',
                                'name'          =>'adr',
                                'parameters'    =>[],
                                'value'         =>'xtended;pobox;street;locality;region;postal'
                            ]
                        ],
                    'ADR TYPE' =>
                        ['ADR;TYPE=WORK:xtended;pobox;street;locality;region;postal',
                            [
                                'group'         =>'',
                                'name'          =>'adr',
                                'parameters'    =>['type'=>['work']],
                                'value'         =>'xtended;pobox;street;locality;region;postal'
                            ]
                        ],
                    'TEL, TYPES with group' =>
                        ['group.TEL;TYPE=HOME,CELL:999-555-1212',
                            [
                                'group'         =>'group',
                                'name'          =>'tel',
                                'parameters'    =>['type'=>['home','cell']],
                                'value'         =>'999-555-1212'
                            ]
                        ],
                    'hyphenated-group URL' =>
                        ['hyphen-group.URL:http\://example.com',
                            [
                                'group'         =>'hyphen-group',
                                'name'          =>'url',
                                'parameters'    =>[],
                                'value'         =>'http\://example.com'
                            ]
                        ],
                    'Version' =>
                        ['VERSION:4.0',
                            [
                                'group'         =>'',
                                'name'          =>'version',
                                'parameters'    =>[],
                                'value'         =>'4.0'
                            ]
                        ],
                    'Nickname, TYPE' =>
                        ['NICKNAME;TYPE=work:Boss',
                            [
                                'group'         =>'',
                                'name'          =>'nickname',
                                'parameters'    =>['type'=>['work']],
                                'value'         =>'Boss'
                            ]
                        ],
                    'BDAY' =>
                        ['BDAY:19960415',
                            [
                                'group'         =>'',
                                'name'          =>'bday',
                                'parameters'    =>[],
                                'value'         =>'19960415'
                            ]
                        ],
                    'ADR GEO LABEL QUOTES NL' =>
                        ['ADR;GEO="geo:12.3457,78.910";LABEL="Mr. John Q. Public, Esq.\nMail Drop: TNE QB\n123 Main Street\nAny Town, CA  91921-1234\nU.S.A.":;;123 Main Street;Any Town;CA;91921-1234;U.S.A.',
                            [
                                'group'         =>'',
                                'name'          =>'adr',
                                'parameters'    =>['geo'=>['geo:12.3457,78.910'],
                                                   'label'=>["Mr. John Q. Public, Esq.\nMail Drop: TNE QB\n123 Main Street\nAny Town, CA  91921-1234\nU.S.A."]],
                                'value'         =>';;123 Main Street;Any Town;CA;91921-1234;U.S.A.'
                            ]
                        ],
                    'GEO' =>
                        ['GEO:geo:37.386013\,-122.082932',
                            [
                                'group'         =>'',
                                'name'          =>'geo',
                                'parameters'    =>[],
                                'value'         =>'geo:37.386013\,-122.082932'
                            ]
                        ],
                    'LOGO data uri' =>
                        ['LOGO:data:image/jpeg;base64,MIICajCCAdOgAwIBAgICBEUwDQYJKoZIhvcAQEEBQAwdzELMAkGA1UEBhMCVVMxLDAqBgNVBAoTI05ldHNjYXBlIENvbW11bmljYXRpb25zIENvcnBvcmF0aW9uMRwwGgYDVQQLExNJbmZvcm1hdGlvbiBTeXN0',
                            [
                                'group'         =>'',
                                'name'          =>'logo',
                                'parameters'    =>[],
                                'value'         =>'data:image/jpeg;base64,MIICajCCAdOgAwIBAgICBEUwDQYJKoZIhvcAQEEBQAwdzELMAkGA1UEBhMCVVMxLDAqBgNVBAoTI05ldHNjYXBlIENvbW11bmljYXRpb25zIENvcnBvcmF0aW9uMRwwGgYDVQQLExNJbmZvcm1hdGlvbiBTeXN0'
                            ]
                        ]
        ];
    }
    
    /**
     * @depends testParseParametersNameValue
     * @param string $rawLine Line to parse.
     * @param array $components Components of expected value.
     * @dataProvider lineProvider
     * @group default
     */
    public function testFromLineText($rawLine, array $components)
    {
        $vcardLine = VCardLine::fromLineText($rawLine, '4.0');
        
        $this->assertEquals($components['group'], $vcardLine->getGroup());
        $this->assertEquals($components['name'], $vcardLine->getName());
        $this->assertEquals( $components['parameters'],
                                $vcardLine->getParameters() );
        $this->assertEquals($components['value'], $vcardLine->getValue());
    }
    
    public function lineProviderOpenIssues()
    {
        return [
                    'TEL QUOTED URI VALUES ISSUE #58' =>
                        ['TEL;VALUE=uri;PREF=1;TYPE="voice,home":tel:+1-555-555-5555;ext=5555',
                            [
                                'group'         =>'',
                                'name'          =>'tel',
                                'parameters'    =>[ 'value'=>['uri'],
                                                    'pref'=>['1'],
                                                    'type'=>['voice','home'] ],
                                'value'         =>'tel:+1-555-555-5555;ext=5555'
                            ]
                        ]
                ];
    }
    
    /**
     * @depends testFromLineText
     * @param string $rawLine Line to parse.
     * @param array $components Components of expected value.
     * @dataProvider lineProviderOpenIssues
     * @group openIssues
     */
    public function testFromLineTextOpenIssues($rawLine, array $components)
    {
        $vcardLine = VCardLine::fromLineText($rawLine, '4.0');
        
        $this->assertEquals($components['group'], $vcardLine->getGroup());
        $this->assertEquals($components['name'], $vcardLine->getName());
        $this->assertEquals( $components['parameters'],
                                $vcardLine->getParameters() );
        $this->assertEquals($components['value'], $vcardLine->getValue());
    }
    
    public function lineProvider30()
    {
        return [
            'PHOTO X-ABCROP-RECTANGLE' =>
                        ['PHOTO;X-ABCROP-RECTANGLE=ABClipRect_1&-9&20&283&283&WGHe9zKmBvRvhyIyYvN/1g==;ENCODING=b;TYPE=JPEG:/9j/4AAQSkZJRgABAQAAAQABAAD/4gQUSUNDX1BST0ZJTEUAAQEAAAQEYXBwbAIAAABtbnRyUkdCIFhZWiAH2QADAA0AFQAWACNhY3NwQVBQTAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA9tYAAQAAAADTLWFwcGzV7zp1myHv5rYyPVUXGqoJAAAAAAAAAAAAAAA',
                            [
                                'group'         =>'',
                                'name'          =>'photo',
                                'parameters'    =>['x-abcrop-rectangle'=>['ABClipRect_1&-9&20&283&283&WGHe9zKmBvRvhyIyYvN/1g=='],
                                                   'mediatype'=>['image/jpeg']],
                                'value'         =>  \base64_decode('/9j/4AAQSkZJRgABAQAAAQABAAD/4gQUSUNDX1BST0ZJTEUAAQEAAAQEYXBwbAIAAABtbnRyUkdCIFhZWiAH2QADAA0AFQAWACNhY3NwQVBQTAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA9tYAAQAAAADTLWFwcGzV7zp1myHv5rYyPVUXGqoJAAAAAAAAAAAAAAA')
                            ]
                        ],
            'SOUND' =>
                        ['SOUND;TYPE=BASIC;ENCODING=b:MIICajCCAdOgAwIBAgICBEUwDQYJKoZIhvcNAQEEBQAwdzELMAkGA1UEBhMCVVMxLDAqBgNVBAoTI05ldHNjYXBlIENvbW11bmljYXRpb25zIENvcnBvcmF0aW9uMRwwGgYDVQQLExNJbmZvcm1hdGlvbiBTeXN0',
                            [
                                'group'         =>'',
                                'name'          =>'sound',
                                'parameters'    =>['mediatype' => ['audio/basic']],
                                'value'         => \base64_decode('MIICajCCAdOgAwIBAgICBEUwDQYJKoZIhvcNAQEEBQAwdzELMAkGA1UEBhMCVVMxLDAqBgNVBAoTI05ldHNjYXBlIENvbW11bmljYXRpb25zIENvcnBvcmF0aW9uMRwwGgYDVQQLExNJbmZvcm1hdGlvbiBTeXN0')
                            ]
                        ]
            ];
    }

    /**
     * @depends testFromLineText
     * @param string $rawLine Line to parse.
     * @param array $components Components of expected value.
     * @dataProvider lineProvider30
     * @group default
     * @group vcard30
     */
    public function testFromLineText30($rawLine, array $components)
    {
        $vcardLine = VCardLine::fromLineText($rawLine, '3.0');
        
        $this->assertEquals($components['group'], $vcardLine->getGroup());
        $this->assertEquals($components['name'], $vcardLine->getName());
        $this->assertEquals( $components['parameters'],
                                $vcardLine->getParameters() );
        $this->assertEquals($components['value'], $vcardLine->getValue());
    }

    
    public function lineProvider21()
    {
        return [
            'TEL bare TYPEs and PREF' =>
                ['TEL;WORK;VOICE;PREF:+1-999-555-1212',
                    [
                        'group'         =>'',
                        'name'          =>'tel',
                        'parameters'    =>['pref'=>['1'],
                                           'type'=>['work','voice'] ],
                        'value'         =>'+1-999-555-1212'
                    ]
                ],
            '2.1 CHARSET UTF-8' =>
            ['N;CHARSET=UTF-8:Last Name;Iñtërnâtiônàlizætiøn;;;',
                [
                    'group'             =>'',
                    'name'              =>'n',
                    'parameters'        =>[], // CHARSET converted and discarded
                    'value'             =>'Last Name;Iñtërnâtiônàlizætiøn;;;'
                ]
            ],
            '2.1 CHARSET 8859-1' =>
            ["N;CHARSET=iso-8859-1:Patrick;Fabriz\xEDus",
                [
                    'group'             =>'',
                    'name'              =>'n',
                    'parameters'        =>[], // CHARSET converted and discarded
                    'value'             =>'Patrick;Fabrizíus'
                ]
            ],
            '2.1 CHARSET UTF-8 Japanese' =>
            ['N;CHARSET=UTF-8:溌剌;元気',
                [
                    'group'             =>'',
                    'name'              =>'n',
                    'parameters'        =>[], // CHARSET converted and discarded
                    'value'             =>'溌剌;元気'
                ]
            ],
            '2.1 CHARSET iso-8859-15' =>
            ["N;CHARSET=iso-8859-15:\xDCbermann;;;;",
                [
                    'group'             =>'',
                    'name'              =>'n',
                    'parameters'        =>[], // CHARSET converted and discarded
                    'value'             =>'Übermann;;;;'
                ]
            ],
            'PHOTO Base64' =>
            ['PHOTO;X-ABCROP-RECTANGLE=ABClipRect_1&-9&20&283&283&WGHe9zKmBvRvhyIyYvN/1g==;ENCODING=BASE64;TYPE=JPEG:/9j/4AAQSkZJRgABAQAAAQABAAD/4gQUSUNDX1BST0ZJTEUAAQEAAAQEYXBwbAIAAABtbnRyUkdCIFhZWiAH2QADAA0AFQAWACNhY3NwQVBQTAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA9tYAAQAAAADTLWFwcGzV7zp1myHv5rYyPVUXGqoJAAAAAAAAAAAAAAA',
                [
                    'group'         =>'',
                    'name'          =>'photo',
                    'parameters'    =>['x-abcrop-rectangle'=>['ABClipRect_1&-9&20&283&283&WGHe9zKmBvRvhyIyYvN/1g=='],
                                        'mediatype'=>['image/jpeg']],
                    'value'         =>\base64_decode('/9j/4AAQSkZJRgABAQAAAQABAAD/4gQUSUNDX1BST0ZJTEUAAQEAAAQEYXBwbAIAAABtbnRyUkdCIFhZWiAH2QADAA0AFQAWACNhY3NwQVBQTAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA9tYAAQAAAADTLWFwcGzV7zp1myHv5rYyPVUXGqoJAAAAAAAAAAAAAAA')
                ]
            ],
            'LABEL QUOTED-PRINTABLE' =>
            ["LABEL;DOM;POSTAL;ENCODING=QUOTED-PRINTABLE:P. O. Box 456=0D=0A123 Main Street=0D=0AAny Town, CA 91921-1234=0D=0AU.S.A.",
                [
                    'group'         =>'',
                    'name'          =>'label',
                    'parameters'    =>['type'=>['dom', 'postal']],
                    'value'         =>  \quoted_printable_decode("P. O. Box 456=0D=0A123 Main Street=0D=0AAny Town, CA 91921-1234=0D=0AU.S.A.")
                ]
            ]
        ];
    }
    
    /**
     * @depends testFromLineText
     * @param string $rawLine Line to parse.
     * @param array $components Components of expected value.
     * @dataProvider lineProvider21
     * @group default
     * @group vcard21
     */
    public function testFromLineText21($rawLine, array $components)
    {
        $vcardLine = VCardLine::fromLineText($rawLine, '2.1');
        
        $this->assertEquals($components['group'], $vcardLine->getGroup());
        $this->assertEquals($components['name'], $vcardLine->getName());
        $this->assertEquals( $components['parameters'],
                                $vcardLine->getParameters() );
        $this->assertEquals($components['value'], $vcardLine->getValue());
    }
    
    /**
     * @group default
     * @depends testConstruct
     * @expectedException EVought\vCardTools\Exceptions\MalformedPropertyException
     */
    public function testFromLineTextBadLine()
    {
        VCardLine::fromLineText('foo', '4.0');
    }
    
    /**
     * @group default
     * @depends testFromLineText
     * @expectedException EVought\vCardTools\Exceptions\MalformedParameterException
     */
    public function testFromLineTextBadCharset40()
    {
        VCardLine::fromLineText('N;CHARSET=iso-8859-1:Patrick;Fabrizius', '4.0');
    }
    
    /**
     * @group default
     * @group vcard21
     * @depends testFromLineText
     * @expectedException EVought\vCardTools\Exceptions\MalformedParameterException
     */
    public function testFromLineTextMultCharset21()
    {
        VCardLine::fromLineText(
            'N;CHARSET=iso-8859-1;CHARSET=iso-8859-1:Patrick;Fabrizius', '2.1'
            );
    }
}
