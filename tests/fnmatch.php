<?php
	
	require_once '../includes/fnmatch.php';
	
	var_dump(fnmatch('gr[a-e]y', 'grey'));
	var_dump(pcre_fnmatch('gr[a-e]y', 'grey'));
	
?>