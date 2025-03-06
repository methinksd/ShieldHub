<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $content = htmlspecialchars($_POST['content']);
    echo "<div>$content</div>"; // Prevents XSS attacks
}
?>
<form method="post">
    <textarea name="content"></textarea>
    <button type="submit">Submit</button>
</form>

