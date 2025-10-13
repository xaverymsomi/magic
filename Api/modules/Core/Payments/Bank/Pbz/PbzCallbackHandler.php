<?php

namespace Modules\Core\Payments\Bank\Pbz;

use Core\database\QueryBuilder as DB;

class PbzCallbackHandler
{
    public function __construct()
    {
        $this->db = new DB;
    }

    public function index(){
        echo 123;exit;
        // body
    }

}