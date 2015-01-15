<?php

use Phinx\Migration\AbstractMigration;

class VcardToolsCreateNicknameTable extends AbstractMigration
{
    
    public function change()
    {
        $table = $this->table('CONTACT_NICKNAME', ['id'=>'NICKNAME_ID']);
        $table  ->addColumn('UID', 'string', ['limit'=>45])
                ->addColumn('NICKNAME', 'string')
                ->addColumn('VALUETYPE', 'string', ['limit'=>40, 'null'=>true])
                ->addColumn('PREF', 'integer', [ 'limit'=>2,
                                                 'null'=>true,
                                                 'signed'=>false ] )
                ->addForeignKey( 'UID', 'CONTACT', 'UID',
                                [ 'delete'=>'CASCADE',
                                  'update'=>'CASCADE'
                                    ])
                ->create();
        
        $table = $this->table( 'CONTACT_NICKNAME_REL_TYPES',
                               [ 'id'=>false,
                                 'primary_key'=>['NICKNAME_ID', 'TYPE_NAME']
                               ] );
        $table  ->addColumn('NICKNAME_ID', 'integer')
                ->addColumn('TYPE_NAME', 'string', ['limit'=>20])
                ->addForeignKey( 'NICKNAME_ID', 'CONTACT_NICKNAME', 'NICKNAME_ID',
                                  [ 'delete'=>'CASCADE',
                                    'update'=>'CASCADE'
                                      ])
                ->create();
        
        $table = $this->table('CONTACT');
        $table->removeColumn('NICKNAME')->update();
    }
    
    /**
     * Migrate Up.
     */
    /* public function up()
    {
    
    }*/

    /**
     * Migrate Down.
     */
    /*public function down()
    {

    }*/
}