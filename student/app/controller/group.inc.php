<?php
session_start();
require_once '../model/group.class.php';

if (($_GET['action'] == 'leaveGroup') && isset($_POST['id'])) {
    $group = new group();
    if (!is_numeric($_POST['id'])) {
        echo 'Group ID is not valid';
    } else {
        $group->leaveGroup($_POST['id']);
        echo 'success';
    }
}
