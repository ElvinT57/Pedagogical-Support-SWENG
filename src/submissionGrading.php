<?php
require_once './vendor/autoload.php';
require_once './userGate.php';
require_once './api/config/db_connect.php';
require_once './api/model/submission.php';
require_once './api/model/user.php';
require_once './api/model/assignment.php';
require_once './api/model/comment.php';
require_once './api/model/section.php';
require_once './api/model/course.php';

authorization_gate([ USER_GUEST, USER_STUDENT ]);

$loader = new \Twig\Loader\FilesystemLoader('./templates');
$twig = new \Twig\Environment($loader, [ ]);
$twig->addExtension(new \Twig\Extension\StringLoaderExtension());

$user = $_SESSION['user'];
$predefComments = getCommentDefinitions($dbh, $user['id']);

$section = getSection($dbh, $_GET['section_id']);
$course = getCourseForSection($dbh, $_GET['section_id']);
$assignment = getAssignment($dbh, $_GET['assignment_id']);

// get student's user information
$student = getUser($dbh, $_GET['student_id']);
// If students profile path doesn't exist, use default profile picture
if (!file_exists($student['profilePath'])) {
    $student['profilePath'] = './Pictures/defaultUserPicture.png'; 
}

// check if it's an assignment that permits upload
if($assignment['permitsUpload'] == 1){
    // retrieve the submission
    $submission = getAssignmentSubmission($dbh, $_GET['submission_id']);

    // retrieve the students who have not been graded yet
    $ungraded = getUngradedStudents($dbh, $_GET['section_id'], $_GET['assignment_id']);

    $pageTitle = PAGE_TITLE;
    echo $twig->render('view/submissionGrading.twig.html', [
    'page_title' => "Grade Submission - {$pageTitle}",
    'info' => (isset($info)) ? $info : NULL,
    'student' => $student,
    'assignment' => $assignment,
    'course' => $course,
    'section' => $section,
    'predefComments' => $predefComments,
    'submission' => $submission,
    'ungraded' => $ungraded,
    'user' => $user,
    'breadcrumbs' => [
        [ 'text' => $course['title'], 'link' => 'viewCourse.php?course_id={{ course.id }}' ],
        [ 'text' => 'Section {{ section.number }}', 'link' => 'viewSection.php?section_id={{ section.id }}' ],
        [ 'text' => 'Grades: {{ assignment.title }}', 'link' => 'viewGrades.php?assignment_id={{ assignment.assignment_id }}&section_id={{ section.id }}' ],
        [ 'text' => '{{ student.firstName }} {{ student.lastName }}', 'link' => '#' ]
    ]
    ]);
} else{
    //retrieve the students who have not been graded (This case would be unsubmitted)
    $ungraded = getUnsubmittedStudent($dbh, $_GET['section_id'], $_GET['assignment_id']);

    $pageTitle = PAGE_TITLE;
    echo $twig->render('view/submissionGrading.twig.html', [
    'page_title' => "Grade Submission - {$pageTitle}",
    'info' => (isset($info)) ? $info : NULL,
    'student' => $student,
    'assignment' => $assignment,
    'course' => $course,
    'section' => $section,
    'predefComments' => $predefComments,
    'ungraded' => $ungraded,
    'user' => $user,
    'breadcrumbs' => [
        [ 'text' => $course['title'], 'link' => 'viewCourse.php?course_id={{ course.id }}' ],
        [ 'text' => 'Section {{ section.number }}', 'link' => 'viewSection.php?section_id={{ section.id }}' ],
        [ 'text' => 'Grades: {{ assignment.title }}', 'link' => 'viewGrades.php?assignment_id={{ assignment.assignment_id }}&section_id={{ section.id }}' ],
        [ 'text' => '{{ student.firstName }} {{ student.lastName }}', 'link' => '#' ]
    ]
    ]);
}
?>