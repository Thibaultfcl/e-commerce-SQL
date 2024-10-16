<?php
require_once 'vendor/autoload.php'; //faker.php

// database connection settings
$host = 'localhost';
$db = 'e-commerce';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset"; //Data Source Name
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

// PDO connection
try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

// faker instance
$faker = Faker\Factory::create();

// loop to generate the data
for ($i = 0; $i < 100; $i++) {
    // user data
    $first_name = $faker->firstName();
    $last_name = $faker->lastName();
    $email = $first_name . '.' . $last_name . '@' . $faker->freeEmailDomain();
    $password = password_hash($faker->password(), PASSWORD_DEFAULT);
    $phone = $faker->e164PhoneNumber();
    $id_address = $i + 1;

    // address data
    $address = $faker->address();
    $address_parts = explode("\n", $address);
    $street = $address_parts[0];
    $city_postal = explode(' ', $address_parts[1]);
    $postal_code = array_pop($city_postal);
    $city = implode(' ', $city_postal);
    $country = $faker->country();

    // sql query
    $stmt = $pdo->prepare('INSERT INTO address (street, postal_code, city, country) VALUES (?, ?, ?, ?)');
    $stmt->execute([$street, $postal_code, $city, $country]);

    $stmt = $pdo->prepare('INSERT INTO user (first_name, last_name, email, password, phone, id_address) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute([$first_name, $last_name, $email, $password, $phone, $id_address]);
}

echo "success";
?>