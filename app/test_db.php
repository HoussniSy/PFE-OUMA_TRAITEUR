<?php

require __DIR__.'/vendor/autoload.php';

use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;

(new Dotenv())->bootEnv(__DIR__.'/.env');

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();

$container = $kernel->getContainer();
$em = $container->get('doctrine')->getManager();
$conn = $em->getConnection();

$docs = $conn->fetchAllAssociative("SELECT id, type, number, date FROM document WHERE type = 'invoice' ORDER BY id DESC LIMIT 5");

print_r($docs);

echo "Executing YEAR on date:\n";
$docsYear = $conn->fetchAllAssociative("SELECT id, type, number, date, YEAR(date) as y FROM document WHERE type = 'invoice'");
print_r($docsYear);
