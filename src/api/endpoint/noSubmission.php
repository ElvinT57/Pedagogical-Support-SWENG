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
            if (!isset($_POST['gradeBase'])) {
                json_response(HTTP_RESPONSE_BAD_REQUEST, "No gradeBase specified.");
            }
            if (!isset($_POST['manualComment'])) {
                json_response(HTTP_RESPONSE_BAD_REQUEST, "No manualComment specified.");
            }
            if (!isset($_POST['commentDefinition_ids'])) {
                // if no saved comments were used, pass an empty array
                $_POST['commentDefinition_ids'] = [];
            }
            if (!isset($_POST['dateSubmitted'])){
                json_response(HTTP_RESPONSE_BAD_REQUEST, "No date submitted specified.");
            }
            
            if (insertNoSubmissionGrade($dbh, $_POST['user_id'], $_POST['assignment_id'], $_POST['gradeBase'], $_POST['manualComment'],$_POST['extraCredit'], $_POST['commentDefinition_ids'], $_POST['ignoreLatePenalty'], $_POST['dateSubmitted'])) {
                json_response(HTTP_RESPONSE_CREATED);
            } else {
                json_response(HTTP_RESPONSE_BAD_REQUEST, 'Failed to insert grade for a no submission assignment.');
            }
        }
    }
} else {
    json_response(HTTP_RESPONSE_BAD_REQUEST);
}