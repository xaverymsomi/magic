<?php

namespace Modules\Core\Payments\Mno\Halotel;

use Core\database\QueryBuilder as DB;

class HalotelCallbackController
{
    public function __construct()
    {
        $this->db = new DB;
    }

    public function index(){
        // body
    }
}