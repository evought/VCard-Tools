<?php

use Phinx\Migration\AbstractMigration;

class VcardToolsCreateXtendedTypes extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-change-method
     *
     * Uncomment this method if you would like to use it.
     *
     */
    public function change()
    {
        $table = $this->table( 'CONTACT_XTENDED_REL_TYPES',
                               [ 'id'=>false,
                                 'primary_key'=>['XTENDED_ID', 'TYPE_NAME']
                               ] );
        $table  ->addColumn('XTENDED_ID', 'integer')
                ->addColumn('TYPE_NAME', 'string', ['limit'=>20])
                ->addForeignKey( 'XTENDED_ID', 'CONTACT_XTENDED', 'XTENDED_ID',
                                  [ 'delete'=>'CASCADE',
                                    'update'=>'CASCADE'
                                      ])
                ->create();
    }

    
    /**
     * Migrate Up.
     *
    public function up()
    {
    
    }*/

    /**
     * Migrate Down.
     *
    public function down()
    {

    }*/
}