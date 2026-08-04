<?php
// MAPI/api.php - Msocial REST API dispatcher
// Actions: register, login, logout, check, create-post, list-posts, my-posts,
//          get-post, update-post, delete-post, search, like, unlike, rank,
//          update-profile, upload, add-comment, delete-comment,
//          follow, unfollow, user-profile

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

require __DIR__ . '/../db_connect.php';

$action = $_REQUEST['action'] ?? '';
$id     = $_GET['id'] ?? null;
$search = trim($_REQUEST['search'] ?? '');

// Only try to decode a JSON body for requests that are not multipart form
// uploads (register / upload / create-post with a file use FormData).
$input = [];
if (stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false) {
    $decoded = json_decode(file_get_contents('php://input'), true);
    if (is_array($decoded)) {
        $input = $decoded;
    }
}

function require_login() {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'You must be logged in']);
        exit;
    }
}

// Attaches the 3 most recent comments to each post (used by mobile feed cards).
function attach_recent_comments(array $posts, $currentUserId) {
    global $conn;
    foreach ($posts as &$post) {
        $stmt = $conn->prepare("
            SELECT comments.*, users.username, users.avatar
            FROM comments
            JOIN users ON comments.user_id = users.id
            WHERE comments.post_id = ?
            ORDER BY comments.created_at DESC
            LIMIT 3
        ");
        $stmt->execute([$post['id']]);
        $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($comments as &$comment) {
            $comment['is_owner'] = ($currentUserId && $currentUserId == $comment['user_id']);
        }
        // Reverse so the oldest of the 3 appears first
        $post['comments'] = array_reverse($comments);
    }
    return $posts;
}

// Renames the uploaded file to a unique name, saves it into ALLupload/,
// and returns ['filename' => ..., 'media_type' => 'image'|'video'].
// Returns null when no file was attached or its extension isn't supported.
function save_uploaded_media($field) {
    if (empty($_FILES[$field]['name']) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $ext = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
    $images = ['jpg', 'jpeg', 'png'];
    $videos = ['mp4'];

    if (!in_array($ext, $images) && !in_array($ext, $videos)) {
        return null;
    }

    $newName = uniqid() . '.' . $ext;
    $target = __DIR__ . '/../ALLupload/' . $newName;
    move_uploaded_file($_FILES[$field]['tmp_name'], $target);

    return [
        'filename' => $newName,
        'media_type' => in_array($ext, $images) ? 'image' : 'video'
    ];
}

try {
    switch ($action) {

        // =============================================
        // AUTH ACTIONS
        // =============================================
        case 'register':
            $username = trim($_POST['username'] ?? ($input['username'] ?? ''));
            $password = $_POST['password'] ?? ($input['password'] ?? '');
            $bio      = trim($_POST['bio'] ?? ($input['bio'] ?? ''));

            if (!$username || !$password) {
                echo json_encode(['status' => 'error', 'message' => 'All fields are required']);
                break;
            }
            if (strlen($username) < 3) {
                echo json_encode(['status' => 'error', 'message' => 'Username must be at least 3 characters']);
                break;
            }
            if (strlen($password) < 8) {
                echo json_encode(['status' => 'error', 'message' => 'Password must be at least 8 characters']);
                break;
            }

            $check = $conn->prepare("SELECT id FROM users WHERE username = ?");
            $check->execute([$username]);
            if ($check->fetch()) {
                echo json_encode(['status' => 'error', 'message' => 'Username already taken']);
                break;
            }

            // Optional avatar upload. If none provided, the front-end falls
            // back to the default logo (assets/HMS_logo.png).
            $avatarFile = null;
            $uploaded = save_uploaded_media('avatar');
            if ($uploaded && $uploaded['media_type'] === 'image') {
                $avatarFile = $uploaded['filename'];
            }

            $stmt = $conn->prepare("INSERT INTO users (username, password, bio, avatar) VALUES (?, ?, ?, ?)");
            $stmt->execute([$username, hash('sha256', $password), $bio ?: null, $avatarFile]);

            echo json_encode(['status' => 'success', 'message' => 'Registered successfully']);
            break;

        case 'login':
            $username = trim($input['username'] ?? '');
            $password = $input['password'] ?? '';

            $stmt = $conn->prepare("SELECT id, username, bio, avatar, password FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && $user['password'] === hash('sha256', $password)) {
                $_SESSION['user_id']  = $user['id'];
                $_SESSION['username'] = $user['username'];

                echo json_encode(['status' => 'success', 'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'bio' => $user['bio'],
                    'avatar' => $user['avatar']
                ]]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Invalid username or password']);
            }
            break;

        case 'logout':
            $_SESSION = [];
            session_destroy();
            echo json_encode(['status' => 'success', 'message' => 'Logged out']);
            break;

        case 'check':
            echo json_encode([
                'status'    => 'success',
                'logged_in' => !empty($_SESSION['user_id']),
                'user_id'   => $_SESSION['user_id'] ?? null,
                'username'  => $_SESSION['username'] ?? null
            ]);
            break;

        // =============================================
        // POSTS (CRUD)
        // =============================================
        case 'create-post':
            require_login();
            $content = trim($_POST['content'] ?? ($input['content'] ?? ''));

            if ($content === '') {
                echo json_encode(['status' => 'error', 'message' => 'Post content is required']);
                break;
            }

            $mediaFile = null;
            $mediaType = 'none';
            $uploaded = save_uploaded_media('media');
            if ($uploaded) {
                $mediaFile = $uploaded['filename'];
                $mediaType = $uploaded['media_type'];
            }

            $stmt = $conn->prepare("INSERT INTO posts (user_id, content, image, media_type) VALUES (?, ?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $content, $mediaFile, $mediaType]);
            $newId = $conn->lastInsertId();

            $stmt = $conn->prepare("SELECT posts.*, users.username, users.avatar FROM posts JOIN users ON posts.user_id = users.id WHERE posts.id = ?");
            $stmt->execute([$newId]);
            echo json_encode(['status' => 'success', 'data' => $stmt->fetch(PDO::FETCH_ASSOC)]);
            break;

        case 'list-posts':
            $currentUserId = $_SESSION['user_id'] ?? 0;
            $stmt = $conn->prepare("
                SELECT posts.*, users.username, users.avatar,
                    (SELECT COUNT(*) FROM likes WHERE likes.post_id = posts.id) AS like_count,
                    EXISTS(SELECT 1 FROM likes WHERE likes.post_id = posts.id AND likes.user_id = ?) AS is_liked
                FROM posts
                JOIN users ON posts.user_id = users.id
                ORDER BY posts.created_at DESC
            ");
            $stmt->execute([$currentUserId]);
            $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['status' => 'success', 'data' => attach_recent_comments($posts, $currentUserId)]);
            break;

        case 'my-posts':
            require_login();
            $currentUserId = $_SESSION['user_id'];
            $stmt = $conn->prepare("
                SELECT posts.*, users.username, users.avatar,
                    (SELECT COUNT(*) FROM likes WHERE likes.post_id = posts.id) AS like_count,
                    EXISTS(SELECT 1 FROM likes WHERE likes.post_id = posts.id AND likes.user_id = ?) AS is_liked
                FROM posts
                JOIN users ON posts.user_id = users.id
                WHERE posts.user_id = ?
                ORDER BY posts.created_at DESC
            ");
            $stmt->execute([$currentUserId, $currentUserId]);
            $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['status' => 'success', 'data' => attach_recent_comments($posts, $currentUserId)]);
            break;

        case 'get-post':
            if (!$id) throw new Exception('Post id required');
            $currentUserId = $_SESSION['user_id'] ?? 0;

            $stmt = $conn->prepare("
                SELECT posts.*, users.username, users.avatar,
                    (SELECT COUNT(*) FROM likes WHERE likes.post_id = posts.id) AS like_count,
                    EXISTS(SELECT 1 FROM likes WHERE likes.post_id = posts.id AND likes.user_id = ?) AS is_liked
                FROM posts
                JOIN users ON posts.user_id = users.id
                WHERE posts.id = ?
            ");
            $stmt->execute([$currentUserId, $id]);
            $post = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$post) {
                echo json_encode(['status' => 'error', 'message' => 'Post not found']);
                break;
            }
            $post['is_owner'] = ($currentUserId && $currentUserId == $post['user_id']);

            $stmt = $conn->prepare("
                SELECT comments.*, users.username, users.avatar
                FROM comments
                JOIN users ON comments.user_id = users.id
                WHERE comments.post_id = ?
                ORDER BY comments.created_at ASC
            ");
            $stmt->execute([$id]);
            $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($comments as &$comment) {
                $comment['is_owner'] = ($currentUserId && $currentUserId == $comment['user_id']);
            }

            echo json_encode(['status' => 'success', 'data' => ['post' => $post, 'comments' => $comments]]);
            break;

        case 'update-post':
            require_login();
            if (!$id) throw new Exception('Post id required');

            $stmt = $conn->prepare("SELECT * FROM posts WHERE id = ?");
            $stmt->execute([$id]);
            $post = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$post || $post['user_id'] != $_SESSION['user_id']) {
                http_response_code(403);
                echo json_encode(['status' => 'error', 'message' => 'You can only edit your own posts']);
                break;
            }

            $content = trim($input['content'] ?? $post['content']);
            $stmt = $conn->prepare("UPDATE posts SET content = ? WHERE id = ?");
            $stmt->execute([$content, $id]);
            echo json_encode(['status' => 'success', 'message' => 'Post updated']);
            break;

        case 'delete-post':
            require_login();
            if (!$id) throw new Exception('Post id required');

            $stmt = $conn->prepare("SELECT * FROM posts WHERE id = ?");
            $stmt->execute([$id]);
            $post = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$post || $post['user_id'] != $_SESSION['user_id']) {
                http_response_code(403);
                echo json_encode(['status' => 'error', 'message' => 'You can only delete your own posts']);
                break;
            }

            $conn->prepare("DELETE FROM posts WHERE id = ?")->execute([$id]);
            echo json_encode(['status' => 'success', 'message' => 'Post deleted']);
            break;

        // =============================================
        // COMMENTS
        // =============================================
        case 'add-comment':
            require_login();
            $postId  = $input['post_id'] ?? null;
            $content = trim($input['content'] ?? '');

            if (!$postId || $content === '') {
                echo json_encode(['status' => 'error', 'message' => 'Comment content is required']);
                break;
            }

            $stmt = $conn->prepare("INSERT INTO comments (post_id, user_id, content) VALUES (?, ?, ?)");
            $stmt->execute([$postId, $_SESSION['user_id'], $content]);
            $newId = $conn->lastInsertId();

            $stmt = $conn->prepare("
                SELECT comments.*, users.username, users.avatar
                FROM comments JOIN users ON comments.user_id = users.id
                WHERE comments.id = ?
            ");
            $stmt->execute([$newId]);
            $comment = $stmt->fetch(PDO::FETCH_ASSOC);
            $comment['is_owner'] = true;

            echo json_encode(['status' => 'success', 'data' => $comment]);
            break;

        case 'delete-comment':
            require_login();
            if (!$id) throw new Exception('Comment id required');

            $stmt = $conn->prepare("SELECT * FROM comments WHERE id = ?");
            $stmt->execute([$id]);
            $comment = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$comment || $comment['user_id'] != $_SESSION['user_id']) {
                http_response_code(403);
                echo json_encode(['status' => 'error', 'message' => 'You can only delete your own comments']);
                break;
            }

            $conn->prepare("DELETE FROM comments WHERE id = ?")->execute([$id]);
            echo json_encode(['status' => 'success', 'message' => 'Comment deleted']);
            break;

        // =============================================
        // SEARCH
        // =============================================
        case 'search':
            if ($search === '') {
                echo json_encode(['status' => 'success', 'data' => ['posts' => [], 'users' => []]]);
                break;
            }
            $term = "%$search%";
            $currentUserId = $_SESSION['user_id'] ?? 0;

            $stmt = $conn->prepare("
                SELECT posts.*, users.username, users.avatar,
                    (SELECT COUNT(*) FROM likes WHERE likes.post_id = posts.id) AS like_count,
                    EXISTS(SELECT 1 FROM likes WHERE likes.post_id = posts.id AND likes.user_id = ?) AS is_liked
                FROM posts
                JOIN users ON posts.user_id = users.id
                WHERE posts.content LIKE ?
                ORDER BY posts.created_at DESC
            ");
            $stmt->execute([$currentUserId, $term]);
            $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = $conn->prepare("SELECT id, username, bio, avatar FROM users WHERE username LIKE ?");
            $stmt->execute([$term]);
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['status' => 'success', 'data' => ['posts' => attach_recent_comments($posts, $currentUserId), 'users' => $users]]);
            break;

        // =============================================
        // LIKES
        // =============================================
        case 'like':
            require_login();
            $postId = $id ?? ($input['post_id'] ?? null);
            if (!$postId) throw new Exception('Post id required');

            $stmt = $conn->prepare("INSERT IGNORE INTO likes (post_id, user_id) VALUES (?, ?)");
            $stmt->execute([$postId, $_SESSION['user_id']]);
            echo json_encode(['status' => 'success', 'message' => 'Liked']);
            break;

        case 'unlike':
            require_login();
            $postId = $id ?? ($input['post_id'] ?? null);
            if (!$postId) throw new Exception('Post id required');

            $stmt = $conn->prepare("DELETE FROM likes WHERE post_id = ? AND user_id = ?");
            $stmt->execute([$postId, $_SESSION['user_id']]);
            echo json_encode(['status' => 'success', 'message' => 'Unliked']);
            break;

        // =============================================
        // RANK (top profiles by total likes received)
        // =============================================
        case 'rank':
            $stmt = $conn->query("
                SELECT users.id, users.username, users.bio, users.avatar,
                    COUNT(likes.id) AS total_likes
                FROM users
                LEFT JOIN posts ON posts.user_id = users.id
                LEFT JOIN likes ON likes.post_id = posts.id
                GROUP BY users.id
                ORDER BY total_likes DESC
            ");
            echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            break;

        // =============================================
        // FOLLOWERS
        // =============================================
        case 'follow':
            require_login();
            $targetId = $id ?? ($input['user_id'] ?? null);
            if (!$targetId) throw new Exception('Target user id required');
            if ($targetId == $_SESSION['user_id']) {
                echo json_encode(['status' => 'error', 'message' => 'You cannot follow yourself']);
                break;
            }

            $stmt = $conn->prepare("INSERT IGNORE INTO followers (follower_id, followed_id) VALUES (?, ?)");
            $stmt->execute([$_SESSION['user_id'], $targetId]);
            echo json_encode(['status' => 'success', 'message' => 'Followed']);
            break;

        case 'unfollow':
            require_login();
            $targetId = $id ?? ($input['user_id'] ?? null);
            if (!$targetId) throw new Exception('Target user id required');

            $stmt = $conn->prepare("DELETE FROM followers WHERE follower_id = ? AND followed_id = ?");
            $stmt->execute([$_SESSION['user_id'], $targetId]);
            echo json_encode(['status' => 'success', 'message' => 'Unfollowed']);
            break;

        // =============================================
        // PROFILE
        // =============================================
        case 'update-profile':
            require_login();
            $bio = trim($_POST['bio'] ?? ($input['bio'] ?? ''));

            $uploaded = save_uploaded_media('avatar');

            if ($uploaded && $uploaded['media_type'] === 'image') {
                $stmt = $conn->prepare("UPDATE users SET bio = ?, avatar = ? WHERE id = ?");
                $stmt->execute([$bio, $uploaded['filename'], $_SESSION['user_id']]);
            } else {
                $stmt = $conn->prepare("UPDATE users SET bio = ? WHERE id = ?");
                $stmt->execute([$bio, $_SESSION['user_id']]);
            }

            echo json_encode(['status' => 'success', 'message' => 'Profile updated']);
            break;

        case 'user-profile':
            $targetId = $id ?: ($_SESSION['user_id'] ?? null);
            if (!$targetId) throw new Exception('User id required');

            $stmt = $conn->prepare("SELECT id, username, bio, avatar, created_at FROM users WHERE id = ?");
            $stmt->execute([$targetId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                echo json_encode(['status' => 'error', 'message' => 'User not found']);
                break;
            }

            $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM followers WHERE followed_id = ?");
            $stmt->execute([$targetId]);
            $followersCount = (int) $stmt->fetch(PDO::FETCH_ASSOC)['c'];

            $stmt = $conn->prepare("
                SELECT COUNT(likes.id) AS c
                FROM likes
                JOIN posts ON posts.id = likes.post_id
                WHERE posts.user_id = ?
            ");
            $stmt->execute([$targetId]);
            $totalLikes = (int) $stmt->fetch(PDO::FETCH_ASSOC)['c'];

            $currentUserId = $_SESSION['user_id'] ?? 0;
            $isFollowing = false;
            if ($currentUserId) {
                $stmt = $conn->prepare("SELECT 1 FROM followers WHERE follower_id = ? AND followed_id = ?");
                $stmt->execute([$currentUserId, $targetId]);
                $isFollowing = (bool) $stmt->fetch();
            }

            $stmt = $conn->prepare("
                SELECT posts.*, users.username, users.avatar,
                    (SELECT COUNT(*) FROM likes WHERE likes.post_id = posts.id) AS like_count,
                    EXISTS(SELECT 1 FROM likes WHERE likes.post_id = posts.id AND likes.user_id = ?) AS is_liked
                FROM posts
                JOIN users ON posts.user_id = users.id
                WHERE posts.user_id = ?
                ORDER BY posts.created_at DESC
            ");
            $stmt->execute([$currentUserId, $targetId]);
            $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['status' => 'success', 'data' => [
                'user' => $user,
                'followers_count' => $followersCount,
                'total_likes' => $totalLikes,
                'is_following' => $isFollowing,
                'is_self' => ($currentUserId && $currentUserId == $targetId),
                'posts' => attach_recent_comments($posts, $currentUserId)
            ]]);
            break;

        // =============================================
        // FILE UPLOAD (post image/video / avatar) - standalone helper page
        // =============================================
        case 'upload':
            require_login();
            $uploaded = save_uploaded_media('file');
            if (!$uploaded) {
                echo json_encode(['status' => 'error', 'message' => 'File upload error or unsupported file type']);
                break;
            }
            echo json_encode(['status' => 'success', 'filename' => $uploaded['filename'], 'media_type' => $uploaded['media_type']]);
            break;

        default:
            echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
