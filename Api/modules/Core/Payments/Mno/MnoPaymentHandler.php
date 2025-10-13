<?php

namespace Modules\Core\Payments\Mno;

use Core\database\QueryBuilder as DB;

class MnoPaymentHandler
{
    public function __construct()
    {
        $this->db = new DB;
    }

    public function index(){
        // body
    }
}