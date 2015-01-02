<?php
/**
 * VCardParser.php
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
 * A class for reading one or more VCards from a raw VCard format via strings
 * or streams.
 *
 * @author evought
 */
class VCardParser
{
    // FIXME: Convert AGENT
    
    /**
     * An associative array of VCards which have already been loaded, indexed
     * by UID. This is used, in particular, to figure out when embedded VCards
     * refer to cards which have already been loaded.
     * @var VCard[]
     */
    private $vcards = [];
    
    private static $bodyRegExp = <<<'EOD'
/
# Expression for breaking a vcard into components for extracting version
# and body
^BEGIN:VCARD\n
VERSION:(?P<version>\d+\.\d+)\n
(?P<body>.*)
(?P<end>END:VCARD\n)
/sux
EOD;
    
    public function __construct()
    {
    }
    
    /**
     * Return a VCard by its uid, if it exists, else null.
     * @param string $uid The uid of the VCard to return. Not empty.
     * @return type
     */
    public function getCard($uid)
    {
        \assert(!empty($uid));
        \assert(\is_string($uid));
        
        if (\array_key_exists($uid, $this->vcards))
            return $this->vcards[$uid];
        else
            return null;
    }
    
    /**
     * Return an array of all uids which have been parsed.
     * @return type
     */
    public function getUIDs()
    {
        return \array_keys($this->vcards);
    }
    
    /**
     * Reset this parser and remove all stored VCards.
     * @return self $this
     */
    public function clear()
    {
        $vcards = [];
        return $this;
    }
    
    /**
     * Parsing loop for one raw vCard. Adds the card to $this->vcards
     * @param string $rawData Not null.
     * @throws Exceptions\UndefinedPropertyException If an encountered property
     * is undefined or not permitted.
     * @throws Exceptions\MalformedPropertyException if an encountered property
     * line does not follow the defined structure.
     * @return VCard[]|null The VCards returned in this pass of parsing.
     */
    public function importCards($rawData)
    {
    	\assert(null !== $rawData);
    	\assert(\is_string($rawData));
        
        // Make newlines consistent, spec requires CRLF, but PHP often strips
        // carriage returns before data gets to us, so we can't depend on it.
        $fixNewlines = \str_replace(["\r\n", "\r"], "\n", $rawData);
        
        $vcard = new VCard();
    	
        $components = $this->getCardBody($fixNewlines);
        
        if ('2.1' === $components['version'])
            $unfoldedData = self::unfold21($components['body']);
        else
            $unfoldedData = self::unfold4($components['body']);
                
        $lines = \explode("\n", $unfoldedData);

        foreach ($lines as $line)
        {
            // FIXME: Make sure that TYPE, ENCODING, CHARSET are dealt
            // with by PropertyBuilder
            $vcardLine = VCardLine::fromLineText($line, $components['version']);
            
            if (null === $vcardLine)
	        continue;

            // FIXME: #25 Deal gracefully with unknown and X-properties
            if (!VCard::isSpecified($vcardLine->getName()))
                throw new Exceptions\UndefinedPropertyException(
                    $vcardLine->getName() . ' is not a defined property.');
            
            $specification = VCard::getSpecification($vcardLine->getName());
           
            
            if ($specification->allowsCommaProperties())
            {
                // Deal with the possibility of multiple values
                $origValue = $vcardLine->getValue();
                $values = \str_getcsv($origValue);
                foreach ($values as $value)
                {
                    $vcardLine->setValue($value);
                    $specification->getBuilder()
                        ->setFromVCardLine($vcardLine)->pushTo($vcard);
                }
            } else {
                $specification->getBuilder()
                    ->setFromVCardLine($vcardLine)->pushTo($vcard);
            }
        }
        
        $vcard->checkSetUID();
        $this->vcards[$vcard->getUID()] = $vcard;
        
        return [$vcard];
    }
    
    /**
     * Import one or more VCards from a file.
     * @param string $file The file path to read from.
     * @throws \Exception If the file cannot be read.
     * @throws Exceptions\UndefinedPropertyException If an encountered property
     * is undefined or not permitted.
     * @throws Exceptions\MalformedPropertyException if an encountered property
     * line does not follow the defined structure.

     * @returns VCard[] The VCards parsed from the file.
     */
    public function importFromFile($file)
    {
        if (!\is_readable($file))
	    throw new \Exception('VCardParser: Path not accessible: ' . $file);

        $rawData = \file_get_contents($file);
        return $this->importCards($rawData);
    }
    
    /**
     * Extracts the version and body of the VCard from the given raw text
     * string, returning the components.
     * This must be done before unfolding occurs because the vcard version may
     * determine other parsing steps (including unfolding rules).
     * @param string $text The raw VCard text
     * @return array Keys will be set for at least 'version' and 'body'.
     * @throws \DomainException If the VCard is not well-formed.
     */
    public function getCardBody($text)
    {
        $fragments = [];
        $matches = \preg_match(self::$bodyRegExp, $text, $fragments);
        if (1 !== $matches)
            throw new \DomainException('Malformed VCard');
        return $fragments;
    }
    
    /**
     * Perform unfolding (joining of continued lines) according to RFC6350.
     * Text must be unfolded before properties are parsed.
     * @param type $rawData
     * @return string The raw text with line continuations removed.
     * @see https://tools.ietf.org/html/rfc6350#section-3.2
     */
    public static function unfold4($rawData)
    {
        \assert(null !== $rawData);
        \assert(\is_string($rawData));
        
        // Joining multiple lines that are split with a soft
        // wrap (space or tab on the beginning of the next line
        $folded = \str_replace(["\n ", "\n\t"], '', $rawData);
        
        return $folded;
    }
    
    /**
     * Perform unfolding (joining of continued lines) according to VCard 2.1.
     * Text must be unfolded before properties are parsed.
     * In VCard 2.1 soft-breaks only occur in Linear-White-Space (LWSP) and
     * are reduced to the LWSP char as opposed to later versions where the LWSP
     * is removed as well. 
     * @param type $rawData
     * @return string The raw text with line continuations removed.
     * @see https://tools.ietf.org/html/rfc6350#section-3.2
     */
    public static function unfold21($rawData)
    {
        \assert(null !== $rawData);
        \assert(\is_string($rawData));
        
        // Joining multiple lines that are split with a soft
        // wrap (space or tab on the beginning of the next line
        $folded = \str_replace(["\n ", "\n\t"], [" ", "\t"], $rawData);
        
        return $folded;
    }
}
