<?php

include 'sys_pref.php';
session_start();

if (isset($_SESSION['LAST_ACTIVITY'])) {
    echo 1;
} else {
    echo 0;
}
