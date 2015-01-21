<?php

use Phinx\Migration\AbstractMigration;

class VcardToolsCreateNicknameTable extends AbstractMigration
{
        
    /**
     * Migrate Up.
     */
    public function up()
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
     * Migrate Down.
     */
    public function down()
    {
        $this->dropTable('CONTACT_NICKNAME');
        $this->dropTable('CONTACT_NICKNAME_REL_TYPES');
        $table = $this->table('CONTACT');
        $table  ->addColumn('NICKNAME', 'string', ['null'=>true])
                ->update();
    }
}