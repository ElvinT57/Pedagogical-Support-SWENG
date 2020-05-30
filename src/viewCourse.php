<?php
require_once './userGate.php';
require_once './vendor/autoload.php';
require_once './api/config/db_connect.php';
require_once './api/model/section.php';
require_once './api/model/course.php';
require_once './api/model/assignment.php';

authorization_gate([ USER_GUEST, USER_STUDENT ]);

$loader = new \Twig\Loader\FilesystemLoader('./templates');
$twig = new \Twig\Environment($loader, [ ]);
$twig->addExtension(new \Twig\Extension\StringLoaderExtension());

$userType = $_SESSION['user']['userType'];
$user = $_SESSION['user'];

$course = getCourse($dbh, $_GET['course_id']);
$sections = getSectionsForUsersCourse($dbh, $_GET['course_id'], $user['id']);
$course['sections'] = $sections;

$pageTitle = PAGE_TITLE;
echo $twig->render('view/course/faculty.twig.html', [
    'page_title' => "View Course - {$pageTitle}",
    'info' => (isset($info)) ? $info : NULL,
    'user' => $user,
    'course' => $course,
    'breadcrumbs' => [
        [ 'text' => $course['title'], 'link' => '#' ]
    ]
]);