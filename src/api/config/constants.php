<?php
    //User Types
    define("USER_STUDENT",          "Student");
    define("USER_TEACHER",          "Teacher");
    define("USER_ADMINISTRATOR",    "Administrator");
    define("USER_GUEST",            "Guest");

    //Semester Types
    define("SEMESTER_FALL",         "Fall");
    define("SEMESTER_Winter",       "Winter");
    define("SEMESTER_Spring",       "Spring");
    define("SEMESTER_Summer",       "Summer");

    //Assignment Types
    define("ASSIGNMENT_HOMEWORK",   "Homework");
    define("ASSIGNMENT_LAB",        "Lab");

    //HTML Constants
    define("PAGE_TITLE",            "Pedagogical Support");

    //Endpoint Constants
    define("ACTION_INSERT",             "insert");
    define("ACTION_INSERT_MULTIPLE",    "insert/multiple");
    define("ACTION_DELETE",             "delete");
    define("ACTION_UPDATE",             "update");

    define("KEY_ACTION",            "action");
    
    //HTTP Response Codes - Success
    define("HTTP_RESPONSE_OK",              200);
    define("HTTP_RESPONSE_CREATED",         201);
    //HTTP Response Codes - Error
    define("HTTP_RESPONSE_BAD_REQUEST",     400);
    define("HTTP_RESPONSE_FORBIDDEN",       403);
    define("HTTP_RESPONSE_UNAUTHORIZED",    401);
    define("HTTP_RESPONSE_CONFLICT",        409);

    //Info Types
    define("INFO_INFO",                     "INFO");
    define("INFO_SUCCESS",                  "SUCCESS");
    define("INFO_ERROR",                    "ERROR");