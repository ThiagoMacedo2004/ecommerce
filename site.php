<?php

use Hcode\Model\Category;
use \Slim\Slim;
use \Hcode\Page;
use \Hcode\PageAdmin;
use \Hcode\Model\User;


$app->get('/', function() {
    
	$page = new Page();
	$page->setTpl("index");
	
	
});