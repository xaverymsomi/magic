<?php

namespace Modules\Undefined;

use Libs\Controller;

class Undefined extends Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->model = new Undefined_Model();
	}

	public function index() : void
	{
		$this->view->title = '404';
		$this->view->msg = '';
		$this->view->sub = '';
		$this->view->icon = '';
		$this->view->data = [];
		$this->view->dropdowns = [];
		$this->render('index');
	}
}