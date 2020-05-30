<?php
require_once './userGate.php';
authorization_gate([ USER_GUEST ]);

require_once './vendor/autoload.php';
require_once './api/config/db_connect.php';
require_once './api/model/course.php';
require_once './api/model/section.php';

$loader = new \Twig\Loader\FilesystemLoader('./templates');
$twig = new \Twig\Environment($loader, [ ]);
$twig->addExtension(new \Twig\Extension\StringLoaderExtension());

$pageTitle = PAGE_TITLE;
$user = $_SESSION['user'];
$userType = $user['userType'];
if ($userType == USER_STUDENT) {
    $courses = getCoursesForStudent($dbh, $user['id']);

    echo $twig->render('view/landing/student.twig.html', [
        'page_title' => "Landing - {$pageTitle}",
        'info' => (isset($info)) ? $info : NULL,
        'user' => $user,
        'courses' => $courses,
        'breadcrumbs' => []
    ]);
} else {
    $courses = getAllCoursesForUser($dbh, $user['id']);

    echo $twig->render('view/landing/faculty.twig.html', [
        'page_title' => "Landing - {$pageTitle}",
        'info' => (isset($info)) ? $info : NULL,
        'user' => $user,
        'courses' => $courses,
        'breadcrumbs' => []
    ]);
}
