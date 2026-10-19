<?php
require_once __DIR__ . "/config/db.php";
require_once __DIR__ . "/config/auth.php";

requireLogin($conn);
?>
<li>
    <a href="event_management.php">Event Management</a>
</li>