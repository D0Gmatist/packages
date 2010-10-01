<?php
	
	require_once '../includes/htmlbuilder.php';
	
	$b = new HTMLBuilder();
	$b->p(function($b) {
		
	});
	
	var_dump((string)$b);
	
?>