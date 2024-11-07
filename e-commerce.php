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

// initiate the status tables
$stmt = $pdo->prepare('SELECT COUNT(*) FROM command_status');
$stmt->execute();
$command_status_count = $stmt->fetchColumn();
if ($command_status_count === 0) {
    $stmt = $pdo->prepare('INSERT INTO command_status (command_status) VALUES (?)');
    $stmt->execute(['pending']);
    $stmt->execute(['shipped']);
    $stmt->execute(['delivered']);
}

$stmt = $pdo->prepare('SELECT COUNT(*) FROM cart_status');
$stmt->execute();
$cart_status_count = $stmt->fetchColumn();
if ($cart_status_count === 0) {
    $stmt = $pdo->prepare('INSERT INTO cart_status (cart_status) VALUES (?)');
    $stmt->execute(['empty']);
    $stmt->execute(['not_empty']);
    $stmt->execute(['deleted']);
}

$stmt = $pdo->prepare('SELECT COUNT(*) FROM payment_type');
$stmt->execute();
$payment_type_count = $stmt->fetchColumn();
if ($payment_type_count === 0) {
    $stmt = $pdo->prepare('INSERT INTO payment_type (type_name) VALUES (?)');
    $stmt->execute(['credit_card']);
    $stmt->execute(['paypal']);
}

// loop to generate the general data
for ($i = 0; $i < 100; $i++) {
    // user data
    $first_name = $faker->firstName();
    $last_name = $faker->lastName();
    $email = strtolower($first_name) . '.' . strtolower($last_name) . '@' . $faker->freeEmailDomain();
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

    // products data
    $product_name = $faker->word();
    $prodct_description = $faker->sentence();
    $product_price = $faker->randomFloat(2, 0, 1000);
    $product_quantity = $faker->numberBetween(1, 1000);

    // sql query
    $stmt = $pdo->prepare('INSERT INTO address (street, postal_code, city, country) VALUES (?, ?, ?, ?)');
    $stmt->execute([$street, $postal_code, $city, $country]);

    $stmt = $pdo->prepare('INSERT INTO user (first_name, last_name, email, password, phone, id_address) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute([$first_name, $last_name, $email, $password, $phone, $id_address]);

    $stmt = $pdo->prepare('INSERT INTO product (product_name, description, price, stock_quantity) VALUES (?, ?, ?, ?)');
    $stmt->execute([$product_name, $prodct_description, $product_price, $product_quantity]);
}

function insertProductRelation($pdo, $relation_cart_id, $relation_command_id, $product_count, $faker) {
    $id_product = $faker->numberBetween(1, $product_count);
    $quantity = $faker->numberBetween(1, 10);
    $stmt = $pdo->prepare('INSERT INTO product_relation (id_cart, id_command, id_product, quantity) VALUES (?, ?, ?, ?)');
    $stmt->execute([$relation_cart_id, $relation_command_id, $id_product, $quantity]);
}

// loop for the command and cart data
for ($i = 0; $i < 100; $i++){
    // command data
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM user');
    $stmt->execute();
    $user_count = $stmt->fetchColumn();
    $command_user_id = $faker->numberBetween(1, $user_count);

    $command_total_price = 0;

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM address');
    $stmt->execute();
    $address_count = $stmt->fetchColumn();
    $command_address_id = $faker->numberBetween(1, $address_count);
    
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM command_status');
    $stmt->execute();
    $command_status_count = $stmt->fetchColumn();
    $id_command_status = $faker->numberBetween(1, $command_status_count);

    $stmt = $pdo->prepare('INSERT INTO command (id_user, total_price, id_address, id_command_status) VALUES (?, ?, ?, ?)');
    $stmt->execute([$command_user_id, $command_total_price, $command_address_id, $id_command_status]);

    // cart data
    $cart_user_id = $faker->numberBetween(1, $user_count);

    $cart_total_price = 0;

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM cart_status');
    $stmt->execute();
    $cart_status_count = $stmt->fetchColumn();
    $id_cart_status = $faker->numberBetween(1, $command_status_count);

    $stmt = $pdo->prepare('INSERT INTO cart (id_user, total_price, id_cart_status) VALUES (?, ?, ?)');
    $stmt->execute([$cart_user_id, $cart_total_price, $id_cart_status]);

    // product relation data
    $relation_cart_id = (rand(0, 1) === 0) ? $i + 1 : NULL;
    $relation_command_id = ($relation_cart_id === NULL) ? $i + 1 : NULL;

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM product');
    $stmt->execute();
    $product_count = $stmt->fetchColumn();
    
    insertProductRelation($pdo, $relation_cart_id, $relation_command_id, $product_count, $faker);

    for ($j = 0; $j < 5; $j++) {
        if (rand(0, 1) === 0) {
            insertProductRelation($pdo, $relation_cart_id, $relation_command_id, $product_count, $faker);
        } else {
            break;
        }
    }

    //product relation data 2
    $relation_cart_id_2 = ($relation_cart_id === NULL) ? $i + 1 : NULL;
    $relation_command_id_2 = ($relation_command_id === NULL) ? $i + 1 : NULL;
    
    insertProductRelation($pdo, $relation_cart_id_2, $relation_command_id_2, $product_count, $faker);

    for ($j = 0; $j < 5; $j++) {
        if (rand(0, 1) === 0) {
            insertProductRelation($pdo, $relation_cart_id_2, $relation_command_id_2, $product_count, $faker);
        } else {
            break;
        }
    }

    // update total price
    $command_total_price = 0;
    $stmt = $pdo->prepare('SELECT id_product, quantity FROM product_relation WHERE id_command = ?');
    $stmt->execute([$i + 1]);
    $products = $stmt->fetchAll();
    $command_total_price = 0;
    foreach ($products as $product) {
        $id_product = $product['id_product'];
        $quantity = $product['quantity'];
        $stmt = $pdo->prepare('SELECT price FROM product WHERE id_product = ?');
        $stmt->execute([$id_product]);
        $price = $stmt->fetchColumn();
        $command_total_price += $price * $quantity;
    }
    $stmt = $pdo->prepare('UPDATE command SET total_price = ? WHERE id_command = ?');
    $stmt->execute([$command_total_price, $i + 1]);

    $cart_total_price = 0;
    $stmt = $pdo->prepare('SELECT id_product, quantity FROM product_relation WHERE id_cart = ?');
    $stmt->execute([$i + 1]);
    $products = $stmt->fetchAll();
    $cart_total_price = 0;
    foreach ($products as $product) {
        $id_product = $product['id_product'];
        $quantity = $product['quantity'];
        $stmt = $pdo->prepare('SELECT price FROM product WHERE id_product = ?');
        $stmt->execute([$id_product]);
        $price = $stmt->fetchColumn();
        $cart_total_price += $price * $quantity;
    }
    $stmt = $pdo->prepare('UPDATE cart SET total_price = ? WHERE id_cart = ?');
    $stmt->execute([$cart_total_price, $i + 1]);
}

//payment data
$stmt = $pdo->prepare('SELECT COUNT(*) FROM user');
$stmt->execute();
$user_count = $stmt->fetchColumn();
for ($i = 0; $i < $user_count; $i++) {
    $payment_user_id = $i + 1;
    $payment_type = $faker->numberBetween(1, 2);
    $payment_details = hash('sha256', $faker->creditCardNumber() . $faker->creditCardExpirationDateString());
    $stmt = $pdo->prepare('INSERT INTO payment (id_user, id_payment_type, payment_details) VALUES (?, ?, ?)');
    $stmt->execute([$payment_user_id, $payment_type, $payment_details]);
}

//invoice data
$stmt = $pdo->prepare('SELECT COUNT(*) FROM command');
$stmt->execute();
$command_count = $stmt->fetchColumn();
$stmt = $pdo->prepare('SELECT COUNT(*) FROM payment');
$stmt->execute();
$payment_count = $stmt->fetchColumn();

for ($i = 0; $i < $command_count; $i++) {
    $invoice_command_id = $i + 1;
    $stmt = $pdo->prepare('SELECT id_user, total_price FROM command WHERE id_command = ?');
    $stmt->execute([$invoice_command_id]);
    $invoice_command = $stmt->fetchAll();
    foreach ($invoice_command as $command) {
        $invoice_user_id = $command['id_user'];
        $invoice_total_price = $command['total_price'];
    }

    $stmt = $pdo->prepare('SELECT id_payment FROM payment WHERE id_user = ?');
    $stmt->execute([$invoice_user_id]);
    $invoice_payment_id = $stmt->fetchColumn();

    $stmt = $pdo->prepare('INSERT INTO invoice (id_command, id_payment, total_price) VALUES (?, ?, ?)');
    $stmt->execute([$invoice_command_id, $invoice_payment_id, $invoice_total_price]);
}

//rate data
$stmt = $pdo->prepare('SELECT COUNT(*) FROM product');
$stmt->execute();
$product_count = $stmt->fetchColumn();
$stmt = $pdo->prepare('SELECT COUNT(*) FROM user');
$stmt->execute();
$user_count = $stmt->fetchColumn();

for ($i = 0; $i < 100; $i++) {
    $rate_product_id = $faker->numberBetween(1, $product_count);
    $rate_user_id = $faker->numberBetween(1, $user_count);
    $rating = $faker->numberBetween(1, 5);
    $rate_comment = $faker->sentence();
    $stmt = $pdo->prepare('INSERT INTO rate (id_product, id_user, rate, comment) VALUES (?, ?, ?, ?)');
    $stmt->execute([$rate_product_id, $rate_user_id, $rating, $rate_comment]);
}

//photo data
//products photo
for ($i = 0; $i < $product_count; $i++) {
    $stmt = $pdo->prepare('SELECT product_name FROM product WHERE id_product = ?');
    $stmt->execute([$i + 1]);
    $product_name = $stmt->fetchColumn();
    $photo_url = $faker->imageUrl(640, 480, "Product", true, $product_name);
    $stmt = $pdo->prepare('INSERT INTO photo (url, id_product) VALUES (?, ?)');
    $stmt->execute([$photo_url, $i + 1]);
}
//user photo
for ($i = 0; $i < $user_count; $i++) {
    $stmt = $pdo->prepare('SELECT id_user FROM user WHERE id_user = ?');
    $stmt->execute([$i + 1]);
    $user_name = $stmt->fetchColumn();
    $photo_url = $faker->imageUrl(640, 480, "User", true, $user_name);
    $stmt = $pdo->prepare('INSERT INTO photo (url, id_user) VALUES (?, ?)');
    $stmt->execute([$photo_url, $i + 1]);
}

echo "success";
?>