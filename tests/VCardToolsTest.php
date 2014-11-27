<?php
/**
 * Unit tests for the vcard-tools.php file routines.
 * @author Eric Vought <evought@pobox.com>
 * 2014-11-16
 * @copyright Eric Vought 2014, Some rights reserved.
 * @license MIT http://opensource.org/licenses/MIT
 */
require_once "vcard-tools.php";
require_once "vcard.php";

class VCardToolsTest extends PHPUnit_Extensions_Database_TestCase
{
    // only instantiate pdo once for test clean-up/fixture load
    static private $pdo = null;

    // only instantiate PHPUnit_Extensions_Database_DB_IDatabaseConnection once
    private $conn = null;

    /**
     * @return PHPUnit_Extensions_Database_DB_IDatabaseConnection
     */
    public function getConnection()
    {
        if ($this->conn === null)
        {
            if (self::$pdo == null)
            {
               self::$pdo = new PDO( $GLOBALS['DB_DSN'], $GLOBALS['DB_USER'], 
                                     $GLOBALS['DB_PASSWD'] );

               self::$pdo->setAttribute( PDO::ATTR_ERRMODE,
                                         PDO::ERRMODE_EXCEPTION );
            }
            $this->conn = $this->createDefaultDBConnection( self::$pdo,
                                                 $GLOBALS['DB_DBNAME'] );
        }

        return $this->conn;
    }

    public function getDataSet()
    {
        return $this->createMySQLXMLDataSet('tests/emptyVCARDDB.xml');
    }

    /**
     * Work around for MySQL problem with truncating tables which are
     * referenced by foreign keys.
     * @see https://github.com/sebastianbergmann/dbunit/issues/37 .
     * @return \PHPUnit_Extensions_Database_Operation
     */
    protected function getSetUpOperation()
    {
        return new \PHPUnit_Extensions_Database_Operation_Composite(array(
            \PHPUnit_Extensions_Database_Operation_Factory::DELETE_ALL(),
            \PHPUnit_Extensions_Database_Operation_Factory::INSERT()
        ));
    }
    
    /**
     * Compare a created vcard with the one retrieved from the database,
     * taking into account fields changed during the save/retrieve operation.
     * @param VCard $vcard
     * @param VCard $dbVCard
     */
    protected function compareVCards(VCard $vcard, VCard $dbVCard)
    {
    	$this->assertNotNull($dbVCard);

    	$this->assertEquals(VCardDB::VCARD_PRODUCT_ID, $dbVCard->prodid);
        unset($dbVCard->prodid);
        
        $this->assertEquals($vcard, $dbVCard);
    }

    /**
     * Ensure that we can instantiate a VCardDB instance.
     */
    public function testCreateVCardDB()
    {
        $vcardDB = new VCardDB(self::$pdo);
        $this->assertNotEmpty($vcardDB);
        $this->assertInstanceOf('VCardDB', $vcardDB);

	$this->assertSame(self::$pdo, $vcardDB->getConnection());

        return $vcardDB;
    }

    /**
     * @depends testCreateVCardDB
     */
    public function testFetchWhenEmpty(VCardDB $vcardDB)
    {
        $this->assertEquals( 0, $this->getConnection()->getRowCount('CONTACT'),
                             "Precondition" );
	$vcards = $vcardDB->fetchAll();

	$this->assertEmpty($vcards);
        $this->assertEquals( 0, $this->getConnection()->getRowCount('CONTACT'),
                             "Postcondition" );
    }

    /**
     * @depends testCreateVCardDB
     */
    public function testSearchWhenEmpty(VCardDB $vcardDB)
    {
        $this->assertEquals( 0, $this->getConnection()->getRowCount('CONTACT'),
                             "Precondition" );
	$vcards = $vcardDB->search("bloomers");

	$this->assertEmpty($vcards);
        $this->assertEquals( 0, $this->getConnection()->getRowCount('CONTACT'),
                             "Postcondition" );
    }

    /**
     * @depends testCreateVCardDB
     */
    public function testStoreAndRetrieveTrivialVCard(VCardDB $vcardDB)
    {
        $this->assertEquals( 0, $this->getConnection()->getRowCount('CONTACT'),
                             "Precondition" );

        $fn = 'foo';
        $vcard = new VCard();
	$vcard->fn = $fn;

        $contactID = $vcardDB->store($vcard);
        $this->assertEquals( 1, $this->getConnection()->getRowCount('CONTACT'),
                             "After storing " . $contactID );

        $resultVCard = VCardDB::fetch_vcard_from_db(self::$pdo, $contactID);

        $this->compareVCards($vcard, $resultVCard);
        return $vcardDB;
    }

    /**
     * @depends testStoreAndRetrieveTrivialVCard
     */
    public function testFetchWithOneVCard(VCardDB $vcardDB)
    {
    	$this->assertEquals( 0, $this->getConnection()->getRowCount('CONTACT'),
    			"Precondition" );
    	
    	$fn = 'foo';
    	$vcard = new VCard();
    	$vcard->fn = $fn;
    	
    	$contactID = $vcardDB->store($vcard);
    	$this->assertEquals( 1, $this->getConnection()->getRowCount('CONTACT'),
    			"After storing " . $contactID );
    	
    	$resultVCards = $vcardDB->fetchAll();
    	$this->assertNotEmpty($resultVCards);
    	$this->assertCount(1, $resultVCards);
    	$this->assertArrayHasKey($contactID, $resultVCards);

    	$resultVCard = $resultVCards[$contactID];
        $this->compareVCards($vcard, $resultVCard);
    }

    /**
     * @depends testStoreAndRetrieveTrivialVCard
     */
    public function testSearchWithOneVCardFails(VCardDB $vcardDB)
    {
    	$this->assertEquals( 0, $this->getConnection()->getRowCount('CONTACT'),
    			"Precondition" );
    	 
    	$fn = 'foo';
    	$vcard = new VCard();
    	$vcard->fn = $fn;
    	 
    	$contact_id = $vcardDB->store($vcard);
    	$this->assertEquals( 1, $this->getConnection()->getRowCount('CONTACT'),
    			"After storing " . $contact_id );
    	 
	$vcards = $vcardDB->search("bloomers");

	$this->assertEmpty($vcards);
        $this->assertEquals( 1, $this->getConnection()->getRowCount('CONTACT'),
                             "Postcondition" );
    }

    /**
     * @depends testStoreAndRetrieveTrivialVCard
     */
    public function testSearchWithOneVCardMatches(VCardDB $vcardDB)
    {
    	$this->assertEquals( 0, $this->getConnection()->getRowCount('CONTACT'),
    			"Precondition" );
    
    	$fn = 'foo';
    	$vcard = new VCard();
    	$vcard->fn = $fn;
    
    	$contactID = $vcardDB->store($vcard);
    	$this->assertEquals( 1, $this->getConnection()->getRowCount('CONTACT'),
    			"After storing " . $contactID );
    
    	$resultVCards = $vcardDB->search("foo");
        	
    	$this->assertNotEmpty($resultVCards);
    	$this->assertCount(1, $resultVCards);
    	$this->assertArrayHasKey($contactID, $resultVCards);
    	
    	$resultVCard = $resultVCards[$contactID];
        $this->compareVCards($vcard, $resultVCard);    	 
    }
    
    
    /**
     * @depends testStoreAndRetrieveTrivialVCard
     */
    public function testStoreAndRetrieveVCard(VCardDB $vcardDB)
    {
        $this->assertEquals( 0, $this->getConnection()->getRowCount('CONTACT'),
                             "Precondition" );

        $expected = [
                        'n_FirstName' => 'Fred',
                        'n_LastName' => 'Jones',
                        'url' => 'http://golf.com',
                        'fn' => 'Fred Jones'
                     ];
        $vcard = new VCard();
	$vcard->fn = $expected['fn'];
        $vcard->n($expected['n_FirstName'], 'FirstName');
        $vcard->n($expected['n_LastName'], 'LastName');
	$vcard->url($expected['url']);
	$vcard->fn = $expected['fn'];

        $contactID = $vcardDB->store($vcard);
        $this->assertEquals( 1, $this->getConnection()->getRowCount('CONTACT'),
                             "After storing " . $contactID );
        $this->assertEquals( 0,
            $this->getConnection()->getRowCount('CONTACT_MAIL_ADDRESS'),
            "After storing " . $contactID );
        $this->assertEquals( 0,
            $this->getConnection()->getRowCount('CONTACT_REL_MAIL_ADDRESS'),
            "After storing " . $contactID );
        $resultVCard = VCardDB::fetch_vcard_from_db(self::$pdo, $contactID);

        $this->compareVCards($vcard, $resultVCard);    	 
	
        return $vcardDB;
    } //testStoreAndRetrieveVCard()

    /**
     * @depends testStoreAndRetrieveVCard
     */
    public function testStoreAndRetrieveWAddress(VCardDB $vcardDB)
    {
        $this->assertEquals( 0, $this->getConnection()->getRowCount('CONTACT'),
                             "Precondition" );

        $expected = [
                        'n_FirstName' => 'Fred',
                        'n_LastName' => 'Jones',
                        'adr_StreetAddress' => '47 Some Street',
			'adr_Locality' => 'Birmingham',
                        'adr_Region' => 'AL',
                        'fn' => 'Fred Jones'
                     ];
        $vcard = new VCard();
	$vcard->fn = $expected['fn'];
        $vcard->n($expected['n_FirstName'], 'FirstName');
        $vcard->n($expected['n_LastName'], 'LastName');
        $vcard->adr($expected['adr_StreetAddress'], 'StreetAddress');
        $vcard->adr($expected['adr_Locality'], 'Locality');
        $vcard->adr($expected['adr_Region'], 'Region');
	$vcard->fn = $expected['fn'];

        $contactID = $vcardDB->store($vcard);
        $this->assertEquals( 1, $this->getConnection()->getRowCount('CONTACT'),
                             "After storing " . $contactID );
        $this->assertEquals( 1,
            $this->getConnection()->getRowCount('CONTACT_MAIL_ADDRESS'),
            "After storing " . $contactID );
        $this->assertEquals( 1,
            $this->getConnection()->getRowCount('CONTACT_REL_MAIL_ADDRESS'),
            "After storing " . $contactID );
        $resultVCard = VCardDB::fetch_vcard_from_db(self::$pdo, $contactID);

        $this->compareVCards($vcard, $resultVCard);    	 
    } //testStoreAndRetrieveWAddress()

    /**
     * @depends testStoreAndRetrieveVCard
     */
    public function testStoreAndRetrieveWEmail(VCardDB $vcardDB)
    {
        $this->assertEquals( 0, $this->getConnection()->getRowCount('CONTACT'),
                             "Precondition" );

        $expected = [
                        'n_FirstName' => 'Fred',
                        'n_LastName' => 'Jones',
                        'email' => 'noone@nowhere.org',
                        'fn' => 'Fred Jones'
                     ];
        $vcard = new VCard();
	$vcard->fn = $expected['fn'];
        $vcard->n($expected['n_FirstName'], 'FirstName');
        $vcard->n($expected['n_LastName'], 'LastName');
        $vcard->email($expected['email']);
	$vcard->fn = $expected['fn'];

        $contactID = $vcardDB->store($vcard);
        $this->assertEquals( 1, $this->getConnection()->getRowCount('CONTACT'),
                             "After storing " . $contactID );
        $this->assertEquals( 1,
            $this->getConnection()->getRowCount('CONTACT_EMAIL'),
            "After storing " . $contactID );
        $this->assertEquals( 1,
            $this->getConnection()->getRowCount('CONTACT_REL_EMAIL'),
            "After storing " . $contactID );

        $resultVCard = VCardDB::fetch_vcard_from_db(self::$pdo, $contactID);
        $this->compareVCards($vcard, $resultVCard);
    } //testStoreAndRetrieveWEmail()

    /**
     * @depends testStoreAndRetrieveVCard
     */
    public function testStoreAndRetrieveWOrg(VCardDB $vcardDB)
    {
        $this->assertEquals( 0, $this->getConnection()->getRowCount('CONTACT'),
                             "Precondition" );

        $expected = [
                        'n_FirstName' => 'Raith',
                        'n_LastName' => 'Seinar',
                        'org' => 'Seinar Fleet Systems',
                        'title' => 'CEO',
                        'fn' => 'Raith Seinar'
                     ];
        $vcard = new VCard();
	$vcard->fn = $expected['fn'];
        $vcard->n($expected['n_FirstName'], 'FirstName');
        $vcard->n($expected['n_LastName'], 'LastName');
        $vcard->title($expected['title']);
        $vcard->org($expected['org'], 'Name');

        $contactID = $vcardDB->store($vcard);
        $this->assertEquals( 1, $this->getConnection()->getRowCount('CONTACT'),
                             "After storing " . $contactID );
        $this->assertEquals( 1,
            $this->getConnection()->getRowCount('CONTACT_ORG'),
            "After storing " . $contactID );
        $this->assertEquals( 1,
            $this->getConnection()->getRowCount('CONTACT_REL_ORG'),
            "After storing " . $contactID );
        $resultVCard = VCardDB::fetch_vcard_from_db(self::$pdo, $contactID);
        $this->compareVCards($vcard, $resultVCard);
        
        return $vcardDB;
    } //testStoreAndRetrieveWOrg()

    /**
     * @depends testStoreAndRetrieveVCard
     */
    public function testStoreAndRetrieveWLogo(VCardDB $vcardDB)
    {
        $this->assertEquals( 0, $this->getConnection()->getRowCount('CONTACT'),
                             "Precondition" );

        $expected = [
                        'org_name'    => 'Seinar Fleet Systems',
                        'org_unit1'   => 'Seinar Advanced Projects Laboratory',
			'org_unit2'   => 'TIE AO1X Division',
                        'fn'          => 'Seinar APL TIE AO1X Division',
			'logo' => 'http://img1.wikia.nocookie.net/__cb20080311192948/starwars/images/3/39/Sienar.svg'
                     ];
        $vcard = new VCard();
	$vcard->fn = $expected['fn'];
        $vcard->org($expected['org_name'], 'Name');
        $vcard->org($expected['org_unit1'], 'Unit1');
        $vcard->org($expected['org_unit2'], 'Unit2');
        $vcard->logo($expected['logo']);

        $contactID = $vcardDB->store($vcard);
        $this->assertEquals( 1, $this->getConnection()->getRowCount('CONTACT'),
                             "After storing " . $contactID );
        $this->assertEquals( 1,
            $this->getConnection()->getRowCount('CONTACT_ORG'),
            "After storing " . $contactID );
        $this->assertEquals( 1,
            $this->getConnection()->getRowCount('CONTACT_REL_ORG'),
            "After storing " . $contactID );
        $this->assertEquals( 1,
            $this->getConnection()->getRowCount('CONTACT_DATA'),
            "After storing " . $contactID );
        $this->assertEquals( 1,
            $this->getConnection()->getRowCount('CONTACT_REL_DATA'),
            "After storing " . $contactID );

        $resultVCard = VCardDB::fetch_vcard_from_db(self::$pdo, $contactID);
        $this->compareVCards($vcard, $resultVCard);
    } //testStoreAndRetrieveWLogo()

    /**
     * @depends testStoreAndRetrieveVCard
     */
    public function testStoreAndRetrieveWNote(VCardDB $vcardDB)
    {
        $this->assertEquals( 0, $this->getConnection()->getRowCount('CONTACT'),
                             "Precondition" );

        $expected = [
                        'fn'          => 'Carpenter',
			'note' => 'It is time to talk of many things...'
                     ];
        $vcard = new VCard();
	$vcard->fn = $expected['fn'];
        $vcard->note($expected['note']);

        $contactID = $vcardDB->store($vcard);
        $this->assertEquals( 1, $this->getConnection()->getRowCount('CONTACT'),
                             "After storing " . $contactID );
        $this->assertEquals( 1,
            $this->getConnection()->getRowCount('CONTACT_NOTE'),
            "After storing " . $contactID );
        $this->assertEquals( 1,
            $this->getConnection()->getRowCount('CONTACT_REL_NOTE'),
            "After storing " . $contactID );

        $resultVCard = VCardDB::fetch_vcard_from_db(self::$pdo, $contactID);
        $this->compareVCards($vcard, $resultVCard);
    } //testStoreAndRetrieveWNote()

    /**
     * @depends testStoreAndRetrieveVCard
     */
    public function testStoreAndRetrieveWTel(VCardDB $vcardDB)
    {
        $this->assertEquals( 0, $this->getConnection()->getRowCount('CONTACT'),
                             "Precondition" );

        $expected = [
                        'fn'          => 'Information',
			'tel' => '555-1212'
                     ];
        $vcard = new VCard();
	$vcard->fn = $expected['fn'];
        $vcard->tel($expected['tel']);

        $contactID = $vcardDB->store($vcard);
        $this->assertEquals( 1, $this->getConnection()->getRowCount('CONTACT'),
                             "After storing " . $contactID );
        $this->assertEquals( 1,
            $this->getConnection()->getRowCount('CONTACT_PHONE_NUMBER'),
            "After storing " . $contactID );
        $this->assertEquals( 1,
            $this->getConnection()->getRowCount('CONTACT_REL_PHONE_NUMBER'),
            "After storing " . $contactID );

        $resultVCard = VCardDB::fetch_vcard_from_db(self::$pdo, $contactID);
	$this->compareVCards($vcard, $resultVCard);
    } //testStoreAndRetrieveWTel()
    
    /**
     * @depends testStoreAndRetrieveVCard
     */
    public function testStoreAndRetrieveWCategory(VCardDB $vcardDB)
    {
    	$this->assertEquals( 0, $this->getConnection()->getRowCount('CONTACT'),
    			"Precondition" );
    
    	$expected = [
                     'fn'          => 'Sigmund Freud',
    	             'category'    => 'mental health'
    		    ];
    	$vcard = new VCard();
    	$vcard->fn = $expected['fn'];
    	$vcard->categories($expected['category']);
    
    	$contactID = $vcardDB->store($vcard);
    	$this->assertEquals( 1, $this->getConnection()->getRowCount('CONTACT'),
    			"After storing " . $contactID );
    	$this->assertEquals( 1,
    			$this->getConnection()->getRowCount('CONTACT_CATEGORIES'),
    			"After storing " . $contactID );
    	$this->assertEquals( 1,
    			$this->getConnection()->getRowCount('CONTACT_REL_CATEGORIES'),
    			"After storing " . $contactID );

    	$resultVCard = VCardDB::fetch_vcard_from_db(self::$pdo, $contactID);
        $this->compareVCards($vcard, $resultVCard);
    } //testStoreAndRetrieveWCategory()
    
    /**
     * @depends testStoreAndRetrieveWOrg
     */
    public function testFetchIDsForOrganization(VCardDB $vcardDB)
    {    	    	    	
    	$raithSeinar = new VCard();
    	$raithSeinar -> n('Raith', 'FirstName')
    	             -> n('Seinar', 'LastName')
    	             -> org('Seinar Fleet Systems', 'Name')
    	             -> title('CEO')
    	             -> fn('Raith Seinar');
    	$rSContactID = $vcardDB->store($raithSeinar);
    	
    	$seinarAPL = new VCard();
    	$seinarAPL   -> org('Seinar Fleet Systems', 'Name')
    	             -> org('Seinar Advanced Projects Laboratory', 'Unit1')
    	             -> org('TIE AO1X Division', 'Unit2')
    	             -> fn('Seinar APL TIE AO1X Division')
    	             -> logo('http://img1.wikia.nocookie.net/__cb20080311192948/starwars/images/3/39/Sienar.svg');
    	$sAPLContactID = $vcardDB->store($seinarAPL);
    	
    	$dDBinks = new vCard();
    	$dDBinks     -> n('Darth Darth', 'FirstName')
    	             -> n('Binks', 'LastName')
    	             -> org('Sith', 'Name')
    	             -> fn('Darth Darth Binks');
    	$dDBContactID = $vcardDB->store($dDBinks);
    	
    	$this->assertEquals( 3, $this->getConnection()->getRowCount('CONTACT'));
    	
    	$IDs = $vcardDB->fetchIDsForOrganization('Seinar Fleet Systems');
    	
    	$this->assertNotEmpty($IDs);
    	$this->assertInternalType("array", $IDs);
    	
    	$this->assertCount( 2, $IDs,
    			       print_r($IDs, true)
    			       . ' rs: ' . $rSContactID
                               . ' apl: ' . $sAPLContactID
                               . ' ddb: ' . $dDBContactID);
    	
    	$this->assertContains($rSContactID, $IDs);
    	$this->assertContains($sAPLContactID, $IDs);
    	// Binks is left out (as he should be).
    }
} // VCardToolsTest
?>
