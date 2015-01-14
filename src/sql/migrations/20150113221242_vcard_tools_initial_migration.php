<?php

use Phinx\Migration\AbstractMigration;

class VcardToolsInitialMigration extends AbstractMigration
{
    
    public function change()
    {
        $table = $this->table( 'CONTACT',
                               [ 'id' => false,
                                 'primary_key' => array('UID')
                               ]
                            );
        $table  ->addColumn('UID', 'string', ['limit'=>45])
                ->addColumn('KIND', 'string', ['limit'=>20, 'null'=>true])
                ->addColumn('FN', 'string')
                ->addColumn('NICKNAME', 'string', ['null'=>true])
                ->addColumn('BDAY', 'timestamp', ['null'=>true])
                ->addColumn('ANNIVERSARY', 'timestamp', ['null'=>true])
                ->addColumn('TZ', 'string', ['limit'=>3, 'null'=>true])
                ->addColumn('TITLE', 'string', ['limit'=>50, 'null'=>true])
                ->addColumn('ROLE', 'string', ['limit'=>50, 'null'=>true])
                ->addColumn('REV', 'string', ['limit'=>50, 'null'=>true])
                ->addColumn('SORT_STRING', 'string', ['limit'=>50, 'null'=>true])
                ->addColumn('URL', 'string', ['null'=>true])
                ->addColumn('VERSION', 'string', ['limit'=>10, 'null'=>true])
                ->create();
        
        $table = $this->table('CONTACT_GEO', ['id'=>'GEO_ID']);
        $table  ->addColumn('UID', 'string', ['limit'=>45])
                ->addColumn('GEO', 'string')
                ->addColumn('MEDIATYPE', 'string', ['null'=>true])
                ->addColumn('VALUETYPE', 'string', ['limit'=>40, 'null'=>true])
                ->addColumn('PREF', 'integer', [ 'limit'=>2,
                                                 'null'=>true,
                                                 'signed'=>false ] )
                ->addForeignKey( 'UID', 'CONTACT', 'UID',
                                [ 'delete'=>'CASCADE',
                                  'update'=>'CASCADE'
                                    ])
                ->create();
        
        $table = $this->table('CONTACT_RELATED', ['id'=>'RELATED_ID']);
        $table  ->addColumn('UID', 'string', ['limit'=>45])
                ->addColumn('RELATED', 'string')
                ->addColumn('VALUETYPE', 'string', ['limit'=>40, 'null'=>true])
                ->addColumn('PREF', 'integer', [ 'limit'=>2,
                                                 'null'=>true,
                                                 'signed'=>false ] )
                ->addForeignKey( 'UID', 'CONTACT', 'UID',
                                [ 'delete'=>'CASCADE',
                                  'update'=>'CASCADE'
                                    ])
                ->create();
        
        $table = $this->table('CONTACT_N', ['id'=>'N_ID']);
        $table  ->addColumn('UID', 'string', ['limit'=>45])
                ->addColumn('GIVEN_NAME', 'string', ['limit'=>50, 'null'=>true])
                ->addColumn('ADDIT_NAME', 'string', ['limit'=>50, 'null'=>true])
                ->addColumn( 'FAMILY_NAME', 'string',
                                ['limit'=>50, 'null'=>true] )
                ->addColumn('PREFIXES', 'string', ['limit'=>50, 'null'=>true])
                ->addColumn('SUFFIXES', 'string', ['limit'=>50, 'null'=>true])
                ->addColumn('VALUETYPE', 'string', ['limit'=>40, 'null'=>true])
                ->addForeignKey( 'UID', 'CONTACT', 'UID',
                                [ 'delete'=>'CASCADE',
                                  'update'=>'CASCADE'
                                    ])
                ->create();
        
        $table = $this->table('CONTACT_ADR', ['id'=>'ADR_ID']);
        $table  ->addColumn('UID', 'string', ['limit'=>45])
                ->addColumn( 'POBOX', 'string',
                             [
                                 'limit'=>50, 'null'=>true,
                                 'comment'=>'deprecated'
                             ])
                ->addColumn( 'EXTENDED_ADDRESS', 'string',
                             ['null'=>true, 'comment'=>'deprecated'] )
                ->addColumn('STREET', 'string', ['null'=>true])
                ->addColumn('LOCALITY', 'string', ['limit'=>50, 'null'=>true])
                ->addColumn('REGION', 'string', ['limit'=>50, 'null'=>true])
                ->addColumn( 'POSTAL_CODE', 'string',
                             ['limit'=>30, 'null'=>true] )
                ->addColumn('COUNTRY', 'string', ['limit'=>50, 'null'=>true])
                ->addColumn( 'PREF', 'integer',
                             ['limit'=>2, 'null'=>true, 'signed'=>false] )
                ->addColumn('VALUETYPE', 'string', ['limit'=>40, 'null'=>true])
                ->addForeignKey( 'UID', 'CONTACT', 'UID',
                                [ 'delete'=>'CASCADE',
                                  'update'=>'CASCADE'
                                    ])
                ->create();
        
        $table = $this->table('CONTACT_TEL', ['id'=>'TEL_ID']);
        $table  ->addColumn('UID', 'string', ['limit'=>45])
                ->addColumn('TEL', 'string')
                ->addColumn('MEDIATYPE', 'string', ['null'=>true])
                ->addColumn('VALUETYPE', 'string', ['limit'=>40, 'null'=>true])
                ->addColumn('PREF', 'integer', [ 'limit'=>2,
                                                 'null'=>true,
                                                 'signed'=>false ] )
                ->addForeignKey( 'UID', 'CONTACT', 'UID',
                                [ 'delete'=>'CASCADE',
                                  'update'=>'CASCADE'
                                    ])
                ->create();

        $table = $this->table('CONTACT_EMAIL', ['id'=>'EMAIL_ID']);
        $table  ->addColumn('UID', 'string', ['limit'=>45])
                ->addColumn('EMAIL', 'string')
                ->addColumn('MEDIATYPE', 'string', ['null'=>true])
                ->addColumn('VALUETYPE', 'string', ['limit'=>40, 'null'=>true])
                ->addColumn('PREF', 'integer', [ 'limit'=>2,
                                                 'null'=>true,
                                                 'signed'=>false ] )
                ->addForeignKey( 'UID', 'CONTACT', 'UID',
                                [ 'delete'=>'CASCADE',
                                  'update'=>'CASCADE'
                                    ])
                ->create();
        
        $table = $this->table('CONTACT_CATEGORIES', ['id'=>'CATEGORY_ID']);
        $table  ->addColumn('UID', 'string', ['limit'=>45])
                ->addColumn('CATEGORY', 'string')
                ->addColumn('MEDIATYPE', 'string', ['null'=>true])
                ->addColumn('VALUETYPE', 'string', ['limit'=>40, 'null'=>true])
                ->addColumn('PREF', 'integer', [ 'limit'=>2,
                                                 'null'=>true,
                                                 'signed'=>false ] )
                ->addForeignKey( 'UID', 'CONTACT', 'UID',
                                [ 'delete'=>'CASCADE',
                                  'update'=>'CASCADE'
                                    ])
                ->create();
        
        $table = $this->table('CONTACT_NOTE', ['id'=>'NOTE_ID']);
        $table  ->addColumn('UID', 'string', ['limit'=>45])
                ->addColumn('NOTE', 'string')
                ->addColumn('MEDIATYPE', 'string', ['null'=>true])
                ->addColumn('VALUETYPE', 'string', ['limit'=>40, 'null'=>true])
                ->addColumn('PREF', 'integer', [ 'limit'=>2,
                                                 'null'=>true,
                                                 'signed'=>false ] )
                ->addForeignKey( 'UID', 'CONTACT', 'UID',
                                [ 'delete'=>'CASCADE',
                                  'update'=>'CASCADE'
                                    ])
                ->create();
        
        $table = $this->table('CONTACT_XTENDED', ['id'=>'XTENDED_ID']);
        $table  ->addColumn('UID', 'string', ['limit'=>45])
                ->addColumn('XNAME', 'string')
                ->addColumn('XVALUE', 'string')
                ->addColumn('MEDIATYPE', 'string', ['null'=>true])
                ->addColumn('VALUETYPE', 'string', ['limit'=>40, 'null'=>true])
                ->addColumn('PREF', 'integer', [ 'limit'=>2,
                                                 'null'=>true,
                                                 'signed'=>false ] )
                ->addForeignKey( 'UID', 'CONTACT', 'UID',
                                [ 'delete'=>'CASCADE',
                                  'update'=>'CASCADE'
                                    ])
                ->create();
        
        $table = $this->table('CONTACT_DATA', ['id'=>'DATA_ID']);
        $table  ->addColumn('UID', 'string', ['limit'=>45])
                ->addColumn('DATA_NAME', 'string', ['limit'=>10])
                ->addColumn('URL', 'string')
                ->addColumn('MEDIATYPE', 'string', ['null'=>true])
                ->addColumn('VALUETYPE', 'string', ['limit'=>40, 'null'=>true])
                ->addColumn('PREF', 'integer', [ 'limit'=>2,
                                                 'null'=>true,
                                                 'signed'=>false ] )
                ->addForeignKey( 'UID', 'CONTACT', 'UID',
                                [ 'delete'=>'CASCADE',
                                  'update'=>'CASCADE'
                                    ])
                ->create();
        
        $table = $this->table('CONTACT_ORG', ['id'=>'ORG_ID']);
        $table  ->addColumn('UID', 'string', ['limit'=>45])
                ->addColumn('NAME', 'string')
                ->addColumn('UNIT1', 'string', ['null'=>true])
                ->addColumn('UNIT2', 'string', ['null'=>true])
                ->addColumn('VALUETYPE', 'string', ['limit'=>40, 'null'=>true])
                ->addColumn('PREF', 'integer', [ 'limit'=>2,
                                                 'null'=>true,
                                                 'signed'=>false ] )
                ->addForeignKey( 'UID', 'CONTACT', 'UID',
                                [ 'delete'=>'CASCADE',
                                  'update'=>'CASCADE'
                                    ])
                ->create();
        
        $table = $this->table( 'CONTACT_GEO_REL_TYPES',
                               [ 'id'=>false,
                                 'primary_key'=>['GEO_ID', 'TYPE_NAME']
                               ] );
        $table  ->addColumn('GEO_ID', 'integer')
                ->addColumn('TYPE_NAME', 'string', ['limit'=>20])
                ->addForeignKey( 'GEO_ID', 'CONTACT_GEO', 'GEO_ID',
                                  [ 'delete'=>'CASCADE',
                                    'update'=>'CASCADE'
                                      ])
                ->create();
        
        $table = $this->table( 'CONTACT_RELATED_REL_TYPES',
                               [ 'id'=>false,
                                 'primary_key'=>['RELATED_ID', 'TYPE_NAME']
                               ] );
        $table  ->addColumn('RELATED_ID', 'integer')
                ->addColumn('TYPE_NAME', 'string', ['limit'=>20])
                ->addForeignKey( 'RELATED_ID', 'CONTACT_RELATED', 'RELATED_ID',
                                  [ 'delete'=>'CASCADE',
                                    'update'=>'CASCADE'
                                      ])
                ->create();
        
        $table = $this->table( 'CONTACT_ADR_REL_TYPES',
                               [ 'id'=>false,
                                 'primary_key'=>['ADR_ID', 'TYPE_NAME']
                               ] );
        $table  ->addColumn('ADR_ID', 'integer')
                ->addColumn('TYPE_NAME', 'string', ['limit'=>20])
                ->addForeignKey( 'ADR_ID', 'CONTACT_ADR', 'ADR_ID',
                                  [ 'delete'=>'CASCADE',
                                    'update'=>'CASCADE'
                                      ])
                ->create();
        
        $table = $this->table( 'CONTACT_TEL_REL_TYPES',
                               [ 'id'=>false,
                                 'primary_key'=>['TEL_ID', 'TYPE_NAME']
                               ] );
        $table  ->addColumn('TEL_ID', 'integer')
                ->addColumn('TYPE_NAME', 'string', ['limit'=>20])
                ->addForeignKey( 'TEL_ID', 'CONTACT_TEL', 'TEL_ID',
                                  [ 'delete'=>'CASCADE',
                                    'update'=>'CASCADE'
                                      ])
                ->create();
        
        $table = $this->table( 'CONTACT_EMAIL_REL_TYPES',
                               [ 'id'=>false,
                                 'primary_key'=>['EMAIL_ID', 'TYPE_NAME']
                               ] );
        $table  ->addColumn('EMAIL_ID', 'integer')
                ->addColumn('TYPE_NAME', 'string', ['limit'=>20])
                ->addForeignKey( 'EMAIL_ID', 'CONTACT_EMAIL', 'EMAIL_ID',
                                  [ 'delete'=>'CASCADE',
                                    'update'=>'CASCADE'
                                      ])
                ->create();
        
        $table = $this->table( 'CONTACT_CATEGORIES_REL_TYPES',
                               [ 'id'=>false,
                                 'primary_key'=>['CATEGORY_ID', 'TYPE_NAME']
                               ] );
        $table  ->addColumn('CATEGORY_ID', 'integer')
                ->addColumn('TYPE_NAME', 'string', ['limit'=>20])
                ->addForeignKey( 'CATEGORY_ID', 'CONTACT_CATEGORIES', 'CATEGORY_ID',
                                  [ 'delete'=>'CASCADE',
                                    'update'=>'CASCADE'
                                      ])
                ->create();
        
        $table = $this->table( 'CONTACT_NOTE_REL_TYPES',
                               [ 'id'=>false,
                                 'primary_key'=>['NOTE_ID', 'TYPE_NAME']
                               ] );
        $table  ->addColumn('NOTE_ID', 'integer')
                ->addColumn('TYPE_NAME', 'string', ['limit'=>20])
                ->addForeignKey( 'NOTE_ID', 'CONTACT_NOTE', 'NOTE_ID',
                                  [ 'delete'=>'CASCADE',
                                    'update'=>'CASCADE'
                                      ])
                ->create();
        
        $table = $this->table( 'CONTACT_DATA_REL_TYPES',
                               [ 'id'=>false,
                                 'primary_key'=>['DATA_ID', 'TYPE_NAME']
                               ] );
        $table  ->addColumn('DATA_ID', 'integer')
                ->addColumn('TYPE_NAME', 'string', ['limit'=>20])
                ->addForeignKey( 'DATA_ID', 'CONTACT_DATA', 'DATA_ID',
                                  [ 'delete'=>'CASCADE',
                                    'update'=>'CASCADE'
                                      ])
                ->create();
        
        $table = $this->table( 'CONTACT_ORG_REL_TYPES',
                               [ 'id'=>false,
                                 'primary_key'=>['ORG_ID', 'TYPE_NAME']
                               ] );
        $table  ->addColumn('ORG_ID', 'integer')
                ->addColumn('TYPE_NAME', 'string', ['limit'=>20])
                ->addForeignKey( 'ORG_ID', 'CONTACT_ORG', 'ORG_ID',
                                  [ 'delete'=>'CASCADE',
                                    'update'=>'CASCADE'
                                      ])
                ->create();
            
    }
    
    
    /**
     * Migrate Up.
     */
 /*   public function up()
    {
    
    } */

    /**
     * Migrate Down.
     */
/*    public function down()
    {

    } */
}