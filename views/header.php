<!DOCTYPE html>
<html ng-app="app">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta http-equiv="Content-Security-Policy" content=""/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <link rel="shortcut icon" type="image/x-icon" href="<?php echo URL; ?>/assets/images/rahisi/official_rahisi_minimal_logo_coloured.png"/>
    <link href="<?php echo APP_DIR; ?>/assets/css/font-awesome.min.css" rel="stylesheet" type="text/css">
    <link href="<?php echo APP_DIR; ?>/assets/css/bootstrap.min.css" rel="stylesheet" type="text/css"/>
    <link href="<?php echo APP_DIR; ?>/assets/css/pe-icon-7-stroke.css" rel="stylesheet" type="text/css"/>
    <link href="<?php echo APP_DIR; ?>/assets/css/helper.css" rel="stylesheet" type="text/css"/>
    <link href="<?php echo APP_DIR; ?>/assets/css/<?php echo $my_theme = 'rahisi'; ?>-login.css" rel="stylesheet"
          type="text/css"/>
    <link href="<?php echo APP_DIR; ?>/assets/css/<?php echo $my_theme; ?>-mabrex.css" rel="stylesheet"
          type="text/css"/>
    <link href="<?php echo APP_DIR; ?>/assets/css/animate.css" rel="stylesheet" type="text/css"/>
    <link href="<?php echo APP_DIR; ?>/assets/css/jquery-ui.css" rel="stylesheet" type="text/css"/>
    <link href="<?php echo APP_DIR; ?>/assets/css/angular-datatables.min.css" rel="stylesheet" type="text/css"/>
    <link href="<?php echo APP_DIR; ?>/assets/css/bootstrap-switch.min.css" rel="stylesheet" type="text/css"/>
    <link href="<?php echo APP_DIR; ?>/assets/css/mabrex.css" rel="stylesheet" type="text/css">

    <link href="<?php echo APP_DIR; ?>/assets/css/dashboard.css" rel="stylesheet" type="text/css">

    <script src="<?php echo APP_DIR; ?>/assets/js/jquery-3.2.1.min.js" type="text/javascript"></script>
    <script src="<?php echo APP_DIR; ?>/assets/js/jquery.cookie.js" type="text/javascript"></script>

    <script src="<?php echo APP_DIR; ?>/assets/js/angular.js" type="text/javascript"></script>
    <script src="<?php echo APP_DIR; ?>/assets/js/angular-filter.min.js" type="text/javascript"></script>
    <script src="<?php echo APP_DIR; ?>/assets/js/jquery.dataTables.min.js" type="text/javascript"></script>
    <script src="<?php echo APP_DIR; ?>/assets/js/angular-datatables.min.js" type="text/javascript"></script>
    <script src="<?php echo APP_DIR; ?>/assets/js/bootstrap.min.js" type="text/javascript"></script>
    <script src="<?php echo APP_DIR; ?>/assets/js/jquery-ui.min.js" type="text/javascript"></script>
    <script src="<?php echo APP_DIR; ?>/assets/js/bootstrap-notify.js" type="text/javascript"></script>
    <script src="<?php echo APP_DIR; ?>/assets/js/dirPagination.js" type="text/javascript"></script>
    <script src="<?php echo APP_DIR; ?>/assets/js/ui-bootstrap-tpls-0.9.0.js" type="text/javascript"></script>
    <script src="<?php echo APP_DIR; ?>/assets/js/bootstrap-switch.min.js" type="text/javascript"></script>
    <script src="<?php echo APP_DIR; ?>/assets/js/sms_validate.js" type="text/javascript"></script>
    <script src="<?php echo APP_DIR; ?>/assets/js/Chart.min.js" type="text/javascript"></script>

    <script src="<?php echo APP_DIR; ?>/assets/js/angular-chart.min.js" type="text/javascript"></script>
    <script src="<?php echo APP_DIR; ?>/assets/js/select.min.js" type="text/javascript"></script>
    <script src="<?php echo APP_DIR; ?>/assets/js/angular-sanitize.min.js" type="text/javascript"></script>
    <link href="<?php echo APP_DIR; ?>/assets/css/select.min.css" rel="stylesheet" type="text/css"/>

    <script src="<?php echo APP_DIR; ?>/assets/js/angular-animate.min.js" type="text/javascript"></script>

    <link href="<?php echo APP_DIR; ?>/assets/css/toaster.min.css" rel="stylesheet" type="text/css"/>
    <script src="<?php echo APP_DIR; ?>/assets/js/toaster.min.js" type="text/javascript"></script>

    <script src="<?php echo APP_DIR; ?>/assets/js/app/app-imageupload.js" type="text/javascript"></script>

    <link href="<?php echo APP_DIR; ?>/assets/css/daterangepicker.css" rel="stylesheet" type="text/css"/>
    <script src="<?php echo APP_DIR; ?>/assets/js/moment.min.js" type="text/javascript"></script>
    <script src="<?php echo APP_DIR; ?>/assets/js/daterangepicker.js" type="text/javascript"></script>
    <script src="<?php echo APP_DIR; ?>/assets/js/ng-bs-daterangepicker.js" type="text/javascript"></script>

    <script src="<?php echo APP_DIR; ?>/assets/js/app/app.js" type="text/javascript"></script>
    <script src="<?php echo APP_DIR; ?>/assets/js/app/app-variables.js" type="text/javascript"></script>
    <script src="<?php echo APP_DIR; ?>/assets/js/app/app-angular.js" type="text/javascript"></script>
    <script src="<?php echo APP_DIR; ?>/assets/js/app/app-directives.js" type="text/javascript"></script>
    <script src="<?php echo APP_DIR; ?>/assets/js/app/angular-create.js" type="text/javascript"></script>
    <script src="<?php echo APP_DIR; ?>/assets/js/app/angular-profile.js" type="text/javascript"></script>
    <script src="<?php echo APP_DIR; ?>/assets/js/app/angular-dashboard.js" type="text/javascript"></script>
    <script src="<?php echo APP_DIR; ?>/assets/js/app/angular_permission.js" type="text/javascript"></script>
    <script src="<?php echo APP_DIR; ?>/assets/js/app/app-exportToExcel.js" type="text/javascript"></script>
    <script src="<?php echo APP_DIR; ?>/assets/js/app/angular_report.js" type="text/javascript"></script>
    <script src="<?php echo APP_DIR; ?>/assets/js/ng-file-upload-all.min.js" type="text/javascript"></script>


    <!-- Socket io -->
<!--    <script src="//socket.rahisi.co.tz:3721/socket.io/socket.io.js" type="text/javascript"></script>-->

    <script src="<?php echo URL; ?><?php echo APP_DIR; ?>/assets/js/alasql.min.js" type="text/javascript"></script>
    <script src="<?php echo URL; ?><?php echo APP_DIR; ?>/assets/js/xlsx.core.min.js" type="text/javascript"></script>
    <script>
        var time = new Date().getTime();
        $(document).mousemove(function () {
            time = new Date().getTime();
        });
        $(document).keypress(function () {
            time = new Date().getTime();
        });
        setInterval(function () {
            if (new Date().getTime() - time >= 900000) { //15 minutes
                window.location.reload(true);
            }
        }, 60000);
    </script>

    <script>
        // Function to execute the PHP script
        function executePHPScript() {
            // let xhr = new XMLHttpRequest();
            // xhr.open('GET', 'http://mabrex.rahisi/Autorun/index', true); // working
            // xhr.send();
        }

        // Execute the PHP script when the page loads
        window.onload = function () {
            executePHPScript();
            setInterval(executePHPScript, 300000); // 300,000 milliseconds = 5 minutes
        };
    </script>

    <title>Mabrex Core</title>
</head>

<body>

<!--<audio id="device_offline" src="--><?php //echo APP_DIR; ?><!--/assets/audio/shut-your-mouth.mp3" preload="auto"></audio>-->
<!--<audio id="device_online" src="--><?php //echo APP_DIR; ?><!--/assets/audio/electronic.mp3" preload="auto"></audio>-->

<audio id="device_online" preload="auto">
    <source src="<?php echo APP_DIR; ?>/assets/audio/shut-your-mouth.mp3" type="audio/mpeg">
    Your browser does not support the audio element.
</audio>

<audio id="device_online" preload="auto">
    <source src="<?php echo APP_DIR; ?>/assets/audio/electronic.mp3" type="audio/mpeg">
    Your browser does not support the audio element.
</audio>
