<?php
require_once './userGate.php';
require_once './vendor/autoload.php';
include('./api/config/db_connect.php');
include('./api/model/comment.php');

authorization_gate([USER_GUEST, USER_STUDENT]);

$loader = new \Twig\Loader\FilesystemLoader('./templates');
$twig = new \Twig\Environment($loader, [ ]);
$twig->addExtension(new \Twig\Extension\StringLoaderExtension());

$user = $_SESSION['user'];
//retrieve the comments for the given user
$comments = getCommentDefinitions($dbh, $user['id']);
 
$pageTitle = PAGE_TITLE;
echo $twig->render('view/commentManagement.twig.html', [
    'page_title' => "Settings - {$pageTitle}",
    'info' => (isset($info)) ? $info : NULL,
    'user' => $user,
    'comments' => $comments,
    'breadcrumbs' => [
        [ 'text' => "Settings", 'link' => '#' ]
    ]
]);