<?php
namespace Modules\Error;

use Libs\Controller;
/**
 * Description of Error
 *
 * @author Developer
 */
 //include_once '/var/www/html/libs/Controller.php';

class Error extends Controller {
    
    /**
     * 
     * @param String $title Title for the error page
     * @param String $msg main error message 
     * @param String $sub sub error message
     * @param Pe-s7-icon $icon icon to display eg pe-s7-warning
     */

    public $module = 'Error';
    /**
     * @var mixed
     */
    private $title;
    /**
     * @var mixed
     */
    private $msg;
    /**
     * @var mixed|null
     */
    private $sub;
    /**
     * @var mixed|string
     */
    private $icon;

    function __construct($title, $msg, $sub=null, $icon='pe-7s-attention') {
        parent::__construct(); 
        $this->title = $title;
        $this->msg = $msg;
        $this->sub = $sub;
        $this->icon = $icon;
    }
    
    /**
     * @return page error page rendered
     */
    function index() {
        $this->view->title = $this->title;
        $this->view->msg = $this->msg;
        $this->view->sub = $this->sub;
        $this->view->icon = $this->icon;
        $this->view->data = [];
        $this->view->dropdowns = [];

        $this->render('index');
    }
}
