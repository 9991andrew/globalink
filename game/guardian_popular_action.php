<?php
include 'includes/mw_init.php';

// link player with the popular Guardian
$guardian = new Guardian();
$guardian->linkPlayerToGuardian($_GET['id'],$_GET['guardian']);

// If Guardian creation is successful, move on to guardian_edit.php
header('Location: map.php');