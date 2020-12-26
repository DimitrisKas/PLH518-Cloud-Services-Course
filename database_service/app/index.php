<?php
include_once ("Models/GenericModels.php");
include_once ("Models/iRestObject.php");
include_once ("Models/Mongo/Users.php");
include_once ("Models/Mongo/db_mongo.php");
include_once ("Utils/Logs.php");

use Psr\Log\LoggerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Models\Mongo\UserM as User;
use RestAPI\Result;

require __DIR__ . '/../vendor/autoload.php';

$app = AppFactory::create();
$app->addBodyParsingMiddleware();

/* ----- API endpoints ----- */

$app->post('/login', function (Request $request, Response $response, $args) {
    // Get all POST parameters
    $params = (array)$request->getParsedBody();
    $result = User::Login($params['username'], $params['password']);

    if ($result instanceof Result)
    {
        logger("Couldn't login user");
        $response->getBody()->write($result->msg);
        return $response->withStatus(401);
    }
    else
        $response->getBody()->write(json_encode($result));

    return $response;
});

// GET /users
// - Retrieve all users' info
$app->get('/users', function (Request $request, Response $response, $args) {
    $users = User::getAll();

    $response->getBody()->write(json_encode($users));
    return $response;
});


// POST /users
// - Add user to database
$app->post('/users', function (Request $request, Response $response, $args) {

    // Get all POST parameters
    $params = (array)$request->getParsedBody();

    $result = User::addOne(new User($params));
    if ($result->success)
        return $response->withStatus(203);
    else
        $response->getBody()->write($result->msg);
        return $response->withStatus(400);
});

// GET /users/{id}
// - Retrieve SINGLE user's info
$app->get('/users/{id}', function (Request $request, Response $response, $args) {

    $response->getBody()->write("Hello world!");
    return $response;
});

// GET /users/search/{username}
// - Search for user with given username (usernames are unique)
$app->get('/users/search/{username}', function (Request $request, Response $response, $args) {
    logger("Searching user");
    $user = User::searchByUsername($args['username']);

    $response->getBody()->write($user->username . "\n");
    $response->getBody()->write($user->password . "\n");
    $response->getBody()->write($user->role . "\n");
    return $response;
});

// PUT /users/{id}
// - Edit user
$app->put('/users/{id}', function (Request $request, Response $response, $args) {

    logger("\n --- At [PUT] /users/{id} - (Edit User)");
    // Get all parameters
    $params = (array)$request->getParsedBody();
    $res = User::updateOne($args['id'], new User($params));

    if ($res->success == false)
        return $response->withStatus(401);

    return $response->withStatus(204);
});

// DELETE /users/{id}
// - Delete user
$app->delete('/users/{id}', function (Request $request, Response $response, $args) {

    logger("\n --- At [DELETE] /users/{id} - (Delete User)");
    $res = User::deleteOne($args['id']);

    if ($res->success == false)
        return $response->withStatus(401);

    return $response->withStatus(204);
});





// GET /logs
$app->get('/logs', function (Request $request, Response $response, $args) {
    $response->getBody()->write(getLogs());
    return $response;
});




/* ----- Error Handling ----- */

$customErrorHandler = function (
    Request $request,
    Throwable $exception,
    bool $displayErrorDetails,
    bool $logErrors,
    bool $logErrorDetails,
    ?LoggerInterface $logger = null
) use ($app) {

    $payload = ['error' => $exception->getMessage()];

    $response = $app->getResponseFactory()->createResponse();
    $response->getBody()->write(
        json_encode($payload, JSON_UNESCAPED_UNICODE)
    );

    return $response;
};

$errorMiddleware = $app->addErrorMiddleware(true, true, true);
//$errorMiddleware->setErrorHandler(\Slim\Exception\HttpNotFoundException::class, $customErrorHandler,false);



$app->run();

