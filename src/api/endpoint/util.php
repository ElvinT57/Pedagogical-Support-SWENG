<?php
//Helper functions for api

/**
 * Setup a properly configured JSON response, set the response code,
 * add an optional message, then die gracefully.
 * @param int $code The response code to send to the client.
 * @param string $message An optional message to send to the client.
 * @param array $data Optional data to return to the user.
 * @return void
 */
function json_response($code, $message = NULL, $data = NULL) {
    header("Content-Type: application/json");
    http_response_code($code);

    $response = [];

    if (isset($data)) {
        $response["data"] = $data;
    }
    if (isset($message)) {
        $response["message"] = $message;
    }
    
    echo json_encode($response);
    die();
}