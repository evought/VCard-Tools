<?php

use Phinx\Migration\AbstractMigration;

class VcardToolsCreateRoleTable extends AbstractMigration
{
    /**
     * Migrate Up.
     */
    public function up()
    {
        $table = $this->table('CONTACT_ROLE', ['id'=>'ROLE_ID']);
        $table  ->addColumn('UID', 'string', ['limit'=>45])
                ->addColumn('ROLE', 'string', ['limit'=>50])
                ->addColumn('VALUETYPE', 'string', ['limit'=>40, 'null'=>true])
                ->addColumn('PREF', 'integer', [ 'limit'=>2,
                                                 'null'=>true,
                                                 'signed'=>false ] )
                ->addForeignKey( 'UID', 'CONTACT', 'UID',
                                [ 'delete'=>'CASCADE',
                                  'update'=>'CASCADE'
                                    ])
                ->create();
        
        $table = $this->table( 'CONTACT_ROLE_REL_TYPES',
                               [ 'id'=>false,
                                 'primary_key'=>['ROLE_ID', 'TYPE_NAME']
                               ] );
        $table  ->addColumn('ROLE_ID', 'integer')
                ->addColumn('TYPE_NAME', 'string', ['limit'=>20])
                ->addForeignKey( 'ROLE_ID', 'CONTACT_ROLE', 'ROLE_ID',
                                  [ 'delete'=>'CASCADE',
                                    'update'=>'CASCADE'
                                      ])
                ->create();
        
        $table = $this->table('CONTACT');
        $table->removeColumn('ROLE')->update();
    }

    /**
     * Migrate Down.
     */
    public function down()
    {
        $this->dropTable('CONTACT_ROLE');
        $this->dropTable('CONTACT_ROLE_REL_TYPES');
        $table = $this->table('CONTACT');
        $table  ->addColumn('ROLE', 'string', ['null'=>true])
                ->update();
    }
}