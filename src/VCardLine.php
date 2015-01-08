<?php
/**
 * vCard class for parsing a vCard and/or creating one
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
 * A utility class for a parsed line from a raw VCard file.
 * Components of the line are extracted and stuffed into this structure by
 * the parsing loop and then are interpreted by the appropriate PropertyBuilder
 * class according to the semantics of a particular property.
 * By making this its own type rather than simply using a multi-dimensional
 * hash, we can get better error-checking and can bury the inevitable
 * array_key_exists(..) etc., in this class as well as, potentially, other
 * code useful to translating the structures.
 * As a design principle, this class should be used as a dumb container,
 * putting behavior and semantics into the appropriate Property subclass.
 * It is possible that this class may be useful for an intermediate layer
 * between VCard and other input/output formats as well.
 * 
 * In all cases, groups, names, and parameter names will be canonicalized to
 * all lowercase and any whitespace will be ignored.
 *
 * @author evought
 */
class VCardLine
{
    /**
     * A regular expression for splitting strings on semicolons which
     * may or may not be escaped with backslashes.
     */
    const SEMICOLON_SPLIT = '/(?<![^\\\\]\\\\);/';
    
    /**
     * The property group, a sequence of alphanumeric or hyphen characters
     * separated from the property name by a dot.
     * @var string.
     */
    private $group;
    
    /**
     * The property name.
     * @var string
     */
    private $name;
    
    /**
     * An array of parameter values indexed by parameter names.
     * Names without values should be stored in $novalue.
     * Values may themselves be complex.
     * @var array
     */
    private $parameters = [];
    
    /**
     * The value text parsed from the VCard line.
     * @var type 
     */
    private $value;

    /**
     * A list of all parameters found for which no value was defined.
     * In other words, in 'TEL;HOME:555-1212', HOME is a parameter with no
     * value. These are illegal in VCard 4.0 but may have significance in
     * earlier versions. They are stored during initial parsing and an attempt
     * is made later to puzzle them out.
     * @var string[] 
     */
    private $novalue = [];
    
    /**
     * The VCard version we are parsing *from*, which may be needed to tweak
     * interpretations at various stages.
     * Will be in the form {major}.{minor}
     * @var string 
     */
    private $version;
    
    /**
     * @var string[]
     */
    private static $imageMediaTypes =
        [
            'cgm', 'example', 'fits', 'g3fax', 'gif', 'ief',
            'jp2', 'jpeg', 'jpm', 'jpx', 'ktx', 'naplps', 'png', 'prs.btif',
            'prs.pti', 'pwg-raster', 'svg+xml', 't38', 'tiff', 'tiff-fx',
            'vnd.adobe.photoshop', 'vnd.airzip.accelerator.azv',
            'vnd.cns.inf2', 'vnd.dece.graphic', 'vnd.djvu', 'vnd.dwg',
            'vnd.dxf', 'vnd.dvb.subtitle', 'vnd.fastbidsheet', 'vnd.fpx',
            'vnd.fst', 'vnd.fujixerox.edmics-mmr', 'vnd.fujixerox.edmics-rlc',
            'vnd.globalgraphics.pgb', 'vnd.mix', 'vnd.ms-modi',
            'vnd.net-fpx', 'vnd.radiance', 'vnd.sealed.png',
            'vnd.sealedmedia.softseal.gif', 'vnd.sealedmedia.softseal.jpg',
            'vnd.svf', 'vnd.tencent.tap', 'vnd.valve.source.texture',
            'vnd.wap.wbmp', 'vnd.xiff'
        ];
    
    private static $audioMediaTypes =
        [
            '1d-interleaved-parityfec', '3gpp', '3gpp2', 'ac3', 'amr', 'amr-wb',
            'amr-wb+', 'aptx', 'asc', 'atrac-advanced-lossless', 'atrac-x',
            'atrac3', 'basic', 'bv16', 'bv32', 'clearmode', 'cn', 'dat12',
            'dls', 'dsr-es201108', 'dsr-es202050', 'dsr-es202211',
            'dsr-es202212', 'dv', 'dvI4', 'eac3', 'encaprtp', 'evrc',
            'evrc-qcp', 'evrc0', 'evrc1', 'evrcb', 'evrcb0', 'evrcb1',
            'evrcnw', 'evrcnw0', 'evrcnw1', 'evrcwb', 'evrcwb0', 'evrcwb1',
            'example', 'g719', 'g7221', 'g722', 'g723', 'g726-16', 'g726-24',
            'g726-32', 'g726-40', 'g728', 'g729', 'g7291', 'g729D', 'g729e',
            'gsm', 'gsm-efr', 'gsm-hr-08', 'ilbc', 'ip-mr_v2.5', 'l8', 'l16',
            'l20', 'l24', 'lpc', 'mobile-xmf', 'mpa', 'mp4', 'mp4a-latm',
            'mpa-robust', 'mpeg', 'mpeg4-generic', 'ogg', 'parityfec', 'pcma',
            'pcma-wb', 'pcmu', 'pcmu-wb', 'prs.sid', 'qcelp', 'raptorfec',
            'red', 'rtp-enc-aescm128', 'rtploopback', 'rtp-midi', 'rtx',
            'smv', 'smv0', 'smv-qcp', 'sp-midi', 'speex', 't140c', 't38',
            'telephone-event', 'tone', 'uemclip', 'ulpfec', 'vdvi', 'vmr-wb',
            'vnd.3gpp.iufp', 'vnd.4sb', 'vnd.audiokoz', 'vnd.celp',
            'vnd.cisco.nse', 'vnd.cmles.radio-events', 'vnd.cns.anp1',
            'vnd.cns.inf1', 'vnd.dece.audio', 'vnd.digital-winds',
            'vnd.dlna.adts', 'vnd.dolby.heaac.1', 'vnd.dolby.heaac.2',
            'vnd.dolby.mlp', 'vnd.dolby.mps', 'vnd.dolby.pl2', 'vnd.dolby.pl2x',
            'vnd.dolby.pl2z', 'vnd.dolby.pulse.1', 'vnd.dra', 'vnd.dts',
            'vnd.dts.hd', 'vnd.dvb.file', 'vnd.everad.plj', 'vnd.hns.audio',
            'vnd.lucent.voice', 'vnd.ms-playready.media.pya',
            'vnd.nokia.mobile-xmf', 'vnd.nortel.vbk', 'vnd.nuera.ecelp4800',
            'vnd.nuera.ecelp7470', 'vnd.nuera.ecelp9600', 'vnd.octel.sbc',
            'vnd.qcelp', 'vnd.rhetorex.32kadpcm', 'vnd.rip',
            'vnd.sealedmedia.softseal.mpeg', 'vnd.vmx.cvsd', 'vorbis',
            'vorbis-config'
        ];

    /**
     * Construct a new, empty VCardLine with $version as the target VCard
     * version.
     * @param string $version A string in the form major.minor.
     */
    public function __construct($version)
    {
        \assert(null !== $version);
        \assert(is_string($version));
        $this->version = $version;
    }
    
    /**
     * Returns the VCard version being used for parsing/interpreting parameters.
     * @return string
     */
    public function getVersion() {return $this->version;}
    
    /**
     * Returns the property group (a sequence of alphanumeric or hyphen characters
     * separated from the property name by a dot) or null if none.
     * @return string
     */
    public function getGroup() {return $this->group;}

    /**
     * Sets the property group.
     * @param string $group It is advisable to store this in lowercase.
     * @return self $this
     */
    public function setGroup($group)
    {
        $this->group = $group;
        return $this;
    }
    
    /**
     * Gets the property name.
     * @return string
     */
    public function getName() {return $this->name;}
    
    /**
     * Sets the property name.
     * @param string $name It is advisable to store this in lowercase.
     * @return self $this
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }
    
    /**
     * Gets the entire array of parameters.
     * @return Array Will be an array of parameter values indexed by parameter
     * names. Values may be complex.
     * @see getParameter()
     */
    public function getParameters()
    {
        \assert(\is_array($this->parameters));
        return $this->parameters;
    }
    
    /**
     * Gets a single parameter value by name.
     * @param string $parameter
     * @return mixed Values may be complex.
     */
    public function getParameter($parameter)
    {
        \assert(\is_array($this->parameters));
        \assert(null !== $parameter);
        \assert(\is_string($parameter));
        return \array_key_exists($parameter, $this->parameters)
               ? $this->parameters[$parameter] : null;
    }
    
    /**
     * Sets the value of a single parameter by name.
     * @param string $parameter The name of the parameter to set.
     * @param mixed $value
     * @return self $this
     */
    public function setParameter($parameter, $value)
    {
        \assert(\is_array($this->parameters));
        \assert(null !== $parameter);
        \assert(\is_string($parameter));
        $this->parameters[$parameter] = $value;
        return $this;
    }
    
    /**
     * Unsets any values for a single named parameter.
     * @param string $parameter
     * @return self $this
     */
    public function unsetParameter($parameter)
    {
        \assert(\is_array($this->parameters));
        \assert(null !== $parameter);
        \assert(is_string($parameter));
        unset($this->parameters[$parameter]);
        return $this;
    }
    
    /**
     * Adds a parameter value to the named parameter.
     * Many parameters may have multiple values (e.g. TYPE) which should be
     * compiled as a list.
     * For the most part, we should avoid figuring out whether extra values are
     * meaningful at this stage and just store them for later processing.
     * @param string $parameter The name of the parameter. Not null.
     * @param string $value The value to add. Not null.
     * @return self $this
     */
    public function pushParameter($parameter, $value)
    {
        \assert(\is_array($this->parameters));
        \assert(null !== $parameter);
        \assert(\is_string($parameter));
        \assert(null !== $value);
        \assert(\is_string($value));
        $this->parameters[$parameter][] = $value;
        return $this;
    }
    
    /**
     * Remove matching values from those set for the named parameter, retaining
     * any non-matching values.
     * This is a convenience method for doing version tweaks on parameter values
     * where a value is moved from one parameter to another.
     * 
     * For example: "$vcardLine->clearParamValues('type', [pref]);" removes
     * any/all values matching 'pref' from the 'type' parameter.
     * @param string $parameter The parameter name. Not null.
     * @param array $values A list of string values to remove.
     * @return self $this
     */
    public function clearParamValues($parameter, Array $values)
    {
        \assert(\is_array($this->parameters));
        \assert(null !== $parameter);
        \assert(\is_string($parameter));
        if (\array_key_exists($parameter, $this->parameters))
        {
            // NOTE: \array_diff(..) does reindex, array_values(..) does.
            $this->parameters[$parameter]
                = \array_values(\array_diff($this->parameters[$parameter], $values));
            if (empty($this->parameters[$parameter]))
                unset($this->parameters[$parameter]);
        }
        return $this;
    }
    
    /**
     * Returns true if-and-only-if at least one value exists for the named
     * parameter.
     * @param string $parameter The name of the parameter to test. Not null.
     * @return bool
     */
    public function hasParameter($parameter)
    {
        \assert(null !== $parameter);
        \assert(is_string($parameter));
        return \array_key_exists($parameter, $this->parameters);
    }
    
    /**
     * Transforms all values for the named parameters to lowercase.
     * Convenience method for canonicalizing chosen parameter values.
     * 
     * @param string[] $paramNames The names of the parameters to transform.
     * @return self $this
     */
    public function lowercaseParameters(Array $paramNames)
    {
        foreach ($paramNames as $parameter)
        {
            if (\array_key_exists($parameter, $this->parameters))
            {
                $this->parameters[$parameter]
                    = \array_map('strtolower', $this->parameters[$parameter]);
            }
        }
        return $this;
    }
    
    /**
     * Returns the property value parsed from this line.
     * @return string
     */
    public function getValue() {return $this->value;}
    
    /**
     * Sets the property value for this line.
     * @param string $value
     * @return self $this
     */
    public function setValue($value)
    {
        $this->value = $value;
        return $this;
    }
    
    /**
     * Helper for parsing raw vCard text. Parse the parameter names/values from
     * an array of raw parameters and store them in this strucure.
     * At this stage, we are not processing the parameter values or doing much
     * checking on whether allowed or disallowed.
     * Rather, we are gathering the parameter values for the specific properties
     * to interpret.
     * Parameter names are canonicalized to lowercase.
     * Parameter values are stripped of quotes and NSWSP if necessary, and
     * unescaped.
     * We do do some checking on parameters whose definition has changed between
     * versions to canonicalize them. For example, bare TYPEs are aggregated
     * for 2.1 cards and the PREF TYPE is turned into a PREF parameter.
     * If we otherwise have malformed parameters (no value), then we throw
     * an exception.
     * @param string[] $rawParams The array of parameter strings (delimiter
     * already removed) from the VCard.
     * @return self $this
     * @throws \DomainException For certain malformed parameter conditions.
     */
    public function parseParameters(Array $rawParams)
    {
        if (empty($rawParams))
	    return $this;

	foreach ($rawParams as $paramStr)
	{
            if (empty($paramStr))
                throw new Exceptions\MalformedParameterException(
                    'Empty or malformed parameter in property: '
                    . $this->name
                    . '; check colons, semi-colons, and unmatched quotes.');
            
            // We should not need to worry about escaping/quoting with respect
            // to the first equals, directly following the parameter name, as
            // parameter names are only alpha-numeric and hyphen.
            // There may be other, quoted equals-signs in the value, but we
            // don't care about them at this point.
	    $param = \explode('=', $paramStr, 2);
            $paramName = \trim(\strtolower($param[0]));
            if (\count($param) == 1)
            {
                $this->novalue[] = $paramName;
            } else {
                $values = \str_getcsv($param[1]);
                foreach ($values as $value)
                    $this->pushParameter( $paramName,
                            \stripcslashes(\trim($value)) );
            }
	}
        
        if (!empty($this->novalue))
        {
            if ($this->version === '2.1')
            {
                if ($this->hasParameter('type'))
                    $this->parameters['type'] =
                        \array_merge( $this->parameters['type'],
                            $this->novalues );
                else
                    $this->parameters['type'] = $this->novalue;
                unset($this->novalue);
            } else {
                throw new Exceptions\MalformedParameterException(
                    'One or more parameters do not have values and version '
                    . ' is not 2.1: '
                    . \implode(',', $this->novalue ) );
                unset($this->novalue);
            }
        }
        
        $this->lowercaseParameters(['type', 'encoding', 'value', 'mediatype']);
        $this->shuffleTypes();

        return $this;
    }
    
    /**
     * VCard 2.1 and 3.0 used TYPE is a catch-all; VCard 4.0 broke it out
     * into distinct parameters. Look for and move these legacy parameter
     * values to where they need to go.
     * @return void
     * @throws Exceptions\IllegalParameterValueException If we have legacy
     * values in TYPE but are not VCard 3.0 or earlier.
     */
    private function shuffleTypes()
    {
        if (!($this->hasParameter('type'))) return;
        
        // VCard 3.0 MEDIATYPES in TYPE parameter
        $this->moveTypeToMediaType(self::$imageMediaTypes, 'image/');
        $this->moveTypeToMediaType(self::$audioMediaTypes, 'audio/');
        
        if (!($this->hasParameter('type'))) return;
        
        if (in_array('pref', $this->getParameter('type')))
        {
            // PREF was allowed as a type in 3.0
            // NOTE: if PREF was specified bare in 2.1, it will have already
            // been moved into TYPE
            if ( $this->getVersion() === '3.0'
                 || $this->getVersion() === '2.1' )
            {
                if (!($this->hasParameter('pref')))
                    $this->setParameter('pref', ['1']);
                $this->clearParamValues('type', ['pref']);
            } else {
                throw new Exceptions\IllegalParameterValueException(
                    'PREF is given as TYPE for ' . $this->getName()
                    . ' and VERSION is not 2.1 or 3.0' );
            }
        }
        
    }
    
    
    /**
     * A utility function to move MEDIATYPEs disguised as TYPEs in legacy
     * vcards. Any mediatypes recognized according to $typeList will be
     * reformatted according to $typeTemplate, added to mediatypes, and removed
     * from types.
     * @param string[] $typeList The list of media sub-types to look for in TYPES,
     * e.g. jpeg. List should all be lowercased.
     * @param string $typeTemplate The type template to use when moving to
     * MEDIATYPE and reformatting. e.g. 'image/' will yield 'image/jpeg'.
     * @throws Exceptions\IllegalParameterValueException if we have legacy
     * media types but are not VCard 3.0 or earlier.
     * @return void
     */
    private function moveTypeToMediaType(Array $typeList, $typeTemplate)
    {
        if (!($this->hasParameter('type'))) return;
        
        $matches = \array_intersect( $this->getParameter('type'),
                                        $typeList );
            
        if (!empty($matches))
        {
            if (!\in_array($this->getVersion(), ['2.1', '3.0']))
                throw new Exceptions\IllegalParameterValueException(
                    'One or more MEDIATYPEs, ' .  \implode(',', $matches)
                    . ', are stored in TYPE and version is not 2.1/3.0' );
            
            foreach ($matches as $mediaType)
            {
                $this->pushParameter('mediatype', $typeTemplate . $mediaType);
            }
            $this->clearParamValues('type', $matches);
        }
    }
    
    /**
     * Returns a new VCardLine initialized from the given line text, parsing
     * it according to the specified VCard version.
     * @param string $rawLine The raw VCard line (line break removed).
     * @param string $version The target VCard version.
     * @return self
     * @throws \DomainException If the line is malformed.
     */
    public static function fromLineText($rawLine, $version)
    {
        // Lines with only whitespace are skipped
	if (empty(trim($rawLine)))
            return null;
     
        $parsed = [];
        
        // https://regex101.com/r/uY5tY2/5
        $re = "/
#Parse a VCard 4.0 (RFC6350) property line into
#group, name, params, value components
#VCard 2.1 allowed NSWSP ([:blank]) in some places
#Match the property name which starts with an optional
#group name followed by a dot
(?:
  (?>(?P<group>[[:alnum:]-]+))
  \\.
)?
(?P<name>[[:alnum:]-]+)
[[:blank:]]*
#The optional params section: each repeating group
#starts with a semicolon and parameter name.
#Value starts with '=' and may be quoted.
#Unquoted must be SAFE-CHAR, otherwise QSAFE-CHAR
#Vcard 2.1 may omit parameter value
(?P<params>
  (?:; [[:blank:]]*[[:alnum:]-]+[[:blank:]]*
    (?:= [[:blank:]]*
      (?>
        (?:\\\"[[:blank:]\\!\\x23-\\x7E[:^ascii:]]*\\\")
        | (?:[[:blank:]\\!\\x23-\\x39\\x3c-\\x7e[:^ascii:]]*)
      )
    )?
  )*
)
#Unescaped colon starts value section
[[:blank:]]*
(?<![^\\\\]\\\\):
[[:blank:]]*
#Value itself contains VALUE-CHAR and anything
#not permitted expected to be removed before regex
#is run.
(?P<value>.+)
/x";
        $matches = \preg_match($re, $rawLine, $parsed);
        if (1 !== $matches)
            throw new Exceptions\MalformedPropertyException(
                'Malformed property entry: ' . $rawLine );
        
        $vcardLine = new static($version);
        $vcardLine  ->setValue($parsed['value'])
                    ->setName(\trim(\strtolower($parsed['name'])))
                    ->setGroup(\trim(\strtolower($parsed['group'])));
        
        if (!empty($parsed['params']))
        {
            // NOTE: params string always starts with a semicolon we don't need
            $parameters = \preg_split( self::SEMICOLON_SPLIT,
                                       \substr($parsed['params'], 1) );
        
            $vcardLine->parseParameters($parameters);
        }
        $vcardLine->handleEncoding();
        $vcardLine->handleCharset();
        
        return $vcardLine;
    }
    
    /**
     * If CHARSET is set, and it is permitted, convert the value to UTF8 and
     * discard the CHARSET parameter.
     * @throws Exceptions\MalformedParameterException If CHARSET is set to
     * anything other than UTF-8 and version is not 4.0.
     * @throws Exceptions\MalformedParameterException If more than one value
     * for CHARSET is provided.
     */
    protected function handleCharset()
    {
        if (($charsetVals = $this->getParameter('charset')) !== null)
        {
            if (count($charsetVals) !== 1)
                throw new Exceptions\MalformedParameterException(
                    'CHARSET has more than one value: ' . $this->getName () );
            $charset = strtolower($charsetVals[0]);
            if (('utf-8' !== strtolower($charset)) && ($this->version === '4.0'))
                throw new Exceptions\MalformedParameterException(
                    'CHARSET is specified for ' . $this->name .
                    ', but version is 4.0' );
            
            // FIXME: What happens if CHARSET is not a valid character set?
            $this->value = \iconv($charset, 'utf-8', $this->value);
            $this->unsetParameter('charset');
        }
    }
    
    /**
     * Handle ENCODING for legacy VCards. If ENCODING is well-formed and
     * permitted, decode the property value and discard parameter.
     * @throws Exceptions\MalformedParameterException If the ENCODING is
     * unknown, not permitted for the specified VCard version or the version is
     * 4.0 (which does not permit ENCODING at all).
     */
    protected function handleEncoding()
    {
        if (($encodingVals = $this->getParameter('encoding')) !== null)
        {
            if (count($encodingVals) !== 1)
                throw new Exceptions\MalformedParameterException(
                    'ENCODING has more than one value: ' . $this->getName () );
            $encoding = strtolower($encodingVals[0]);
            switch ($this->version) {
                case '2.1':
                    if ('quoted-printable' === $encoding)
                    {
                        $this->value = \quoted_printable_decode($this->value);
                    } elseif ('base64' === $encoding) {
                        $this->value = \base64_decode($this->value);
                    } else {
                        throw new Exceptions\MalformedParameterException(
                            'Unknown 2.1 ENCODING, ' . $encoding . ', for '
                            . $this->name );
                    }
                    break;
                case '3.0':
                    if ('b' === $encoding)
                    {
                        $this->value = \base64_decode($this->value);
                    } else {
                        throw new Exceptions\MalformedParameterException(
                            'Unknown 3.0 ENCODING, ' . $encoding . '. for '
                            . $this->name );
                    }
                    break;
                default:
                    throw new Exceptions\MalformedParameterException(
                            'ENCODING not permitted in VCard 4.0 at '
                            . $this->name );
                    break;
            }
            $this->unsetParameter('encoding');
        }
    }
}
