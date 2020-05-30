<?php
require_once '../config/db_connect.php';
require_once '../model/section.php';
require_once '../../userGate.php';
// guests and students cannot modify our database
authorization_gate([USER_GUEST],   HTTP_RESPONSE_FORBIDDEN);
authorization_gate([USER_STUDENT], HTTP_RESPONSE_UNAUTHORIZED);

require_once "util.php";

if (isset($_POST[KEY_ACTION])) {
    require_once "../model/course.php";

    $action = $_POST[KEY_ACTION];
    $user = $_SESSION['user'];

    switch($action) {
        case ACTION_UPDATE: {
            if (!isset($_POST['courseTitle'])) {
                json_response(HTTP_RESPONSE_BAD_REQUEST, "No course title provided.");
            }
            if (!isset($_POST['startDate'])) {
                json_response(HTTP_RESPONSE_BAD_REQUEST, "No start date provided.");
            }
            if (!isset($_POST['endDate'])) {
                json_response(HTTP_RESPONSE_BAD_REQUEST, "No end date provided.");
            }
            if (!isset($_POST['course_id'])) {
                json_response(HTTP_RESPONSE_BAD_REQUEST, "No course id provided.");
            }
            
            
            if (editCourse($dbh, $_POST['course_id'], $_POST['courseTitle'], $_POST['startDate'], $_POST['endDate']))
            {
                json_response(HTTP_RESPONSE_CREATED, "Successfully created update the course.");
            } else {
                json_response(HTTP_RESPONSE_BAD_REQUEST, "failed updating the section after executing editCourse()");
            }
        }
    }
} else {
    json_response(HTTP_RESPONSE_BAD_REQUEST);
}