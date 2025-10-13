<?php

namespace Modules\Core\Payments\Mno\Yas;

use Core\database\QueryBuilder as DB;

class YasPaymentVerify
{
    public function __construct()
    {
        $this->db = new DB;
    }

    public function index(){
        // body
    }
}