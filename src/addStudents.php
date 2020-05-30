<?php 
    require_once './userGate.php';
    authorization_gate([ USER_GUEST, USER_STUDENT ]);

    require_once './api/config/db_connect.php';
    require_once './api/model/enrollment.php';
    require_once './vendor/autoload.php';
    require_once './api/model/section.php';
    require_once './api/model/course.php';

    $loader = new \Twig\Loader\FilesystemLoader('./templates');
    $twig = new \Twig\Environment($loader, [ ]);
    $twig->addExtension(new \Twig\Extension\StringLoaderExtension());

    $user = $_SESSION['user'];
    $section = getSection($dbh, $_GET['section_id']);
    $course = getCourseForSection($dbh, $_GET['section_id']);

    $pageTitle = PAGE_TITLE;
    echo $twig->render('view/addStudents.twig.html', [
        'page_title' => "Add Students - {$pageTitle}",
        'info' => (isset($info)) ? $info : NULL,
        'course' => $course,
        'section' => $section,
        'user' => $user,
        'breadcrumbs' => [
            [ 'text' => $course['title'], 'link' => 'viewCourse.php?course_id={{ course.id }}' ],
            [ 'text' => 'Section {{ section.number }}', 'link' => 'viewSection.php?section_id={{ section.id }}' ],
            [ 'text' => 'Roster', 'link' => 'classRoster.php?section_id={{ section.id }}' ],
            [ 'text' => 'Add Students', 'link' => '#' ]
        ]
    ]);

?>
