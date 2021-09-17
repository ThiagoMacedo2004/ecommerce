<?php

use Hcode\Model\Category;
use Hcode\Model\Product;
use \Slim\Slim;
use \Hcode\Page;
use \Hcode\PageAdmin;
use \Hcode\Model\User;


$app->get('/', function() {
    
	$products = Product::listAll();

	$page = new Page();
	$page->setTpl("index", [
		'products'=>Product::checkList($products)
	]);
	
	
});