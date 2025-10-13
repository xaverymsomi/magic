<?php

/**
 * Description of index
 *
 * @author Developer
 */

namespace Modules\Dashboard;

use Libs\Auth;
use Libs\Controller;
use Libs\Perm_Auth;

class Dashboard extends Controller
{
    public $module = 'Dashboard';
    public $model;

    function __construct()
    {
        parent::__construct();
        $this->model = new Dashboard_Model();
        Auth::checkLogin();
    }

    function index()
    {
        $this->view->title = 'Home';
        $this->render('index');
    }

    function fetch_dashboard_admin_data()
    {
        $this->renderJson('fetch_dashboard_admin_data');
    }
    function fetch_dashboard_medical_data()
    {
        $this->renderJson('fetch_dashboard_medical_data');
    }

    function create_new_ticket()
    {
        $user_id = filter_var($_SESSION['id'], FILTER_SANITIZE_SPECIAL_CHARS);
        $permission = Perm_Auth::getPermissions();
        if ($permission->verifyPermission('create_ticket')) {
            //checking permission
            $this->view->title = 'Create New Business';
            $this->view->controller = 'Dashboard';
            $this->view->action = 'post_create_ticket';
            $this->view->icon = 'ticket';
            $this->view->data = ['has_extra' => 0];
            $this->view->dropdowns = $this->model->getFormDropdowns();
            $this->view->disabled = [];
            $this->render('create_ticket');
        } else {
            $this->_permissionDenied(__METHOD__);
        }
    }
}
