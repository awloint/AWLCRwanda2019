<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'scripts/dbconfig.php';
require 'scripts/Paystack.php';

//  Connect to the Database using PDO
$dsn = "mysql:host=$host;dbname=$db";
//Create PDO Connection with the dbconfig data
$conn = new PDO($dsn, $username, $password);

$date = date("Y-m-d H:i:s");




$paystack = new Paystack('sk_test_af1b02672c3124527de88d9b4ab4df6c72dd23c9');
// the code below throws an exception if there was a problem completing the request,
// else returns an object created from the json response
$trx = $paystack->transaction->verify(
    [
     'reference'=>$_GET['reference']
    ]
);
// status should be true if there was a successful call
if (!$trx->status) {
    exit($trx->message);
}

if('success' == $trx->data->status){
    // Update the database with paid
    $updatePaid = "UPDATE awlc2019 SET paid=:paid, paid_at=:paid_at WHERE email=:email";
    // prepare the Query
    $paidquery = $conn->prepare($updatePaid);
    //Bind
    $paidquery->bindValue(":paid", 'yes');
    $paidquery->bindValue(":paid_at", $date);
    $paidquery->bindValue(":email", $trx->data->customer->email);

    //Execute the Query
    $paidquery->execute();
    //Fetch the Result
    $paidquery->rowCount();
    if ($paidquery->rowCount() > 0) {
        header('Location: success.html');
    }

}