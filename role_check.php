<?php // role_check.php
function checkUserRole($requiredRoles) {
    if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $requiredRoles)) {
        header("Location: unauthorized.php"); // Redirect to an unauthorized access page
        exit();
    }
}
?>