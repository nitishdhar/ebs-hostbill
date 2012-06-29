<?php
/*
 * EBS Payment Gateway Integration Module for HostBill
 * Author - Nitish Dhar
 * Email - nitishdhar11@gmail.com
 *  
 * http://nitishdhar.com
 */

$_REQUEST['cmd'] = 'callback';
$_REQUEST['module'] = 'ebs';

session_start();
include('../../../../includes/general.php');
FrontController::init('module');
?>
