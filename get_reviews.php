<?php
require_once __DIR__ . '/database.php';

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

switch ($uri) {
    case '/code_reviews':
        getReviews();
        break;
    case '/repos':
        getRepos();
        break;
    case '/branches';
        getBranches();
        break;
    case '/commits';
        getCommits();
        break;
    // Add more cases here for additional endpoints
    default:
        echo json_encode(["error" => "Endpoint not found"]);
        break;
}

function getReviews()
{
    global $database;
    $repository = $_GET['repository'] ?? '';
    $branch = $_GET['branch'] ?? '';
    $commit = $_GET['commit'] ?? '';

    if (empty($repository) || empty($branch) || empty($commit)) {
        echo json_encode(["error" => "Missing repository, branch or commit"]);
        return;
    }

    $stmt = $database->prepare("SELECT file_name, id, timestamp, repository, branch, commit_hash, commit_message, code, user_name, review FROM reviews WHERE repository = :repository AND branch = :branch AND commit_hash = :commit ORDER BY timestamp DESC");
    $stmt->bindValue(':repository', $repository, SQLITE3_TEXT);
    $stmt->bindValue(':branch', $branch, SQLITE3_TEXT);
    $stmt->bindValue(':commit', $commit, SQLITE3_TEXT);
    $result = $stmt->execute();

    $reviews = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $reviews[] = $row;
    }

    echo json_encode(["reviews" => $reviews]);
}

function getRepos()
{
    global $database;

    $result = $database->query("SELECT DISTINCT repository FROM reviews");

    $repos = [];

    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $repos[] = $row['repository'];
    }

    echo json_encode(["repositories" => $repos]);
}

function getBranches()
{
    global $database;

    $repository = $_GET['repository'] ?? '';

    if (empty($repository)) {
        echo json_encode(["error" => "Missing repository"]);
        return;
    }

    $stmt = $database->prepare("SELECT DISTINCT branch FROM reviews WHERE repository = :repository");
    $stmt->bindValue(':repository', $repository, SQLITE3_TEXT);
    $result = $stmt->execute();

    $branches = [];

    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $branches[] = $row['branch'];
    }

    echo json_encode(["branches" => $branches]);
}

function getCommits()
{
    global $database;

    // get branch and repository from query parameters

    $branch = $_GET['branch'] ?? '';
    $repository = $_GET['repository'] ?? '';

    if (empty($branch) || empty($repository)) {
        echo json_encode(["error" => "Missing branch or repository"]);
        return;
    }

    $stmt = $database->prepare("SELECT DISTINCT commit_hash, commit_message FROM reviews WHERE branch = :branch AND repository = :repository");

    $stmt->bindValue(':branch', $branch, SQLITE3_TEXT);

    $stmt->bindValue(':repository', $repository, SQLITE3_TEXT);

    $result = $stmt->execute();

    $commits = [];

    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $commits[] = ['hash' => $row['commit_hash'], 'message' => $row['commit_message']];
    }

    echo json_encode(["commits" => $commits]);
}
