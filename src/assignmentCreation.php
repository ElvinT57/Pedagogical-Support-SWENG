<?php
require_once './vendor/autoload.php';
require_once './userGate.php';
require_once './api/config/db_connect.php';
require_once './api/model/course.php';
require_once './api/model/section.php';

authorization_gate([ USER_GUEST, USER_STUDENT ]);

$loader = new \Twig\Loader\FilesystemLoader('./templates');
$twig = new \Twig\Environment($loader, [ ]);
$twig->addExtension(new \Twig\Extension\StringLoaderExtension());

$courseId = $_GET['course_id'];
$course = getCourse($dbh, $courseId);
$section = getSectionsFromCourse($dbh, $courseId);

echo $twig->render('view/assignmentCreation.twig.html', [
    'page_title' => 'Pedagogical Support',
    'info' => (isset($info)) ? $info : NULL,
    'course' => $course,
    'sections' => $section,
    'breadcrumbs' => [
        [ 'text' => $course['title'], 'link' => 'viewCourse.php?course_id={{ course.id }}' ],
        [ 'text' => "Create Assignment", 'link' => '#' ]
    ]
]);
?>