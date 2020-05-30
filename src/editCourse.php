<?php
require_once './vendor/autoload.php';
require_once './userGate.php';
require_once './api/config/db_connect.php';
require_once './api/model/enrollment.php';
require_once './api/model/course.php';
require_once './api/model/section.php';
require_once './userGate.php';

authorization_gate([ USER_GUEST, USER_STUDENT ]);

$loader = new \Twig\Loader\FilesystemLoader('./templates');
$twig = new \Twig\Environment($loader, [ ]);
$twig->addExtension(new \Twig\Extension\StringLoaderExtension());
$user = $_SESSION['user'];
$course = getCourse($dbh, $_GET['course_id']);

$pageTitle = PAGE_TITLE;
echo $twig->render('view/editCourse.twig.html', [
    'page_title' => "Edit Course - {$pageTitle}",
    'info' => (isset($info)) ? $info : NULL,
    'course' => $course,
    'user' => $user,
    'breadcrumbs' => [
        [ 'text' => $course['title'], 'link' => 'viewCourse.php?course_id={{ course.id }}' ],
        [ 'text' => 'Edit Course', 'link' => '#' ]
    ]
]);
?>
