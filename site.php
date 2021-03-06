<?php

use Hcode\Model\Addres;
use Hcode\Model\Address;
use Hcode\Model\Cart;
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

$app->get('/categories/:idcategory', function($idcategory){

	$page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;

	$category = new Category();

	$category->get((int)$idcategory);

	$pagination = $category->getProductsPage($page);

	$pages = [];

	for($i = 1; $i <= $pagination['pages']; $i++){
		array_push($pages, [
			'link'=>'/categories/'.$category->getidcategory().'?page='.$i,
			'page'=>$i
		]);
	}

	$page = new Page();

	$page->setTpl("category",[
		'category'=>$category->getValues(),
		'products'=>$pagination['data'],
		'pages'=>$pages
	]);

});

$app->get('/products/:desurl', function($desurl){

	$product = new Product();

	$product->getFromURL($desurl);

	$page = new Page();

	$page->setTpl("product-detail",[
		'product'=>$product->getValues(),
		'categories'=>$product->getCategories()
	]);


});

$app->get('/cart', function(){

	$cart = Cart::getFromSession();

	$page = new Page();

	$page->setTpl("cart", [
		'cart'=>$cart->getValues(),
		'products'=>$cart->getProducts()
	]);


});

$app->get('/cart/:idproduct/add', function($idproduct){

	$product = new Product();

	$product->get((int)$idproduct);

	$cart = Cart::getFromSession();

	$qtd = (isset($_GET['qtd'])) ? (int)$_GET['qtd'] : 1;

	for($i = 0; $i < $qtd; $i++){
		
		$cart->addProduct($product);

	}


	header('Location: /cart');
	exit;


});

$app->get('/cart/:idproduct/minus', function($idproduct){

	$product = new Product();

	$product->get((int)$idproduct);

	$cart = Cart::getFromSession();

	$cart->removeProduct($product);
	
	header('Location: /cart');
	exit;

});

$app->get('/cart/:idproduct/remove', function($idproduct){

	$product = new Product();

	$product->get((int)$idproduct);

	$cart = Cart::getFromSession();

	$cart->removeProduct($product, true);
	
	header('Location: /cart');
	exit;

});

$app->get('/login', function(){

	$page = new Page();

	$page->setTpl("Login",[
		'error'=>'Erro Teste',
		'errorRegister'=>User::getErrorRegister(),
		'registerValues'=>(isset($_SESSION['registerValues'])) ? $_SESSION['registerValues'] : ['name'=> '', 'email'=>'', 'phone'=>'']
	]);

});

$app->post('/register', function(){

	$_SESSION['registerValues'] = $_POST;

	if(!isset($_POST['name']) || $_POST['name'] == ''){

		User::setErrorRegister("Preencha o seu nome.");

		header('Location: /login');
		exit;
	}

	$_SESSION['registerValues'] = $_POST;

	if(!isset($_POST['email']) || $_POST['email'] == ''){

		User::setErrorRegister("Preencha seu email.");

		header('Location: /login');
		exit;
	}

	$_SESSION['registerValues'] = $_POST;

	if(!isset($_POST['password']) || $_POST['password'] == ''){

		User::setErrorRegister("Preencha preencha sua senha.");

		header('Location: /login');
		exit;
	}

	if(User::checkLoginExist($_POST['email']) === true ){

		User::setErrorRegister('E-mail j?? cadastrado.');
		header('Location: /login');
		die;

	};



	$user = new User();

	$user->setData([
		'inadmin'=>0,
		'deslogin'=>$_POST['email'],
		'desperson'=>$_POST['name'],
		'desemail'=>$_POST['email'],
		'despassword'=>$_POST['password'],
		'nrphone'=>$_POST['phone']
	]);

	$user->save();

	// User::login($_POST['email'], $_POST['password']);
	
	header('Location: /checkout');
	exit;

});

$app->get('/checkout', function(){

	User::verifyLogin(false);

	$address = new Address();
	
	$cart = Cart::getFromSession();
	
	if(isset($_GET['zipcode'])){
		
		$address->loadFromCEP($_GET['zipcode']);

		$cart->setdeszipcode($_GET['zipcode']);

		$cart->save();

		
	}

	$page = new Page();

	$page->setTpl('checkout', [
		'cart'=>$cart->getValues(),
		'address'=>$address->getValues(),
		'products'=>$cart->getProducts()
	]);

});

$app->post('/checkout', function(){

	User::verifyLogin(false);

	$user = User::getFromSession();

	$address = new Address();

	$_POST['deszipcode'] = $_POST['zipcode'];
	$_POST['idperson'] = 1;

	$address->setData($_POST);

	$address->save();

	header('Location: /order');

});


$app->get('/forgot', function(){

	$page = new Page();

	$page->setTpl("forgot");
});

$app->post('/forgot', function(){

	$user = User::getForgot($_POST["email"], false);

	header("Location: /forgot/sent");
	exit;

});

$app->get('/forgot/sent', function(){

	$page = new Page();

	$page->setTpl("forgot-sent");

});

$app->get('/forgot/reset', function(){

	$user = User::getForgot($_GET["code"]);

	$page = new Page();

	$page->setTpl("forgot-reset");

});


$app-> get('/profile', function(){

	User::verifyLogin(false);

	$user = User::getFromSession();

	$page = new Page();

	$page->setTpl('profile',[
		'user'=>$user->getValues(),
		'profileMsg'=>'',
		'profileError'=>''
	]);

});




