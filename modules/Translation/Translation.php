<?php


namespace Modules\Translation;


use Libs\Perm_Auth;

class Translation extends \Libs\Controller
{
    public $model;
    public function __construct()
    {
        parent::__construct();
        $this->model = new Translation_Model();
    }

    public function index()
    {
        $perm = Perm_Auth::getPermissions();
        if ($perm->verifyPermission('view_translations')) {
            //            $this->view->data = $this->model->getConfigurationData(filter_var($_SESSION['council'], FILTER_SANITIZE_NUMBER_INT));
            $this->view->data = $this->model->getTranslationData();
            $this->view->dropdowns = $this->model->getMiscellaneousDropdowns();
            $this->render('index');
        } else {
            $this->_permissionDenied(__METHOD__);
        }
    }
}
