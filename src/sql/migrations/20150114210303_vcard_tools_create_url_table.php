<?php

use Phinx\Migration\AbstractMigration;

class VcardToolsCreateUrlTable extends AbstractMigration
{   
    /**
     * Migrate Up.
     */
    public function up()
    {
        $table = $this->table('CONTACT_URL', ['id'=>'URL_ID']);
        $table  ->addColumn('UID', 'string', ['limit'=>45])
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
        
        $table = $this->table( 'CONTACT_URL_REL_TYPES',
                               [ 'id'=>false,
                                 'primary_key'=>['URL_ID', 'TYPE_NAME']
                               ] );
        $table  ->addColumn('URL_ID', 'integer')
                ->addColumn('TYPE_NAME', 'string', ['limit'=>20])
                ->addForeignKey( 'URL_ID', 'CONTACT_URL', 'URL_ID',
                                  [ 'delete'=>'CASCADE',
                                    'update'=>'CASCADE'
                                      ])
                ->create();
        
        $table = $this->table('CONTACT');
        $table->removeColumn('URL')->update();
    }

    /**
     * Migrate Down.
     */
    public function down()
    {
        $this->dropTable('CONTACT_URL');
        $this->dropTable('CONTACT_URL_REL_TYPES');
        $table = $this->table('CONTACT');
        $table  ->addColumn('URL', 'string', ['null'=>true])
                ->update();
    }
}