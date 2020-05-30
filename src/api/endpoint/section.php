<?php
require_once '../config/db_connect.php';
require_once '../model/section.php';
require_once '../../userGate.php';
// guests and students cannot modify our database
authorization_gate([USER_GUEST],   HTTP_RESPONSE_FORBIDDEN);
authorization_gate([USER_STUDENT], HTTP_RESPONSE_UNAUTHORIZED);

require_once "util.php";

if (isset($_POST[KEY_ACTION])) {
    require_once "../model/section.php";

    $action = $_POST[KEY_ACTION];
    $user = $_SESSION['user'];

    switch($action) {
        case ACTION_UPDATE: {
            if (!isset($_POST['semester'])) {
                json_response(HTTP_RESPONSE_BAD_REQUEST, "No semester provided.");
            }
            if (!isset($_POST['crn'])) {
                json_response(HTTP_RESPONSE_BAD_REQUEST, "No crn provided.");
            }
            if (!isset($_POST['section'])) {
                json_response(HTTP_RESPONSE_BAD_REQUEST, "No section provided.");
            }
            if (!isset($_POST['daysTaught'])) {
                json_response(HTTP_RESPONSE_BAD_REQUEST, "No days taught provided.");
            }
            if (!isset($_POST['beginTime'])) {
                json_response(HTTP_RESPONSE_BAD_REQUEST, "No section begin time provided.");
            }
            if (!isset($_POST['session'])) {
                json_response(HTTP_RESPONSE_BAD_REQUEST, "No session provided.");
            }
            if (!isset($_POST['year'])) {
                json_response(HTTP_RESPONSE_BAD_REQUEST, "No year provided.");
            }
            if (!isset($_POST['course_id'])) {
                json_response(HTTP_RESPONSE_BAD_REQUEST, "No course id provided.");
            }
            if (!isset($_POST['section_id'])) {
                json_response(HTTP_RESPONSE_BAD_REQUEST, "No section id provided.");
            }
            
            if (
                updateSection($dbh, $_POST["semester"], $_POST["crn"], $_POST["section"], 
                $_POST["daysTaught"], $_POST["beginTime"], $_POST["labDays"], 
                $_POST["labBeginTime"], $_POST["session"], $_POST["year"], $_POST["course_id"], 
                $_POST["section_id"])
                ) 
            {
                json_response(HTTP_RESPONSE_CREATED, "Successfully created a new section");
            } else {
                json_response(HTTP_RESPONSE_BAD_REQUEST, "failed updating the section after calling updateSection()");
            }
        }
        case ACTION_INSERT: {
            if (!isset($_POST['semester'])) {
                json_response(HTTP_RESPONSE_BAD_REQUEST, "No semester provided.");
            }
            if (!isset($_POST['crn'])) {
                json_response(HTTP_RESPONSE_BAD_REQUEST, "No crn provided.");
            }
            if (!isset($_POST['section'])) {
                json_response(HTTP_RESPONSE_BAD_REQUEST, "No section provided.");
            }
            if (!isset($_POST['daysTaught'])) {
                json_response(HTTP_RESPONSE_BAD_REQUEST, "No days taught provided.");
            }
            if (!isset($_POST['beginTime'])) {
                json_response(HTTP_RESPONSE_BAD_REQUEST, "No section begin time provided.");
            }
            if (!isset($_POST['session'])) {
                json_response(HTTP_RESPONSE_BAD_REQUEST, "No session provided.");
            }
            if (!isset($_POST['year'])) {
                json_response(HTTP_RESPONSE_BAD_REQUEST, "No year provided.");
            }
            if (!isset($_POST['course_id'])) {
                json_response(HTTP_RESPONSE_BAD_REQUEST, "No course id provided.");
            }
            if (!isset($_POST['user_id'])) {
                json_response(HTTP_RESPONSE_BAD_REQUEST, "No user id provided.");
            }
            
            if (addSection($dbh,$_POST["user_id"], $_POST["course_id"], $_POST["semester"], $_POST["crn"], $_POST["section"], 
                           $_POST["daysTaught"], $_POST["beginTime"], $_POST["labDays"], 
                           $_POST["labBeginTime"], $_POST["session"], $_POST["year"])) 
            {
                json_response(HTTP_RESPONSE_CREATED, "Successfully created a new section");
            } else {
                json_response(HTTP_RESPONSE_BAD_REQUEST, "failed updating the section after calling updateSection()");
            }
        }
    }
} else {
    json_response(HTTP_RESPONSE_BAD_REQUEST);
}