<?php

class Create_api_users_table
{
    private $_lava;

    public function __construct()
    {
        $this->_lava = lava_instance();
        $this->_lava->call->dbforge();
    }

    public function up()
    {
        if (!$this->_lava->dbforge->table_exists('users')) {
            $this->_lava->dbforge
                ->add_field([
                    'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE, 'auto_increment' => TRUE, 'null' => FALSE],
                    'name' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => FALSE],
                    'email' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => FALSE],
                    'password' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => FALSE],
                    'created_at' => ['type' => 'TIMESTAMP', 'null' => FALSE, 'default' => 'CURRENT_TIMESTAMP'],
                ])
                ->add_key('id', primary: TRUE)
                ->add_key('email', unique: TRUE, name: 'users_email_unique')
                ->create_table('users');
        }
    }

    public function down()
    {
        $this->_lava->dbforge->drop_table('users');
    }
}