<?php

require_once __DIR__.'/vendor/autoload.php';

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Mailserver\Db\Connection;
use Mailserver\Db\Repository;
use Mailserver\Model\Admin;
use Mailserver\Model\User;

$app = new Silex\Application();
$app->register(new Silex\Provider\SessionServiceProvider());
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/views',
));

$app['debug'] = true;
// Initialize Connection settings
Connection::setConfig(array(
    'host' => 'localhost',
    'db' => 'test',
    'user' => 'root',
    'password' => ''
));
$app['db'] = Connection::getInstance();

$app['session']->start();

$app->post('/change_password', function(Request $request) use ($app) {
	if (empty($app['session']->get('admin_id')))
		return $app->redirect('/login');

	$email = $request->get('email');
	$password = $request->get('password1');
	$repeat = $request->get('password2');
	
	if ($password != $repeat) {
		// return informando que senhas nao coincide
		return $app['twig']->render('index.html.twig', array(
			'error' => 'As senhas informadas não coincidem, verifique e tente novamente.'
		));
	}

	$repository = new Repository($app['db'], '\Mailserver\Model\User');

	$conditions = array(
		'email' => $email
	);
    
    $users = $repository->find($conditions, array(), 1);
    if (!isset($users[0])) {
    	// return informando que usuario nao encontrado com o e-mail informado
    	return $app['twig']->render('index.html.twig', array(
			'error' => 'O e-mail informado não foi encontrado no servidor.'
		));
    }

	$user = $users[0];
	$sql = 'UPDATE '.User::$tableName." SET password = ENCRYPT(?, CONCAT('$6$', SUBSTRING(SHA(RAND()), -16))) WHERE id = ?";
	$stmt = $app['db']->prepare($sql);
	$success = $stmt->execute(array(
		$password, $user['id']
	));

	if (!$success) {
		// return error template
		return $app['twig']->render('index.html.twig', array(
			'error' => 'Erro ao tentar alterar a senha. Tente novamente mais tarde.'
		));
	}

	// return success template
	$app['session']->getFlashBag()->add('success', 'Senha alterada com sucesso para o e-mail "'.$email.'".');

	return $app->redirect('/');
});

$app->get('/login', function() use ($app) {
	return $app['twig']->render('login.html.twig');
});

$app->get('/logout', function() use ($app) {
	$app['session']->remove('admin_id');
	$app['session']->clear();
	return $app->redirect('/login');
});

$app->post('/login_check', function(Request $request) use ($app) {

	$email = $request->get('email');

	$repository = new Repository($app['db'], '\Mailserver\Model\Admin');

	$conditions = array(
		'email' => $email
	);

    $admins = $repository->find($conditions, array(), 1);

    if (!isset($admins[0])) {
    	$app['session']->getFlashBag()->add('error', 'Nenhum usuário existente com o e-mail informado.');
    	return $app->redirect('/login');
    }

    $admin = $admins[0];
    $salt = $admin['salt'];
    $password = $request->get('password');
    if (md5($salt.$password) != $admin['pwd']) {
    	$app['session']->getFlashBag()->add('error', 'Acesso negado. Senha incorreta.');
    	return $app->redirect('/login');
    }

    $app['session']->set('admin_id', $admin['id']);
    return $app->redirect('/');
});

$app->get('/', function() use ($app) {

	if (empty($app['session']->get('admin_id')))
		return $app->redirect('/login');

	return $app['twig']->render('index.html.twig');
});

$app->run();