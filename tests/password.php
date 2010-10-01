<?php
	
	require_once '../includes/password.php';
	
	$password = new Password('F!@aC9');
	$password->checkLength();
	$password->checkCharacters();
	$password->checkWords();
	
	var_dump($password->score(), $password->log());
	
?>