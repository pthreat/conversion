<?php

	ini_set('display_errors','On');
	error_reporting(E_ALL);

	$autoLoader	=	sprintf('%s/../vendor/autoload.php',realpath(__DIR__));

	if(!(file_exists($autoLoader)&&is_readable($autoLoader))){

		echo "Please install composer first and run: composer dump-autload\n";
		exit(1);

	}

	require $autoLoader;
