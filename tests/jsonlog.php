<?php
	
	require_once '../includes/jsonlog.php';
	
	$log = new JsonLog();
	
	// Define log items:
	$log->create('step-one');
	$log->create('step-two');
	$log->create('step-three');
	
	try {
		$log->begin('step-one');
		
		// Do stuff ...
		
		$log->end('step-one');
		
	/*------------------------------------------------------------*/
		
		$log->begin('step-two');
		
		if (rand(1, 3) == 1) throw new Exception('Randomly break.');
		
		$log->end('step-two');
		
	/*------------------------------------------------------------*/
		
		$log->begin('step-three');
		
		// Do stuff ...
		
		$log->end('step-three');
	}
	
	catch (Exception $e) {
		$log->message($e->getMessage());
	}
	
	// Send json data:
	header('Content-Type: text/plain');
	
	echo $log;
	
?>