<?php
/**
 * vCard class for parsing a vCard and/or creating one
 *
 * @link https://github.com/evought/VCard-Tools
 * @author Martins Pilsetnieks, Roberts Bruveris, Eric Vought
 * @see RFC 2426, RFC 2425, RFC 6350
 * @license MIT http://opensource.org/licenses/MIT
 */
namespace EVought\vCardTools;

use Rhumsaa\Uuid\Uuid;
use Rhumsaa\Uuid\Exception\UnsatisfiedDependencyException;

/**
 * Representation of a vCard record exposing properties and parameters of a
 * contact. Provides ability to import/export raw .vcf vcard text (via
 * __construct() and __toString(). Implements the \Iterator interface to
 * allow iteration over all properties with values. Implements \Countable
 * to allow access to multiple  vCard records created from the same import
 * (deprecated).
 * The 'uid' parameter uniquely identifies this VCard (or, technically, the
 * object the VCard refers to).
 * @api
 * @author evought
 *
 */
class VCard implements \Iterator
{
    const endl = "\n";
    
    /**
     * The target version of VCards produced.
     */
    const VERSION = '4.0';

    /**
     * An array of PropertySpecifications, name=>specification, which define
     * the properties and their constraints as well as return PropertyBuilders
     * on request.
     * @var PropertySpecification[]
     */
    private static $specifications;
    
    /**
     * @var array Internal options container. Options:
     *	bool Collapse: If true, elements that can have multiple values but have only a single value are returned as that value instead of an array
     *		If false, an array is returned even if it has only one value.
     */
    private $Options = array( 'Collapse' => false );

    /**
     * @var Properties[] The collection of Properties.
     */
    private $Data = [];
    
    /**
     * The unique ID for this contact.
     * @var string
     */
    private $uid;

    private static function initSpecifications()
    {
        if (null !== self::$specifications) return;
        
        // https://tools.ietf.org/html/rfc6350#section-6.1.3
        self::registerSpecification(
            new PropertySpecification(
                'source',
                PropertySpecification::MULTIPLE_VALUE,
                __NAMESPACE__ . '\SimplePropertyBuilder'
            )
        );
        //https://tools.ietf.org/html/rfc6350#section-6.1.4
        self::registerSpecification(
            new PropertySpecification(
                'kind',
                PropertySpecification::SINGLE_VALUE,
                __NAMESPACE__ . '\SimplePropertyBuilder'
            )
        );
        // https://tools.ietf.org/html/rfc6350#section-6.1.5
        self::registerSpecification(
            new PropertySpecification(
                'xml',
                PropertySpecification::MULTIPLE_VALUE,
                __NAMESPACE__ . '\SimplePropertyBuilder'
            )
        );
        // https://tools.ietf.org/html/rfc6350#section-6.2.1
        // FN is typed according to spec. No idea why.
        self::registerSpecification(
            new PropertySpecification(
                'fn',
                PropertySpecification::SINGLE_VALUE,
                __NAMESPACE__ . '\TypedPropertyBuilderImpl',
                ['allowedTypes'=>['work', 'home']]
            )
        );
        // https://tools.ietf.org/html/rfc6350#section-6.2.2
        self::registerSpecification(
            new PropertySpecification(
                'n',
                PropertySpecification::MULTIPLE_VALUE,
                __NAMESPACE__ . '\StructuredPropertyBuilderImpl',
                ['allowedFields'=>['FamilyName', 'GivenName', 'AdditionalNames',
                    'Prefixes', 'Suffixes']]
            )
        );
        // https://tools.ietf.org/html/rfc6350#section-6.2.3
        self::registerSpecification(
            new PropertySpecification(
                'nickname',
                PropertySpecification::MULTIPLE_VALUE,
                __NAMESPACE__ . '\SimplePropertyBuilder'
            )
        );
        // https://tools.ietf.org/html/rfc6350#section-6.2.4
        self::registerSpecification(
            new PropertySpecification(
                'photo',
                PropertySpecification::MULTIPLE_VALUE,
                __NAMESPACE__ . '\DataPropertyBuilder',
                ['allowedTypes'=>['work', 'home']]
            )
        );
        // https://tools.ietf.org/html/rfc6350#section-6.2.5
        self::registerSpecification(
            new PropertySpecification(
                'bday',
                PropertySpecification::SINGLE_VALUE,
                __NAMESPACE__ . '\SimplePropertyBuilder'
            )
        );
        // https://tools.ietf.org/html/rfc6350#section-6.2.6
        self::registerSpecification(
            new PropertySpecification(
                'anniversary',
                PropertySpecification::SINGLE_VALUE,
                __NAMESPACE__ . '\SimplePropertyBuilder'
            )
        );
        // https://tools.ietf.org/html/rfc6350#section-6.2.7
        self::registerSpecification(
            new PropertySpecification(
                'gender',
                PropertySpecification::SINGLE_VALUE,
                __NAMESPACE__ . '\StructuredPropertyBuilderImpl',
                ['allowedFields'=>['Sex', 'Text']]
            )
        );
        // https://tools.ietf.org/html/rfc6350#section-6.3.1
        self::registerSpecification(
            new PropertySpecification(
                'adr',
                PropertySpecification::MULTIPLE_VALUE,
                __NAMESPACE__ . '\TypedStructuredPropertyBuilder',
                ['allowedTypes'=>[ 'dom', 'intl', 'postal', 'parcel',
                            'home', 'work'],
                 'allowedFields'=>['POBox', 'ExtendedAddress', 'StreetAddress', 
                            'Locality', 'Region', 'PostalCode', 'Country']
                ]
            )
        );
        //https://tools.ietf.org/html/rfc6350#section-6.4.1
        self::registerSpecification(
            new PropertySpecification(
                'tel',
                PropertySpecification::MULTIPLE_VALUE,
                __NAMESPACE__ . '\TypedPropertyBuilderImpl',
                ['allowedTypes'=>['home', 'msg', 'work', 'voice', 'fax', 
                       'cell', 'video', 'pager', 'bbs', 'modem', 'car', 
                       'isdn', 'pcs']
                ]
            )
        );
        // https://tools.ietf.org/html/rfc6350#section-6.4.2
        self::registerSpecification(
            new PropertySpecification(
                'email',
                PropertySpecification::MULTIPLE_VALUE,
                __NAMESPACE__ . '\TypedPropertyBuilderImpl',
                ['allowedTypes'=>['internet', 'x400']]
            )
        );
        // https://tools.ietf.org/html/rfc6350#section-6.4.3
        self::registerSpecification(
            new PropertySpecification(
                'impp',
                PropertySpecification::MULTIPLE_VALUE,
                __NAMESPACE__ . '\TypedPropertyBuilderImpl',
                ['allowedTypes'=>['personal', 'business', 'home', 'work',
                    'mobile']
                ]
            )
        );
        // https://tools.ietf.org/html/rfc6350#section-6.4.4
        self::registerSpecification(
            new PropertySpecification(
                'language',
                PropertySpecification::MULTIPLE_VALUE,
                __NAMESPACE__ . '\TypedPropertyBuilderImpl',
                ['allowedTypes'=>['work', 'home']]
            )
        );
        //https://tools.ietf.org/html/rfc6350#section-6.5.1
        self::registerSpecification(
            new PropertySpecification(
                'tz',
                PropertySpecification::MULTIPLE_VALUE,
                __NAMESPACE__ . '\TypedPropertyBuilderImpl',
                ['allowedTypes'=>['work', 'home']]
            )
        );
        // https://tools.ietf.org/html/rfc6350#section-6.5.2
        self::registerSpecification(
            new PropertySpecification(
                'geo',
                PropertySpecification::MULTIPLE_VALUE,
                __NAMESPACE__ . '\TypedPropertyBuilderImpl',
                ['allowedTypes'=>['work', 'home']]
            )
        );
        // https://tools.ietf.org/html/rfc6350#section-6.6.1
        self::registerSpecification(
            new PropertySpecification(
                'title',
                PropertySpecification::MULTIPLE_VALUE,
                __NAMESPACE__ . '\TypedPropertyBuilderImpl',
                ['allowedTypes'=>['work', 'home']]
            )
        );
        // https://tools.ietf.org/html/rfc6350#section-6.6.2
        self::registerSpecification(
            new PropertySpecification(
                'role',
                PropertySpecification::MULTIPLE_VALUE,
                __NAMESPACE__ . '\TypedPropertyBuilderImpl',
                ['allowedTypes'=>['work', 'home']]
            )
        );
        // https://tools.ietf.org/html/rfc6350#section-6.6.3
        self::registerSpecification(
            new PropertySpecification(
                'logo',
                PropertySpecification::MULTIPLE_VALUE,
                __NAMESPACE__ . '\DataPropertyBuilder',
                ['allowedTypes'=>['work', 'home']]
            )
        );
        // https://tools.ietf.org/html/rfc6350#section-6.6.4
        self::registerSpecification(
            new PropertySpecification(
                'org',
                PropertySpecification::MULTIPLE_VALUE,
                __NAMESPACE__ . '\TypedStructuredPropertyBuilder',
                ['allowedTypes'=>['work', 'home'],
                 'allowedFields'=>['Name', 'Unit1', 'Unit2']
                ]
            )
        );
        //https://tools.ietf.org/html/rfc6350#section-6.6.5
        self::registerSpecification(
            new PropertySpecification(
                'member',
                PropertySpecification::MULTIPLE_VALUE,
                __NAMESPACE__ . '\SimplePropertyBuilder'
            )
        );
        // https://tools.ietf.org/html/rfc6350#section-6.6.6
        self::registerSpecification(
            new PropertySpecification(
                'related',
                PropertySpecification::MULTIPLE_VALUE,
                __NAMESPACE__ . '\TypedPropertyBuilderImpl',
                ['allowedTypes' => [ 'contact', 'acquaintance', 'friend', 'met',
                           'co-worker', 'colleague', 'co-resident',
                           'neighbor', 'child', 'parent', 'sibling',
                           'spouse', 'kin', 'muse', 'crush', 'date',
                           'sweetheart', 'me', 'agent', 'emergency' ]
                ]
            )
        );
        // https://tools.ietf.org/html/rfc6350#section-6.7.1
        self::registerSpecification(
            new PropertySpecification(
                'categories',
                PropertySpecification::COMMA_VALUE,
                __NAMESPACE__ . '\TypedPropertyBuilderImpl',
                ['allowedTypes'=>['work', 'home']]
            )
        );
        // https://tools.ietf.org/html/rfc6350#section-6.7.2
        self::registerSpecification(
            new PropertySpecification(
                'note',
                PropertySpecification::MULTIPLE_VALUE,
                __NAMESPACE__ . '\TypedPropertyBuilderImpl',
                ['allowedTypes'=>['work', 'home']]
            )
        );
        // https://tools.ietf.org/html/rfc6350#section-6.7.3
        self::registerSpecification(
            new PropertySpecification(
                'prodid',
                PropertySpecification::SINGLE_VALUE,
                __NAMESPACE__ . '\SimplePropertyBuilder'
            )
        );
        // https://tools.ietf.org/html/rfc6350#section-6.7.4
        self::registerSpecification(
            new PropertySpecification(
                'rev',
                PropertySpecification::SINGLE_VALUE,
                __NAMESPACE__ . '\SimplePropertyBuilder'
            )
        );
        // https://tools.ietf.org/html/rfc6350#section-6.7.5
        self::registerSpecification(
            New PropertySpecification(
                'sound',
                PropertySpecification::MULTIPLE_VALUE,
                __NAMESPACE__ . '\DataPropertyBuilder',
                ['allowedTypes'=>['work', 'home']]
            )
        );
        // https://tools.ietf.org/html/rfc6350#section-6.7.6
        self::registerSpecification(
            new PropertySpecification(
                'uid',
                PropertySpecification::SINGLE_VALUE,
                __NAMESPACE__ . '\SimplePropertyBuilder'
            )
        );
        // https://tools.ietf.org/html/rfc6350#section-6.7.7
        self::registerSpecification(
            new PropertySpecification(
                'clientpidmap',
                PropertySpecification::MULTIPLE_VALUE,
                __NAMESPACE__ . '\StructuredPropertyBuilderImpl',
                ['allowedFields'=>['Pid', 'Uri']]
            )
        );
        // https://tools.ietf.org/html/rfc6350#section-6.7.8
        self::registerSpecification(
            new PropertySpecification(
                'url',
                PropertySpecification::MULTIPLE_VALUE,
                __NAMESPACE__ . '\TypedPropertyBuilderImpl',
                ['allowedTypes'=>['work', 'home']]
            )
        );
        // https://tools.ietf.org/html/rfc6350#section-6.7.9
        self::registerSpecification(
            new PropertySpecification(
                'version',
                PropertySpecification::SINGLE_VALUE,
                __NAMESPACE__ . '\SimplePropertyBuilder'
            )
        );
        // https://tools.ietf.org/html/rfc6350#section-6.8.1
        self::registerSpecification(
            New PropertySpecification(
                'key',
                PropertySpecification::MULTIPLE_VALUE,
                __NAMESPACE__ . '\DataPropertyBuilder',
                ['allowedTypes'=>['work', 'home']]
            )
        );
        //https://tools.ietf.org/html/rfc6350#section-6.9.1
        self::registerSpecification(
            new PropertySpecification(
                'fburl',
                PropertySpecification::MULTIPLE_VALUE,
                __NAMESPACE__ . '\TypedPropertyBuilderImpl',
                ['allowedTypes'=>['work', 'home']]
            )
        );
        // https://tools.ietf.org/html/rfc6350#section-6.9.2
        self::registerSpecification(
            new PropertySpecification(
                'caladruri',
                PropertySpecification::MULTIPLE_VALUE,
                __NAMESPACE__ . '\TypedPropertyBuilderImpl',
                ['allowedTypes'=>['work', 'home']]
            )
        );
        // https://tools.ietf.org/html/rfc6350#section-6.9.3
        self::registerSpecification(
            new PropertySpecification(
                'caluri',
                PropertySpecification::MULTIPLE_VALUE,
                __NAMESPACE__ . '\TypedPropertyBuilderImpl',
                ['allowedTypes'=>['work', 'home']]
            )
        );
        // from VCard 3.0, deprecated.
        // https://www.ietf.org/rfc/rfc2426.txt , sec 3.5.4
        // Just store the value; let the caller figure out what they want
        // to do with it.
        self::registerSpecification(
            new PropertySpecification(
                'agent',
                PropertySpecification::SINGLE_VALUE,
                __NAMESPACE__ . '\SimplePropertyBuilder'
            )
        );
    }
    
    /**
     * Add a PropertySpecification to the the internal registry.
     * Any existing definition for that property name is replaced.
     * @param \EVought\vCardTools\PropertySpecification $specification
     */
    private static function registerSpecification(
                                    PropertySpecification $specification )
    {
        self::$specifications[$specification->getName()] = $specification;
    }
    
    /**
     * Returns the static registry of property specifications.
     * @return array An array of PropertySpecifications indexed by property
     * name.
     */
    public static function getSpecifications()
    {
        self::initSpecifications();
        return self::$specifications;
    }
    
    /**
     * Returns true if-and-only-if a definition exists for the named property.
     * @param type $name The name of the property to test.
     * @return bool
     */
    public static function isSpecified($name)
    {
        \assert(null !== $name);
        \assert(\is_string($name));
        return (\array_key_exists($name, self::getSpecifications()));
    }
    
    /**
     * Return a PropertySpecification for the given property name, or null
     * if no specification is defined.
     * @param string $name
     * @return PropertySpecification
     */
    public static function getSpecification($name)
    {
        self::initSpecifications();
        \assert(null !== $name);
        \assert(\is_string($name));
        return \array_key_exists($name, self::$specifications)
                ? self::$specifications[$name] : null;
    }
    
    /**
     * vCard constructor
     * @param string $Path to file, optional.
     * @param string $RawData Raw vCard data as a string to import.
     * @param array $Options Additional options, optional. Currently supported
     * options:
     * bool Collapse: If true, elements that can have multiple values but 
     * have only a single value are returned as that value instead of an array
     * If false, an array is returned even if it has only one value.
     * @throws \Exception If the path to the raw data is not accessible.
     * @return boolean
     */
    public function __construct( $Path = false, $RawData = false,
                                     array $Options = null )
    {        
        // Checking preconditions for the parser. If path is given, the file 
        // should be accessible. If raw data is given, it is taken as it is.
	if ($Path)
	{
	    if (!is_readable($Path))
	    throw new \Exception('vCard: Path not accessible (' . $Path . ')');

	    $RawData = file_get_contents($Path);
	}

       if (!$RawData) return true;

       if ($Options) $this->Options = array_merge($this -> Options, $Options);

       $this->processRawCard($RawData);
    } // --construct()

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
    
    /**
     * Add the proferred $property to this VCard.
     * If the property is defined as requiring a single value, then the
     * passed property will replace any existing property by the same name,
     * otherwise, it is added to the list of values for that property.
     * The uid property is handled specially, resulting in a call to setUID(..)
     * and being discarded.
     * @param \EVought\vCardTools\Property $property
     * @return \EVought\vCardTools\VCard $this
     */
    public function push(Property $property)
    {
        if ('uid' === $property->getName())
            $this->setUID($property->getValue());
        if ($property->getSpecification()->requiresSingleValue())
            $this->Data[$property->getName()] = $property;
        else
            $this->Data[$property->getName()][] = $property;
        
        return $this;
    }
    
    public static function builder($propName)
    {
        $specification = self::getSpecification($propName);
        if (null === $specification)
            throw new \DomainException(
                    $propName . ' is not a defined property.' );
        return $specification->getBuilder();
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
    private function getCardBody($text)
    {
        $fragments = [];
        $matches = \preg_match(
            '/^BEGIN:VCARD\nVERSION:(?P<version>\d+\.\d+)\n(?P<body>.*)(?P<end>END:VCARD\n)$/s',
                    $text, $fragments );
        if (1 !== $matches)
            throw new \DomainException('Malformed VCard');
        return $fragments;
    }
    
    /**
     * Handle the legacy AGENT property by parsing and storing the embedded
     * VCard.
     * @param string $agentText
     */
    protected function handleAgent($agentText)
    {
        $ClassName = \get_class($this);
        
        // Unescape embedded special characters (e.g. comma, newline) so they
        // can be parsed.
        $unescaped = self::unescape($agentText);
        
        $agent = new $ClassName(false, $unescaped);
        if (!isset($this -> Data['agent']))
        {
            $this -> Data['agent'] = [];
        }
	$this -> Data['agent'][] = $agent;
    }

    /**
     * Parsing loop for one raw vCard. Sets appropriate internal properties.
     * @param string $rawData Not null.
     */
    protected function processRawCard($rawData)
    {
    	\assert(null !== $rawData);
    	\assert(\is_string($rawData));
        
        // Make newlines consistent, spec requires CRLF, but PHP often strips
        // carriage returns before data gets to us, so we can't depend on it.
        $fixNewlines = \str_replace(["\r\n", "\r"], "\n", $rawData);
    	
        $components = $this->getCardBody($fixNewlines);
        
        if ('2.1' === $this->version)
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

            // FIXME: Deal with COMMA properties
            // FIXME: Deal gracefully with unknown and X-properties
            if (!self::isSpecified($vcardLine->getName()))
                throw new \DomainException(
                    $vcardLine->getName() . ' is not a defined property.');
            
            $builder = self::getSpecification($vcardLine->getName())
                        ->getBuilder();
            $builder->setFromVCardLine($vcardLine);
            
            $property = $builder->build();
            $this->push($property);
        }
        
        if (\array_key_exists('uid', $this->Data))
        {
            $this->uid = $this->Data['uid']->getValue();
            unset($this->Data['uid']);
        }
    } // processSingleRawCard()

    /**
     * Magic method to get the various vCard properties as object members, e.g.
     *	a call to $vCard -> N gets the "N" property
     *
     * @param string $key The name of the property to get. Not null.
     *
     * @return Properties[]|Property|null If no property by that name is set, return
     * null. If a single value is required for the given
     * property name, return the Property, otherwise return an array of
     * Properties.
     */
    public function __get($key)
    {
    	assert(null !== $key);
    	
        $keyLower = strtolower($key);
        if (!array_key_exists($keyLower, $this->Data))
            return null;
        if ('uid' === $keyLower)
        {
            return $this->getSpecification($keyLower)
                ->getBuilder()->setValue($this->checkSetUID())->build();
        }
        return $this->Data[$keyLower];
    } // __get()
    
    public function __set($name, $value)
    {
        \assert(null !== $name);
        $specification = $this->getSpecification($name);
        if (null === $specification)
            throw new \DomainException($name . ' is not a defined property.');
        if ('uid' === $name)
        {
            if ($value === null)
            {
                $this->uid = null;
            } else {
                \assert($value instanceof Property);
                $this->uid = $value->getValue();
            }
            return;
        }
        if (null === $value)
        {
            unset($this->Data[$name]);
            return;
        }
        
        if ($specification->allowsMultipleValues())
        {
            if (!\is_array($value))
                throw new \DomainException($name . ' takes multiple values.');
            foreach ($value as $property)
            {
                if (!($property instanceof Property))
                    throw new \DomainException('Not a property.');
                if (!($property->getName() === $name))
                    throw new \DomainException(
                        $property->getName()
                        . ' Cannot be assigned to property ' . $name);
            }
        } else {
            if (!($value instanceof Property))
                throw new \DomainException('Not a property.');
            if (!($value->getName() === $name))
                throw new \DomainException(
                    $value->getName() . ' Cannot be assigned to property '
                    . $name);
        }
        $this->Data[$name] = $value;
    }

    /**
     * Return the unique ID of this contact, if one has been set.
     * @return string|null Description
     * @see setUID()
     * @see checkSetUID()
     */
    public function getUID()
    {
        return $this->uid;
    }
    
    /**
     * Sets the Unique ID for this VCard. If no UID is provided, a new
     * RFC 4122-compliant UUID will be generated.
     * @param string $uid The UID to set. Defaults to a newly-generated
     * version 1 UUID as a urn. UIDs must uniquely identify
     * the object the card represents.
     * @see https://tools.ietf.org/html/rfc6350#section-6.7.6
     * @return string The new uid value.
     */
    public function setUID($uid = null)
    {
        if (empty($uid))
        {
            $uid = Uuid::uuid1()->getUrn();
        }
        $this->uid = $uid;
        return $uid;
    }
    
    /**
     * Sets the Unique ID for this VCard *only if it does not have one already*.
     * If no UID is set and none is provided with this call, generates a new one
     * by calling setUID(..). This is intended to be used just prior to external
     * storage to ensure that an identifier has been set *somewhere* without
     * clobbering if it has.
     * @param string $uid
     * @return string The new uid value.
     * @see VCard::setUID()
     */
    public function checkSetUID($uid = null)
    {
        if (!empty($this->uid))
            return $this->uid;
        else
            return $this->setUID($uid);
    }
    
    /**
     * Clear all values of the named element.
     * @param string $key The property to unset. Not null.
     */
    public function __unset($key)
    {
    	assert(null !== $key);
    	assert(is_string($key));
    	
        if (array_key_exists($key, $this->Data))
	    unset($this->Data[$key]);
	    return $this;
    } // __unset()

    /**
     * Return true if the named element has at least one value,
     * false otherwise.
     * @param string $key The name of the property to test. Not null.
     * @return bool
     */
    public function __isset($key)
    {
    	assert(null !== $key);
    	assert(is_string($key));
    	
        return isset($this->Data[$key]);
    } // __isset()

    /**
     * If FN is not set, set it appropriately from either the
     * individual or organization name (RFC says FN should not
     * be empty).
     * Use this just before saving or displaying the record using
     * anything other than the toString() method.
     * @return VCard $this for method chaining.
     */
    public function setFNAppropriately()
    {
        // FIXME: #63 : Property __toString() v. output
        // FIXME: #64 : Get PREFerred Property
        if ( (!(\array_key_exists('fn', $this->Data)))
             || empty($this->Data["fn"]->getValue()) )
        {
            $fullname = '';
	    if ( \array_key_exists("kind", $this->Data)
		 && $this->Data["kind"]->getValue() === "organization" )
	    {
                if (\array_key_exists('org', $this->Data))
                {
                    $org = $this->Data['org'][0];
                    $fullname = \implode(' ', $org->getValue());
                }
            }
	    if ( \array_key_exists("kind", $this->Data)
		 && $this->Data["kind"]->getValue() === "organization" )
	    {
                if (\array_key_exists('n', $this->Data))
                {
                    $org = $this->Data['n'];
                    $fullname = \implode(' ', $n->getValue());
                }
            }
            $this->Data["fn"] = $this->getSpecification('fn')->getBuilder()
                    ->setValue(trim($fullname))->build();
        }
        return $this;
    } // setFNAppropriately()

    /**
     * Magic method for getting vCard content out
     *
     * @return string Raw vCard content
     * @todo #63 : Differentiate between string conversion and output.
     * @todo #65 : Output folding as per RFC6350
     */
    public function __toString()
    {
        $this->setFNAppropriately();

	$text = 'BEGIN:VCARD'. self::endl;
	$text .= 'VERSION:'.self::VERSION . self::endl;
        
        $this->checkSetUID();
        $text .= 'UID:' . $this->uid . self::endl;

        // FIXME: Remove the newlines in Property::__toString and add them here.
	foreach ($this->Data as $key=>$values)
	{
            // FIXME: #63 : Property __toString() v. output
	    if (!\is_array($values))
 	    {
		$text .= (string) $values;
		continue;
	    }
 
	    foreach ($values as $value)
	    {
		$text .= (string) $value;
            }
        }

	$text .= 'END:VCARD'.self::endl;
	return $text;
    } // __toString()

    // !Helper methods

    /**
     * Removes the escaping slashes from the text.
     *
     * @access private
     *
     * @param string $Text Text to prepare. Not null.
     *
     * @return string Resulting text.
     */
    public static function unescape($Text)
    {
        self::initSpecifications();
    	assert(null !== $Text);
    	assert(is_string($Text));
    	
        return stripcslashes($Text);
    }

    /**
     * Adds escaping slashes to text to conform with RFC6350.
     * Must be done prior to raw vcard output.
     * @access private
     *
     * @param string $text Text to prepare. Not null.
     *
     * @return string Resulting text.
     */
    public static function escape($text)
    {
    	assert(null !== $text);
    	assert(is_string($text));
    	
        return addcslashes($text, "\\\n,:;");
    }
    
    // !Interface methods

    /**
     * Reset the interator.
     * @see Iterator::rewind()
     */
    public function rewind()
    {
        reset($this -> Data);
    }

    /**
     * Return the value at the current iterator position.
     * @see Iterator::current()
     */
    public function current()
    {
        return current($this -> Data);
    }

    /**
     * Advance the iterator.
     * @see Iterator::next()
     */
    public function next()
    {
        return next($this -> Data);
    }

    /**
     * Is the current iterator position valid?
     * @see Iterator::valid()
     */
    public function valid()
    {
        return ($this -> current() !== false);
    }

    /**
     * Return the key at the current iterator position.
     * @see Iterator::key()
     */
    public function key()
    {
        return key($this -> Data);
    }

    /**
     * @param string $key The name of the property to test. Not null.
     * @return bool True if the specified key is a single value VCard element,
     * false otherwise.
     * @throws \DomainException If the property is not defined.
     * @deprecated Call VCard::getSpecification($key)->requiresSingleValue().
     */
    public static function keyIsSingleValueElement($key)
    {
        self::initSpecifications();
    	assert(null !== $key);
    	assert(is_string($key));
    	
        if (!self::isSpecified($key))
            throw new \DomainException($key . ' is not a defined property.');
        return self::getSpecification($key)->requiresSingleValue();
    }

    /**
     * @param string $key The name of the property to test. Not null.
     * @return bool True if the specified key is a multiple-value VCard element,
     * (is able to contain multiple values on the same line separated by commas) 
     * false otherwise.
     * @throws \DomainException If the property is not defined.
     * @deprecated Call VCard::getSpecification($key)->allowsCommaValues.
     */
    public static function keyIsMultipleValueElement($key)
    {
    	assert(null !== $key);
    	assert(is_string($key));
    	
        if (!self::isSpecified($key))
            throw new \DomainException($key . ' is not a defined property.');
        return self::getSpecification($key)->allowsCommaValues();
    }

    /**
     * @param string $key The name of the property to test. Not null.
     * @return bool True if the specified key is a structured VCard element,
     * false otherwise.
     * @throws \DomainException If the property is not defined.
     * @deprecated Check the type of a Property or PropertyBuilder.
     */
    public static function keyIsStructuredElement($key)
    {
    	assert(null !== $key);
    	assert(is_string($key));
    	
        if (!self::isSpecified($key))
            throw new \DomainException($key . ' is not a defined property.');
        $constraints = self::getSpecification($key)->getConstraints();
        
        return array_key_exists('allowedFields', $constraints);
    }

    /**
     * Returns true if a named property is a file property (it potentially
     * contains a blob or reference to external data), false otherwise.
     * @param string $key The name of the property to test. Not null.
     * @throws \DomainException If the property is not defined.
     * @return boolean
     * @deprecated check the type of the Property or PropertyBuilder.

     */
    public static function keyIsFileElement($key)
    {
    	assert(null !== $key);
    	assert(is_string($key));
    	
        if (!self::isSpecified($key))
            throw new \DomainException($key . ' is not a defined property.');
        return self::getSpecification($key)->getBuilder() instanceof DataPropertyBuilder;
    }
    
    /**
     * Returns true if-and-only-if $key names a type-able VCard property.
     * If this returns true, then keyAllowedTypes($key) shall return the
     * types defined for the name property.
     * @param string $key The name of the property to test.
     * @throws \DomainException If the property is not defined.
     * @return bool
     * @deprecated Check the type of a Property or PropertyBuilder.
     */
    public static function keyIsTypeAble($key)
    {
        assert(null !== $key);
        assert(is_string($key));
        
        if (!self::isSpecified($key))
            throw new \DomainException($key . ' is not a defined property.');
        $constraints = self::getSpecification($key)->getConstraints();
        
        return array_key_exists('allowedTypes', $constraints);
    }
    
    /**
     * Returs the types allowed for a given type-able property, identified by
     * $key.
     * @param string $key The name of the property, not null.
     * keyIsTypeAble($key) must be true.
     * @return array An array of allowed type names.
     * @throws \DomainException If the property is not defined or is not a
     * type-able property.
     * @deprecated Query the PropertySpecification via Property or
     * PropertyBuilder.
     */
    public static function keyAllowedTypes($key)
    {
        assert(null !== $key);
        assert(is_string($key));
        if (!self::isSpecified($key))
            throw new \DomainException($key . ' is not a defined property.');
        $constraints = self::getSpecification($key)->getConstraints();
        
        if (!array_key_exists('allowedTypes', $constraints))
            throw new \DomainException($key . ' is not a typed property.');
        return $constraints['allowedTypes'];
    }
    
    /**
     * Returns the fields defined for the structured property identified by
     * $key.
     * @param string $key The name of the property. Not null.
     * keyIsStructuredElement($key) must be true.
     * @return array An array of allowed field names.
     * @throws \DomainException If the requested key is not defined or does not
     * represent a structured property.
     * @deprecated Query the PropertySpecification via Property or
     * PropertyBuilder.
     */
    public static function keyAllowedFields($key)
    {
        assert(null !== $key);
        assert(is_string($key));
        
        if (!self::isSpecified($key))
            throw new \DomainException($key . ' is not a defined property.');
        $constraints = self::getSpecification($key)->getConstraints();
        
        if (!array_key_exists('allowedFields', $constraints))
            throw new \DomainException($key . ' is not a structured property.');
        return $constraints['allowedFields'];
    }
} // VCard

