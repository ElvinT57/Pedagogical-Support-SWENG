<?php
require_once './userGate.php';
authorization_gate([ USER_GUEST, USER_STUDENT ]);

require_once './api/config/db_connect.php';
require_once './api/model/enrollment.php';
require_once './api/model/section.php';
require_once './api/model/course.php';
require_once './vendor/autoload.php';

//get query string and user id
$courseId = $_GET['course_id'];
$user = $_SESSION['user'];
$userId = $user['id'];

if(isset($_POST['submit'])) {
    $semester = $_POST['semester'];
    $crn = $_POST['crn'];
    $sectionNum = $_POST['section'];
    $daysTaught = $_POST['days'];
    $beginTime = $_POST['begin-time'];

    if(!isset($_POST['lab-days']) && !isset($_POST['lab-begin-time'])) {
        $labMeetingDays = '';
        $labBeginTime = '';
    }

    else {
        $labMeetingDays = $_POST['lab-days'];
        $labBeginTime = $_POST['lab-begin-time']; 
    }

    $sessionNum = $_POST['session'];
    $year = $_POST['year'];

    //validation of section query
    $validSectionQuery = "SELECT *
                            FROM section
                            WHERE crn = :crn 
                            AND section.number = :sectionNum 
                            AND course_id = :courseID;";

    $validSectionStmt = $dbh->prepare($validSectionQuery);
    $validSectionStmt->bindParam(':crn', $crn);
    $validSectionStmt->bindParam(':sectionNum', $sectionNum);
    $validSectionStmt->bindParam(':courseID', $_GET['course_id']);

    $validSectionStmt->execute();

    $sectionCount = $validSectionStmt->rowCount();

    //check for any existing section records in the course
    if($sectionCount >= 1) {
        $info = [
            "type" => INFO_ERROR,
            "message" => "A section with section number '${sectionNum}' and CRN ${crn} already exists for this course."
        ];
    } else {
        if (addSection($dbh, $userId, $semester, $crn, $sectionNum, $daysTaught, $beginTime, $labMeetingDays, $labBeginTime, $sessionNum, $year, $courseId)) {
            $info = [
                "type" => INFO_SUCCESS,
                "message" => "A section with section number '${sectionNum}' and CRN ${crn} already exists for this course."
            ];
        } else {  
            $info = [
                "type" => INFO_ERROR,
                "message" => "Something went wrong when trying to create the section."
            ];
        }
    }
}


$loader = new \Twig\Loader\FilesystemLoader('./templates');
$twig = new \Twig\Environment($loader, [ ]);
$twig->addExtension(new \Twig\Extension\StringLoaderExtension());

$course = getCourse($dbh, $courseId);
$pageTitle = PAGE_TITLE;
echo $twig->render('view/sectionCreation.twig.html', [
    'page_title' => "Create Section - {$pageTitle}",
    'info' => (isset($info)) ? $info : NULL,
    'course' => $course,
    'user' => $user,
    'breadcrumbs' => [
        [ 'text' => $course['title'], 'link' => 'viewCourse.php?course_id={{ course.id }}' ],
        [ 'text' => 'Add Section', 'link' => '#' ]
    ]
]);