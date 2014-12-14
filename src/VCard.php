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
class VCard implements \Countable, \Iterator
{
    const MODE_SINGLE = 'single';
    const MODE_MULTIPLE = 'multiple';

    const endl = "\n";

    /**
     * @var string Current object mode - single or multiple
     * (for a single vCard within a file and multiple combined vCards)
     */
    private $Mode = self::MODE_SINGLE;  //single, multiple

    /**
     * @var array Internal options container. Options:
     *	bool Collapse: If true, elements that can have multiple values but have only a single value are returned as that value instead of an array
     *		If false, an array is returned even if it has only one value.
     */
    private $Options = array( 'Collapse' => false );

    /**
     * @var array Internal data container. Contains vCard objects for
     * multiple vCards and just the data for single vCards.
     */
    private $Data = array();

    /**
     * Parts of structured properties according to the spec.
     * @var array
     */
    private static $Spec_StructuredElements
        = array(
            'n' => array(
                    'LastName', 'FirstName', 'AdditionalNames',
                    'Prefixes', 'Suffixes' ),

	    'adr' => array( 'POBox', 'ExtendedAddress', 'StreetAddress', 
                            'Locality', 'Region', 'PostalCode', 'Country' ),

	    'org' => array('Name', 'Unit1', 'Unit2')
		);

    /**
     * Properties which may have multiple values *within a single raw vCard
     * line* separated by commas, according to spec.
     * @var array
     */
    private static $Spec_MultipleValueElements = array('nickname', 'categories');

    /**
     * Type parameter values allowed for given properties according to spec.
     * @var array
     */
    private static $Spec_ElementTypes
        = [
	    'email' => ['internet', 'x400', 'pref'],

	    'adr' => [ 'dom', 'intl', 'postal', 'parcel',
                            'home', 'work', 'pref' ],

	    'label' => [ 'dom', 'intl', 'postal', 'parcel',
			 'home', 'work', 'pref' ],

	    'tel' => [ 'home', 'msg', 'work', 'pref', 'voice', 'fax', 
                       'cell', 'video', 'pager', 'bbs', 'modem', 'car', 
                       'isdn', 'pcs' ],

	    'impp' => [ 'personal', 'business', 'home', 'work', 'mobile', 
                             'pref' ],

	    'note' => ['home', 'work'],

            'url'  => ['home', 'work'],
            
            'org'  => ['home', 'work'],
            
            'geo'  => ['home', 'work'],
            
            'key'  => ['home', 'work'],
            
            'related' => [ 'contact', 'acquaintance', 'friend', 'met',
                           'co-worker', 'colleague', 'co-resident',
                           'neighbor', 'child', 'parent', 'sibling',
                           'spouse', 'kin', 'muse', 'crush', 'date',
                           'sweetheart', 'me', 'agent', 'emergency' ]
            ];
    
    /**
     * Properties which may contain a BLOB or associated external data.
     * @var array
     */
    private static $Spec_FileElements = array('photo', 'logo', 'sound', 'key');

    /**
     * Properties with only (zero or) one value according to spec
     * @var array
     */
    private static $Spec_SingleElements
        = array('fn', 'kind', 'bday', 'anniversary', 'prodid', 'rev', 'uid');

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

       $RawData = $this->checkRawDataAndSetMode($RawData);

       // In multiple card mode the raw text is split at card 
       // beginning markers and each
       // fragment is parsed in a separate vCard object.
       if ($this -> Mode == self::MODE_MULTIPLE)
           $this->processMultipleRawCards($RawData);
       else
           $this->processSingleRawCard($RawData);
    } // --construct()

    /**
     * Checks the raw VCard data for major errors (such as BEGIN and
     * END markers) and throws an exception if malformed.
     * Sets mode to single or multiple.
     * @param string $rawData Not null.
     * @throws \Exception if the matching BEGIN and END lines are not found.
     */
    protected function checkRawDataAndSetMode($rawData)
    {
    	assert(null !== $rawData);
    	assert(is_string($rawData));
    	
        // Counting the begin/end separators. If none or the count doesn't match,
        // there is a problem with the file. If there is only one, this is a 
        // single vCard, if more, multiple vCards are combined.
	$Matches = array();
	$vCardBeginCount = preg_match_all( '{^BEGIN\:VCARD}miS', $rawData,       
                                           $Matches );
        $vCardEndCount = preg_match_all('{^END\:VCARD}miS', $rawData, $Matches);

        if (($vCardBeginCount != $vCardEndCount) || !$vCardBeginCount)
	{
            throw new Exception('vCard: invalid vCard');
	}


        $this->Mode = ($vCardBeginCount == 1)
                          ? VCard::MODE_SINGLE : VCard::MODE_MULTIPLE;

        // Removing/changing inappropriate newlines, i.e., all CRs or 
        // multiple newlines are changed to a single newline
        $rawData = str_replace("\r", "\n", $rawData);
        $rawData = preg_replace('{(\n+)}', "\n", $rawData);
	return $rawData;
    } // checkRawDataAndSetMode()

    /**
     * If there are multiple VCards in the raw input, process them
     * recursively by constructing new VCard objects.
     * @param string $rawData Not null.
     */
    protected function processMultipleRawCards($rawData)
    {
    	assert(null !== $rawData);
    	assert(is_string($rawData));
    	
        $rawData = explode('BEGIN:VCARD', $rawData);
        $rawData = array_filter($rawData);

        foreach ($rawData as $SinglevCardRawData)
        {
            // Prepending "BEGIN:VCARD" to the raw string because
            // we exploded on that one.
	    // If there won't be the BEGIN marker in the new object, 
            // it will fail.
	    $SinglevCardRawData = 'BEGIN:VCARD' . "\n" . $SinglevCardRawData;

            $ClassName = get_class($this);
	    $this->Data[] = new $ClassName(false, $SinglevCardRawData);
	}
    } // processMultipleRawCards()

    /**
     * Parsing loop for one raw vCard. Sets appropriate internal properties.
     * @param string $rawData Not null.
     */
    protected function processSingleRawCard($rawData)
    {
    	assert(null !== $rawData);
    	assert(is_string($rawData));
    	
        // Protect the BASE64 final = sign (detected by the line beginning 
        // with whitespace), otherwise the next replace will get rid of it
        $rawData = preg_replace('{(\n\s.+)=(\n)}', '$1-base64=-$2', $rawData);

        // Joining multiple lines that are split with a hard wrap
        // and indicated by an equals sign at the end of line
        // (quoted-printable-encoded values in v2.1 vCards)
        $rawData = str_replace("=\n", '', $rawData);

        // Joining multiple lines that are split with a soft
        // wrap (space or tab on the beginning of the next line
        $rawData = str_replace(array("\n ", "\n\t"), '-wrap-', $rawData);

        // Restoring the BASE64 final equals sign (see a few lines above)
        $rawData = str_replace( "-base64=-\n", "=\n", $rawData );

        $Lines = explode("\n", $rawData);

        foreach ($Lines as $Line)
        {
	    // Lines without colons are skipped because, most
            // likely, they contain no data.
	    if (strpos($Line, ':') === false)
	    {
	        continue;
	    }
					
            // Each line is split into two parts.
            // The key contains the element name and additional 
            // parameters, if present; value is just the value
	    list($Key, $Value) = explode(':', $Line, 2);
	    if (empty($Value)) continue;

	    // Key is transformed to lowercase because, even
            // though the element and parameter names are written
            // in uppercase, it is quite possible that they will
            // be in lower- or mixed case.
	    // The key is trimmed to allow for non-significant
            // WSP characters as allowed by v2.1
	    $Key = strtolower(trim(self::Unescape($Key)));

	    // These two lines can be skipped as they aren't 
            // necessary at all.
	    if ($Key == 'begin' || $Key == 'end')
	    {
	        continue;
	    }

            if ( (strpos($Key, 'agent') === 0)
                 && (stripos($Value, 'begin:vcard') !== false) )
            {
                $ClassName = get_class($this);
		$Value = new $ClassName( false,
                                         str_replace('-wrap-', "\n", $Value) );
                if (!isset($this -> Data[$Key]))
                {
                    $this -> Data[$Key] = array();
                }
		$this -> Data[$Key][] = $Value;
		continue;
            } else {
                $Value = str_replace('-wrap-', '', $Value);
	    }

            $Value = trim(self::Unescape($Value));
	    $Type = array();

	    // Here additional parameters are parsed
	    $KeyParts = explode(';', $Key);
	    $Key = $KeyParts[0];
	    $Encoding = false;

	    if (strpos($Key, 'item') === 0)
	    {
	        $TmpKey = explode('.', $Key, 2);
	        $Key = $TmpKey[1];
	        $ItemIndex = (int)str_ireplace('item', '', $TmpKey[0]);
	    }

	    if (count($KeyParts) > 1)
	    {
	        $Parameters = self::ParseParameters( $Key,
                                    array_slice($KeyParts, 1) );

		foreach ($Parameters as $ParamKey => $ParamValue)
		{
		    switch ($ParamKey)
		    {
		        case 'encoding':
			    $Encoding = $ParamValue;
			    if (in_array($ParamValue, array('b', 'base64')))
			    {
			        //$Value = base64_decode($Value);
			    } elseif ($ParamValue == 'quoted-printable') {
                                // v2.1
				$Value = quoted_printable_decode($Value);
                            }
                            break;
                        case 'charset': // v2.1
                            if ($ParamValue != 'utf-8' && $ParamValue != 'utf8')
                            {
                                $Value = mb_convert_encoding(
                                             $Value, 'UTF-8', $ParamValue );
			    }
			    break;
                        case 'type':
			    $Type = $ParamValue;
			    break;
		    } //switch
	        } //foreach $Parameters
            } // if

            // Checking files for colon-separated additional parameters 
            // (Apple's Address Book does this), for example,
            // "X-ABCROP-RECTANGLE" for photos
            if ( $this->keyIsFileElement($Key)
                     && isset($Parameters['encoding'])
                     && in_array($Parameters['encoding'], array('b', 'base64')) )
            {
		// If colon is present in the value, it must contain Address
                // Book parameters (colon is an invalid character for base64 
                // so it shouldn't appear in valid files)
                if (strpos($Value, ':') !== false)
                {
		    $Value = explode(':', $Value);
		    $Value = array_pop($Value);
                }
	    }

	    // Values are parsed according to their type
            if ($this->keyIsStructuredElement($Key))
	    {
	        $Value = self::ParseStructuredValue($Value, $Key);
                if ($Type)
                {
                    $Value['Type'] = $Type;
                }
            } else {
		if ($this->keyIsMultipleValueElement($Key))
                {
                    $Value = self::ParseMultipleTextValue($Value, $Key);
                }

	        if ($Type)
                {
		    $Value = array('Value' => $Value, 'Type' => $Type);
                }
	    }

	    if (is_array($Value) && $Encoding)
	    {
	        $Value['Encoding'] = $Encoding;
	    }


	    if ($this->keyIsSingleValueElement($Key))
            {
	        $this -> Data[$Key] = $Value;
	    } else {
	        if (!isset($this -> Data[$Key]))
                {
		    $this -> Data[$Key] = array();
	        }

                if ($this->keyIsMultipleValueElement($Key))
                    $this->Data[$Key]
                                    = array_merge($this -> Data[$Key], $Value);
		else
                        $this -> Data[$Key][] = $Value;
            }
        } // foreach $Lines
    } // processSingleRawCard()

    /**
     * Magic method to get the various vCard values as object members, e.g.
     *	a call to $vCard -> N gets the "N" value
     *
     * @param string $Key The name of the property to get. Not null.
     *
     * @return mixed $Value All values of the named property (may return
     * scalar or array).
     */
    public function __get($Key)
    {
    	assert(null !== $Key);
    	
        $Key = strtolower($Key);
        if (isset($this -> Data[$Key]))
        {
            if ($Key == 'agent' || $this->keyIsSingleValueElement($Key))
	    {
	        return $this -> Data[$Key];
	    } elseif ($this->keyIsFileElement($Key)) {
	        $Value = $this -> Data[$Key];

		foreach ($Value as $K => $V)
		{
		    if (is_array($V))
                    {
			if (stripos($V['Value'], 'uri:') === 0)
			{
                            $Value[$K]['Value'] = substr($V, 4);
                            $Value[$K]['Encoding'] = 'uri';
			}
                    }
		}
		return $Value;
            }

            if ( $this -> Options['Collapse']
                 && is_array($this -> Data[$Key])
                 && (count($this -> Data[$Key]) == 1))
            {
                return $this -> Data[$Key][0];
            }
            return $this -> Data[$Key];
	} elseif ($Key == 'Mode') {
            return $this -> Mode;
	}
	return array();
    } // __get()

    /**
     * Magic assignment function.
     * Sets the named element to the requested value, replacing any
     * current value. Note that the nature of
     * the element will determine what needs to be passed as an argument: in
     * the case of a single value element, it will need to be a string
     * and other elements (allowing multiple values), an array.
     * Attempting to (e.g.) add a string to an element
     * accepting multiple values will do Bad Things(tm). This is provided
     * for completeness and because the __call syntax makes it very difficult
     * to construct and add a set of values in a batch (say, loading VCards
     * from a database or POST form) and can have unprectable results.
     * @param string $key The name of the property to set.
     * @param string $value An appropriate value/values for the property.
     * @throws \DomainException if the $value is not appropriately a string,
     * an array, or an array of arrays.
     */
    public function __set($key, $value)
    {
    	assert(null !== $key);
    	assert(is_string($key));
    	
	if (empty($value))
        {
            unset($this->Data[$key]);
            return;
        }

	if ($this->keyIsSingleValueElement($key))
        {
            if (!is_string($value))
                throw new \DomainException( "Elements constraint violation: "
                                           . $key
                                           . " requires a single value." );
        } else {

	    if (!is_array($value))
                throw new \DomainException( "Elements constraint violation: "
                                           . $key
                                           . " requires an array of values." );
            if ($this->keyIsStructuredElement($key))
            {
                $result = \array_filter($value, '\is_array');
                if (\count($result) != \count($value))
                    throw new \DomainException( "Elements constraint violation: "
                                                . $key
                                           . " requires an array of arrays." );
            }
        }
        $this->Data[$key] = $value;
    } // __set()

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
        if (array_key_exists('uid', $this->Data))
                return $this->Data['uid'];
        else
                return $this->setUID($uid);
    }
    
    /**
     * Saves an embedded file
     *
     * @param string $Key Not null.
     * @param int $Index of the file, defaults to 0
     * @param string $TargetPath where the file should be saved, including
     * the filename.
     *
     * @return bool Operation status
     */
    public function SaveFile($Key, $Index = 0, $TargetPath = '')
    {
    	assert(null !== $Key);
    	assert(is_string($Key));
    	
	if (!isset($this -> Data[$Key]))
	{
	    return false;
	}
        if (!isset($this -> Data[$Key][$Index]))
        {
	    return false;
        }

	// Returing false if it is an image URL
	if (stripos($this -> Data[$Key][$Index]['Value'], 'uri:') === 0)
	{
	    return false;
	}

	if ( is_writable($TargetPath)
	     || ( !file_exists($TargetPath)
                  && is_writable(dirname($TargetPath)) ) )
	{
	    $RawContent = $this -> Data[$Key][$Index]['Value'];
	    if ( isset($this -> Data[$Key][$Index]['Encoding'])
                 && $this -> Data[$Key][$Index]['Encoding'] == 'b' )
	    {
	        $RawContent = base64_decode($RawContent);
	    }
	    $Status = file_put_contents($TargetPath, $RawContent);
	    return (bool)$Status;
        } else {
	    throw new Exception( 'vCard: Cannot save file ('
                                 . $Key . '), target path not writable ('
                                 . $TargetPath.')' );
	}
	return false;
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
	    unset($this->Data["$key"]);
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
     * Magic method for adding data to the vCard
     *
     * @param string $Key The name of the property to set values on.
     * @param array $Arguments Method call arguments. First element is value.
     *
     * @return VCard Current object for method chaining
     */
    public function __call($Key, Array $Arguments)
    {
    	assert(null !== $Key);
    	assert(is_string($Key));
    	
	$Key = strtolower($Key);

	$Value = isset($Arguments[0]) ? $Arguments[0] : false;

	if ($this->keyIsSingleValueElement($Key))
	{
	    $this -> Data[$Key] = $Value;
	    return $this;
	}

	if (!isset($this -> Data[$Key]))
	{
	    $this -> Data[$Key] = array();
	}

	if (count($Arguments) > 1)
	{
	    $Types = array_values(array_slice($Arguments, 1));

	    if ( $this->keyIsStructuredElement($Key)
                 && ( in_array($Arguments[1], self::$Spec_StructuredElements[$Key])
                        || 'Type' === $Arguments[1] )
	       )
	    {
		$LastElementIndex = 0;

		if (count($this -> Data[$Key]))
		{
		    $LastElementIndex = count($this -> Data[$Key]) - 1;
		}

		if (isset($this -> Data[$Key][$LastElementIndex]))
		{
		    if (empty($this -> Data[$Key][$LastElementIndex][$Types[0]]))
		    {
			$this->Data[$Key][$LastElementIndex][$Types[0]] = $Value;
		    } else {
			$LastElementIndex++;
		    }
		}

		if (!isset($this -> Data[$Key][$LastElementIndex]))
		{
		    $this->Data[$Key][$LastElementIndex] = array(
							$Types[0] => $Value
						);
		}
            } elseif (isset(self::$Spec_ElementTypes[$Key])) {
                $this -> Data[$Key][] = array(
					'Value' => $Value,
					'Type' => $Types
					);
            }
	} elseif ($Value) {
	    $this -> Data[$Key][] = $Value;
	}

	return $this;
    } // __call()

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
        if (!array_key_exists("fn", $this->Data) || empty($this->Data["fn"]))
        {
	    if ( array_key_exists("kind", $this->Data)
		 && $this->Data["kind"] == "organization" )
	    {
	        $fullname = (isset($this->Data["org"]))
			? implode(" ", $this->Data["org"][0]) : "";
            } else {
                $fullname = (isset($this->Data["n"])) ?
		    implode(" ", $this->Data["n"][0]) : "";
            }
            $this->Data["fn"] = trim($fullname);
        }
        return $this;
    } // setFNAppropriately()

    /**
     * Magic method for getting vCard content out
     *
     * @return string Raw vCard content
     */
    public function __toString()
    {
        $this->setFNAppropriately();

	$Text = 'BEGIN:VCARD'.self::endl;
	$Text .= 'VERSION:4.0'.self::endl;

	foreach ($this -> Data as $Key => $Values)
	{
	    $KeyUC = strtoupper($Key);
	    $Key = strtolower($Key);

	    if ($KeyUC === 'VERSION')
	    {
                continue;
	    }

	    if ($this->keyIsSingleValueElement($Key))
 	    {
                $Text .= $KeyUC . ":" . self::Escape($Values);
		$Text .= self::endl;
		continue;
	    }
 
	    foreach ($Values as $Index => $Value)
	    {
		$Text .= $KeyUC;
		if (is_array($Value) && isset($Value['Type']))
                {
                    $Text .= ';TYPE='
                             . self::PrepareTypeStrForOutput($Value['Type']);
		}
		$Text .= ':';

		if ($this->keyIsStructuredElement($Key))
		{
		    $PartArray = array();
                    foreach (self::$Spec_StructuredElements[$Key] as $Part)
                    {
                        $PartArray[] = isset($Value[$Part])
                                       ? self::Escape($Value[$Part]) : '';
                    }
						$Text .= implode(';', $PartArray);
		} elseif ( is_array($Value)
                           && isset(self::$Spec_ElementTypes[$Key]) ) {
		    $Text .= self::Escape($Value['Value']);
		} else {
                    $Text .= self::Escape($Value);
		}

		$Text .= self::endl;
            } // foreach
        }

	$Text .= 'END:VCARD'.self::endl;
	return $Text;
    } // __toString()

    // !Helper methods

    /**
     * Takes an array of types and turns them into a single string for
     * inclusion in a raw vCard line.
     * @param array $Type The array of type values to prepare. Not null.
     * @return string
     */
    private static function PrepareTypeStrForOutput(Array $Type)
    {
        return implode(',', array_map('strtoupper', $Type));
    }

    /**
     * Removes the escaping slashes from the text.
     *
     * @access private
     *
     * @param string $Text Text to prepare. Not null.
     *
     * @return string Resulting text.
     */
    private static function Unescape($Text)
    {
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
    private static function Escape($text)
    {
    	assert(null !== $text);
    	assert(is_string($text));
    	
        return addcslashes($text, "\\\n,:;");
    }

    /**
     * Separates the various parts of a structured value according to the spec.
     *
     * @access private
     *
     * @param string Raw text string
     * @param string Key (e.g., N, ADR, ORG, etc.)
     *
     * @return array Parts in an associative array.
     */
    private static function ParseStructuredValue($Text, $Key)
    {
    	assert(null !== $Text);
    	assert(is_string($Text));
    	assert(null !== $Key);
    	assert(is_string($Key));
    	
        $Text = array_map('trim', explode(';', $Text));

	$Result = array();
	$Ctr = 0;

	foreach (self::$Spec_StructuredElements[$Key] as $Index => $StructurePart)
	{
	    if (!empty($Text[$Index]))
	        $Result[$StructurePart] = $Text[$Index];
	}
	return $Result;
    } // ParseStructuredValue(

    /**
     * Split multiple element values by commas, except that RFC6350
     * allowed escaping is handled (comma and backslash).
     * @param string $Text The value text removed from the vCard line.
     * @return array An array of elements retrieved from this line.
     */
    private static function ParseMultipleTextValue($Text)
    {
    	assert(null !== $Text);
    	assert(is_string($Text));
	// split by commas, except that a comma escaped by
	// a backslash does not count except that a backslash
	// escaped by a backslash does not count...
	return preg_split(preg_quote('/(?<![^\\]\\),/'), $Text);
    }

    /**
     * Helper for parsing raw vCard text. Parse the parameters for the
     * property, $Key, from an array of raw parameters. Return the parameters
     * as keys and values.
     * @param string $Key The name of the property as parsed from the vCard.
     * Not null.
     * @param array $RawParams The array of parameters (separated by delimiter and delimiter
     * already removed. Not null.
     * @return array
     * @access private
     */
    private static function ParseParameters($Key, array $RawParams = null)
    {
    	assert(null !== $Key);
    	assert(is_string($Key));
    	assert(null !== $RawParams);
    	
        if (!$RawParams)
	{
	    return array();
	}

	// Parameters are split into (key, value) pairs
	$Parameters = array();
	foreach ($RawParams as $Item)
	{
	    $Parameters[] = explode('=', strtolower($Item));
	}

	$Type = array();
	$Result = array();

	// And each parameter is checked whether anything can/should be done 
        // because of it
	foreach ($Parameters as $Index => $Parameter)
	{
	    // Skipping empty elements
	    if (!$Parameter)
            {
                continue;
            }

            // Handling type parameters without the explicit TYPE parameter name 
            // (2.1 valid)
            if (count($Parameter) == 1)
            {
		// Checks if the type value is allowed for the specific element
		// The second part of the "if" statement means that email 
                // elements can have non-standard types (see the spec)
		if ( ( isset(self::$Spec_ElementTypes[$Key])
                       && in_array( $Parameter[0],
                           self::$Spec_ElementTypes[$Key] ) )
                     || ($Key == 'email' && is_scalar($Parameter[0])) )
                {
		    $Type[] = $Parameter[0];
                }
            } elseif (count($Parameter) > 2) {
		$TempTypeParams = self::ParseParameters( $Key,
                    explode(',', $RawParams[$Index]) );
		if ($TempTypeParams['type'])
		{
		    $Type = array_merge($Type, $TempTypeParams['type']);
		}
            } else {
                switch ($Parameter[0])
		{
		    case 'encoding':
                        if ( in_array( $Parameter[1],
                                array('quoted-printable', 'b', 'base64') ) )
                        {
                            $Result['encoding'] = $Parameter[1] == 'base64'
                                                  ? 'b' : $Parameter[1];
			}
			break;
                    case 'charset':
                        $Result['charset'] = $Parameter[1];
                        break;
                    case 'type':
                        $Type = array_merge($Type, explode(',', $Parameter[1]));
                        break;
                    case 'value':
                        if (strtolower($Parameter[1]) == 'url')
                        {
                            $Result['encoding'] = 'uri';
                        }
			break;
                } // switch
            } // else
        } // foreach

        $Result['type'] = $Type;

	return $Result;
    } // ParseParameters()

    // !Interface methods

    /**
     * Returns the number of internal vCard records created from the same
     * import. vCard records embedded inside of other vCard records is
     * deprecated in the vCard 4.0 specification. Ability to load multiple
     * records in one go in this class is therefore deprecated. Access to
     * embedded records in older vCards will eventually be provided through
     * a different facility.
     * @deprecated
     * @return integer
     */
    public function count()
    {
        switch ($this -> Mode)
	{
            case self::MODE_SINGLE:
                return 1;
		break;
            case self::MODE_MULTIPLE:
                return count($this -> Data);
		break;
        }
            return 0;
    }

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
     */
    public static function keyIsSingleValueElement($key)
    {
    	assert(null !== $key);
    	assert(is_string($key));
    	
	return in_array($key, self::$Spec_SingleElements);
    }

    /**
     * @param string $key The name of the property to test. Not null.
     * @return bool True if the specified key is a multiple-value VCard element,
     * (is able to contain multiple values on the same line separated by commas) 
     * false otherwise.
     */
    public static function keyIsMultipleValueElement($key)
    {
    	assert(null !== $key);
    	assert(is_string($key));
    	
	return in_array($key, self::$Spec_MultipleValueElements);
    }

    /**
     * @param string $key The name of the property to test. Not null.
     * @return bool True if the specified key is a structured VCard element,
     * false otherwise.
     */
    public static function keyIsStructuredElement($key)
    {
    	assert(null !== $key);
    	assert(is_string($key));
    	
        return isset(self::$Spec_StructuredElements[$key]);
    }

    /**
     * Returns true if a named property is a file property (it potentially
     * contains a blob or reference to external data), false otherwise.
     * @param string $key The name of the property to test. Not null.
     * @return boolean
     */
    public static function keyIsFileElement($key)
    {
    	assert(null !== $key);
    	assert(is_string($key));
    	
	return in_array($key, self::$Spec_FileElements);
    }
    
    public static function keyIsTypeAble($key)
    {
        assert(null !== $key);
        assert(is_string($key));
        
        return array_key_exists($key, VCard::$Spec_ElementTypes);
    }
    
    public static function keyAllowedTypes($key)
    {
        assert(null !== $key);
        assert(is_string($key));
        assert(array_key_exists($key, VCard::$Spec_ElementTypes));
        return VCard::$Spec_ElementTypes[$key];
    }
} // VCard

