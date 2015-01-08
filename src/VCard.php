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
class VCard implements PropertyContainer
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
     * @var Properties[] The collection of Properties.
     */
    private $data = [];
    
    /**
     * The unique ID for this contact.
     * @var string
     */
    private $uid;
    
    /**
     * The index of a property array currently being iterated over, if
     * applicable.
     * @var int
     */
    private $current_index = 0;

    private static function initSpecifications()
    {
        if (null !== self::$specifications) return;
        
        // https://tools.ietf.org/html/rfc6350#section-6.1.3
        self::registerSpecification(
            new PropertySpecification(
                'source',
                PropertySpecification::MULTIPLE_PROPERTY,
                __NAMESPACE__ . '\MediaTypePropertyBuilderImpl',
                PropertySpecification::$cardinalities['Zero To N'],
                [
                    'allowedValueTypes'=>['uri'],
                    'valueTypeDefault'=>'uri'
                ]
            )
        );
        //https://tools.ietf.org/html/rfc6350#section-6.1.4
        self::registerSpecification(
            new PropertySpecification(
                'kind',
                PropertySpecification::SINGLE_PROPERTY,
                __NAMESPACE__ . '\SimplePropertyBuilder',
                PropertySpecification::$cardinalities['Zero or One'],
                [
                    'allowedValueTypes'=>['text'],
                    'valueTypeDefault'=>'text'
                ]
            )
        );
        // https://tools.ietf.org/html/rfc6350#section-6.1.5
        self::registerSpecification(
            new PropertySpecification(
                'xml',
                PropertySpecification::MULTIPLE_PROPERTY,
                __NAMESPACE__ . '\SimplePropertyBuilder',
                PropertySpecification::$cardinalities['Zero To N'],
                [
                    'allowedValueTypes'=>['text'],
                    'valueTypeDefault'=>'text'
                ]
            )
        );
        // https://tools.ietf.org/html/rfc6350#section-6.2.1
        // FN is typed according to spec. No idea why.
        self::registerSpecification(
            new PropertySpecification(
                'fn',
                PropertySpecification::MULTIPLE_PROPERTY,
                __NAMESPACE__ . '\TypedPropertyBuilderImpl',
                PropertySpecification::$cardinalities['One To N'],
                [
                    'allowedValueTypes'=>['text'],
                    'valueTypeDefault'=>'text',
                    'allowedTypes'=>['work', 'home']
                ]
            )
        );
        // https://tools.ietf.org/html/rfc6350#section-6.2.2
        self::registerSpecification(
            new PropertySpecification(
                'n',
                PropertySpecification::MULTIPLE_PROPERTY,
                __NAMESPACE__ . '\StructuredPropertyBuilderImpl',
                PropertySpecification::$cardinalities['Zero or One'],
                [
                    'allowedValueTypes'=>['text'],
                    'valueTypeDefault'=>'text',
                    'allowedFields'=>['FamilyName', 'GivenName',
                        'AdditionalNames', 'Prefixes', 'Suffixes']
                ]
            )
        );
        // https://tools.ietf.org/html/rfc6350#section-6.2.3
        self::registerSpecification(
            new PropertySpecification(
                'nickname',
                PropertySpecification::MULTIPLE_PROPERTY,
                __NAMESPACE__ . '\SimplePropertyBuilder',
                PropertySpecification::$cardinalities['Zero To N'],
                [
                    'allowedValueTypes'=>['text'],
                    'valueTypeDefault'=>'text'
                ]
            )
        );
        // https://tools.ietf.org/html/rfc6350#section-6.2.4
        self::registerSpecification(
            new PropertySpecification(
                'photo',
                PropertySpecification::MULTIPLE_PROPERTY,
                __NAMESPACE__ . '\DataPropertyBuilder',
                PropertySpecification::$cardinalities['Zero To N'],
                [
                    'allowedValueTypes'=>['uri'],
                    'valueTypeDefault'=>'uri',
                    'allowedTypes'=>['work', 'home']
                ]
            )
        );
        // https://tools.ietf.org/html/rfc6350#section-6.2.5
        self::registerSpecification(
            new PropertySpecification(
                'bday',
                PropertySpecification::MULTIPLE_PROPERTY,
                __NAMESPACE__ . '\SimplePropertyBuilder',
                PropertySpecification::$cardinalities['Zero or One'],
                [
                    'allowedValueTypes'=>['date-and-or-time','text'],
                    'valueTypeDefault'=>'date-and-or-time'
                ]
            )
        );
        // https://tools.ietf.org/html/rfc6350#section-6.2.6
        self::registerSpecification(
            new PropertySpecification(
                'anniversary',
                PropertySpecification::MULTIPLE_PROPERTY,
                __NAMESPACE__ . '\SimplePropertyBuilder',
                PropertySpecification::$cardinalities['Zero or One'],
                [
                    'allowedValueTypes'=>['date-and-or-time','text'],
                    'valueTypeDefault'=>'date-and-or-time'
                ]
            )
        );
        // https://tools.ietf.org/html/rfc6350#section-6.2.7
        self::registerSpecification(
            new PropertySpecification(
                'gender',
                PropertySpecification::SINGLE_PROPERTY,
                __NAMESPACE__ . '\StructuredPropertyBuilderImpl',
                PropertySpecification::$cardinalities['Zero or One'],
                [
                    'allowedValueTypes'=>['text'],
                    'valueTypeDefault'=>'text',
                    'allowedFields'=>['Sex', 'Text']
                ]
            )
        );
        // https://tools.ietf.org/html/rfc6350#section-6.3.1
        self::registerSpecification(
            new PropertySpecification(
                'adr',
                PropertySpecification::MULTIPLE_PROPERTY,
                __NAMESPACE__ . '\TypedStructuredPropertyBuilder',
                PropertySpecification::$cardinalities['Zero To N'],
                [
                    'allowedValueTypes'=>['text'],
                    'valueTypeDefault'=>'text',
                    'allowedTypes'=>[ 'dom', 'intl', 'postal', 'parcel',
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
                PropertySpecification::MULTIPLE_PROPERTY,
                __NAMESPACE__ . '\TypeMediaTypePropertyBuilder',
                PropertySpecification::$cardinalities['Zero To N'],
                [
                    'allowedValueTypes'=>['text', 'uri'],
                    'valueTypeDefault'=>'text',
                    'allowedTypes'=>['home', 'msg', 'work', 'voice', 'fax', 
                       'cell', 'video', 'pager', 'bbs', 'modem', 'car', 
                       'isdn', 'pcs']
                ]
            )
        );
        // https://tools.ietf.org/html/rfc6350#section-6.4.2
        self::registerSpecification(
            new PropertySpecification(
                'email',
                PropertySpecification::MULTIPLE_PROPERTY,
                __NAMESPACE__ . '\TypedPropertyBuilderImpl',
                PropertySpecification::$cardinalities['Zero To N'],
                [
                    'allowedValueTypes'=>['text'],
                    'valueTypeDefault'=>'text',
                    'allowedTypes'=>['internet', 'x400']
                ]
            )
        );
        // https://tools.ietf.org/html/rfc6350#section-6.4.3
        self::registerSpecification(
            new PropertySpecification(
                'impp',
                PropertySpecification::MULTIPLE_PROPERTY,
                __NAMESPACE__ . '\TypeMediaTypePropertyBuilder',
                PropertySpecification::$cardinalities['Zero To N'],
                [
                    'allowedValueTypes'=>['uri'],
                    'valueTypeDefault'=>'uri',
                    'allowedTypes'=>['personal', 'business', 'home', 'work',
                    'mobile']
                ]
            )
        );
        // https://tools.ietf.org/html/rfc6350#section-6.4.4
        self::registerSpecification(
            new PropertySpecification(
                'language',
                PropertySpecification::MULTIPLE_PROPERTY,
                __NAMESPACE__ . '\TypedPropertyBuilderImpl',
                PropertySpecification::$cardinalities['Zero To N'],
                [
                    'allowedValueTypes'=>['language-tag'],
                    'valueTypeDefault'=>'language-tag',
                    'allowedTypes'=>['work', 'home']
                ]
            )
        );
        //https://tools.ietf.org/html/rfc6350#section-6.5.1
        self::registerSpecification(
            new PropertySpecification(
                'tz',
                PropertySpecification::MULTIPLE_PROPERTY,
                __NAMESPACE__ . '\TypeMediaTypePropertyBuilder',
                PropertySpecification::$cardinalities['Zero To N'],
                [
                    'allowedValueTypes'=>['text', 'uri', 'utc-offset'],
                    'valueTypeDefault'=>'text',
                    'allowedTypes'=>['work', 'home']
                ]
            )
        );
        // https://tools.ietf.org/html/rfc6350#section-6.5.2
        self::registerSpecification(
            new PropertySpecification(
                'geo',
                PropertySpecification::MULTIPLE_PROPERTY,
                __NAMESPACE__ . '\TypeMediaTypePropertyBuilder',
                PropertySpecification::$cardinalities['Zero To N'],
                [
                    'allowedValueTypes'=>['uri'],
                    'valueTypeDefault'=>'uri',
                    'allowedTypes'=>['work', 'home']
                ]
            )
        );
        // https://tools.ietf.org/html/rfc6350#section-6.6.1
        self::registerSpecification(
            new PropertySpecification(
                'title',
                PropertySpecification::MULTIPLE_PROPERTY,
                __NAMESPACE__ . '\TypedPropertyBuilderImpl',
                PropertySpecification::$cardinalities['Zero To N'],
                [
                    'allowedValueTypes'=>['text'],
                    'valueTypeDefault'=>'text',
                    'allowedTypes'=>['work', 'home']
                ]
            )
        );
        // https://tools.ietf.org/html/rfc6350#section-6.6.2
        self::registerSpecification(
            new PropertySpecification(
                'role',
                PropertySpecification::MULTIPLE_PROPERTY,
                __NAMESPACE__ . '\TypedPropertyBuilderImpl',
                PropertySpecification::$cardinalities['Zero To N'],
                [
                    'allowedValueTypes'=>['text'],
                    'valueTypeDefault'=>'text',
                    'allowedTypes'=>['work', 'home']
                ]
            )
        );
        // https://tools.ietf.org/html/rfc6350#section-6.6.3
        self::registerSpecification(
            new PropertySpecification(
                'logo',
                PropertySpecification::MULTIPLE_PROPERTY,
                __NAMESPACE__ . '\DataPropertyBuilder',
                PropertySpecification::$cardinalities['Zero To N'],
                [
                    'allowedValueTypes'=>['uri'],
                    'valueTypeDefault'=>'uri',
                    'allowedTypes'=>['work', 'home']
                ]
            )
        );
        // https://tools.ietf.org/html/rfc6350#section-6.6.4
        self::registerSpecification(
            new PropertySpecification(
                'org',
                PropertySpecification::MULTIPLE_PROPERTY,
                __NAMESPACE__ . '\TypedStructuredPropertyBuilder',
                PropertySpecification::$cardinalities['Zero To N'],
                [
                    'allowedValueTypes'=>['text'],
                    'valueTypeDefault'=>'text',
                    'allowedTypes'=>['work', 'home'],
                    'allowedFields'=>['Name', 'Unit1', 'Unit2']
                ]
            )
        );
        //https://tools.ietf.org/html/rfc6350#section-6.6.5
        self::registerSpecification(
            new PropertySpecification(
                'member',
                PropertySpecification::MULTIPLE_PROPERTY,
                __NAMESPACE__ . '\MediaTypePropertyBuilderImpl',
                PropertySpecification::$cardinalities['Zero To N'],
                [
                    'allowedValueTypes'=>['uri'],
                    'valueTypeDefault'=>'uri'
                ]
            )
        );
        // https://tools.ietf.org/html/rfc6350#section-6.6.6
        self::registerSpecification(
            new PropertySpecification(
                'related',
                PropertySpecification::MULTIPLE_PROPERTY,
                __NAMESPACE__ . '\TypedPropertyBuilderImpl',
                PropertySpecification::$cardinalities['Zero To N'],
                [
                    'allowedValueTypes'=>['uri', 'text'],
                    'valueTypeDefault'=>'uri',
                    'allowedTypes' => [ 'contact', 'acquaintance', 'friend',
                           'met', 'co-worker', 'colleague', 'co-resident',
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
                PropertySpecification::COMMA_PROPERTY,
                __NAMESPACE__ . '\TypedPropertyBuilderImpl',
                PropertySpecification::$cardinalities['Zero To N'],
                [
                    'allowedValueTypes'=>['text'],
                    'valueTypeDefault'=>'text',
                    'allowedTypes'=>['work', 'home']
                ]
            )
        );
        // https://tools.ietf.org/html/rfc6350#section-6.7.2
        self::registerSpecification(
            new PropertySpecification(
                'note',
                PropertySpecification::MULTIPLE_PROPERTY,
                __NAMESPACE__ . '\TypedPropertyBuilderImpl',
                PropertySpecification::$cardinalities['Zero To N'],
                [
                    'allowedValueTypes'=>['text'],
                    'valueTypeDefault'=>'text',
                    'allowedTypes'=>['work', 'home']
                ]
            )
        );
        // https://tools.ietf.org/html/rfc6350#section-6.7.3
        self::registerSpecification(
            new PropertySpecification(
                'prodid',
                PropertySpecification::SINGLE_PROPERTY,
                __NAMESPACE__ . '\SimplePropertyBuilder',
                PropertySpecification::$cardinalities['Zero or One'],
                [
                    'allowedValueTypes'=>['text'],
                    'valueTypeDefault'=>'text'
                ]
            )
        );
        // https://tools.ietf.org/html/rfc6350#section-6.7.4
        self::registerSpecification(
            new PropertySpecification(
                'rev',
                PropertySpecification::SINGLE_PROPERTY,
                __NAMESPACE__ . '\SimplePropertyBuilder',
                PropertySpecification::$cardinalities['Zero or One'],
                [
                    'allowedValueTypes'=>['timestamp'],
                    'valueTypeDefault'=>'timestamp'
                ]
            )
        );
        // https://tools.ietf.org/html/rfc6350#section-6.7.5
        self::registerSpecification(
            New PropertySpecification(
                'sound',
                PropertySpecification::MULTIPLE_PROPERTY,
                __NAMESPACE__ . '\DataPropertyBuilder',
                PropertySpecification::$cardinalities['Zero To N'],
                [
                    'allowedValueTypes'=>['uri'],
                    'valueTypeDefault'=>'uri',
                    'allowedTypes'=>['work', 'home']
                ]
            )
        );
        // https://tools.ietf.org/html/rfc6350#section-6.7.6
        self::registerSpecification(
            new PropertySpecification(
                'uid',
                PropertySpecification::SINGLE_PROPERTY,
                __NAMESPACE__ . '\SimplePropertyBuilder',
                PropertySpecification::$cardinalities['Zero or One'],
                [
                    'allowedValueTypes'=>['uri', 'text'],
                    'valueTypeDefault'=>'uri'
                ]
            )
        );
        // https://tools.ietf.org/html/rfc6350#section-6.7.7
        self::registerSpecification(
            new PropertySpecification(
                'clientpidmap',
                PropertySpecification::MULTIPLE_PROPERTY,
                __NAMESPACE__ . '\StructuredPropertyBuilderImpl',
                PropertySpecification::$cardinalities['Zero To N'],
                [
                    'allowedValueTypes'=>[],
                    'valueTypeDefault'=>null,
                    'allowedFields'=>['Pid', 'Uri']
                ]
            )
        );
        // https://tools.ietf.org/html/rfc6350#section-6.7.8
        self::registerSpecification(
            new PropertySpecification(
                'url',
                PropertySpecification::MULTIPLE_PROPERTY,
                __NAMESPACE__ . '\TypeMediaTypePropertyBuilder',
                PropertySpecification::$cardinalities['Zero To N'],
                [
                    'allowedValueTypes'=>['uri'],
                    'valueTypeDefault'=>'uri',
                    'allowedTypes'=>['work', 'home']
                ]
            )
        );
        // https://tools.ietf.org/html/rfc6350#section-6.7.9
        self::registerSpecification(
            new PropertySpecification(
                'version',
                PropertySpecification::SINGLE_PROPERTY,
                __NAMESPACE__ . '\SimplePropertyBuilder',
                PropertySpecification::$cardinalities['Exactly One'],
                [
                    'allowedValueTypes'=>['text'],
                    'valueTypeDefault'=>'text'
                ]
            )
        );
        // https://tools.ietf.org/html/rfc6350#section-6.8.1
        self::registerSpecification(
            New PropertySpecification(
                'key',
                PropertySpecification::MULTIPLE_PROPERTY,
                __NAMESPACE__ . '\DataPropertyBuilder',
                PropertySpecification::$cardinalities['Zero To N'],
                [
                    'allowedValueTypes'=>['uri', 'text'],
                    'valueTypeDefault'=>'uri',
                    'allowedTypes'=>['work', 'home']
                ]
            )
        );
        //https://tools.ietf.org/html/rfc6350#section-6.9.1
        self::registerSpecification(
            new PropertySpecification(
                'fburl',
                PropertySpecification::MULTIPLE_PROPERTY,
                __NAMESPACE__ . '\TypedPropertyBuilderImpl',
                PropertySpecification::$cardinalities['Zero To N'],
                [
                    'allowedValueTypes'=>['uri'],
                    'valueTypeDefault'=>'uri',
                    'allowedTypes'=>['work', 'home']]
            )
        );
        // https://tools.ietf.org/html/rfc6350#section-6.9.2
        self::registerSpecification(
            new PropertySpecification(
                'caladruri',
                PropertySpecification::MULTIPLE_PROPERTY,
                __NAMESPACE__ . '\TypeMediaTypePropertyBuilder',
                PropertySpecification::$cardinalities['Zero To N'],
                [
                    'allowedValueTypes'=>['uri'],
                    'valueTypeDefault'=>'uri',
                    'allowedTypes'=>['work', 'home']
                ]
            )
        );
        // https://tools.ietf.org/html/rfc6350#section-6.9.3
        self::registerSpecification(
            new PropertySpecification(
                'caluri',
                PropertySpecification::MULTIPLE_PROPERTY,
                __NAMESPACE__ . '\TypeMediaTypePropertyBuilder',
                PropertySpecification::$cardinalities['Zero To N'],
                [
                    'allowedValueTypes'=>['uri'],
                    'valueTypeDefault'=>'uri',
                    'allowedTypes'=>['work', 'home']
                ]
            )
        );
        // from VCard 3.0, deprecated.
        // https://www.ietf.org/rfc/rfc2426.txt , sec 3.5.4
        // Just store the value; let the caller figure out what they want
        // to do with it.
        self::registerSpecification(
            new PropertySpecification(
                'agent',
                PropertySpecification::SINGLE_PROPERTY,
                __NAMESPACE__ . '\SimplePropertyBuilder',
                PropertySpecification::$cardinalities['Zero To N'],
                [
                    'allowedValueTypes'=>['vcard', 'text', 'uri'],
                    'valueTypeDefault'=>'vcard'
                ]
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
     * @param bool $strict If false and no specification is defined for the
     * requested property, return a generic specification suitable for building
     * unknown extended properties.
     * @return PropertySpecification
     */
    public static function getSpecification($name, $strict = true)
    {
        self::initSpecifications();
        \assert(null !== $name);
        \assert(\is_string($name));
        if (\array_key_exists($name, self::$specifications))
            return self::$specifications[$name];
        elseif (false === $strict)
            return self::getGenericSpecification ($name);
        else
            return null;
    }
    
    public static function getGenericSpecification($name)
    {
        return new PropertySpecification(
            $name,
            PropertySpecification::MULTIPLE_PROPERTY,
            __NAMESPACE__ . '\SimplePropertyBuilder',
            PropertySpecification::$cardinalities['Zero To N']
        );
    }
    
    /**
     * Return an iterator over all Properties in this container which are not
     * defined.
     * @return \Iterator
     */
    public function getUndefinedProperties()
    {
        return new \CallbackFilterIterator($this, function ($current) {
            \assert($current instanceof Property);
            return (VCard::isSpecified($current->getName()) === false);
        });
    }
    
    /**
     * vCard constructor
     */
    public function __construct()
    {        
    } // --construct()
    
    /**
     * Add any number of Property instances to this VCard.
     * If a target property is defined as requiring a single value, then the
     * last passed such property will replace any existing property by the same
     * name, otherwise, it is added to the list of values for that property.
     * The uid property is handled specially, resulting in a call to setUID(..)
     * and being discarded.
     * Arrays or Traversables of (only) Properties will be unpacked and pushed.
     * @param Property|Array|\Traversable $properties,...
     * @return VCard $this
     */
    public function push($properties)
    {
        $items = func_get_args();
        foreach ($items as $item)
            $this->pushOneArg($item);
        return $this;
    }
    
    /**
     * Utility function for push without variable args.
     * Push one Property instance or array|\Traversable of such instances.
     * @return void
     * @param Property|array|\Traversable $item
     * @throws \UnexpectedValueException If any individual value is not a 
     * Property.
     */
    private function pushOneArg($item)
    {
        if ($item instanceof Property)
        {
            if ('uid' === $item->getName())
                $this->setUID($item->getValue());
            elseif ($item->getSpecification()->requiresSingleProperty())
                $this->data[$item->getName()] = $item;
            else
                $this->data[$item->getName()][] = $item;            
        } elseif (is_array($item) || ($item instanceof \Traversable)) {
            foreach ($item as $property)
            {
                $this->pushOneArg($property);
            }
        } else {
            throw new \UnexpectedValueException('Not a property');
        }        
    }
    
    /**
     * Empty this container of all properties.
     * @return self $this
     */
    public function clear()
    {
        $this->data = [];
        $this->clearUID();
        return $this;
    }
    
    /**
     * Return a new builder for the requested property name.
     * @param string $propName The name of the property to return a builder for.
     * @param bool $strict If true, will throw an exception if the requested
     * property has not been defined, otherwise, a generic builder will be
     * returned, suitable for unknown extended properties.
     * @return PropertyBuilder
     * @throws \DomainException If the requested property has not been defined
     * and $strict is true.
     */
    public static function builder($propName, $strict = true)
    {
        $specification = self::getSpecification($propName, $strict);
        if (null === $specification)
            throw new \DomainException(
                    $propName . ' is not a defined property.' );
        return $specification->getBuilder();
    }
    
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
        if ('uid' === $keyLower)
        {
            return $this->getUIDAsProperty();
        }
        if (!array_key_exists($keyLower, $this->data))
            return null;
        return $this->data[$keyLower];
    } // __get()

    /**
     * Magic setter method for value assignment (overloads '=' operator
     * for assignment). Using assignment to set Property values is
     * primarily useful when you want to replace all values of a named
     * property at the same time: __set() clears current values and replaces
     * them with what is passed in (which may be null, in which case this is
     * the equivalent of uset() for a property.
     *
     * Using assignment, however, is less useful for adding values. To use
     * assignment, the caller has to worry about whether the named property
     * requires single or multiple values and the names of the Property
     * instances passed in must match the property being assigned to. To add
     * Property instances to a VCard, it is recommended that push() be used
     * instead.
     * 
     * It should also be noted that assignment does not return a useful value
     * and therefore assignments may not be chained.
     * @param string $name
     * @param Property[]|Property $value If $name refers to a property which
     * requiresSingleValue(), $value shall be a Property, otherwise it may be
     * a Property or anything Traversable which contains Properties.
     * @return void
     * @throws UndefinedPropertyException If the named property is undefined/
     * not permitted (no PropertySpecification available).
     * @throws \DomainException If there is a mismatch between the name of a
     *     Property assigned and the name of the property being set.
     * @throws \UnexpectedValueException If a value or member of a value array
     *     is not a Property.
     * @see push() As a safer method for adding properties.
     */
    public function __set($name, $value)
    {
        \assert(null !== $name);
        \assert(is_string($name));

        if (!( (null === $value)
               || (is_array($value))
               || ($value instanceof \Traversable)
               || ($value instanceof Property) ))
            throw new \UnexpectedValueException(
                'Argument must be a Property, an array, or a \Traversable' );
        
        $specification = $this->getSpecification($name);
        if (null === $specification)
            throw new UndefinedPropertyException(
                $name . ' is not a defined property.' );

        unset($this->data[$name]);
        
        if (null === $value)
        {
            if ('uid' === $name) $this->uid = null;
        } else if ( $specification->requiresSingleProperty()
                || ($value instanceof Property) )
        {
            if (!($value instanceof Property))
                throw new \UnexpectedValueException( $name .
                    ' requiresSingleValue() and $value is not a Property.' );
            if ($name !== $value->getName())
                throw new \DomainException(
                        $value->getName()
                        . ' Cannot be assigned to property ' . $name);
                
            $this->push($value);
        } else {        
            // Does not require a single and is NOT a bare Property
            foreach ($value as $property)
            {
                if (!($property instanceof Property))
                    throw new \UnexpectedValueException('Not a Property.');
                if (!($property->getName() === $name))
                    throw new \DomainException(
                        $property->getName()
                        . ' Cannot be assigned to property ' . $name);
                $this->push($property);
            }
        }
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
     * Note that it is not recommended or possible to set the uid to null
     * using this method (since passing null here will cause a new uid to
     * be automatically generated). It is possible to temporarily clear the uid,
     * but it has to be done explicitly by calling clearUID() to ensure that the
     * caller really intends to set it to no value.
     * @param string $uid The UID to set. Defaults to a newly-generated
     * version 1 UUID as a urn. UIDs must uniquely identify
     * the object the card represents.
     * @see clearUID()
     * @see checkSetUID()
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
     * Explicitly unset uid.
     * This should simply cause the uid to be regenerated on next output, but
     * it may be useful to temporarily clear it in testcases, to do
     * uid-independent comparisons, or when needing to explicitly change the
     * identity of the VCard (so that a new card will not be seen as an
     * update of an older card).
     * @return VCard $this
     */
    public function clearUID()
    {
        $this->uid = null;
        return $this;
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
     * Present the internal UID value as a property which can then be used
     * for formatting or export. This method is used to fake a UID property
     * in some public methods.
     * It has two potential side-effects:
     * 1) checkSetUID is called, so if no uid has been set, a new one will be
     * generated.
     * 2) Identity comparisons between multiple magic uid properties will fail.
     * @return Property
     */
    protected function getUIDAsProperty()
    {
        $builder = self::builder('uid');
        return $builder->setValue($this->checkSetUID())->build();
    }
    
    /**
     * Clear all values of the named element.
     * @param string $key The property to unset. Not null.
     */
    public function __unset($key)
    {
    	assert(null !== $key);
    	assert(is_string($key));
    	
        if (array_key_exists($key, $this->data))
	    unset($this->data[$key]);
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
    	
        return isset($this->data[$key]);
    } // __isset()

    /**
     * If FN is not set, set it appropriately from either the
     * individual or organization name (RFC says FN should not
     * be empty).
     * Use this just before saving or displaying the record.
     * @return VCard $this for method chaining.
     */
    public function setFNAppropriately()
    {
        // FIXME: #64 : Get PREFerred Property
        if ( (!(\array_key_exists('fn', $this->data)))
             && \array_key_exists("kind", $this->data) )
        {
	    if ( ($this->data["kind"]->getValue() === "organization")
                 && \array_key_exists('org', $this->data) )
	    {
                // FIXME: copy LANGUAGE parameter and ALTID?
                foreach ($this->data['org'] as $org)
                    self::builder('fn')->setValue((string) $org)->pushTo($this);
            } elseif ( ($this->data["kind"]->getValue() === "individual")
                 && \array_key_exists('n', $this->data) )
	    {
                // FIXME: copy LANGUAGE parameter and ALTID?
                foreach ($this->data['n'] as $n)
                    self::builder('fn')->setValue((string) $n)->pushTo($this);
            }
        }
        return $this;
    } // setFNAppropriately()

    /**
     * Magic method for getting vCard content out
     *
     * @return string Raw vCard content
     * @deprecated Use output() instead.
     */
    public function __toString()
    {
        return $this->output();
    }

    /**
     * Format VCard as the raw VCard (.vcf) text format.
     * @todo #65 : Output folding as per RFC6350
     * @return string
     */
    public function output()
    {
        $this->setFNAppropriately();

	$text = 'BEGIN:VCARD'. self::endl;
	$text .= 'VERSION:'.self::VERSION . self::endl;
        
        $text .= $this->getUIDAsProperty()->output();

        // FIXME: Remove the newlines in Property::__toString and add them here.
	foreach ($this->data as $key=>$values)
	{
	    if (!\is_array($values))
 	    {
		$text .= $values->output();
		continue;
	    }
 
	    foreach ($values as $value)
	    {
		$text .= $value->output();
            }
        }

	$text .= 'END:VCARD'.self::endl;
	return $text;
    }

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
     * Reset the interator. Iterator will loop over all Property instances
     * set for this VCard, with current() returning the current such instance
     * until valid() returns false.
     * @see Iterator::rewind()
     */
    public function rewind()
    {
        reset($this->data);
        $this->current_index = 0;
    }

    /**
     * Return the value at the current iterator position.
     * @see Iterator::current()
     * @return Property
     */
    public function current()
    {
        if (key($this->data) === null) return false;
        
        if (is_array(current($this->data)))
            return current($this->data)[$this->current_index];
        else
            return current($this->data);
    }

    /**
     * Advance the iterator.
     * @see Iterator::next()
     */
    public function next()
    {
        if (is_array(current($this->data)))
        {
            $this->current_index += 1;
            if ($this->current_index === count(current($this->data)))
            {
                $this->current_index = 0;
                next($this->data);
            }
        } else {
            next($this->data);
        }
    }

    /**
     * Is the current iterator position valid?
     * @see Iterator::valid()
     */
    public function valid()
    {
        return ($this->current() !== false);
    }

    /**
     * Return the key at the current iterator position. Currently always
     * returns false. May change once PIDs are implemented.
     * @see Iterator::key()
     */
    public function key()
    {
        return null;
    }
    
    /**
     * Get the total count of Properties in this VCard.
     * This method counts all Property instances, not just the number of
     * property types defined.
     * In other words, if this card contains two ADR properties,
     * $this->adr will return a single array value containing two Property
     * instances and this method will count both of them in the total.
     * @return int
     */
    public function count()
    {
        // NOTE: Cannot use \COUNT_RECURSIVE because that counts the arrays
        // as well as their contents. We want a count only of Properties.
        $count = 0;
        foreach ($this->data as $key=>$values)
        {
            if (is_array($this->data[$key]))
                $count += count($values);
            else
                $count += 1;
        }
        return $count;
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
        return self::getSpecification($key)->requiresSingleProperty();
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
        return self::getSpecification($key)->allowsCommaProperties();
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

