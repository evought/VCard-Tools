<?php
/**
 * A tool for storing/retrieving vCards from a database.
 * @author Eric Vought evought@pobox.com 2014-11-16
 * @copyright Eric Vought 2014, Some rights reserved.
 * @license MIT http://opensource.org/licenses/MIT
 */
namespace EVought\vCardTools;

/**
 * A class for storing and retrieving vCard instances from a database, using
 * the RDBMS schema defined for vCardTools.
 * @author evought
 * @api
 *
 */
class VCardDB implements VCardRepository
{
// FIXME: Add a method to fetch all contact IDs (without loading the records).
    
    // The product id we will use when creating new vcards
    const VCARD_PRODUCT_ID = '-//VCard Tools//1.0//en';

    /**
     * The \PDO connection used for storage and retrieval.
     */
    private $connection;

    /**
     * The array of default SQL query information used to fetch/store CONTACTS.
     * Loaded from the default .ini file.
     * Structure follows that of the .ini file: top-level keys define sections
     * with keys containing the actual queries.
     * Use getQueryInfo(..) to access.
     * @var array
     */
    private static $sharedQueries = null;
    
    /**
     * The array of customized SQL query information used to fetch/store
     * CONTACTS, if present. May be loaded from a custom .ini file during
     * construction. Structure follows that of the .ini file: top-level keys define sections
     * with keys containing the actual queries. Use getQueryInfo(..) to access.
     * @var array
     */
    private $queries = null;

    /**
     * Retrieve the current \PDO connection.
     */
    public function getConnection() {return $this->connection;}

    /**
     * Construct a new instance.
     * @param \PDO $connection A \PDO connection to read from/write to. Not null. Caller
     * retains responsibility for connection, but this class shall ensure that
     * the reference is cleared upon clean-up.
     * @param string $iniFilePath The pathname to an .ini file from which to
     * load SQL queries for fetch/store of CONTACTS. If not set, the default
     * .ini provided with the package shall be used. This can be used
     * (carefully) to adapt queries to a specific database. Keys not found in
     * the custom .ini will still be loaded from the shared queries.
     * @throws \ErrorException if the default .ini cannot be accessed.
     * @throws \DomainException if the custom .ini file cannot be accessed.
     */
    public function __construct(\PDO $connection, $iniFilePath = null)
    {
        assert(!empty($connection));
        $this->connection = $connection;
        
        if (null === VCardDB::$sharedQueries)
        {
            $defaultINI = __DIR__ . '/sql/VCardDBQueries.ini';
            if (\is_readable($defaultINI))
            {
                VCardDB::$sharedQueries = \parse_ini_file($defaultINI, true);
            } else {
                throw new \ErrorException( 'Default .ini, ' . $defaultINI
                                            . ' cannot be loaded.' );
            }
        }
        
        if (!(empty($iniFilePath)))
        {
            if (\is_readable($iniFilePath))
            {
                $this->queries = \parse_ini_file($iniFilePath, true);
            } else {
                throw new \DomainException( 'Custom .ini, ' . $iniFilePath
                                            . ' cannot be loaded.' );
            }
        }
    }

    /**
     * Make sure that $connection is cleared.
     */
    public function __destruct()
    {
        unset($this->connection);
    }
    
    /* Interface methods: VCardRepository */
    
    /**
     * Fetch all vcards from the database.
     * @param string $kind If kind is given, only fetch those of that kind (e.g.
     * organization).
     * @return array An array of vCards keyed by uid.
     */
    public function fetchAll($kind='%')
    {
    	assert(isset($this->connection));
    	
    	$stmt = $this->connection->prepare($this->getQueryInfo('search', 'all'));
    	$stmt->bindValue(":kind", $kind);

        $stmt->execute();
        $result = $stmt->setFetchMode(\PDO::FETCH_ASSOC);

        $vcards = array();

        while ($row = $stmt->fetch())
        {
	    $vcards[$row["UID"]] = $this->i_fetchVCard($row);
        } // while

        $stmt->closeCursor();

        return $vcards;
    } // fetchAll()
    
        /**
     * Store the whole vcard to the database, calling sub-functions to store
     * related tables (e.g. address) as necessary.
     * @param VCard $vcard The record to store.
     * @return integer The new contact id.
     */
    function store(VCard $vcard)
    {
        assert(!empty($this->connection));

        $uid = $this->i_storeJustContact($vcard);

        foreach ( ['n', 'adr', 'org'] as $propertyName)
        {
            if (empty($vcard->$propertyName)) continue;
            foreach ($vcard->$propertyName as $property)
            {
            	$this->i_storeStructuredProperty($property, $uid);
            }
        }
        
        foreach ( [ 'nickname', 'url' ,'photo', 'logo', 'sound', 'key', 'note',
                    'tel', 'geo', 'email', 'categories', 'related' ]
                    as $propertyName )
        {
            if (empty($vcard->$propertyName)) continue;
	    foreach ($vcard->$propertyName as $property)
    	    {
    	    	$this->i_storeBasicProperty($property, $uid);
    	    }
        }
        
        foreach ($vcard->getUndefinedProperties() as $property)
        {
            $this->i_storeXtendedProperty($property, $uid);
        }

        return $uid;
    } // store()

    /**
     * Returns all vcards where the fn or categories match the requested search
     * string.
     * @param string $searchString The pattern to search for (SQL matching rules). If
     * omitted, match all cards.
     * @param string $kind If kind is given, return only cards of that kind (e.g.
     * organization).
     * @return array of vCards indexed by uid.
     */
    public function search($searchString='%', $kind='%')
    {
    	assert(isset($this->connection));
    	
        $stmt = $this->connection->prepare(
                        $this->getQueryInfo('search', 'search') );
        $stmt->bindValue(":kind", $kind);
        $stmt->bindValue(":searchString", $searchString);
        $stmt->execute();

        $uids = $stmt->fetchAll(\PDO::FETCH_COLUMN, 0);
        $stmt->closeCursor();
 
        $uids += $this->fetchIDsForCategory( $searchString,
        		$kind="%" );
        if (empty($uids)) return array();

        return $this->fetchByID($uids);
    } // search()

        /**
     * Returns a list of all contact_ids where the org.name parameter matches
     * the query.
     * @param string $organizationName The name of the organization to search for. May
     * be a SQL pattern. String, not empty.
     * @param string $kind If kind is provided, limit results to a specific Kind (e.g.
     * individual.
     * @return array The list of contact uids. Actual vCards are not fetched.
     */
    public function fetchIDsForOrganization($organizationName, $kind="%")
    {
    	assert(isset($this->connection));
    	assert(!empty($organizationName));
    	assert(is_string($organizationName));
    	
        $stmt = $this->connection->prepare(
                    $this->getQueryInfo('search', 'organization') );
        $stmt->bindValue(':organizationName', $organizationName);
        $stmt->bindValue(':kind', $kind);
        $stmt->execute();
        $uids = $stmt->fetchAll(\PDO::FETCH_COLUMN, 0);
        $stmt->closeCursor();

        return $uids;
    } // fetchIDsForOrganization()

    /**
     * Returns a list of all contact uids in a given category.
     * @param string $category The string representing the category to search for.
     * May be a SQL pattern. Not empty.
     * @param string $kind If given, the kind (e.g. individual) to filter by.
     * @return array An array of contact uids. No vCards are fetched.
     */
    public function fetchIDsForCategory($category, $kind="%")
    {
    	assert(isset($this->connection));
    	assert(!empty($category));
    	assert(is_string($category));
    	
        $stmt = $this->connection->prepare(
                        $this->getQueryInfo('search', 'categories') );
        $stmt->bindValue(":category", $category);
        $stmt->bindValue(':kind', $kind);
        $stmt->execute();
        $uids = $stmt->fetchAll(\PDO::FETCH_COLUMN, 0);
        $stmt->closeCursor();

        return $uids;
    } // fetchIDsForCategory()

    /**
     * Retrieve vCard records for the given Contact IDs.
     * @param array $uids A list of contact uids to fetch.
     * @return array An array of vCards indexed by uid.
     */
    public function fetchByID(Array $uids)
    {
    	assert(isset($this->connection));
    	assert(!empty($uids));
    	
        $vcards = array();
        foreach($uids as $uid)
        {
	    $vcards[$uid] = $this->fetchOne($uid);
        }

        return $vcards;
    } // fetchByID()

    /**
     * Fetch a single vcard given a contact uid.
     * @param string $uid The ID of the record to fetch. String, not empty.
     * @return VCard|null The completed vcard or false if none found.
     */
    public function fetchOne($uid)
    {
    	assert(isset($this->connection));
    	assert(!empty($uid));
    	assert(is_string($uid));
    	
        $stmt = $this->connection->prepare(
                        $this->getQueryInfo('fetch', 'contact') );
        $stmt->bindValue(":uid", $uid);
        $stmt->execute();
        assert($stmt->rowCount() <= 1);
                
        $result = $stmt->setFetchMode(\PDO::FETCH_ASSOC);

        $vcard = false;

        $row = $stmt->fetch();
        if ($row != false) $vcard = $this->i_fetchVCard($row);
        $stmt->closeCursor();

        return $vcard;
    } // fetchOne()
    
    /* Private methods */

    /**
     * Returns information about a configured query by section and key.
     * The returned value will be taken from the custom .ini supplied at
     * construction (if any) first and then from default settings.
     * @param string $section The section name to look in. Not null.
     * @param string $key The key to look up. Not null.
     * @return string|mixed
     * @throws \ErrorException If a configured value does not exist.
     */
    private function getQueryInfo($section, $key)
    {
        assert(null !== VCardDB::$sharedQueries);
        assert(null !== $section);
        assert(is_string($section));
        assert(null !== $key);
        assert(is_string($key));
        
        if (!empty($this->queries))
        {
            if ( array_key_exists($section, $this->queries)
                    && array_key_exists($key, $this->queries[$section]) )
            {
                return $this->queries[$section][$key];
            }
        }
        
        if ( array_key_exists($section, VCardDB::$sharedQueries)
                && array_key_exists($key, VCardDB::$sharedQueries[$section]) )
        {
            return VCardDB::$sharedQueries[$section][$key];
        } else {
            throw new \ErrorException( $section.':'.$key .
                                  ' not found in configured VCardDB queries.' );
        }
    }

    /**
     * Saves the vcard contact data to the database, returns the id of the
     * new connection record (resuses existing uid if provided).
     * Stores JUST the info from the CONTACT table itself, no sub-tables.
     * @param VCard $vcard The record to store.
     * @return string The new uid.
     */
    private function i_storeJustContact(VCard $vcard)
    {
        assert(!empty($this->connection));

        $vcard->setFNAppropriately();
        $uid = $vcard->checkSetUID();

        $stmt = $this->connection->prepare(
                    $this->getQueryInfo('store', 'contact') );
        
        $stmt->bindValue(':uid', $uid);

        foreach ( [ 'kind', 'rev' ]
                  as $simpleProperty )
        {
            assert( $vcard->getSpecification($simpleProperty)->requiresSingleProperty(),
                    $simpleProperty . ' must be a single value element' );
            $stmt->bindValue( ':'.$simpleProperty,
                                empty($vcard->$simpleProperty)
                                ? null : $vcard->$simpleProperty->getValue(),
                                \PDO::PARAM_STR );
        }

        // HACK: #53, #54, #55: VCard and the spec think URL,
        // NICKNAME, etc. are multiple.
        // Database doesn't. Arbitrarily take the first value.
        foreach ( [ 'role', 'title', 'tz', 'fn', 'bday', 'anniversary' ]
                    as $hackMultiple )
        {
            assert(!($vcard->getSpecification($hackMultiple)->requiresSingleProperty()),
                    $simpleProperty . ' must NOT be a single value element');
            $hackMultipleValue = $vcard->$hackMultiple;
            if (empty($hackMultipleValue))
            {
                $stmt->bindValue(':'.$hackMultiple, null, \PDO::PARAM_NULL);
            } else {
                $stmt->bindValue( ':'.$hackMultiple,
                                    $hackMultipleValue[0]->getValue());
            }        
        }
    
        $stmt->execute();

        return $uid;
    } // i_storeJustContact()

    /**
     * Store a structured property (multiple complex values) which requires a
     * subsidiary table/link table and return the ID of the new record.
     * @param StructuredProperty $property The property to store.
     * @param string uid The uid of the Contact record the property will be
     * stored under.
     * @return integer The new property record id.
     */
    private function i_storeStructuredProperty( StructuredProperty $property,
    		                                $uid )
    {
    	assert($this->connection !== null);
    	assert(!empty($uid));
    	assert(is_string($uid));
    	    	
    	$stmt = $this->connection->prepare(
                    $this->getQueryInfo('store', $property->getName()) );
        
        $stmt->bindValue(':uid', $uid);
        $stmt->bindValue(':valuetype', $property->getValueType(false));
        if ($property->getSpecification()->isCardinalityToN())
            $stmt->bindValue('pref', $property->getPref(false), \PDO::PARAM_INT);
    	foreach($property->getAllowedFields() as $key)
    	{
            $stmt->bindValue(':'.$key, $property->getField($key), \PDO::PARAM_STR);
    	}
    	
    	$stmt->execute();
    	$propertyID = $this->connection->lastInsertId();
    	
        if ($property instanceof TypedProperty)
            $this->i_associateTypes($property, $propertyID);
        return $propertyID;
    } // i_storeStructuredProperty()
        
    /**
     * Create an association between the given types and the property/id
     * combination in the database.
     * @param TypedProperty $property The property from which to store types.
     * with.
     * @param type $propertyID The id of the property within the appropriate
     * property specific table to associate types with.
     */
    private function i_associateTypes(TypedProperty $property, $propertyID)
    {
    	assert($this->connection !== null);
    	assert($propertyID !== null);
    	assert(is_numeric($propertyID));

        if (empty($property->getTypes())) {return;}
        
        $stmt = $this->connection->prepare(
                    $this->getQueryInfo('associateTypes', $property->getName()) );
        
        foreach ($property->getTypes() as $type)
        {
            $stmt->bindValue(':id', $propertyID);
            $stmt->bindValue(':type', $type);
            $stmt->execute();
        }
    	
    	return;
    } // associateTypes(..)
    
    /**
     * Store a basic property (multiple simple values) which requires a
     * subsidiary table and return the ID of the new record.
     * @param Property $property The property to store.
     * @param string $uid The uid of the CONTACT to associate the new
     * record with.
     * @return integer The ID of the newly created record.
     */
    private function i_storeBasicProperty(Property $property, $uid)
    {
    	assert($this->connection !== null);
    	assert(!empty($uid));
    	assert(is_string($uid));
    	
    	$stmt = $this->connection->prepare(
                    $this->getQueryInfo('store', $property->getName()) );
    	
        $stmt->bindValue(':uid', $uid);
    	$stmt->bindValue(':value', $property->getValue());
        $stmt->bindValue(':valuetype', $property->getValueType(false));
        $stmt->bindValue(':pref', $property->getPref(false), \PDO::PARAM_INT);
        if ($property instanceof MediaTypeProperty)
            $stmt->bindValue (':mediatype', $property->getMediaType());
    	$stmt->execute();
    	$propertyID = $this->connection->lastInsertId();
        
        if ($property instanceof TypedProperty)
            $this->i_associateTypes($property, $propertyID);

    	return $propertyID;
    } // i_storeBasicProperty()
    
    /**
     * Store an xtended (unspecified, vendor extension) property.
     * @param Property $property The property to store.
     * @param string $uid The uid of the CONTACT to associate the new
     * record with.
     * @return integer The ID of the newly created record.
     */
    private function i_storeXtendedProperty(Property $property, $uid)
    {
    	assert($this->connection !== null);
    	assert(!empty($uid));
    	assert(is_string($uid));
    	
    	$stmt = $this->connection->prepare(
                    $this->getQueryInfo('store', 'xtended') );
    	
        $stmt->bindValue(':uid', $uid);
        $stmt->bindValue(':name', $property->getName());
    	$stmt->bindValue(':value', $property->getValue());
        $stmt->bindValue(':valuetype', $property->getValueType(false));
        $stmt->bindValue('pref', $property->getPref(false), \PDO::PARAM_INT);
        $stmt->bindValue (':mediatype', $property->getMediaType());
    	$stmt->execute();
    	$propertyID = $this->connection->lastInsertId();

    	return $propertyID;
    }
    
    /**
     * Internal helper to fill in details of a vcard.
     * @param array $row The associative array row returned from the db. Not empty.
     * @return VCard The finished vcard.
     */
    protected function i_fetchVCard(Array $row)
    {
    	assert(isset($this->connection));
    	assert(!empty($row));
    	
        $vcard = new VCard();
        $vcard->setUID($row["UID"]);

        // FIXME: fetch columns explicitly instead of "SELECT *" and map
        $simpleCols = [ 'KIND', 'FN', 'BDAY', 'ANNIVERSARY', 'TITLE', 'ROLE',
                        'REV', 'VERSION' ];
        foreach ($simpleCols as $col)
        {
            if (!empty($row[$col]))
                $vcard->push(
                    VCard::builder(\strtolower($col))
                        ->setValue($row[$col])->build() );
        }
        
        $vcard->push(
            VCard::builder('prodid')
                ->setValue(self::VCARD_PRODUCT_ID)->build() );
        
        foreach (['org', 'adr', 'n'] as $structuredProperty)
        {
            $vcard->$structuredProperty
                = $this->i_fetchStructuredProperty( $structuredProperty,
                                                    $vcard->getUID() );
        }
        
        // Basic Properties
        foreach ( [ 'nickname', 'url', 'note', 'email', 'tel', 'categories',
                    'geo', 'logo', 'photo', 'sound', 'key', 'related' ]
                    as $property )
        {
            $vcard->$property
                = $this->i_fetchBasicProperty($property, $vcard->getUID());
        }
        
        $xtended = $this->i_fetchXtendedProperties($vcard->getUID());
        if (null !== $xtended) $vcard->push($xtended);

        return $vcard;
    } // i_fetchVCard()
    
    /**
     * Retrieve all types associated with a given property/id in the db.
     * @param TypedPropertyBuilder $property The builder to add types to.
     * @param type $propertyID The ID of the property to fetch types for within
     * the property-specific sub-table.
     * @return bool true if-and-only-if types were fetched.
     */
    private function i_fetchTypesForPropertyID( TypedPropertyBuilder $builder,
                                                $propertyID )
    {
        assert(null !== $propertyID);
        assert(is_numeric($propertyID));
        
        $stmt = $this->connection->prepare(
                    $this->getQueryInfo('fetchTypes', $builder->getName()) );
    	$stmt->bindValue(":id", $propertyID);
    	$stmt->execute();
    	
    	$results = $stmt->fetchAll(\PDO::FETCH_COLUMN, 0);
    	$stmt->closeCursor();
        
        $builder->setTypes($results);

    	return empty($results);
    }
    
    /**
     * Fetch all records for the named structured property (e.g. adr) and
     * return them in an array.
     * @param string $propertyName The name of the associated records to
     * retrieve. String, not null.
     * @param integer $uid The uid of the CONTACT the records are
     * associated with. String, not null.
     * @return NULL|StructuredProperty[] An array of the properties or null if
     * none available.
     */
    private function i_fetchStructuredProperty($propertyName, $uid)
    {
    	assert(isset($this->connection));
    	assert($propertyName !== null);
    	assert(is_string($propertyName));
    	assert(!empty($uid));
    	assert(is_string($uid));
    	    	    	 
    	// Fetch each $propertyName record in turn    	
    	$propList = [];

    	$stmt = $this->connection->prepare(
                    $this->getQueryInfo('fetch', $propertyName) );
        $stmt->bindValue(":id", $uid);
        $stmt->execute();
        
        while ($result = $stmt->fetch(\PDO::FETCH_ASSOC))
    	{
            $builder = VCard::builder($propertyName);
            
            // FIXME: Need to store this
            $propertyID = $result['pid'];
            unset($result['pid']);
            
            if (null !== $result['pref'])
                $builder->setPref($result['pref']);
            unset($result['pref']);
            
            if ( array_key_exists('mediatype', $result)
                 && (null !== $result['mediatype']) )
                $builder->setMediaType($result['mediatype']);
            unset($result['mediatype']);
            
            if (null !== $result['valuetype'])
                $builder->setValueType($result['valuetype']);
            unset($result['valuetype']);
            
            \assert($builder instanceof StructuredPropertyBuilder);            
            $builder->setValue(\array_filter($result, '\strlen'));
            
            if ($builder instanceof TypedPropertyBuilder)
                $this->i_fetchTypesForPropertyID($builder, $propertyID);
            
            $propList[] = $builder->build();
    	}
        $stmt->closeCursor();
    	return empty($propList) ? null : $propList;    	 
    } // i_fetchStructuredProperty()
    
    /**
     * Fetches all records of a basic multi-value property associated with
     * the given contact ID.
     * @param string $propertyName The name of the property to return
     * records for (e.g. email). String, not null.
     * @param string $uid The contact uid records are associated with.
     * Numeric, not null.
     * @return NULL|Property[] Returns an array of associated records, or null
     * if none found.
     */
    private function i_fetchBasicProperty($propertyName, $uid)
    {
    	assert(isset($this->connection));
    	assert($propertyName !== null);
    	assert(is_string($propertyName));
    	assert(!empty($uid));
    	assert(is_string($uid));
    	
    	// Fetch each property record in turn
    	$stmt = $this->connection->prepare(
                    $this->getQueryInfo('fetch', $propertyName) );
        $stmt->bindValue(':id', $uid);
        $stmt->execute();
        
        /** @var Property[] */
        $properties = [];
        while ($result = $stmt->fetch(\PDO::FETCH_ASSOC))
    	{
            $builder = VCard::builder($propertyName);
            $builder->setValue($result['value']);
            
            // FIXME: Need to store this.
            $propertyID = $result['pid'];
            if (null !== $result['pref'])
                $builder->setPref($result['pref']);
            if (null !== $result['valuetype'])
                $builder->setValueType($result['valuetype']);
            if ( array_key_exists('mediatype', $result)
                 && (null !== $result['mediatype']) )
                $builder->setMediaType($result['mediatype']);
            
            if ($builder instanceof TypedPropertyBuilder)
                $this->i_fetchTypesForPropertyID($builder, $propertyID);

            $properties[] = $builder->build();            
        }
        $stmt->closeCursor();

        return empty($properties) ? null : $properties;
    } // i_fetchBasicProperty()
    
    /**
     * Fetches all records for generic, x-tended properties for which we have
     * no detailed specification.
     * @param string $uid The contact uid records are associated with.
     * Numeric, not null.
     * @return NULL|Property[] Returns an array of associated records, or null
     * if none found.
     */
    private function i_fetchXtendedProperties($uid)
    {
    	assert(isset($this->connection));
    	assert(!empty($uid));
    	assert(is_string($uid));
    	
    	// Fetch each property record in turn
    	$stmt = $this->connection->prepare(
                    $this->getQueryInfo('fetch', 'xtended') );
        $stmt->bindValue(':id', $uid);
        $stmt->execute();
        
        /** @var Property[] */
        $properties = [];
        while ($result = $stmt->fetch(\PDO::FETCH_ASSOC))
    	{
            $builder = VCard::builder($result['name'], false);
            $builder->setValue($result['value']);
            
            // FIXME: Need to store this.
            $propertyID = $result['pid'];
            if (null !== $result['pref'])
                $builder->setPref($result['pref']);
            if (null !== $result['mediatype'])
                $builder->setMediaType($result['mediatype']);
            if (null !== $result['valuetype'])
                $builder->setValueType($result['valuetype']);


            $properties[] = $builder->build();            
        }
        $stmt->closeCursor();

        return empty($properties) ? null : $properties;
    }
    
    /**
     * Deletes a CONTACT from the database by uid. Should delete all dependent
     * records (e.g. properties) for that CONTACT as well.
     * @param integer $uid The uid of the record to delete. Numeric,
     * not null.
     * @return bool If a record was deleted, false otherwise.
     * @throws \PDOException On database failure.
     */
    public function deleteContact($uid)
    {
        \assert(isset($this->connection));
        \assert(!empty($uid));
        \assert(\is_string($uid));
        
        $stmt = $this->connection->prepare(
                    $this->getQueryInfo('delete', 'contact') );
        $stmt->bindValue(':uid', $uid);
        $stmt->execute();
        $rows = $stmt->rowCount();
        $stmt->closeCursor();
        
        return (1 === $rows);
    }

} // VCardDB
