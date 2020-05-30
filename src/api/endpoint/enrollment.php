<?php
require_once '../config/db_connect.php';
require_once '../model/section.php';
require_once '../../userGate.php';
// guests and students cannot modify our database
authorization_gate([USER_GUEST],   HTTP_RESPONSE_FORBIDDEN);
authorization_gate([USER_STUDENT], HTTP_RESPONSE_UNAUTHORIZED);

require_once "util.php";

if (isset($_POST[KEY_ACTION])) {
    require_once "../model/enrollment.php";

    $action = $_POST[KEY_ACTION];
    $user = $_SESSION['user'];

    switch($action) {
        case ACTION_UPDATE: {
            if (!isset($_POST['student_id'])) {
                json_response(HTTP_RESPONSE_BAD_REQUEST, "No student id provided.");
            }
            if (!isset($_POST['status'])) {
                json_response(HTTP_RESPONSE_BAD_REQUEST, "No status provided.");
            }
            
            if (updateDropStatus($dbh, $_POST['student_id'], $_POST['status'])) {
                json_response(HTTP_RESPONSE_CREATED);
            } else {
                json_response(HTTP_RESPONSE_BAD_REQUEST, "Error updating the student's enrollment status");
            }
        }
    }
} else {
    json_response(HTTP_RESPONSE_BAD_REQUEST);
}