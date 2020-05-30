<?php
    require_once 'api/config/constants.php';

    session_start();

    if (!isset($_SESSION['user'])) {
        $_SESSION['user'] = [
            'userType' => USER_GUEST
        ];
    }

    /**
     * Block the specified users from enter this page and return an optional error.
     * @param array $types Block the specified types of users from entering this page
     * @param bool $error If set, how should the gate respond to a authorization failure.
     *             String: Redirect the user to the specified page.
     *             int: Return the specified response code and die.
     *             Null: Echo an error message and die
     */
    function authorization_gate($types, $error = "login.php") {
        assert(is_array($types));
        assert(in_array(USER_GUEST, $types) || in_array(USER_STUDENT, $types) || in_array(USER_TEACHER, $types));

        foreach ($types as $key) {
            if ($_SESSION['user']['userType'] == $key) {
                if (isset($error)) {
                    if (is_string($error)) {
                        header("Location: {$error}");
                        die();
                    } else if (is_int($error)) {
                        http_response_code($error);
                        die();
                    }
                } else {
                    die("You are not authorized to view this page.");
                }
            }
        }
    }
?>