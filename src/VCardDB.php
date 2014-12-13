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
class VCardDB
{
// FIXME: Add a method to fetch all contact IDs (without loading the records).
    
    // The product id we will use when creating new vcards
    const VCARD_PRODUCT_ID = '-//VCard Tools//1.0//en';

    /**
     * The \PDO connection used for storage and retrieval.
     */
    private $connection;

    /**
     * Retrieve the current \PDO connection.
     */
    public function getConnection() {return $this->connection;}

    /**
     * Construct a new instance.
     * @param \PDO $connection A \PDO connection to read from/write to. Not null. Caller
     * retains responsibility for connection, but this class shall ensure that
     * the reference is cleared upon clean-up.
     */
    public function __construct(\PDO $connection)
    {
        assert(!empty($connection));
        $this->connection = $connection;
    }

    /**
     * Make sure that $connection is cleared.
     */
    public function __destruct()
    {
        unset($this->connection);
    }

    /**
     * Store the whole vcard to the database, calling sub-functions to store
     * related tables (e.g. address) as necessary.
     * FIXME: None of these routines deal with ENCODING.
     * @param VCard $vcard The record to store.
     * @return integer The new contact id.
     */
    function store(VCard $vcard)
    {
        assert(!empty($this->connection));

        $contactID = $this->i_storeJustContact($vcard);

        foreach ( ['n', 'adr', 'org'] as $propertyName)
        {
            foreach ($vcard->$propertyName as $value)
            {
            	$this->i_storeStructuredProperty( $propertyName, $value,
            			                  $contactID );
            }
        }
        
        foreach ( ['photo', 'logo', 'sound', 'key', 'note', 'tel', 'geo',
        		'email', 'categories'] as $propertyName )
        {
	    foreach ($vcard->$propertyName as $value)
    	    {
    	    	$this->i_storeBasicProperty($propertyName, $value, $contactID);
    	    }
        }

        return $contactID;
    } // store()


    /**
     * Saves the vcard contact data to the database, returns the id of the
     * new connection record.
     * Stores JUST the info from the CONTACT table itself, no sub-tables.
     * @param VCard $vcard The record to store.
     * @return integer The new contact id.
     */
    private function i_storeJustContact(VCard $vcard)
    {
        assert(!empty($this->connection));

        $vcard->setFNAppropriately();

        $stmt = $this->connection->prepare("INSERT INTO CONTACT (KIND, FN, NICKNAME, BDAY, ANNIVERSARY, TZ, ROLE, TITLE, REV, UID, URL) VALUES (:kind, :fn, :nickname, :bday, :anniversary, :tz, :role, :title, :rev, :uid, :url)");

        foreach ( [ 'kind', 'fn', 'bday', 'anniversary', 'rev', 'uid' ]
                  as $simpleProperty )
        {
            assert( $vcard->keyIsSingleValueElement($simpleProperty),
                    $simpleProperty . ' must be a single value element' );
            $stmt->bindValue( ':'.$simpleProperty, empty($vcard->$simpleProperty)
                                ? null : $vcard->$simpleProperty, \PDO::PARAM_STR );
        }

        // HACK: VCard and the spec think URL, NICKNAME, etc. are multiple.
        // Database doesn't. Arbitrarily take the first value.
        foreach (['url', 'nickname', 'role', 'title', 'tz'] as $hackMultiple)
        {
            assert(!($vcard->keyIsSingleValueElement($hackMultiple)),
                    $simpleProperty . ' must NOT be a single value element');
            $hackMultipleValue = $vcard->$hackMultiple;
            if (empty($hackMultipleValue))
            {
                $stmt->bindValue(':'.$hackMultiple, null, \PDO::PARAM_NULL);
            } else {
                $stmt->bindValue(':'.$hackMultiple, $hackMultipleValue[0]);
            }        
        }
    
        $stmt->execute();
        $contact_id = $this->connection->lastInsertId();

        return $contact_id;
    } // i_storeJustContact()

    /**
     * Store a structured property (multiple complex values) which requires a
     * subsidiary table/link table and return the ID of the new record.
     * @param string $propertyName
     * @param array $propertyValue
     * @param integer $contactID
     * @return integer The new property record id.
     */
    private function i_storeStructuredProperty( $propertyName,
    		                                Array $propertyValue,
    		                                $contactID )
    {
    	assert($this->connection !== null);
    	assert($propertyName !== null);
    	assert(is_string($propertyName));
    	assert(!empty($propertyValue));
    	assert($contactID !== null);
    	assert(is_numeric($contactID));

    	static $storeSQL = [
    	    'adr'=>'INSERT INTO CONTACT_ADR (CONTACT_ID, STREET, LOCALITY, REGION, POSTAL_CODE, COUNTRY) VALUES (:ContactID, :StreetAddress, :Locality, :Region, :PostalCode, :Country)',
    	    'org'=>'INSERT INTO CONTACT_ORG (CONTACT_ID, NAME, UNIT1, UNIT2) VALUES (:ContactID, :Name, :Unit1, :Unit2)',
            'n'=>'INSERT INTO CONTACT_N (CONTACT_ID, GIVEN_NAME, ADDIT_NAME, FAMILY_NAME, PREFIXES, SUFFIXES) VALUES (:ContactID, :FirstName, :AdditionalNames, :LastName, :Prefixes, :Suffixes)'
    	];
    	
    	static $fields = [
    	    'adr'=>[ 'StreetAddress', 'Locality', 'Region',
    	             'PostalCode', 'Country'],
    	    'org'=>['Name', 'Unit1', 'Unit2'],
            'n'=>[ 'Prefixes', 'FirstName', 'AdditionalNames', 'LastName',
              'Suffixes' ]
    	];
    	
    	$stmt = $this->connection->prepare($storeSQL[$propertyName]);
        
        $stmt->bindValue(':ContactID', $contactID);
    	foreach($fields[$propertyName] as $key)
    	{
    		$value = empty($propertyValue[$key])
    		           ? null : $propertyValue[$key];
    		$stmt->bindValue(':'.$key, $value, \PDO::PARAM_STR);
    	}
    	
    	$stmt->execute();
    	$propertyID = $this->connection->lastInsertId();
    	
        if ( VCard::keyIsTypeAble($propertyName)
                && (!empty($propertyValue['Type'])) )
            $this->i_associateTypes( $propertyName, $propertyID,
                                   $propertyValue['Type'] );

        return $propertyID;
    } // i_storeStructuredProperty()
        
    /**
     * Create an association between the given types and the property/id
     * combination in the database.
     * @staticvar array $typesSQL SQL statments keyed by property for creating
     * type records in the appropriate property-specific type table.
     * @param type $propertyName The name of the property to associate the types
     * with.
     * @param type $propertyID The id of the property within the appropriate
     * property specific table to associate types with.
     * @param array $types An array of string types. May be empty.
     */
    private function i_associateTypes($propertyName, $propertyID, Array $types)
    {
    	assert($this->connection !== null);
    	assert($propertyName !== null);
    	assert(is_string($propertyName));
    	assert($propertyID !== null);
    	assert(is_numeric($propertyID));
        assert( VCard::keyIsTypeAble($propertyName),
                $propertyName . ' must be a type-able property.');
    	assert($types !== null);
        if (empty($types)) {return;}
        
        static $typesSQL = [
            'adr' => 'INSERT INTO CONTACT_ADR_REL_TYPES (ADR_ID, TYPE_NAME) VALUES (:id, :type)',
            'org' => 'INSERT INTO CONTACT_ORG_REL_TYPES (ORG_ID, TYPE_NAME) VALUES (:id, :type)'
        ];
        
        assert(array_key_exists($propertyName, $typesSQL));

        $stmt = $this->connection->prepare($typesSQL[$propertyName]);
        
        foreach ($types as $type)
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
     * @param string $propertyName The name of the property to store. String,
     * not null.
     * @param mixed $value The value of the property to store. Not empty.
     * @param integer $contactID The ID of the CONTACT to associate the new
     * record with.
     * @return integer The ID of the newly created record.
     */
    private function i_storeBasicProperty($propertyName, $value, $contactID)
    {
    	assert($this->connection !== null);
    	assert($propertyName !== null);
    	assert(is_string($propertyName));
    	assert(!empty($value));
    	assert($contactID !== null);
    	assert(is_numeric($contactID));
    	
    	$storeSQL = [
    	    'note'=>'INSERT INTO CONTACT_NOTE (CONTACT_ID, NOTE) VALUES (:ContactID, :value)',
    	    'tel'=>'INSERT INTO CONTACT_TEL (CONTACT_ID, TEL) VALUES (:ContactID, :value)',
    	    'email'=>'INSERT INTO CONTACT_EMAIL (CONTACT_ID, EMAIL) VALUES (:ContactID, :value)',
    	    'categories'=>'INSERT INTO CONTACT_CATEGORIES(CONTACT_ID, CATEGORY) VALUES (:ContactID, :value)',
    	    'photo'=>'INSERT INTO CONTACT_DATA (CONTACT_ID, DATA_NAME, URL) VALUES (:ContactID, \'photo\', :value)',
    	    'logo'=>'INSERT INTO CONTACT_DATA (CONTACT_ID, DATA_NAME, URL) VALUES (:ContactID, \'logo\', :value)',
    	    'sound'=>'INSERT INTO CONTACT_DATA (CONTACT_ID, DATA_NAME, URL) VALUES (:ContactID, \'sound\', :value)',
            'key'=>'INSERT INTO CONTACT_DATA (CONTACT_ID, DATA_NAME, URL) VALUES (:ContactID, \'key\', :value)',
            'geo'=>'INSERT INTO CONTACT_GEO (CONTACT_ID, GEO) VALUES (:ContactID, :value)'
    	];

    	assert(array_key_exists($propertyName, $storeSQL));
    	
    	$stmt = $this->connection->prepare($storeSQL[$propertyName]);
    	
        $stmt->bindValue(':ContactID', $contactID);
    	$stmt->bindValue(":value", $value);
    	$stmt->execute();
    	$propertyID = $this->connection->lastInsertId();

    	return $propertyID;
    } // i_storeBasicProperty()
    
    /**
     * Fetch all vcards from the database.
     * @param string $kind If kind is given, only fetch those of that kind (e.g.
     * organization).
     * @return array An array of vCards keyed by contact id.
     */
    public function fetchAll($kind='%')
    {
    	assert(isset($this->connection));
    	
    	$stmt = $this->connection->prepare('SELECT * FROM CONTACT WHERE IFNULL(KIND, \'\') LIKE :kind');
    	$stmt->bindValue(":kind", $kind);

        $stmt->execute();
        $result = $stmt->setFetchMode(\PDO::FETCH_ASSOC);

        $vcards = array();

        while ($row = $stmt->fetch())
        {
	    $vcards[$row["CONTACT_ID"]] = $this->i_fetchVCard($row);
        } // while

        $stmt->closeCursor();

        return $vcards;
    } // fetchAll()

    /**
     * Returns all vcards where the fn or categories match the requested search
     * string.
     * @param string $searchString The pattern to search for (SQL matching rules). If
     * omitted, match all cards.
     * @param string $kind If kind is given, return only cards of that kind (e.g.
     * organization).
     * @return array of vCards indexed by contact id.
     */
    public function search($searchString='%', $kind='%')
    {
    	assert(isset($this->connection));
    	
        $stmt = $this->connection->prepare('SELECT CONTACT_ID FROM CONTACT WHERE FN LIKE :searchString AND IFNULL(KIND,\'\') LIKE :kind');
        $stmt->bindValue(":kind", $kind);
        $stmt->bindValue(":searchString", $searchString);
        $stmt->execute();

        $contactIDs = $stmt->fetchAll(\PDO::FETCH_COLUMN, 0);
        $stmt->closeCursor();
 
        $contactIDs += $this->fetchIDsForCategory( $searchString,
        		$kind="%" );
        if (empty($contactIDs)) return array();

        return $this->fetchByID($contactIDs);
    } // search()

    /**
     * Returns a list of all contact_ids where the org.name parameter matches
     * the query.
     * @param string $organizationName The name of the organization to search for. May
     * be a SQL pattern. String, not empty.
     * @param string $kind If kind is provided, limit results to a specific Kind (e.g.
     * individual.
     * @return array The list of contact ids. Actual vCards are not fetched.
     */
    public function fetchIDsForOrganization($organizationName, $kind="%")
    {
    	assert(isset($this->connection));
    	assert(!empty($organizationName));
    	assert(is_string($organizationName));
    	
        $stmt = $this->connection->prepare('SELECT DISTINCT CONTACT_ID FROM CONTACT_ORG WHERE NAME LIKE :organizationName');
        $stmt->bindValue(':organizationName', $organizationName);
        $stmt->execute();
        $contactIDs = $stmt->fetchAll(\PDO::FETCH_COLUMN, 0);
        $stmt->closeCursor();

        if (empty($contactIDs)) return array();

        return $this->filterIDsByKind($contactIDs, $kind);
    } // fetchIDsForOrganization()

    /**
     * Returns only the contact_ids from the input list where kind matches.
     * @param array $contactIDs The list of contact IDs to filter. An array
     * (non-empty) of numerics.
     * @param string $kind The kind of record desired (e.g. individual)
     * @return A new list of any IDs that match.
     */
    public function filterIDsByKind(Array $contactIDs, $kind)
    {
    	assert(isset($this->connection));
    	assert(!empty($contactIDs));
    	assert(is_string($kind));
    	
        $stmt = $this->connection->prepare("SELECT CONTACT_ID FROM CONTACT WHERE CONTACT_ID IN ("
	. implode(",", $contactIDs) . ") AND KIND LIKE :kind");
        $stmt->bindValue(":kind", $kind);
        $stmt->execute();
        $contactIDs = $stmt->fetchAll(\PDO::FETCH_COLUMN, 0);
        $stmt->closeCursor();

        return $contactIDs;    
    } // filterIDsByKind()

    /**
     * Returns a list of all contact_ids in a given category.
     * @param string $category The string representing the category to search for.
     * May be a SQL pattern. Not empty.
     * @param string $kind If given, the kind (e.g. individual) to filter by.
     * @return array An array of contact IDs. No vCards are fetched.
     */
    public function fetchIDsForCategory($category, $kind="%")
    {
    	assert(isset($this->connection));
    	assert(!empty($category));
    	assert(is_string($category));
    	
        $stmt = $this->connection->prepare("SELECT DISTINCT CONTACT_ID FROM CONTACT_CATEGORIES WHERE CATEGORY LIKE :category");
        $stmt->bindValue(":category", $category);
        $stmt->execute();
        $contactIDs = $stmt->fetchAll(\PDO::FETCH_COLUMN, 0);
        $stmt->closeCursor();

        if (empty($contactIDs)) return [];
        return $this->filterIDsByKind($contactIDs, $kind);
    } // fetchIDsForCategory()

    /**
     * Retrieve vCard records for the given Contact IDs.
     * @param array $contactIDs
     * @return array An array of vCards indexed by contact ID.
     */
    public function fetchByID(Array $contactIDs)
    {
    	assert(isset($this->connection));
    	assert(!empty($contactIDs));
    	
        $vcards = array();
        foreach($contactIDs as $contactID)
        {
	    $vcards[$contactID] = $this->fetchOne($contactID);
        }

        return $vcards;
    } // fetchByID()

    /**
     * Fetch a single vcard given a contact_id.
     * @param integer $contactID The ID of the record to fetch. Numeric, not empty.
     * @return VCard|null The completed vcard or false if none found.
     */
    public function fetchOne($contactID)
    {
    	assert(isset($this->connection));
    	assert(!empty($contactID));
    	assert(is_numeric($contactID));
    	
        $stmt = $this->connection->prepare("SELECT * FROM CONTACT WHERE CONTACT_ID = :contactID");
        $stmt->bindValue(":contactID", $contactID);
        $stmt->execute();
        assert($stmt->rowCount() <= 1);
                
        $result = $stmt->setFetchMode(\PDO::FETCH_ASSOC);

        $vcard = false;

        $row = $stmt->fetch();
        if ($row != false) $vcard = $this->i_fetchVCard($row);
        $stmt->closeCursor();

        return $vcard;
    } // fetchOne()

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
        $contactID = $row["CONTACT_ID"];

        $simpleCols = [ 'KIND', 'FN', 'NICKNAME', 'BDAY', 'ANNIVERSARY',
                        'TITLE', 'ROLE', 'REV', 'UID', 'URL', 'VERSION' ];
        foreach ($simpleCols as $col)
        {
            if (!empty($row[$col])) $vcard->$col($row[$col]);
        }
        
	$vcard->prodid(self::VCARD_PRODUCT_ID);
        
        foreach (['org', 'adr', 'n'] as $structuredProperty)
        {
            $vcard->$structuredProperty
                = $this->i_fetchStructuredProperty($structuredProperty, $contactID);
        }
        
        // Basic Properties
        foreach ( ['note', 'email', 'tel', 'categories', 'geo', 'logo',
        		'photo', 'sound', 'key'] as $property )
        {
            $vcard->$property
                = $this->i_fetchBasicProperty($property, $contactID);
        }

        return $vcard;
    } // i_fetchVCard()

    /**
     * Fetch the IDs of all properties in a link-table for the named
     * property (e.g. email). The IDs will only make sense in the context of
     * the appropriate subsidiary table for that property.
     * @param string $propertyName The name of the property to find associated
     * records for. String, not null.
     * @param numeric $contactID The ID of the CONTACT the records will be
     * associated with. Numeric, not null.
     * @return Null|array
     */
    
    /**
     * Retrieve all types associated with a given property/id in the db.
     * @staticvar array $fetchTypesSQL SQL statements keyed by property name
     * for fetching types.
     * @param type $propertyName The name of the property to fetch types for.
     * @param type $propertyID The ID of the property to fetch types for within
     * the property-specific sub-table.
     * @return type An array of string types.
     */
    private function i_fetchTypesForPropertyID($propertyName, $propertyID)
    {
        assert(null !== $propertyName);
        assert(is_string($propertyName));
        assert(null !== $propertyID);
        assert(is_numeric($propertyID));
        assert( VCard::keyIsTypeAble($propertyName),
                $propertyName . ' must be a type-able property.' );
        
        static $fetchTypesSQL = [
            'adr' => 'SELECT TYPE_NAME FROM CONTACT_ADR_REL_TYPES WHERE ADR_ID=:id',
            'org' => 'SELECT TYPE_NAME FROM CONTACT_ORG_REL_TYPES WHERE ORG_ID=:id'
        ];
        \assert(\array_key_exists($propertyName, $fetchTypesSQL));
        
        $stmt = $this->connection->prepare($fetchTypesSQL[$propertyName]);
    	$stmt->bindValue(":id", $propertyID);
    	$stmt->execute();
    	
    	$results = $stmt->fetchAll(\PDO::FETCH_COLUMN, 0);
    	$stmt->closeCursor();

    	return $results ? $results : null;
    }
    
    /**
     * Fetch all records for the named structured property (e.g. ADR) and
     * return them in an array.
     * @param string $propertyName The name of the associate records to
     * retrieve. String, not null.
     * @param integer $contactID The ID of the CONTACT the records are
     * associated with. Numeric, not null.
     * @return NULL|array An array of the structured properties or null if none
     * available.
     */
    private function i_fetchStructuredProperty($propertyName, $contactID)
    {
    	assert(isset($this->connection));
    	assert($propertyName !== null);
    	assert(is_string($propertyName));
    	assert(!empty($contactID));
    	assert(is_numeric($contactID));
    	
    	static $getRecSql = [
    	        'adr'=>'SELECT ADR_ID AS PropID, STREET AS StreetAddress, LOCALITY AS Locality, REGION AS Region, POSTAL_CODE AS PostalCode, COUNTRY AS Country FROM CONTACT_ADR WHERE CONTACT_ID=:id',
    	        'org'=>'SELECT ORG_ID AS PropID, NAME AS Name, UNIT1 AS Unit1, UNIT2 AS Unit2  FROM CONTACT_ORG WHERE CONTACT_ID=:id',
                'n'=>'SELECT N_ID AS PropID, GIVEN_NAME AS FirstName, ADDIT_NAME AS AdditionalNames, FAMILY_NAME as LastName, PREFIXES AS Prefixes, SUFFIXES AS Suffixes FROM CONTACT_N WHERE CONTACT_ID=:id'
    	];
    	
    	assert(array_key_exists($propertyName, $getRecSql));
    	    	 
    	// Fetch each $propertyName record in turn    	
    	$propList = array();

    	$stmt = $this->connection->prepare($getRecSql[$propertyName]);
        $stmt->bindValue(":id", $contactID);
        $stmt->execute();
        
        while ($result = $stmt->fetch(\PDO::FETCH_ASSOC))
    	{
            $propertyID = $result['PropID'];
            unset($result['PropID']);
            $record = \array_filter($result, '\strlen');
            if (VCard::keyIsTypeAble($propertyName))
            {
                $types = $this->i_fetchTypesForPropertyID($propertyName, $propertyID);
                if (!empty($types)) {$record['Type'] = $types;}
            }
            
            $propList[] = $record;
    	}
        $stmt->closeCursor();
    	return empty($propList) ? null : $propList;    	 
    } // i_fetchStructuredProperty()
    
    /**
     * Fetches all records of a basic multi-value property associated with
     * the given contact ID.
     * @param string $propertyName The name of the property to return
     * records for (e.g. email). String, not null.
     * @param numeric $contactID The contact ID records are associated with.
     * Numeric, not null.
     * @return NULL|array Returns an array of associated records, or null if
     * none found.
     */
    private function i_fetchBasicProperty($propertyName, $contactID)
    {
    	assert(isset($this->connection));
    	assert($propertyName !== null);
    	assert(is_string($propertyName));
    	assert($contactID !== null);
    	assert(is_numeric($contactID));
    	    	
    	static $getRecSql = [
            'note'=>'SELECT NOTE FROM CONTACT_NOTE WHERE CONTACT_ID=:id',
            'tel'=>"SELECT TEL FROM CONTACT_TEL WHERE CONTACT_ID=:id",
            'email'=>'SELECT EMAIL FROM CONTACT_EMAIL WHERE CONTACT_ID=:id',
            'categories'=>'SELECT CATEGORY FROM CONTACT_CATEGORIES WHERE CONTACT_ID=:id',
            'logo'=>'SELECT URL FROM CONTACT_DATA WHERE CONTACT_ID=:id AND DATA_NAME=\'logo\'',
            'photo'=>'SELECT URL FROM CONTACT_DATA WHERE CONTACT_ID=:id AND DATA_NAME=\'photo\'',
            'sound'=>'SELECT URL FROM CONTACT_DATA WHERE CONTACT_ID=:id AND DATA_NAME=\'sound\'',
            'key'=>'SELECT URL FROM CONTACT_DATA WHERE CONTACT_ID=:id AND DATA_NAME=\'key\'',
            'geo'=>'SELECT GEO FROM CONTACT_GEO WHERE CONTACT_ID=:id'
    	];
    	
    	assert(array_key_exists($propertyName, $getRecSql));
    	    
    	// Fetch each property record in turn
    	$stmt = $this->connection->prepare($getRecSql[$propertyName]);
        $stmt->bindValue(':id', $contactID);
        $stmt->execute();
        $propList = $stmt->fetchAll(\PDO::FETCH_COLUMN, 0);
        $stmt->closeCursor();
    	return empty($propList) ? null : $propList;
    } // i_fetchBasicProperty()    
    
    /**
     * Deletes a CONTACT from the database by ID. Should delete all dependent
     * records (e.g. properties) for that CONTACT as well.
     * @param integer $contactID The ID of the record to delete. Numeric,
     * not null.
     * @return bool If a record was deleted, false otherwise.
     * @throws \PDOException On database failure.
     */
    public function deleteContact($contactID)
    {
        \assert(isset($this->connection));
        \assert(null !== $contactID);
        \assert(\is_numeric($contactID));
        
        $stmt = $this->connection->prepare(
            'DELETE FROM CONTACT WHERE CONTACT_ID=:contactID' );
        $stmt->bindValue(':contactID', $contactID);
        $stmt->execute();
        $rows = $stmt->rowCount();
        $stmt->closeCursor();
        
        return (1 === $rows);
    }

} // VCardDB
