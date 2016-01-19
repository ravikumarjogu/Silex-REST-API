<?php

require_once __DIR__.'/vendor/autoload.php'; // Add the autoloading mechanism of Composer
$app = new Silex\Application(); // Create the Silex application, in which all configuration is going to go
$app['debug'] = true;

$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
    'db.options' => array(
        'driver'   => 'pdo_sqlite',
        'path'     => __DIR__.'/db/app.db',
    ),
));
$app['articles'] = array(
    array(
        'title'    => 'Lorem ipsum dolor sit amet',
        'contents' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aenean mollis vestibulum ultricies. Sed sit amet sagittis nisl. Nulla leo metus, efficitur non risus ut, tempus convallis sem. Mauris pharetra sagittis ligula pharetra accumsan. Cras auctor porta enim, a eleifend enim volutpat vel. Nam volutpat maximus luctus. Phasellus interdum elementum nulla, nec mollis justo imperdiet ac. Duis arcu dolor, ultrices eu libero a, luctus sollicitudin diam. Phasellus finibus dictum turpis, nec tincidunt lacus ullamcorper et. Praesent laoreet odio lacus, nec lobortis est ultrices in. Etiam facilisis elementum lorem ut blandit. Nunc faucibus rutrum nulla quis convallis. Fusce molestie odio eu mauris molestie, a tempus lorem volutpat. Sed eu lacus eu velit tincidunt sodales nec et felis. Nullam velit ex, pharetra non lorem in, fringilla tristique dolor. Mauris vel erat nibh.',
        'author'   => 'Sammy',
        'date'     => '2014-12-18',
    ),
    array(
        'title'    => 'Duis ornare',
        'contents' => 'Duis ornare, odio sit amet euismod vulputate, purus dui fringilla neque, quis eleifend purus felis ut odio. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Pellentesque bibendum pretium ante, eu aliquet dolor feugiat et. Pellentesque laoreet est lectus, vitae vulputate libero sollicitudin consequat. Vivamus finibus interdum egestas. Nam sagittis vulputate lacus, non condimentum sapien lobortis a. Sed ligula ante, ultrices ut ullamcorper nec, facilisis ac mi. Nam in vehicula justo. In hac habitasse platea dictumst. Duis accumsan pellentesque turpis, nec eleifend ex suscipit commodo.',
        'author'   => 'Sammy',
        'date'     => '2014-11-08',
    ),
    array(
        'title'    => 'A ornare',
        'contents' => 'a ornare, odio sit amet euismod vulputate, purus dui fringilla neque, quis eleifend purus felis ut odio. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Pellentesque bibendum pretium ante, eu aliquet dolor feugiat et. Pellentesque laoreet est lectus, vitae vulputate libero sollicitudin consequat. Vivamus finibus interdum egestas. Nam sagittis vulputate lacus, non condimentum sapien lobortis a. Sed ligula ante, ultrices ut ullamcorper nec, facilisis ac mi. Nam in vehicula justo. In hac habitasse platea dictumst. Duis accumsan pellentesque turpis, nec eleifend ex suscipit commodo.',
        'author'   => 'aLICE',
        'date'     => '2014-11-08',
    ),
);

$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/templates', // The path to the templates, which is in our case points to /var/www/templates
));
$app->get('/articles', function (Silex\Application $app)  { // Match the root route (/) and supply the application as argument
    $articles=$app['articles'];
    return $app['twig']->render( // Render the page index.html.twig
            'index.html.twig',
            array(
                'articles' => $articles, // Supply arguments to be used in the template
            )
        );    
})->bind('index');
//get
$app->get('/', function (Silex\Application $app)  { // Match the root route (/) and supply the application as argument
    $client = new GuzzleHttp\Client();
    $res = $client->request('GET', 'https://basic-rails-api.herokuapp.com/api/v1/users');
	$users = json_decode($res->getBody());
    // print_r($users);
    return $app['twig']->render( // Render the page index.html.twig
        	'users.html.twig',
       	 	array(
        	    'users' => $users, // Supply arguments to be used in the template
                'max' => count($users)
        	)
    	);
})->bind('users');
//users -index
//new user form
//post
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

$app->post('/users', function (Request $request) {
    $client = new GuzzleHttp\Client();
    $res = $client->request('POST', "https://basic-rails-api.herokuapp.com/api/v1/users",["query" => ["name"=> $request->get('name'),"email"=>$request->get('email'),"password"=>$request->get('password'),"password_confirmation"=>$request->get('password_confirmation')]]);
    $resp=json_decode($res->getBody());
    // print_r($resp);
    if(!$resp->id){
       // print_r($resp);
       foreach($resp as $key=>$value){
        echo $key." : {";
        foreach ($value as $num => $err) {
            echo $err.",";
        }
        echo "}"."<br>";
       }
       echo "<a href='/users/new'>Retry</a><br>";
       return "Form contains errors.."; 
    }
    return new Response("User created! <a href='/users/".$resp->id."'>check out</a>", 201);
});
//get
$app->get('users/{id}', function (Silex\Application $app, $id)  { // Add a parameter for an ID in the route, and it will be supplied as argument in the function
    $client = new GuzzleHttp\Client();

    $res = $client->request('GET', "https://basic-rails-api.herokuapp.com/api/v1/users/".$id);
    $user = json_decode($res->getBody());
    if (!$user->status) {
        $app->abort(404, 'The user could not be found');
    }
    
    return $app['twig']->render(
        'user.html.twig',
        array(
            'user' => $user->data,
            'id' => $id
        )
    );
})
    ->assert('id', '\d+') // specify that the ID should be an integer
    ->bind('user'); // name the route so it can be referred to later in the section 'Generating routes'

//edit form
$app->get('/users/{id}/edit',function (Silex\Application $app,$id) {
    $client = new GuzzleHttp\Client();

    $res = $client->request('GET', "https://basic-rails-api.herokuapp.com/api/v1/users/".$id);
    $user = json_decode($res->getBody());
    if (!$user->status) {
        $app->abort(404, 'The user could not be found');
    }
    return $app['twig']->render(
        'edit-user.html.twig',
        array(
            'user' => $user->data,
            'id' => $id
        )
    );
   // return "Lets edit".$id;
})->bind("edit-user");
//put
$app->put('/users/{id}', function (Request $request, Silex\Application $app, $id) {
    $client = new GuzzleHttp\Client();
    $res = $client->request('PUT', "https://basic-rails-api.herokuapp.com/api/v1/users/".$id,["query" => ["name"=> $request->get('name'),"email"=>$request->get('email'),"password"=>$request->get('password'),"password_confirmation"=>$request->get('password_confirmation')]]);
            
    $resp=json_decode($res->getBody());
    
    // print_r($resp);
    if(!$resp->status){
       // print_r($resp);
       foreach($resp->data as $key=>$value){
        echo $key." : {";
        foreach ($value as $num => $err) {
            echo $err.",";
        }
        echo "}"."<br>";
       }
       echo "<a href='/users/".$id."/edit'>Retry</a><br>";
       return "Form contains errors.."; 
    }
    return new Response("User updated! <a href='/users/".$id."'>check out</a>", 201);
});
//delete
$app->delete('/users/{id}', function (Silex\Application $app,$id) {
    $client = new GuzzleHttp\Client();
    $res = $client->delete("https://basic-rails-api.herokuapp.com/api/v1/users/".$id);
            
    $resp=json_decode($res->getBody());
    if(!$resp->status){
        echo $resp->message;
        return "<br>Error in deleting account";
    }
    return "User deleted <a href='/users'>All users</a>";
    
});


$app->get('/users', function() use ($app) {
    return $app->redirect("/");
});
$app->get('/users/new', function (Silex\Application $app) {
    
    return $app['twig']->render(
        'new-user.html.twig',
        array(
        )
    );
})->bind('new-user');

$app->get('articles/{id}', function (Silex\Application $app, $id)  { // Add a parameter for an ID in the route, and it will be supplied as argument in the function
    if (!array_key_exists($id, $app['articles'])) {
        $app->abort(404, 'The article could not be found');
    }
    $article = $app['articles'][$id];
    return $app['twig']->render(
        'single.html.twig',
        array(
            'article' => $article,
        )
    );
})
    ->assert('id', '\d+') // specify that the ID should be an integer
    ->bind('single'); // name the route so it can be referred to later in the section 'Generating routes'

// Section A
// We will later add the configuration, etc. here

$app->register(new Silex\Provider\UrlGeneratorServiceProvider());
Request::enableHttpMethodParameterOverride();
// This should be the last line
$app->run(); // Start the application, i.e. handle the request
?>