<?php
require_once './userGate.php';
authorization_gate([ USER_GUEST, USER_STUDENT ]);

require_once './api/config/db_connect.php';
require_once './api/model/enrollment.php';
require_once './api/model/section.php';
require_once './api/model/course.php';
require_once './vendor/autoload.php';

//set globalvariables
$courseId = $_GET['course_id'];
$sectionId = $_GET['section_id'];
$user = $_SESSION['user'];

$loader = new \Twig\Loader\FilesystemLoader('./templates');
$twig = new \Twig\Environment($loader, [ ]);
$twig->addExtension(new \Twig\Extension\StringLoaderExtension());
$user = $_SESSION['user'];
$course = getCourseForSection($dbh, $sectionId);
$section = getSection($dbh, $sectionId);

$pageTitle = PAGE_TITLE;
echo $twig->render('view/editSection.twig.html', [
    'page_title' => "Edit Section - {$pageTitle}",
    'info' => (isset($info)) ? $info : NULL,
    'section' => $section,
    'course' => $course,
    'user' => $user,
    'section' => $section,
    'breadcrumbs' => [
        [ 'text' => $course['title'], 'link' => 'viewCourse.php?course_id={{ course.id }}' ],
        [ 'text' => 'Section {{ section.number }}', 'link' => 'viewSection.php?course_id={{ course.id }}&section_id={{ section.id }}' ],
        [ 'text' => 'Edit Section', 'link' => '#' ]
    ]
]);
