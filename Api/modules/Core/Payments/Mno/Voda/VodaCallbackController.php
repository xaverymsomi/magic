<?php

namespace Modules\Core\Payments\Mno\Voda;

use Core\database\QueryBuilder as DB;

class VodaCallbackController
{
    public function __construct()
    {
        $this->db = new DB;
    }

    public function index(){
        // body
    }
}