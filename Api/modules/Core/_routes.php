<?php

use Modules\Core\Auth\AuthController;
use Modules\Core\Payments\Bank\Crdb\CrdbCallbackHandler;
use Modules\Core\Payments\Bank\Crdb\CrdbController;
use Modules\Core\Payments\Bank\Nmb\NmbCallbackHandler;
use Modules\Core\Payments\Bank\Nmb\NmbController;
use Modules\Core\Payments\Bank\Nmb\NmbGetTransactionOrder;
use Modules\Core\Payments\Bank\Nmb\NmbPaymentVerify;
use Modules\Core\Payments\Bank\Nmb\NmbProcessPayment;
use Modules\Core\Payments\Bank\Nmb\NmbSearchPayment;
use Modules\Core\Payments\Bank\Pbz\PbzCallbackHandler;
use Modules\Core\Payments\Bank\Pbz\PbzController;
use Modules\Core\Payments\Bank\Pbz\PbzSearchPaymentController;
use Modules\Core\Payments\Bank\Pbz\PbzSelcomeController;
use Modules\Core\Payments\Mno\MnoPaymentHandler;
use Modules\Core\Payments\Mno\Yas\YasPaymentVerify;
use Modules\Core\Payments\Mno\Yas\YasProcessPayment;
use Modules\Core\Payments\Mno\Yas\YasSearchPaymentController;

// AUTH
$router->post('api/login', [AuthController::class, 'login']);
$router->post('api/change_password', [AuthController::class, 'changePassword']);
$router->post('api/reset_password', [AuthController::class, 'resetPassword']);

//MNO
$router->get('VCPayment/pay', [CrdbController::class, 'index']);
$router->post('api/portal/VCPayment/pushRequest', [MnoPaymentHandler::class, 'index']);
$router->post('api/app/agent/VCPayment/pushRequest', [MnoPaymentHandler::class, 'index']);
$router->post('VCPayment/pushRequest', [MNOPaymentHandler::class, 'index']);

$router->post('VCPayment/{id}/callback', [MnoCallbackHandler::class, 'index']);
//$router->post('VCPayment/callback/{id}', [MNOCalbackHandler::class, 'index']);

$router->post('api/payment/yas/verify.php', [YasPaymentVerify::class, 'index']);
$router->post('api/payment/yas/payment.php',[YasProcessPayment::class, 'index']);
$router->post('api/payment/yas/search_payment.php',[YasSearchPaymentController::class, 'index']);

//ZAN MALIPO
//$router->post('api/zanmalipo/request_control', [ZanMalipoRequestControlHandler::class, 'requestControlNumber']);
//$router->post('api/zanmalipo/request_response', [ZanMalipoResponseHandler::class, 'index']);
//$router->post('api/zanmalipo/bill_cancel_request', [BillCancellationRequestHandler::class, 'process_request']);
//$router->post('api/zanmalipo/payment_info', [BillPaymentHandler::class, 'index']);
//$router->post('api/zanmalipo/request_reconciliation', [BillReconciliationRequestHandler::class, 'requestControlNumber']);
//$router->post('api/zanmalipo/reconciliation_response', [BillReconciliationResponseHandler::class, 'index']);

//PBZ
$router->post('api/payment/pbz/verify.php', [PbzController::class, 'index']);
$router->post('api/payment/pbz/payment.php',[PbzCallbackHandler::class, 'index']);
$router->post('api/payment/pbz/search_payment.php',[PbzSearchPaymentController::class, 'index']);
$router->post('api/payment/pbz/selcome_payment.php',[PbzSelcomeController::class, 'index']);

//Payment CRDB route
$router->post('api/payment/crdb/get_payment_url', [CrdbController::class, 'index']);
$router->post('api/payment/crdb/complete_payment',[CrdbCallbackHandler::class, 'index']);

//Payment NMB route
$router->post('api/payment/nmb/payment_checkout', [NmbController::class, 'index']);
$router->post('api/payment/nmb/payment_order', [NmbGetTransactionOrder::class, 'index']);
$router->get('api/payment/nmb/complete_payment',[NmbCallbackHandler::class, 'index']);

$router->post('api/payment/nmb/verify.php', [NmbPaymentVerify::class, 'index']);
$router->post('api/payment/nmb/payment.php',[NmbProcessPayment::class, 'index']);
$router->post('api/payment/nmb/search_payment.php',[NmbSearchPayment::class, 'index']);
