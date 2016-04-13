#!/usr/bin/php
<?php

	ini_set('display_errors','On');
	error_reporting(E_ALL);

	set_include_path(realpath(__DIR__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR);

	require 'Color.class.php';

	use \stange\util\conversion\Color	as	ColorConvert;

	$hasArg	=	isset($_SERVER['argv'][1]);
	$color	=	$hasArg	? $_SERVER['argv'][1]	:	'#c390d4';

	echo "\n"; 

	if(!$hasArg){

		echo "-------------------------------------------------------------------------------------------------------------\n";
		echo "NOTE: You can specify any color in any *supported* notation through the command line as the first argument :) !\n";
		echo "---------------------------------------------------------------------------------------------------------------\n\n";

	}

	$colorConvert	=	new ColorConvert($color);

	echo sprintf('Detected format is: %s%s',$colorConvert->getDetectedFormat(),"\n\n");

	echo sprintf('Convert %s to HEX: %s%s',$color,$colorConvert->toHex(),"\n");
	echo sprintf('Convert %s to RGB: %s%s',$color,$colorConvert->toRGB(),"\n");
	echo sprintf('Convert %s to ANSI: %s%s',$color,$colorConvert->toANSI(),"\n");
	echo sprintf('Convert %s to ANSI: %s%s',$color,$colorConvert->toString(),"\n");


	echo "\n";
