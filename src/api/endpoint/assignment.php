<?php
require_once '../config/db_connect.php';
require_once '../../userGate.php';
authorization_gate([USER_GUEST],   HTTP_RESPONSE_FORBIDDEN);
authorization_gate([USER_STUDENT], HTTP_RESPONSE_UNAUTHORIZED);

require_once "util.php";

if(isset($_POST[KEY_ACTION])) {
    require_once '../model/assignment.php';

    $action = $_POST[KEY_ACTION];
    $user = $_SESSION['user'];

    switch($action) {
        case ACTION_UPDATE: {
            $REQUIRED = [
                "id",
                "title",
                "description",
                "maxGrade",
                "number",
                "assignmentType",
                "permitsUpload",
                "dateAvailable",
                "dateDue",
                "latePenalty",
                "latePenaltyInterval",
                "section_ids"
            ];

            foreach ($REQUIRED as $key) {
                if (!isset($_POST[$key])) {
                    json_response(HTTP_RESPONSE_BAD_REQUEST, "Missing required field: '${key}'");
                }
            }

            if (updateAssignment(
                    $dbh,
                    $_POST["id"],
                    $_POST["title"],
                    $_POST["description"],
                    $_POST["maxGrade"],
                    $_POST["number"],
                    $_POST["assignmentType"],
                    $_POST["permitsUpload"],
                    $_POST["dateAvailable"],
                    $_POST["dateDue"],
                    $_POST["latePenalty"],
                    $_POST["latePenaltyInterval"],
                    $_POST["section_ids"]
            )) {
                json_response(HTTP_RESPONSE_CREATED);
            } else {
                json_response(HTTP_RESPONSE_BAD_REQUEST, "Failed to update assignment.");
            }
        }

        case ACTION_INSERT: {
            $REQUIRED = [
                "course_id",
                "title",
                "description",
                "maxGrade",
                "number",
                "assignmentType",
                "permitsUpload",
                "dateAvailable",
                "dateDue",
                "latePenalty",
                "latePenaltyInterval",
                "section_ids"
            ];
            

            foreach ($REQUIRED as $key) {
                if (!isset($_POST[$key])) {
                    json_response(HTTP_RESPONSE_BAD_REQUEST, "Missing required field: '${key}'");
                }
            }

            if(addAssignment(
                $dbh,
                $_POST["course_id"],
                $_POST["title"],
                $_POST["description"],
                $_POST["maxGrade"],
                $_POST["number"],
                $_POST["assignmentType"],
                $_POST["permitsUpload"],
                $_POST["dateAvailable"],
                $_POST["dateDue"],
                $_POST["latePenalty"],
                $_POST["latePenaltyInterval"],
                $_POST["section_ids"]
            )) {
                json_response(HTTP_RESPONSE_CREATED);
            } else {
                json_response(HTTP_RESPONSE_BAD_REQUEST, "Failed to add assignment.");
            }
        }
    }
} else {
    json_response(HTTP_RESPONSE_BAD_REQUEST);
}

?>