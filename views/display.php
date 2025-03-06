<?php
global $pdo;
require 'config/db.php';
$stmt = $pdo->query("SELECT posts.title, posts.content, users.username FROM posts JOIN users ON posts.user_id = users.id ORDER BY posts.created_at DESC");
$posts = $stmt->fetchAll();
?>

<h2>Latest Posts</h2>
<?php foreach ($posts as $post): ?>
    <h3><?= htmlspecialchars($post['title']) ?></h3>
    <p><?= htmlspecialchars($post['content']) ?></p>
    <small>By: <?= htmlspecialchars($post['username']) ?></small>
<?php endforeach; ?>
