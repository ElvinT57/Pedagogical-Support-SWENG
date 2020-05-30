<?php
require_once '../config/db_connect.php';
require_once '../../userGate.php';
authorization_gate([USER_GUEST],   HTTP_RESPONSE_FORBIDDEN);
authorization_gate([USER_STUDENT], HTTP_RESPONSE_UNAUTHORIZED);

require_once "util.php";

if (isset($_POST[KEY_ACTION])) {
    require_once "../model/grade.php";

    $action = $_POST[KEY_ACTION];
    $user = $_SESSION['user'];
    switch($action) {
        case ACTION_INSERT: {
            if (!isset($_POST['assignment_id'])) {
                json_response(HTTP_RESPONSE_BAD_REQUEST, "No assignment_id specified.");
            }
            if (!isset($_POST['user_id'])) {
                json_response(HTTP_RESPONSE_BAD_REQUEST, "no user_id specified.");
            }
            if (!isset($_POST['gradeBase'])) {
                json_response(HTTP_RESPONSE_BAD_REQUEST, "No gradeBase specified.");
            }

            if (insertLabGrade($dbh, $_POST['assignment_id'], $_POST['user_id'], $_POST['gradeBase'])) {
                json_response(HTTP_RESPONSE_CREATED, "Success inserting grade.");
            } else {
                json_response(HTTP_RESPONSE_BAD_REQUEST, "Insertion Failed.");
            }
        }

        case ACTION_UPDATE: {
            if (!isset($_POST['assignment_id'])) {
                json_response(HTTP_RESPONSE_BAD_REQUEST, "No assignment_id specified.");
            }
            if (!isset($_POST['user_id'])) {
                json_response(HTTP_RESPONSE_BAD_REQUEST, "no user_id specified.");
            }
            if (!isset($_POST['gradeBase'])) {
                json_response(HTTP_RESPONSE_BAD_REQUEST, "No gradeBase specified.");
            }

            if (updateLabGrade($dbh, $_POST['assignment_id'], $_POST['user_id'], $_POST['gradeBase'])) {
                json_response(HTTP_RESPONSE_CREATED, "Success inserting grade.");
            } else {
                json_response(HTTP_RESPONSE_BAD_REQUEST, "Insertion Failed.");
            }
        }
    }
} else {
    json_response(HTTP_RESPONSE_BAD_REQUEST, "No Key Action Specified.");
}