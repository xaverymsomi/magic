<?php

namespace Modules\Core\Payments\Bank\Pbz;

use Core\database\QueryBuilder as DB;

class PbzSelcomeController
{
    public function __construct()
    {
        $this->db = new DB;
    }

    public function index(){
        // body
    }
}