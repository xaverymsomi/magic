<div class="container-fluid" id="page-content">
    <?php
    //Getting all user roles
    use Libs\Auth;
    use Libs\Perm_Auth;

    $perm = Perm_Auth::getPermissions();
    //verifying user roles
    if ($perm->verifyPermission('dashboard_admin')) {
        include 'dashboard_admin.php';
    }
    elseif ($perm->verifyPermission('dashboard_medical')) {
        include 'dashboard_medical_doctor.php';
    }
    elseif ($perm->verifyPermission('dashboard_finance')) {
        include 'dashboard_finance.php';
    }
    ?>
</div>
