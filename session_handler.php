<?php
session_start();

function setSessionData($key, $value) {
    $_SESSION[$key] = $value;
}

function getSessionData($key) {
    return isset($_SESSION[$key]) ? $_SESSION[$key] : null;
}

function destroySession() {
    session_destroy();
}
?>
