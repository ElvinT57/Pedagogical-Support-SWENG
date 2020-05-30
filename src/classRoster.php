<?php
require_once './userGate.php';
require_once './vendor/autoload.php';
require_once './api/config/db_connect.php';
require_once './api/model/section.php';
require_once './api/model/course.php';
require_once './api/model/util.php';

authorization_gate([ USER_GUEST, USER_STUDENT ]);

$loader = new \Twig\Loader\FilesystemLoader('./templates');
$twig = new \Twig\Environment($loader, [ ]);
$twig->addExtension(new \Twig\Extension\StringLoaderExtension());

$user = $_SESSION['user'];
$section = getSection($dbh, $_GET['section_id']);
$course = getCourseForSection($dbh, $_GET['section_id']);

// retrieve the users that belong to this section
$students = getStudentsFromSection($dbh, $_GET['section_id']);
 
$students = sortTable($students, 'lastName');
 
echo $twig->render('view/classRoster.twig.html', [
    'page_title' => PAGE_TITLE,
    'info' => (isset($info)) ? $info : NULL,
    'students' => $students,
    'course' => $course,
    'section' => $section,
    'user' => $user,
    'breadcrumbs' => [
        [ 'text' => $course['title'], 'link' => 'viewCourse.php?course_id={{ course.id }}' ],
        [ 'text' => 'Section {{ section.number }}', 'link' => 'viewSection.php?section_id={{ section.id }}' ],
        [ 'text' => 'Roster', 'link' => '#' ]
    ]
]);
?>