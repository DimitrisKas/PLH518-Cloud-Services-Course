<?php
include_once ("Models/GenericModels.php");
include_once ("Models/iRestObject.php");
include_once ("Models/Mongo/Users.php");
include_once ("Models/Mongo/db_mongo.php");

use Psr\Log\LoggerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Models\Mongo\UserM as User;

require __DIR__ . '/../vendor/autoload.php';

$app = AppFactory::create();
$app->addBodyParsingMiddleware();

/* ----- API endpoints ----- */

// GET /users
// - Retrieve all users' info
$app->get('/users', function (Request $request, Response $response, $args) {
    $response->getBody()->write("Hello world!");
    return $response;
});

// POST /users
// - Add user to database
$app->post('/users', function (Request $request, Response $response, $args) {

    // Get all POST parameters
    $params = (array)$request->getParsedBody();

    $success = User::addOne(new User($params));
    if ($success)
        $response->withStatus(203);
    else
        $response->withStatus(400);

    return $response;
});

// GET /users/{id}
// - Retrieve SINGLE user's info
$app->get('/users/{id}', function (Request $request, Response $response, $args) {

    $response->getBody()->write("Hello world!");
    return $response;
});

// PUT /users/{id}
// - Edit user
$app->put('/users/{id}', function (Request $request, Response $response, $args) {

    $response->getBody()->write("Hello world!");
    return $response;
});

// DELETE /users/{id}
// - Delete user
$app->delete('/users/{id}', function (Request $request, Response $response, $args) {

    $response->getBody()->write("Hello world!");
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

