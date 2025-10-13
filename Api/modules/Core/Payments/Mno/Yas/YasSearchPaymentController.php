<?php

namespace Modules\Core\Payments\Mno\Yas;

use Core\database\QueryBuilder as DB;

class YasSearchPaymentController
{
    public function __construct()
    {
        $this->db = new DB;
    }

    public function index(){
        // body
    }
}