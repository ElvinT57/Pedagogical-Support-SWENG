<?php
require_once '../config/db_connect.php';
require_once '../../userGate.php';
// guests and students cannot modify our database
authorization_gate([USER_GUEST],   HTTP_RESPONSE_FORBIDDEN);
authorization_gate([USER_STUDENT], HTTP_RESPONSE_UNAUTHORIZED);

require_once "util.php";

if (isset($_POST[KEY_ACTION])) {
    require_once "../model/comment.php";

    $action = $_POST[KEY_ACTION];
    $user = $_SESSION['user'];
    switch($action) {
        case ACTION_INSERT: {
            if (!isset($_POST['comment_text'])) {
                json_response(HTTP_RESPONSE_BAD_REQUEST, "No comment_text specified.");
            }

            if (addCommentDefinition($dbh, $user['id'], $_POST['comment_text'])) {
                json_response(HTTP_RESPONSE_CREATED);
            } else {
                // TODO: (Low) Error message should be more descriptive
                // e.g. state why the query failed
                json_response(HTTP_RESPONSE_BAD_REQUEST);
            }
        }
        case ACTION_DELETE: {
            if (!isset($_POST['comment_id'])) {
                json_response(HTTP_RESPONSE_BAD_REQUEST, "No comment_id specified.");
            }

            if (toggleCommentDefinition($dbh, $_POST['comment_id'], 1)) {
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