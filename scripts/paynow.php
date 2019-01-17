<?php
/**
 * This script handles registration and payment
 *
 * PHP version 7.2
 *
 * @category Registration_And_Payment
 * @package  Registration_And_Payment
 * @author   Benson Imoh,ST <benson@stbensonimoh.com>
 * @license  MIT https://opensource.org/licenses/MIT
 * @link     https://stbensonimoh.com
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

$_POST = json_decode(file_get_contents('php://input'), true);
//pull in the database
require 'dbconfig.php';
require 'Paystack.php';

// Capture Post Data that is coming from the form
$firstName = $_POST['firstName'];
$lastName = $_POST['lastName'];
$email = $_POST['email'];
$phone = $_POST['phone'];
$country = $_POST['country'];
$occupation = $_POST['occupation'];
$organisation = $_POST['organisation'];
$member = $_POST['member'];
$referrer = $_POST['referrer'];
$firstConference = $_POST['firstConference'];
$currency = $_POST['currency'];
$amount = $_POST['amount'];

//  Connect to the Database using PDO
$dsn = "mysql:host=$host;dbname=$db";
//Create PDO Connection with the dbconfig data
$conn = new PDO($dsn, $username, $password);

//  Check to see if the user is in the database already
$usercheck = "SELECT * FROM awlc2019 WHERE email=?";
// prepare the Query
$usercheckquery = $conn->prepare($usercheck);
//Execute the Query
$usercheckquery->execute(array("$email"));
//Fetch the Result
$usercheckquery->rowCount();
if ($usercheckquery->rowCount() > 0) {   
    // Check to ss if the user has paid
    $usercheckpaid = "SELECT * FROM awlc2019 WHERE email=? AND paid='yes'";
    // prepare the Query
    $usercheckpaidquery = $conn->prepare($usercheckpaid);
    // Execute the Query
    $usercheckpaidquery->execute(array("$email"));
    // Fetch the Result
    $usercheckpaidquery->rowCount();
    if ($usercheckpaidquery->rowCount() > 0 ) {
        echo json_encode("user_exists");
    } else {
        // User has registered but hasn't paid so initiatlize payment
        $paystack = new Paystack($paystackKey);
        // throw an exception if there was a problem completing the request,
        // else returns an object created from the json response
        $trx = $paystack->transaction->initialize(
            [
            'amount'=> $amount, /* 20 naira */
            'email'=> $email,
            'currency' => $currency,
            'callback_url' => 'https://awlo.org/awlc/rwanda2019/verify.php',
            'metadata' => json_encode(
                [
                'custom_fields'=> [
                    [
                    'display_name'=> "First Name",
                    'variable_name'=> "first_name",
                    'value'=> $firstName
                    ],
                    [
                    'display_name'=> "Last Name",
                    'variable_name'=> "last_name",
                    'value'=> $lastName
                    ],
                    [
                    'display_name'=> "Mobile Number",
                    'variable_name'=> "mobile_number",
                    'value'=> $phone
                    ]
                ]
                ]
            )
            ]
        );

        // status should be true if there was a successful call
        // if (!$trx->status) {
        //     exit($trx->message);
        // }


        echo json_encode($trx->data->authorization_url);

    }
    
} else {
    // Insert the user into the database
    $enteruser = "INSERT into awlc2019 (firstName, 
                        lastName, 
                        email, 
                        phone, 
                        country, 
                        occupation, 
                        organisation, 
                        member, 
                        referrer, 
                        firstConference)
                VALUES (:firstName, 
                        :lastName, 
                        :email, 
                        :phone, 
                        :country, 
                        :occupation, 
                        :organisation, 
                        :member, 
                        :referrer, 
                        :firstConference)";
    //  Prepare Query
    $enteruserquery = $conn->prepare($enteruser);
    //  Execute the Query
    $enteruserquery->execute(
        array(
            "firstName" => $firstName,
            "lastName" => $lastName,
            "email" => $email,
            "phone" => $phone,
            "country" => $country,
            "occupation" => $occupation,
            "organisation" => $organisation,
            "member" => $member,
            "referrer" => $referrer,
            "firstConference" => $firstConference
        )
    );

    //  Fetch Result
    $enteruserquery->rowCount();
    // Check to see if the query executed successfully
    if ($enteruserquery->rowCount() > 0) {
        $paystack = new Paystack($paystackKey);
        // throw an exception if there was a problem completing the request,
        // else returns an object created from the json response
        $trx = $paystack->transaction->initialize(
            [
            'amount'=> $amount, /* 20 naira */
            'email'=> $email,
            'currency' => $currency,
            'callback_url' => 'https://awlo.org/awlc/rwanda2019/verify.php',
            'metadata' => json_encode(
                [
                'custom_fields'=> [
                    [
                    'display_name'=> "First Name",
                    'variable_name'=> "first_name",
                    'value'=> $firstName
                    ],
                    [
                    'display_name'=> "Last Name",
                    'variable_name'=> "last_name",
                    'value'=> $lastName
                    ],
                    [
                    'display_name'=> "Mobile Number",
                    'variable_name'=> "mobile_number",
                    'value'=> $phone
                    ]
                ]
                ]
            )
            ]
        );

        // status should be true if there was a successful call
        // if (!$trx->status) {
        //     exit($trx->message);
        // }


        echo json_encode($trx->data->authorization_url);
    }
}
