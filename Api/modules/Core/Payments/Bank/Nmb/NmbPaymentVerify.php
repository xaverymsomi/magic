<?php

namespace Modules\Core\Payments\Bank\Nmb;

use Core\database\QueryBuilder as DB;

class NmbPaymentVerify
{

    public function __construct()
    {
        $this->db = new DB;
    }

    public function index(){
        // body
    }

}