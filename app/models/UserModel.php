<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class UserModel extends Model
{
    protected $table = 'users';

    public function findByUsername($username)
    {
        return $this->db
            ->table($this->table)
            ->where('username', $username)
            ->get();
    }
}