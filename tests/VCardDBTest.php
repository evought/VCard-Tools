<?php
/**
 * Unit tests for the vcard-tools.php file routines.
 * @author Eric Vought <evought@pobox.com>
 * 2014-11-16
 * @copyright Eric Vought 2014, Some rights reserved.
 * @license MIT http://opensource.org/licenses/MIT
 */
use EVought\vCardTools\VCard as VCard;
use EVought\vCardTools\VCardDB as VCardDB;

class VCardDBTest extends PHPUnit_Extensions_Database_TestCase
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
               self::$pdo = vcard_db_connect();
            }
            $this->conn = $this->createDefaultDBConnection( self::$pdo,
                                                 VCARD_DBNAME );
        }

        return $this->conn;
    }

    public function getDataSet()
    {
        return $this->createMySQLXMLDataSet(__DIR__ . '/emptyVCARDDB.xml');
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
     * Check the row counts in the database after a query via assertions.
     * @param array $tables An associative array mapping table names to
     * expected counts.
     * @param VCard $vcard If provided, a VCard to print for diagnostic
     * purposes if row counts do not match.
     */
    protected function checkRowCounts(Array $tables, VCard $vcard = null)
    {
        assert(!empty($tables));
        $xtraInfo = '';
        if (null === $vcard)
        {
            $xtraInfo = ' after storing ' . print_r($vcard, true);
        }
        
        foreach ($tables as $table => $count)
        {
            $this->assertEquals( $count, $this->getConnection()->getRowCount($table),
                'Row count should be ' . $count . ' in '. $table
                    . $xtraInfo );
        }
    }
    
    /**
     * Some cards for testing.
     * @return an individual VCard.
     */
    public function getRaithSeinar()
    {
    	$path = __DIR__ . '/vcards/RaithSeinar.vcf';
    	$vcard = new vCard($path);
    	unset($vcard->version); // don't want version to cause == to fail.
    	return $vcard;
    }
    
    /**
     * Some cards for testing.
     * @return an organization VCard.
     */
    public function getSeinarAPL()
    {
    	$path = __DIR__ . '/vcards/SeinarAPL.vcf';
   	$vcard = new vCard($path); // don't want version to cause == to fail.
   	unset($vcard->version);
   	return $vcard;    	 
    }

    /**
     * Some cards for testing.
     * @return an individual VCard.
     */
    public function getDDBinks()
    {
    	$path = __DIR__ . '/vcards/DDBinks.vcf';
   	$vcard = new vCard($path); // don't want version to cause == to fail.
   	unset($vcard->version);
   	return $vcard;
    }

    /**
     * Ensure that we can instantiate a VCardDB instance.
     */
    public function testCreateVCardDB()
    {
        $vcardDB = new VCardDB(self::$pdo);
        $this->assertNotEmpty($vcardDB);
        $this->assertInstanceOf('EVought\vCardTools\VCardDB', $vcardDB);

	$this->assertSame(self::$pdo, $vcardDB->getConnection());

        return $vcardDB;
    }

    /**
     * @depends testCreateVCardDB
     */
    public function testFetchWhenEmpty(VCardDB $vcardDB)
    {
        $this->checkRowCounts(['CONTACT'=>0]);

	$vcards = $vcardDB->fetchAll();

	$this->assertEmpty($vcards);
        $this->checkRowCounts(['CONTACT'=>0]);
    }

    /**
     * @depends testCreateVCardDB
     */
    public function testSearchWhenEmpty(VCardDB $vcardDB)
    {
        $this->checkRowCounts(['CONTACT'=>0]);
	$vcards = $vcardDB->search("bloomers");

	$this->assertEmpty($vcards);
        $this->checkRowCounts(['CONTACT'=>0]);
    }

    /**
     * @depends testCreateVCardDB
     */
    public function testStoreAndRetrieveTrivialVCard(VCardDB $vcardDB)
    {
        $this->checkRowCounts(['CONTACT'=>0]);

        $fn = 'foo';
        $vcard = new VCard();
	$vcard->fn = $fn;

        $contactID = $vcardDB->store($vcard);
        $this->checkRowCounts(['CONTACT'=>1], $vcard);

        $resultVCard = $vcardDB->fetchOne($contactID);

        $this->compareVCards($vcard, $resultVCard);
        return $vcardDB;
    }

    /**
     * @depends testStoreAndRetrieveTrivialVCard
     */
    public function testFetchWithOneVCard(VCardDB $vcardDB)
    {
    	$this->checkRowCounts(['CONTACT'=>0]);
    	
    	$fn = 'foo';
    	$vcard = new VCard();
    	$vcard->fn = $fn;
    	
    	$contactID = $vcardDB->store($vcard);
    	$this->checkRowCounts(['CONTACT'=>1], $vcard);
    	
    	$resultVCards = $vcardDB->fetchAll();
    	$this->assertNotEmpty($resultVCards, print_r($vcardDB->fetchOne($contactID), true));
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
    	$this->checkRowCounts(['CONTACT'=>0]);
    	 
    	$fn = 'foo';
    	$vcard = new VCard();
    	$vcard->fn = $fn;
    	 
    	$contact_id = $vcardDB->store($vcard);
    	$this->checkRowCounts(['CONTACT'=>1], $vcard);
    	 
	$vcards = $vcardDB->search("bloomers");

	$this->assertEmpty($vcards);
    }


    
    
    /**
     * @depends testStoreAndRetrieveTrivialVCard
     */
    public function testStoreAndRetrieveVCard(VCardDB $vcardDB)
    {
        $this->checkRowCounts(['CONTACT'=>0]);

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

        $contactID = $vcardDB->store($vcard);
        $this->checkRowCounts( ['CONTACT'=>1, 'CONTACT_N'=>1, 'CONTACT_ADR'=>0],
                               $vcard );
        $resultVCard = $vcardDB->fetchOne($contactID);

        $this->compareVCards($vcard, $resultVCard);    	 
	
        return $vcardDB;
    } //testStoreAndRetrieveVCard()
    
    /**
     * @depends testStoreAndRetrieveVCard
     */
    public function testStoreAndRetrieveVCardWAnniversary(VCardDB $vcardDB)
    {
        $this->checkRowCounts(['CONTACT'=>0]);

        $expected = [
                        'n_FirstName' => 'Fred',
                        'n_LastName' => 'Jones',
                        'anniversary' => '2012-09-01 00:00:00',
                        'fn' => 'Fred Jones'
                     ];
        $vcard = new VCard();
	$vcard->fn = $expected['fn'];
        $vcard->n($expected['n_FirstName'], 'FirstName');
        $vcard->n($expected['n_LastName'], 'LastName');
	$vcard->anniversary($expected['anniversary']);

        $contactID = $vcardDB->store($vcard);
        $this->checkRowCounts( ['CONTACT'=>1, 'CONTACT_N'=>1, 'CONTACT_ADR'=>0],
                               $vcard );
        $resultVCard = $vcardDB->fetchOne($contactID);

        $this->compareVCards($vcard, $resultVCard);    	 
	
        return $vcardDB;
    }
    
    /**
     * @depends testStoreAndRetrieveVCard
     */
    public function testStoreAndRetrieveWName(VCardDB $vcardDB)
    {
        $this->checkRowCounts(['CONTACT'=>0]);
        
        $n = ['FirstName'=>'Fred', 'LastName'=>'Jones'];
        $fn = 'Fred Jones';
        $vcard = new VCard();
	$vcard->fn = $fn;
        $vcard->n = [$n];

        $contactID = $vcardDB->store($vcard);
        $this->checkRowCounts( ['CONTACT'=>1, 'CONTACT_N'=>1],
                               $vcard );
        $resultVCard = $vcardDB->fetchOne($contactID);

        $this->compareVCards($vcard, $resultVCard);

        return $vcardDB;
    } //testStoreAndRetrieveWName()
    
     /**
     * @depends testStoreAndRetrieveWName
     */
    public function testStoreAndRetrieveWNames(VCardDB $vcardDB)
    {
        $this->checkRowCounts(['CONTACT'=>0]);
        
        $n1 = ['FirstName'=>'Samuel', 'LastName'=>'Clemens'];
        $n2 = ['FirstName'=>'Mark', 'LastName'=>'Twain'];
        $fn = 'Mark Twain';
        $vcard = new VCard();
	$vcard->fn = $fn;
        $vcard->n = [$n1, $n2];

        $contactID = $vcardDB->store($vcard);
        $this->checkRowCounts(['CONTACT'=>1, 'CONTACT_N'=>2], $vcard);
        $resultVCard = $vcardDB->fetchOne($contactID);

        $this->compareVCards($vcard, $resultVCard);

        return $vcardDB;
    } //testStoreAndRetrieveWNames()
    
    /**
     * @depends testStoreAndRetrieveVCard
     */
    public function testStoreAndRetrieveWAddress(VCardDB $vcardDB)
    {
        $this->checkRowCounts(['CONTACT'=>0]);
        
        $n = ['FirstName'=>'Fred', 'LastName'=>'Jones'];
        $adr = [
            'StreetAddress'=>'47 Some Street',
            'Locality'=>'Birmingham',
            'Region'=>'AL'
            ];
        $fn = 'Fred Jones';
        $vcard = new VCard();
	$vcard->fn = $fn;
        $vcard->n = [$n];
        $vcard->adr = [$adr];

        $contactID = $vcardDB->store($vcard);
        $this->checkRowCounts( ['CONTACT'=>1, 'CONTACT_N'=>1, 'CONTACT_ADR'=>1],
                               $vcard );
        $resultVCard = $vcardDB->fetchOne($contactID);

        $this->compareVCards($vcard, $resultVCard);

        return $vcardDB;
    } //testStoreAndRetrieveWAddress()

    /**
     * @depends testStoreAndRetrieveWAddress
     */
    public function testStoreAndRetrieveWAddressType(VCardDB $vcardDB)
    {
        $this->checkRowCounts(['CONTACT'=>0]);
        
        $n = ['FirstName'=>'Fred', 'LastName'=>'Jones'];
        $adr = [
            'StreetAddress'=>'47 Some Street',
            'Locality'=>'Birmingham',
            'Region'=>'AL',
            'Type'=>['work']
            ];
        $fn = 'Fred Jones';
        $vcard = new VCard();
	$vcard->fn = $fn;
        $vcard->n = [$n];
        $vcard->adr = [$adr];

        $contactID = $vcardDB->store($vcard);
        $this->checkRowCounts( [ 'CONTACT'=>1, 'CONTACT_N'=>1, 'CONTACT_ADR'=>1,
                                 'CONTACT_ADR_REL_TYPES'=>1 ],
                               $vcard );
        $resultVCard = $vcardDB->fetchOne($contactID);

        $this->compareVCards($vcard, $resultVCard);    	 
    } //testStoreAndRetrieveWAddressType()

    /**
     * @depends testStoreAndRetrieveVCard
     */
    public function testStoreAndRetrieveWUID(VCardDB $vcardDB)
    {
    	$this->checkRowCounts(['CONTACT'=>0]);
    
    	$expected = 'someUIDValue';

    	$vcard = new vCard();
    	$vcard->uid = $expected;
    	$vcard->fn = 'nothingInteresting';
    
    	$contactID = $vcardDB->store($vcard);
    	$this->checkRowCounts(['CONTACT'=>1], $vcard);
    	$resultVCard = $vcardDB->fetchOne($contactID);
    
    	$this->compareVCards($vcard, $resultVCard);
    }

    /**
     * @depends testStoreAndRetrieveVCard
     */
    public function testStoreAndRetrieveWRelated(VCardDB $vcardDB)
    {
    	$this->checkRowCounts(['CONTACT'=>0]);
    
    	$expected = 'someUIDValue';

    	$vcard = new vCard();
    	$vcard->related = [$expected];
    	$vcard->fn = 'nothingInteresting';
    
    	$contactID = $vcardDB->store($vcard);
    	$this->checkRowCounts(['CONTACT'=>1], $vcard);
    	$resultVCard = $vcardDB->fetchOne($contactID);
    
    	$this->compareVCards($vcard, $resultVCard);
    }
    
    /**
     * @depends testStoreAndRetrieveVCard
     */
    public function testFetchByID(VCardDB $vcardDB)
    {
    	$this->checkRowCounts(['CONTACT'=>0]);
    	 
    	$vcard1 = $this->getRaithSeinar();
    	$id1 = $vcardDB->store($vcard1);
    	 
    	$vcard2 = $this->getSeinarAPL();
    	$id2 = $vcardDB->store($vcard2);
    
    	$this->checkRowCounts(['CONTACT'=>2]);
    	 
    	$resultVCards = $vcardDB->fetchByID([$id1, $id2]);
    
    	$this->assertNotEmpty($resultVCards);
    	$this->assertCount(2, $resultVCards);
    	$this->assertArrayHasKey($id1, $resultVCards);
    	$this->assertArrayHasKey($id2, $resultVCards);
    
    	$result1 = $resultVCards[$id1];
    	$this->compareVCards($vcard1, $result1);
    	 
    	$result2 = $resultVCards[$id2];
    	$this->compareVCards($vcard2, $result2);
    	 
    	return $vcardDB;
    }
    
    /**
     * @depends testFetchByID
     */
    public function testSearchWithOneVCardMatches(VCardDB $vcardDB)
    {
    	$this->checkRowCounts(['CONTACT'=>0]);
    
    	$fn = 'foo';
    	$vcard = new VCard();
    	$vcard->fn = $fn;
    
    	$contactID = $vcardDB->store($vcard);
    	$this->checkRowCounts(['CONTACT'=>1], $vcard);
    
    	$resultVCards = $vcardDB->search("foo");
    	 
    	$this->assertNotEmpty($resultVCards);
    	$this->assertCount(1, $resultVCards);
    	$this->assertArrayHasKey($contactID, $resultVCards);
    	 
    	$resultVCard = $resultVCards[$contactID];
    	$this->compareVCards($vcard, $resultVCard);
    }
    
    /**
     * @depends testStoreAndRetrieveVCard
     */
    public function testStoreAndRetrieveWEmail(VCardDB $vcardDB)
    {
        $this->checkRowCounts(['CONTACT'=>0]);

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
        $this->checkRowCounts( [ 'CONTACT'=>1, 'CONTACT_N'=>1,
                                 'CONTACT_EMAIL'=>1 ],
                               $vcard );

        $resultVCard = $vcardDB->fetchOne($contactID);
        $this->compareVCards($vcard, $resultVCard);
    } //testStoreAndRetrieveWEmail()

    /**
     * @depends testStoreAndRetrieveVCard
     */
    public function testStoreAndRetrieveWOrg(VCardDB $vcardDB)
    {
        $this->checkRowCounts(['CONTACT'=>0]);

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
        $this->checkRowCounts( ['CONTACT'=>1, 'CONTACT_N'=>1, 'CONTACT_ORG'=>1],
                               $vcard );
        $resultVCard = $vcardDB->fetchOne($contactID);
        $this->compareVCards($vcard, $resultVCard);
        
        return $vcardDB;
    } //testStoreAndRetrieveWOrg()

    /**
     * @depends testStoreAndRetrieveVCard
     */
    public function testStoreAndRetrieveWLogo(VCardDB $vcardDB)
    {
        $this->checkRowCounts(['CONTACT'=>0]);

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
        $this->checkRowCounts( [ 'CONTACT'=>1, 'CONTACT_ORG'=>1,
                                 'CONTACT_DATA'=>1 ],
                               $vcard );

        $resultVCard = $vcardDB->fetchOne($contactID);
        $this->compareVCards($vcard, $resultVCard);
    } //testStoreAndRetrieveWLogo()

    /**
     * @depends testStoreAndRetrieveVCard
     */
    public function testStoreAndRetrieveWNote(VCardDB $vcardDB)
    {
        $this->checkRowCounts(['CONTACT'=>0]);

        $expected = [
                        'fn'          => 'Carpenter',
			'note' => 'It is time to talk of many things...'
                     ];
        $vcard = new VCard();
	$vcard->fn = $expected['fn'];
        $vcard->note($expected['note']);

        $contactID = $vcardDB->store($vcard);
        $this->checkRowCounts( ['CONTACT'=>1, 'CONTACT_NOTE'=>1],
                               $vcard );

        $resultVCard = $vcardDB->fetchOne($contactID);
        $this->compareVCards($vcard, $resultVCard);
    } //testStoreAndRetrieveWNote()

    /**
     * @depends testStoreAndRetrieveVCard
     */
    public function testStoreAndRetrieveWTel(VCardDB $vcardDB)
    {
        $this->checkRowCounts(['CONTACT'=>0]);

        $expected = [
                        'fn'          => 'Information',
			'tel' => '555-1212'
                     ];
        $vcard = new VCard();
	$vcard->fn = $expected['fn'];
        $vcard->tel($expected['tel']);

        $contactID = $vcardDB->store($vcard);
        $this->checkRowCounts(['CONTACT'=>1, 'CONTACT_TEL'=>1], $vcard );

        $resultVCard = $vcardDB->fetchOne($contactID);
	$this->compareVCards($vcard, $resultVCard);
    } //testStoreAndRetrieveWTel()
    
    
    /**
     * @depends testStoreAndRetrieveVCard
     */
    public function testStoreAndRetrieveWKey(VCardDB $vcardDB)
    {
        $this->checkRowCounts(['CONTACT'=>0]);

        $expected = [
                        'fn'  => 'Keymaster',
			'key' => 'http://www.example.com/keys/jdoe.cer'
                     ];
        $vcard = new VCard();
	$vcard->fn = $expected['fn'];
        // WORKAROUND: Cannot use __call(..) with 'key'!
        $vcard->key = [$expected['key']];

        $contactID = $vcardDB->store($vcard);
        $this->checkRowCounts(['CONTACT'=>1, 'CONTACT_DATA'=>1], $vcard);

        $resultVCard = $vcardDB->fetchOne($contactID);
	$this->compareVCards($vcard, $resultVCard);
    } //testStoreAndRetrieveWKey()
    
     /**
     * @depends testStoreAndRetrieveVCard
     */
    public function testStoreAndRetrieveWGeo(VCardDB $vcardDB)
    {
        $this->checkRowCounts(['CONTACT'=>0]);

        $expected = [
                        'fn'          => 'Someplace in Vienna',
			'geo' => 'geo:48.198634,16.371648;crs=wgs84;u=40'
                     ];
        $vcard = new VCard();
	$vcard->fn = $expected['fn'];
        $vcard->geo($expected['geo']);

        $contactID = $vcardDB->store($vcard);
        $this->checkRowCounts(['CONTACT'=>1, 'CONTACT_GEO'=>1], $vcard);

        $resultVCard = $vcardDB->fetchOne($contactID);
	$this->compareVCards($vcard, $resultVCard);
    } //testStoreAndRetrieveWTel()
    
    /**
     * @depends testStoreAndRetrieveVCard
     */
    public function testStoreAndRetrieveWCategory(VCardDB $vcardDB)
    {
    	$this->checkRowCounts(['CONTACT'=>0]);
    
    	$expected = [
                     'fn'          => 'Sigmund Freud',
    	             'category'    => 'mental health'
    		    ];
    	$vcard = new VCard();
    	$vcard->fn = $expected['fn'];
    	$vcard->categories($expected['category']);
    
    	$contactID = $vcardDB->store($vcard);
        $this->checkRowCounts(['CONTACT'=>1, 'CONTACT_CATEGORIES'=>1], $vcard);

    	$resultVCard = $vcardDB->fetchOne($contactID);
        $this->compareVCards($vcard, $resultVCard);
    } //testStoreAndRetrieveWCategory()
    
        /**
     * @depends testStoreAndRetrieveVCard
     */
    public function testStoreAndRetrieveThreeVCards(VCardDB $vcardDB)
    {
        $this->checkRowCounts( [ 'CONTACT'=>0, 'CONTACT_N'=>0,
                                 'CONTACT_ORG'=>0, 'CONTACT_CATEGORIES'=>0,
                                 'CONTACT_DATA'=>0
                               ]); // pre-condition
                
    	$raithSeinar = $this->getRaithSeinar();
    	$rSContactID = $vcardDB->store($raithSeinar);
    
    	$seinarAPL = $this->getSeinarAPL();
    	$sAPLContactID = $vcardDB->store($seinarAPL);
    
    	$dDBinks = $this->getDDBinks();
    	$dDBContactID = $vcardDB->store($dDBinks);
    
    	$this->checkRowCounts( [ 'CONTACT'=>3, 'CONTACT_N'=>2,
                                 'CONTACT_ORG'=>3, 'CONTACT_CATEGORIES'=>5,
                                 'CONTACT_DATA'=>1
                               ]);
        
        $vcards = $vcardDB->fetchAll();
        $this->assertCount(3, $vcards);
        
        $this->compareVCards($raithSeinar, $vcards[$rSContactID]);
        $this->compareVCards($seinarAPL, $vcards[$sAPLContactID]);
        $this->compareVCards($dDBinks, $vcards[$dDBContactID]);
        
    	return $vcardDB;
    }
    
    /**
     * @depends testStoreAndRetrieveThreeVCards
     */
    public function testFetchIDsForOrganization(VCardDB $vcardDB)
    {    	    	    	
    	$raithSeinar = $this->getRaithSeinar();
    	$rSUID = $vcardDB->store($raithSeinar);
    	
    	$seinarAPL = $this->getSeinarAPL();
    	$sAPLUID = $vcardDB->store($seinarAPL);
    	
    	$dDBinks = $this->getDDBinks();
    	$dDBUID = $vcardDB->store($dDBinks);
    	
        $this->checkRowCounts(['CONTACT'=>3]);
    	
    	$IDs = $vcardDB->fetchIDsForOrganization('Seinar Fleet Systems');
    	
    	$this->assertNotEmpty($IDs);
    	$this->assertInternalType("array", $IDs);
    	
    	$this->assertCount( 2, $IDs,
    			       print_r($IDs, true)
    			       . ' rs: ' . $rSUID
                               . ' apl: ' . $sAPLUID
                               . ' ddb: ' . $dDBUID);
    	
    	$this->assertContains($rSUID, $IDs);
    	$this->assertContains($sAPLUID, $IDs);
    	// Binks is left out (as he should be).
    	
    	return $vcardDB;
    }
    
    /**
     * @depends testFetchIDsForOrganization
     */
    public function testFetchIDsForOrganizationByKind(VCardDB $vcardDB)
    {
    	$raithSeinar = $this->getRaithSeinar();
    	$rSUID = $vcardDB->store($raithSeinar);
    	 
    	$seinarAPL = $this->getSeinarAPL();
    	$sAPLUID = $vcardDB->store($seinarAPL);
    	 
    	$dDBinks = $this->getDDBinks();
    	$dDBUID = $vcardDB->store($dDBinks);
    	 
    	$this->checkRowCounts(['CONTACT'=>3]);
        
        $this->assertEquals('individual', $vcardDB->fetchOne($rSUID)->kind);
        $this->assertEquals('organization', $vcardDB->fetchOne($sAPLUID)->kind);
        $this->assertEquals('individual', $vcardDB->fetchOne($dDBUID)->kind);

    	$IDs = $vcardDB->fetchIDsForOrganization( 'Seinar Fleet Systems',
                                                  'individual' );
    	 
    	$this->assertNotEmpty($IDs);
    	$this->assertInternalType("array", $IDs);
    	 
    	$this->assertCount( 1, $IDs,
    			print_r($IDs, true)
    			. ' rs: ' . $rSUID
    			. ' apl: ' . $sAPLUID
    			. ' ddb: ' . $dDBUID);
    	 
    	$this->assertContains($rSUID, $IDs);
    }
    
    /**
     * @depends testStoreAndRetrieveThreeVCards
     */
    public function testFetchIDsForCategory(VCardDB $vcardDB)
    {
    	$raithSeinar = $this->getRaithSeinar();
    	$rSContactID = $vcardDB->store($raithSeinar);
    	 
    	$seinarAPL = $this->getSeinarAPL();
    	$sAPLContactID = $vcardDB->store($seinarAPL);
    	 
    	$dDBinks = $this->getDDBinks();
    	$dDBContactID = $vcardDB->store($dDBinks);
    	 
    	$this->checkRowCounts(['CONTACT'=>3]);
    	 
    	$IDs = $vcardDB->fetchIDsForCategory('military industrial');
    	 
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
    	 
    	return $vcardDB;
    }
    
    /**
     * @depends testFetchIDsForCategory
     */
    public function testFetchIDsForCategoryByKind(VCardDB $vcardDB)
    {
    	$raithSeinar = $this->getRaithSeinar();
    	$rSContactID = $vcardDB->store($raithSeinar);
    
    	$seinarAPL = $this->getSeinarAPL();
    	$sAPLContactID = $vcardDB->store($seinarAPL);
    
    	$dDBinks = $this->getDDBinks();
    	$dDBContactID = $vcardDB->store($dDBinks);
    
    	$this->checkRowCounts(['CONTACT'=>3]);
    
    	$IDs = $vcardDB->fetchIDsForCategory( 'military industrial',
                                              'organization' );
    
    	$this->assertNotEmpty($IDs);
    	$this->assertInternalType("array", $IDs);
    
    	$this->assertCount( 1, $IDs,
    			print_r($IDs, true)
    			. ' rs: ' . $rSContactID
    			. ' apl: ' . $sAPLContactID
    			. ' ddb: ' . $dDBContactID);
    
    	$this->assertContains($sAPLContactID, $IDs);
    
    	return $vcardDB;
    }
    
    /**
     * @depends testStoreAndRetrieveThreeVCards
     * @param VCardDB $vcardDB
     */
    public function testDeleteVCard(VCardDB $vcardDB)
    {
        $this->checkRowCounts( [ 'CONTACT'=>0, 'CONTACT_N'=>0,
                                 'CONTACT_ORG'=>0, 'CONTACT_CATEGORIES'=>0,
                                 'CONTACT_DATA'=>0
                               ]); // pre-condition
                
    	$raithSeinar = $this->getRaithSeinar();
    	$rSContactID = $vcardDB->store($raithSeinar);
    
    	$seinarAPL = $this->getSeinarAPL();
    	$sAPLContactID = $vcardDB->store($seinarAPL);
    
    	$dDBinks = $this->getDDBinks();
    	$dDBContactID = $vcardDB->store($dDBinks);
    
    	$this->checkRowCounts( [ 'CONTACT'=>3, 'CONTACT_N'=>2,
                                 'CONTACT_ORG'=>3, 'CONTACT_CATEGORIES'=>5,
                                 'CONTACT_DATA'=>1
                               ]);

        $vcardDB->deleteContact($dDBContactID);
        
        $this->checkRowCounts( [ 'CONTACT'=>2, 'CONTACT_N'=>1,
                                 'CONTACT_ORG'=>2, 'CONTACT_CATEGORIES'=>5,
                                 'CONTACT_DATA'=>1
                               ]);
        
        $vcards = $vcardDB->fetchAll();
        $this->assertCount(2, $vcards);
        
        $this->compareVCards($raithSeinar, $vcards[$rSContactID]);
        $this->compareVCards($seinarAPL, $vcards[$sAPLContactID]);
        
        return $vcardDB;
    }
    
} // VCardDBTest

