<?php

namespace Modules\Core\Payments\Mno\Airtel;

use Core\database\QueryBuilder as DB;

class AirtelController
{
    public function __construct()
    {
        $this->db = new DB;
    }

    public function index(){
        // body
    }
}