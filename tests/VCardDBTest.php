<?php
/**
 * Unit tests for the vcard-tools.php file routines.
 * @author Eric Vought <evought@pobox.com>
 * 2014-11-16
 * @copyright Eric Vought 2014, Some rights reserved.
 * @license MIT http://opensource.org/licenses/MIT
 */
namespace EVought\vCardTools;
use EVought\DataUri\DataUri;

class VCardDBTest extends \PHPUnit_Extensions_Database_TestCase
{
    // only instantiate pdo once for test clean-up/fixture load
    static private $pdo = null;

    // only instantiate PHPUnit_Extensions_Database_DB_IDatabaseConnection once
    private $conn = null;
    
    private $raithSeinar = null;
    private $seinarAPL = null;
    private $ddBinks = null;
    private $johnDoe = null;

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

    	$this->assertEquals( VCardDB::VCARD_PRODUCT_ID,
                                $dbVCard->prodid->getValue() );
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
    	if (null === $this->raithSeinar)
    	{
    	    $path = __DIR__ . '/vcards/RaithSeinar.vcf';
            
            $parser = new VCardParser();
            $vcards = $parser->importFromFile($path);
            
            $this->assertCount(1, $vcards);
	    $this->raithSeinar = $vcards[0];
    	}
	return $this->raithSeinar;
    }
	
    /**
     * Some cards for testing.
     * @return an organization VCard.
     */
    public function getSeinarAPL()
    {
    	if (null === $this->seinarAPL)
    	{
	    $path = __DIR__ . '/vcards/SeinarAPL.vcf';
            
            $parser = new VCardParser();
            $vcards = $parser->importFromFile($path);
            
            $this->assertCount(1, $vcards);
            $this->seinarAPL = $vcards[0];
    	}
	return $this->seinarAPL;
    }
    	
    /**
     * Some cards for testing.
     * @return an individual VCard.
     */
    public function getDDBinks()
    {
    	if (null === $this->ddBinks)
    	{
	    $path = __DIR__ . '/vcards/DDBinks.vcf';
            $parser = new VCardParser();
            $vcards = $parser->importFromFile($path);
            
            $this->assertCount(1, $vcards);
            $this->ddBinks = $vcards[0];
    	}
	return $this->ddBinks;
    }
    
    /**
     * Some cards for testing.
     * @return an organization VCard.
     */
    public function getJohnDoe()
    {
    	if (null === $this->johnDoe)
    	{
	    $path = __DIR__ . '/vcards/JohnDoe.vcf';
            
            $parser = new VCardParser();
            $vcards = $parser->importFromFile($path);
            
            $this->assertCount(1, $vcards);
            $this->johnDoe = $vcards[0];
    	}
	return $this->johnDoe;
    }

    
    /**
     * Ensure that we can instantiate a VCardDB instance.
     * @group default
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
     * @group default
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
     * @group default
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
     * @group default
     * @depends testCreateVCardDB
     */
    public function testStoreAndRetrieveTrivialVCard(VCardDB $vcardDB)
    {
        $this->checkRowCounts(['CONTACT'=>0]);

        $vcard = new VCard();
	$vcard->push(VCard::builder('fn')->setValue('foo')->build());

        $contactID = $vcardDB->store($vcard);
        $this->checkRowCounts(['CONTACT'=>1], $vcard);

        $resultVCard = $vcardDB->fetchOne($contactID);

        $this->compareVCards($vcard, $resultVCard);
        return $vcardDB;
    }

    /**
     * @group default
     * @depends testStoreAndRetrieveTrivialVCard
     */
    public function testFetchWithOneVCard(VCardDB $vcardDB)
    {
    	$this->checkRowCounts(['CONTACT'=>0]);
    	
        $vcard = new VCard();
    	VCard::builder('fn')->setValue('foo')->pushTo($vcard);
    	
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
     * @group default
     * @depends testStoreAndRetrieveTrivialVCard
     */
    public function testSearchWithOneVCardFails(VCardDB $vcardDB)
    {
    	$this->checkRowCounts(['CONTACT'=>0]);
    	
        $vcard = new VCard();
    	VCard::builder('fn')->setValue('foo')->pushTo($vcard);
    	 
    	$contact_id = $vcardDB->store($vcard);
    	$this->checkRowCounts(['CONTACT'=>1], $vcard);
    	 
	$vcards = $vcardDB->search("bloomers");

	$this->assertEmpty($vcards);
    }


    
    
    /**
     * @group default
     * @depends testStoreAndRetrieveTrivialVCard
     */
    public function testStoreAndRetrieveVCard(VCardDB $vcardDB)
    {
        $this->checkRowCounts(['CONTACT'=>0]);

        $expected = [
                        'n_GivenName' => 'Fred',
                        'n_FamilyName' => 'Jones',
                        'url' => 'http://golf.com',
                        'fn' => 'Fred Jones'
                     ];
        $vcard = new VCard();
	$vcard->push(VCard::builder('fn')->setValue($expected['fn'])->build())
              ->push(
                VCard::builder('n')
                    ->setField('GivenName', $expected['n_GivenName'])
                    ->setField('FamilyName', $expected['n_FamilyName'])
                    ->build() )
              ->push(
                  VCard::builder('url')->setValue($expected['url'])->build() );

        $contactID = $vcardDB->store($vcard);
        $this->checkRowCounts( [
                                'CONTACT'=>1, 'CONTACT_N'=>1, 'CONTACT_ADR'=>0,
                                'CONTACT_URL'=>1 ],
                               $vcard );
        $resultVCard = $vcardDB->fetchOne($contactID);

        $this->compareVCards($vcard, $resultVCard);    	 
	
        return $vcardDB;
    } //testStoreAndRetrieveVCard()
    
    /**
     * @group default
     * @depends testStoreAndRetrieveVCard
     */
    public function testStoreAndRetrieveVCardWAnniversary(VCardDB $vcardDB)
    {
        $this->checkRowCounts(['CONTACT'=>0]);

        $expected = [
                        'n_GivenName' => 'Fred',
                        'n_FamilyName' => 'Jones',
                        'anniversary' => '2012-09-01 00:00:00',
                        'fn' => 'Fred Jones'
                     ];
        $vcard = new VCard();
        $vcard->push(VCard::builder('fn')->setValue($expected['fn'])->build());
        $vcard->push(
                VCard::builder('n')
                    ->setField('GivenName', $expected['n_GivenName'])
                    ->setField('FamilyName', $expected['n_FamilyName'])
                    ->build() )
              ->push(
                VCard::builder('anniversary')
                    ->setValue($expected['anniversary'])->build() );

        $contactID = $vcardDB->store($vcard);
        $this->checkRowCounts( ['CONTACT'=>1, 'CONTACT_N'=>1, 'CONTACT_ADR'=>0],
                               $vcard );
        $resultVCard = $vcardDB->fetchOne($contactID);

        $this->compareVCards($vcard, $resultVCard);    	 
	
        return $vcardDB;
    }
    
    /**
     * @group default
     * @depends testStoreAndRetrieveVCard
     */
    public function testStoreAndRetrieveWName(VCardDB $vcardDB)
    {
        $this->checkRowCounts(['CONTACT'=>0]);
        
        $n = VCard::builder('n')
            ->setValue(['GivenName'=>'Fred', 'FamilyName'=>'Jones'])
            ->build();
        $fn = VCard::builder('fn')->setValue('Fred Jones')->build();
        $vcard = new VCard();
	$vcard->push($n)->push($fn);

        $contactID = $vcardDB->store($vcard);
        $this->checkRowCounts( ['CONTACT'=>1, 'CONTACT_N'=>1],
                               $vcard );
        $resultVCard = $vcardDB->fetchOne($contactID);

        $this->compareVCards($vcard, $resultVCard);

        return $vcardDB;
    } //testStoreAndRetrieveWName()
    
    /**
     * @group default
     * @depends testStoreAndRetrieveWName
     */
    public function testStoreAndRetrieveWNames(VCardDB $vcardDB)
    {
        $this->checkRowCounts(['CONTACT'=>0]);
        
        $n1 = VCard::builder('n')
            ->setValue(['GivenName'=>'Samuel', 'FamilyName'=>'Clemens'])
            ->build();
        $n2 = VCard::builder('n')
            ->setValue(['GivenName'=>'Mark', 'FamilyName'=>'Twain'])
            ->build();
        $fn = VCard::builder('fn')->setValue('Mark Twain')->build();
        $vcard = new VCard();
	$vcard->push($n1)->push($n2)->push($fn);

        $contactID = $vcardDB->store($vcard);
        $this->checkRowCounts(['CONTACT'=>1, 'CONTACT_N'=>2], $vcard);
        $resultVCard = $vcardDB->fetchOne($contactID);

        $this->compareVCards($vcard, $resultVCard);

        return $vcardDB;
    } //testStoreAndRetrieveWNames()
    
    /**
     * @group default
     * @depends testStoreAndRetrieveVCard
     */
    public function testStoreAndRetrieveWAddress(VCardDB $vcardDB)
    {
        $this->checkRowCounts(['CONTACT'=>0]);
        
        $n = VCard::builder('n')
                ->setValue(['GivenName'=>'Fred', 'FamilyName'=>'Jones'])
                ->build();
        $adr = VCard::builder('adr')
                ->setValue(['StreetAddress'=>'47 Some Street',
                            'Locality'=>'Birmingham',
                            'Region'=>'AL'])
                ->build();
        $fn = VCard::builder('fn')->setValue('Fred Jones')->build();
        $vcard = new VCard();
	$vcard->push($fn)->push($n)->push($adr);

        $contactID = $vcardDB->store($vcard);
        $this->checkRowCounts( ['CONTACT'=>1, 'CONTACT_N'=>1, 'CONTACT_ADR'=>1],
                               $vcard );
        $resultVCard = $vcardDB->fetchOne($contactID);

        $this->compareVCards($vcard, $resultVCard);

        return $vcardDB;
    } //testStoreAndRetrieveWAddress()
    
     /**
     * @group default
     * @depends testStoreAndRetrieveVCard
     */
    public function testStoreAndRetrieveWAddressGroup(VCardDB $vcardDB)
    {
        $this->checkRowCounts(['CONTACT'=>0]);
        
        $n = VCard::builder('n')
                ->setValue(['GivenName'=>'Fred', 'FamilyName'=>'Jones'])
                ->build();
        $adr = VCard::builder('adr')
                ->setValue(['StreetAddress'=>'47 Some Street',
                            'Locality'=>'Birmingham',
                            'Region'=>'AL'])
                ->setGroup('shmoo')
                ->build();
        $fn = VCard::builder('fn')->setValue('Fred Jones')->build();
        $vcard = new VCard();
	$vcard->push($fn)->push($n)->push($adr);

        $contactID = $vcardDB->store($vcard);
        $this->checkRowCounts( ['CONTACT'=>1, 'CONTACT_N'=>1, 'CONTACT_ADR'=>1],
                               $vcard );
        $resultVCard = $vcardDB->fetchOne($contactID);

        $this->compareVCards($vcard, $resultVCard);

        return $vcardDB;
    }

    /**
     * @group default
     * @depends testStoreAndRetrieveWAddress
     */
    public function testStoreAndRetrieveWAddressType(VCardDB $vcardDB)
    {
        $this->checkRowCounts(['CONTACT'=>0]);
        
        $n = VCard::builder('n')
                ->setValue(['GivenName'=>'Fred', 'FamilyName'=>'Jones'])
                ->build();
        $adr = VCard::builder('adr')
                ->setValue(['StreetAddress'=>'47 Some Street',
                            'Locality'=>'Birmingham',
                            'Region'=>'AL'])
                ->addType('work')
                ->build();
        $fn = VCard::builder('fn')->setValue('Fred Jones')->build();
        $vcard = new VCard();
	$vcard->push($n)->push($adr)->push($fn);

        $contactID = $vcardDB->store($vcard);
        $this->checkRowCounts( [ 'CONTACT'=>1, 'CONTACT_N'=>1, 'CONTACT_ADR'=>1,
                                 'CONTACT_ADR_REL_TYPES'=>1 ],
                               $vcard );
        $resultVCard = $vcardDB->fetchOne($contactID);

        $this->compareVCards($vcard, $resultVCard);    	 
    } //testStoreAndRetrieveWAddressType()

    /**
     * @group default
     * @depends testStoreAndRetrieveWAddress
     */
    public function testStoreAndRetrieveWAddressPref(VCardDB $vcardDB)
    {
        $this->checkRowCounts(['CONTACT'=>0]);
        $vcard = new VCard();
        
        VCard::builder('adr')
                ->setValue(['StreetAddress'=>'47 Some Street',
                            'Locality'=>'Birmingham',
                            'Region'=>'AL'])
                ->setPref(1)
                ->pushTo($vcard);
        VCard::builder('fn')->setValue('Fred Jones')->pushTo($vcard);

        $contactID = $vcardDB->store($vcard);
        $this->checkRowCounts( [ 'CONTACT'=>1, 'CONTACT_N'=>0, 'CONTACT_ADR'=>1],
                               $vcard );
        $resultVCard = $vcardDB->fetchOne($contactID);

        $this->compareVCards($vcard, $resultVCard);    	 
    } //testStoreAndRetrieveWAddressType()
    
    /**
     * @group default
     * @depends testStoreAndRetrieveVCard
     */
    public function testStoreAndRetrieveWUID(VCardDB $vcardDB)
    {
    	$this->checkRowCounts(['CONTACT'=>0]);
    
    	$vcard = new vCard();
    	$vcard->setUID('someUIDValue');
    	$vcard->push(
            VCard::builder('fn')->setValue('nothingInteresting')->build() );
    
    	$contactID = $vcardDB->store($vcard);
    	$this->checkRowCounts(['CONTACT'=>1], $vcard);
    	$resultVCard = $vcardDB->fetchOne($contactID);
    
    	$this->compareVCards($vcard, $resultVCard);
    }

    /**
     * @group default
     * @depends testStoreAndRetrieveVCard
     */
    public function testStoreAndRetrieveWRelated(VCardDB $vcardDB)
    {
    	$this->checkRowCounts(['CONTACT'=>0]);
    
    	$vcard = new vCard();
    	$vcard->push(VCard::builder('related')
                            ->setValue('someUIDValue')->build());
    	$vcard->push(
            VCard::builder('fn')->setValue('nothingInteresting')->build() );
    
    	$contactID = $vcardDB->store($vcard);
    	$this->checkRowCounts(['CONTACT'=>1], $vcard);
    	$resultVCard = $vcardDB->fetchOne($contactID);
    
    	$this->compareVCards($vcard, $resultVCard);
    }
    
    /**
     * @group default
     * @depends testStoreAndRetrieveVCard
     */
    public function testStoreAndRetrieveWXtended(VCardDB $vcardDB)
    {
    	$this->checkRowCounts(['CONTACT'=>0, 'CONTACT_XTENDED'=>0]);
    
    	$vcard = new vCard();
    	VCard::builder('undefined', false)
                            ->setValue('someValue')->addType('work')
                            ->pushTo($vcard);
    	VCard::builder('fn')->setValue('nothingInteresting')->pushTo($vcard);
    
    	$contactID = $vcardDB->store($vcard);
    	$this->checkRowCounts( ['CONTACT'=>1, 'CONTACT_XTENDED'=>1,
                                'CONTACT_XTENDED_REL_TYPES'=>1 ], $vcard );
    	$resultVCard = $vcardDB->fetchOne($contactID);
    
    	$this->compareVCards($vcard, $resultVCard);
    }
    
    /**
     * @group default
     * @depends testStoreAndRetrieveVCard
     */
    public function testStoreAndRetrieveWXtendedGroup(VCardDB $vcardDB)
    {
    	$this->checkRowCounts(['CONTACT'=>0, 'CONTACT_XTENDED'=>0]);
    
    	$vcard = new vCard();
    	VCard::builder('undefined', false)
                            ->setValue('someValue')->addType('work')
                            ->setGroup('shmoo')
                            ->pushTo($vcard);
    	VCard::builder('fn')->setValue('nothingInteresting')->pushTo($vcard);
    
    	$contactID = $vcardDB->store($vcard);
    	$this->checkRowCounts( ['CONTACT'=>1, 'CONTACT_XTENDED'=>1,
                                'CONTACT_XTENDED_REL_TYPES'=>1 ], $vcard );
    	$resultVCard = $vcardDB->fetchOne($contactID);
    
    	$this->compareVCards($vcard, $resultVCard);
    }
    
    /**
     * @group default
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
     * @group default
     * @depends testFetchByID
     */
    public function testSearchWithOneVCardMatches(VCardDB $vcardDB)
    {
    	$this->checkRowCounts(['CONTACT'=>0]);
    
    	$vcard = new VCard();
    	$vcard->push(VCard::builder('fn')->setValue('foo')->build());
    
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
     * @group default
     * @depends testStoreAndRetrieveVCard
     */
    public function testStoreAndRetrieveWEmail(VCardDB $vcardDB)
    {
        $this->checkRowCounts(['CONTACT'=>0]);

        $expected = [
                        'n_GivenName' => 'Fred',
                        'n_FamilyName' => 'Jones',
                        'email' => 'noone@nowhere.org',
                        'fn' => 'Fred Jones'
                     ];
        $vcard = new VCard();
	VCard::builder('fn')->setValue($expected['fn'])->pushTo($vcard);
        VCard::builder('n')
                ->setField('GivenName', $expected['n_GivenName'])
                ->setField('FamilyName', $expected['n_FamilyName'])
                ->pushTo($vcard);
        VCard::builder('email')->setValue($expected['email'])->pushTo($vcard);

        $contactID = $vcardDB->store($vcard);
        $this->checkRowCounts( [ 'CONTACT'=>1, 'CONTACT_N'=>1,
                                 'CONTACT_EMAIL'=>1 ],
                               $vcard );

        $resultVCard = $vcardDB->fetchOne($contactID);
        $this->compareVCards($vcard, $resultVCard);
    } //testStoreAndRetrieveWEmail()
    
     /**
     * @group default
     * @depends testStoreAndRetrieveVCard
     */
    public function testStoreAndRetrieveWEmailAndGroup(VCardDB $vcardDB)
    {
        $this->checkRowCounts(['CONTACT'=>0]);

        $expected = [
                        'n_GivenName' => 'Fred',
                        'n_FamilyName' => 'Jones',
                        'email' => 'noone@nowhere.org',
                        'group' => 'shmoo',
                        'fn' => 'Fred Jones'
                     ];
        $vcard = new VCard();
	VCard::builder('fn')->setValue($expected['fn'])->pushTo($vcard);
        VCard::builder('n')
                ->setField('GivenName', $expected['n_GivenName'])
                ->setField('FamilyName', $expected['n_FamilyName'])
                ->pushTo($vcard);
        VCard::builder('email')->setValue($expected['email'])
                               ->setGroup($expected['group'])->pushTo($vcard);

        $contactID = $vcardDB->store($vcard);
        $this->checkRowCounts( [ 'CONTACT'=>1, 'CONTACT_N'=>1,
                                 'CONTACT_EMAIL'=>1 ],
                               $vcard );

        $resultVCard = $vcardDB->fetchOne($contactID);
        $this->compareVCards($vcard, $resultVCard);
    }

    /**
     * @group default
     * @depends testStoreAndRetrieveVCard
     */
    public function testStoreAndRetrieveWOrg(VCardDB $vcardDB)
    {
        $this->checkRowCounts(['CONTACT'=>0]);

        $expected = [
                        'n_GivenName' => 'Raith',
                        'n_FamilyName' => 'Seinar',
                        'org' => 'Seinar Fleet Systems',
                        'title' => 'CEO',
                        'fn' => 'Raith Seinar'
                     ];
        $vcard = new VCard();
	$vcard->push(VCard::builder('fn')->setValue($expected['fn'])->build());
        $vcard->push(
            VCard::builder('n')
                ->setField('GivenName', $expected['n_GivenName'])
                ->setField('FamilyName', $expected['n_FamilyName'])
                ->build() );
        $vcard->push(
            VCard::builder('title')->setValue($expected['title'])
                ->build() );
        $vcard->push(
            VCard::builder('org')->setField('Name', $expected['org'])
                ->build() );

        $contactID = $vcardDB->store($vcard);
        $this->checkRowCounts( ['CONTACT'=>1, 'CONTACT_N'=>1, 'CONTACT_ORG'=>1],
                               $vcard );
        $resultVCard = $vcardDB->fetchOne($contactID);
        $this->compareVCards($vcard, $resultVCard);
        
        return $vcardDB;
    } //testStoreAndRetrieveWOrg()

    /**
     * @group default
     * @depends testStoreAndRetrieveVCard
     */
    public function testStoreAndRetrieveWLogo(VCardDB $vcardDB)
    {
        $this->checkRowCounts([ 'CONTACT'=>0, 'CONTACT_ORG'=>0,
                                'CONTACT_DATA'=>0 ]);

        $expected = [
                        'org_name'    => 'Seinar Fleet Systems',
                        'org_unit1'   => 'Seinar Advanced Projects Laboratory',
			'org_unit2'   => 'TIE AO1X Division',
                        'fn'          => 'Seinar APL TIE AO1X Division',
			'logo' => 'http://img1.wikia.nocookie.net/__cb20080311192948/starwars/images/3/39/Sienar.svg'
                     ];
        $vcard = new VCard();
	VCard::builder('fn')->setValue($expected['fn'])->pushTo($vcard);
        VCard::builder('org')
                ->setField('Name', $expected['org_name'])
                ->setField('Unit1', $expected['org_unit1'])
                ->setField('Unit2', $expected['org_unit2'])
                ->pushTo($vcard);
        VCard::builder('logo')->setValue($expected['logo'])
                ->pushTo($vcard);

        $contactID = $vcardDB->store($vcard);
        $this->checkRowCounts( [ 'CONTACT'=>1, 'CONTACT_ORG'=>1,
                                 'CONTACT_DATA'=>1 ],
                               $vcard );

        $resultVCard = $vcardDB->fetchOne($contactID);
        $this->compareVCards($vcard, $resultVCard);
    } //testStoreAndRetrieveWLogo()
    
    /**
     * @group default
     * @depends testStoreAndRetrieveVCard
     */
    public function testStoreAndRetrieveWLogoDataMediaType(VCardDB $vcardDB)
    {
        $this->checkRowCounts([ 'CONTACT'=>0, 'CONTACT_ORG'=>0,
                                'CONTACT_DATA'=>0 ]);

        $expected = [
                        'org_name'    => 'Seinar Fleet Systems',
                        'org_unit1'   => 'Seinar Advanced Projects Laboratory',
			'org_unit2'   => 'TIE AO1X Division',
                        'fn'          => 'Seinar APL TIE AO1X Division',
			'logo' => '|-0-|'
                     ];
        $vcard = new VCard();
        
        $dataUri = new DataUri( 'image/example', $expected['logo'],
                DataUri::ENCODING_BASE64 );
        
	VCard::builder('fn')->setValue($expected['fn'])->pushTo($vcard);
        VCard::builder('org')
                ->setField('Name', $expected['org_name'])
                ->setField('Unit1', $expected['org_unit1'])
                ->setField('Unit2', $expected['org_unit2'])
                ->pushTo($vcard);
        VCard::builder('logo')
                ->setValue($dataUri->toString())
                ->setMediaType('image/example')
                ->pushTo($vcard);

        $contactID = $vcardDB->store($vcard);
        $this->checkRowCounts( [ 'CONTACT'=>1, 'CONTACT_ORG'=>1,
                                 'CONTACT_DATA'=>1 ],
                               $vcard );

        $resultVCard = $vcardDB->fetchOne($contactID);
        $this->compareVCards($vcard, $resultVCard);
    } //testStoreAndRetrieveWLogo()

    /**
     * @group default
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
	$vcard->push(VCard::builder('fn')->setValue($expected['fn'])->build());
        $vcard->push( VCard::builder('note')->setValue($expected['note'])
            ->build() );

        $contactID = $vcardDB->store($vcard);
        $this->checkRowCounts( ['CONTACT'=>1, 'CONTACT_NOTE'=>1],
                               $vcard );

        $resultVCard = $vcardDB->fetchOne($contactID);
        $this->compareVCards($vcard, $resultVCard);
    } //testStoreAndRetrieveWNote()

    /**
     * @group default
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
	$vcard->push(VCard::builder('fn')->setValue($expected['fn'])->build());
        $vcard->push(
            VCard::builder('tel')->setValue($expected['tel'])->build() );

        $contactID = $vcardDB->store($vcard);
        $this->checkRowCounts(['CONTACT'=>1, 'CONTACT_TEL'=>1], $vcard );

        $resultVCard = $vcardDB->fetchOne($contactID);
	$this->compareVCards($vcard, $resultVCard);
        
        return $vcardDB;
    } //testStoreAndRetrieveWTel()
    
    /**
     * @group default
     * @depends testStoreAndRetrieveWTel
     */
    public function testStoreAndRetrieveWTelPref(VCardDB $vcardDB)
    {
        $this->checkRowCounts(['CONTACT'=>0]);

        $vcard = new VCard();
	VCard::builder('fn')->setValue('Information')->pushTo($vcard);
        VCard::builder('tel')->setValue('555-1212')->setPref(1)->pushTo($vcard);

        $contactID = $vcardDB->store($vcard);
        $this->checkRowCounts(['CONTACT'=>1, 'CONTACT_TEL'=>1], $vcard );

        $resultVCard = $vcardDB->fetchOne($contactID);
	$this->compareVCards($vcard, $resultVCard);
        
        return $vcardDB;
    } //testStoreAndRetrieveWTel()
    
    /**
     * @group default
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
	$vcard->push(VCard::builder('fn')->setValue($expected['fn'])->build());
        $vcard->push(
            VCard::builder('key')->setValue($expected['key'])->build() );

        $contactID = $vcardDB->store($vcard);
        $this->checkRowCounts(['CONTACT'=>1, 'CONTACT_DATA'=>1], $vcard);

        $resultVCard = $vcardDB->fetchOne($contactID);
	$this->compareVCards($vcard, $resultVCard);
    } //testStoreAndRetrieveWKey()
    
    /**
     * @group default
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
	$vcard->push(VCard::builder('fn')->setValue($expected['fn'])->build());
        $vcard->push(
            VCard::builder('geo')->setValue($expected['geo'])->build() );

        $contactID = $vcardDB->store($vcard);
        $this->checkRowCounts(['CONTACT'=>1, 'CONTACT_GEO'=>1], $vcard);

        $resultVCard = $vcardDB->fetchOne($contactID);
	$this->compareVCards($vcard, $resultVCard);
    }
    
    /**
     * @group default
     * @depends testStoreAndRetrieveVCard
     */
    public function testStoreAndRetrieveWTZ(VCardDB $vcardDB)
    {
        $this->checkRowCounts([ 'CONTACT'=>0,
                                'CONTACT_TZ'=>0, 'CONTACT_TZ_REL_TYPES'=>0 ]);

        $expected = [
                        'fn'          => 'Whatever',
			'tz' => 'America/New_York'
                     ];
        $vcard = new VCard();
	$vcard->push(VCard::builder('fn')->setValue($expected['fn'])->build());
        VCard::builder('tz')->setValue($expected['tz'])
                            ->addType('work')->pushTo($vcard);

        $contactID = $vcardDB->store($vcard);
        $this->checkRowCounts( [ 'CONTACT'=>1,
                                 'CONTACT_TZ'=>1, 'CONTACT_TZ_REL_TYPES'=>1 ],
                               $vcard );

        $resultVCard = $vcardDB->fetchOne($contactID);
	$this->compareVCards($vcard, $resultVCard);
    }
    
    /**
     * @group default
     * @depends testStoreAndRetrieveVCard
     */
    public function testStoreAndRetrieveWRole(VCardDB $vcardDB)
    {
        $this->checkRowCounts([ 'CONTACT'=>0,
                                'CONTACT_ROLE'=>0, 'CONTACT_ROLE_REL_TYPES'=>0 ]);

        $expected = [
                        'fn'          => 'Whatever',
			'role' => 'crescent'
                     ];
        $vcard = new VCard();
	$vcard->push(VCard::builder('fn')->setValue($expected['fn'])->build());
        VCard::builder('role')->setValue($expected['role'])
                            ->addType('work')->pushTo($vcard);

        $contactID = $vcardDB->store($vcard);
        $this->checkRowCounts( [ 'CONTACT'=>1,
                                 'CONTACT_ROLE'=>1, 'CONTACT_ROLE_REL_TYPES'=>1 ],
                               $vcard );

        $resultVCard = $vcardDB->fetchOne($contactID);
	$this->compareVCards($vcard, $resultVCard);
    }
    
    
    /**
     * @group default
     * @depends testStoreAndRetrieveVCard
     */
    public function testStoreAndRetrieveWTitle(VCardDB $vcardDB)
    {
        $this->checkRowCounts([ 'CONTACT'=>0,
                                'CONTACT_TITLE'=>0, 'CONTACT_TITLE_REL_TYPES'=>0 ]);

        $expected = [
                        'fn'          => 'Whatever',
			'title' => 'magnifico'
                     ];
        $vcard = new VCard();
	$vcard->push(VCard::builder('fn')->setValue($expected['fn'])->build());
        VCard::builder('title')->setValue($expected['title'])
                            ->addType('work')->pushTo($vcard);

        $contactID = $vcardDB->store($vcard);
        $this->checkRowCounts( [ 'CONTACT'=>1,
                                 'CONTACT_TITLE'=>1, 'CONTACT_TITLE_REL_TYPES'=>1 ],
                               $vcard );

        $resultVCard = $vcardDB->fetchOne($contactID);
	$this->compareVCards($vcard, $resultVCard);
    }
    
    /**
     * @group default
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
    	$vcard->push(VCard::builder('fn')->setValue($expected['fn'])->build());
    	$vcard->push(
            VCard::builder('categories')->setValue($expected['category'])
                ->build() );
    
    	$contactID = $vcardDB->store($vcard);
        $this->checkRowCounts(['CONTACT'=>1, 'CONTACT_CATEGORIES'=>1], $vcard);

    	$resultVCard = $vcardDB->fetchOne($contactID);
        $this->compareVCards($vcard, $resultVCard);
    } //testStoreAndRetrieveWCategory()
    
    /**
     * @group default
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
     * @group default
     * @depends testStoreAndRetrieveVCard
     */
    public function testStoreAndRetrieveJohnDoe(VCardDB $vcardDB)
    {
        $this->checkRowCounts( [ 'CONTACT'=>0, 'CONTACT_N'=>0,
                                 'CONTACT_ORG'=>0, 'CONTACT_CATEGORIES'=>0,
                                 'CONTACT_TEL'=>0, 'CONTACT_TEL_REL_TYPES'=>0,
                                 'CONTACT_EMAIL'=>0,
                                 'CONTACT_EMAIL_REL_TYPES'=>0,
                                 'CONTACT_ADR'=>0, 'CONTACT_ADR_REL_TYPES'=>0,
                                 'CONTACT_DATA'=>0,'CONTACT_DATA_REL_TYPES'=>0
                               ]); // pre-condition

    	$johnDoe = $this->getJohnDoe();
    	$contactID = $vcardDB->store($johnDoe);

        $this->checkRowCounts( [ 'CONTACT'=>1, 'CONTACT_N'=>1,
                                 'CONTACT_ORG'=>0, 'CONTACT_CATEGORIES'=>0,
                                 'CONTACT_TEL'=>3, 'CONTACT_TEL_REL_TYPES'=>6,
                                 'CONTACT_EMAIL'=>2,
                                 'CONTACT_EMAIL_REL_TYPES'=>2,
                                 'CONTACT_ADR'=>1, 'CONTACT_ADR_REL_TYPES'=>1,
                                 'CONTACT_DATA'=>1,'CONTACT_DATA_REL_TYPES'=>0
                               ]);
        
        $vcards = $vcardDB->fetchAll();
        $this->assertCount(1, $vcards);
        $this->compareVCards($johnDoe, $vcards[$contactID]);
    }
    
    /**
     * @group default
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
     * @group default
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
        
        $this->assertEquals( 'individual',
                             $vcardDB->fetchOne($rSUID)->kind->getValue() );
        $this->assertEquals( 'organization',
                             $vcardDB->fetchOne($sAPLUID)->kind->getValue() );
        $this->assertEquals( 'individual',
                             $vcardDB->fetchOne($dDBUID)->kind->getValue() );

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
     * @group default
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
     * @group default
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
     * @group default
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

