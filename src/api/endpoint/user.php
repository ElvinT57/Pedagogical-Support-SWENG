<?php
require_once '../config/db_connect.php';
require_once '../model/section.php';
require_once '../../userGate.php';
// guests and students cannot modify our database
authorization_gate([USER_GUEST],   HTTP_RESPONSE_FORBIDDEN);
authorization_gate([USER_STUDENT], HTTP_RESPONSE_UNAUTHORIZED);

require_once "util.php";

if (isset($_POST[KEY_ACTION])) {
    require_once "../model/user.php";

    $action = $_POST[KEY_ACTION];
    $user = $_SESSION['user'];

    switch($action) {
        case ACTION_INSERT_MULTIPLE: {
            if (!isset($_POST['students'])) {
                json_response(HTTP_RESPONSE_BAD_REQUEST, "No students provided.");
            }
            if (!isset($_POST['section_id'])) {
                json_response(HTTP_RESPONSE_BAD_REQUEST, "No section_id provided.");
            }
            
            if (addStudentsAndEnrollInSection($dbh, $_POST['section_id'], $_POST['students'])) {
                json_response(HTTP_RESPONSE_CREATED);
            } else {
                // TODO: (Low) Error message should be more descriptive
                // e.g. state why the query failed
                json_response(HTTP_RESPONSE_BAD_REQUEST);
            }
        }
    }
} else {
    json_response(HTTP_RESPONSE_BAD_REQUEST);
}