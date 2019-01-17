<?php
/**
 * This script verifies the transactions and returns a reference
 * 
 * PHP version 7.2
 * 
 * @category Form_Processors
 * @package  Form_Processor
 * @author   Benson Imoh,ST <benson@stbensonimoh.com>
 * @license  MIT https://opensource.org/licenses/MIT
 * @link     https://stbensonimoh.com 
 */ 
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

require './scripts/dbconfig.php';
require './scripts/Paystack.php';

// Pull in Sendupulse Classes
require 'sendpulse-rest-api-php/ApiInterface.php';
require 'sendpulse-rest-api-php/ApiClient.php';
require 'sendpulse-rest-api-php/Storage/TokenStorageInterface.php';
require 'sendpulse-rest-api-php/Storage/FileStorage.php';
require 'sendpulse-rest-api-php/Storage/SessionStorage.php';
require 'sendpulse-rest-api-php/Storage/MemcachedStorage.php';
require 'sendpulse-rest-api-php/Storage/MemcacheStorage.php';
// Pull in PHPMailer Classes
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
use Sendpulse\RestApi\ApiClient;
use Sendpulse\RestApi\Storage\FileStorage;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

$SPApiClient = new ApiClient(API_USER_ID, API_SECRET, new FileStorage());


//  Connect to the Database using PDO
$dsn = "mysql:host=$host;dbname=$db";
//Create PDO Connection with the dbconfig data
$conn = new PDO($dsn, $username, $password);

$date = date("Y-m-d H:i:s");


// Initialize Transaction

$paystack = new Paystack($paystackKey);
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

if ('success' == $trx->data->status) {  
    $email = $trx->data->customer->email;
    // Update the database with paid
    $updatePaid =   "UPDATE awlc2019 
                    SET paid=:paid, paid_at=:paid_at WHERE email=:email";
    // prepare the Query
    $paidquery = $conn->prepare($updatePaid);
    //Bind
    $paidquery->bindValue(":paid", 'yes');
    $paidquery->bindValue(":paid_at", $date);
    $paidquery->bindValue(":email", $email);

    //Execute the Query
    $paidquery->execute();
    //Fetch the Result
    $paidquery->rowCount();
    if ($paidquery->rowCount() > 0) {
        //Query the database with Customer email to get phone number
        $phonecheck = "SELECT * FROM awlc2019 WHERE email=?";
        // prepare the Query
        $phonecheckquery = $conn->prepare($phonecheck);
        //Execute the Query
        $phonecheckquery->execute(array("$email"));
        //Fetch the Result
        $phonecheckquery->rowCount();
        if ($phonecheckquery->rowCount() > 0) {
            // get the phone number
            while ($result = $phonecheckquery->fetch(PDO::FETCH_ASSOC)) {
                $phone = $result['phone'];
                $firstName = $result['firstName'];
                $lastName = $result['lastName'];
            }

        }

        //Send SMS
            // prepare the parameters
            $url = 'https://www.bulksmsnigeria.com/api/v1/sms/create';
            $from = 'AWLO';
            $body = "Dear ".$firstName. " " .$lastName. ", thank you for registering for African Women in Leadership Conference Rwanda 2019. Look out for updates on awlo.org/awlc and our social media pages. See you in Rwanda!";
            $token = $smstoken;
            $myvars = 'api_token=' . $token . '&from=' . $from . '&to='
                        . $phone . '&body=' . $body;
            //start CURL
            // create curl resource
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $myvars);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_exec($ch);

        /**
         * Add User to the SendPule mailing List
         */
        $bookID = 2233446;
        $emails = array(
                array(
                    'email'         =>  $email,
                    'variables'     =>  array(
                    'phone'         =>  $phone,
                    'name'          =>  $firstName,
                    'lastName'      =>  $lastName
                )
            )
        );
        // Without Confirmation
        var_dump($SPApiClient->addEmails($bookID, $emails));


        // Send Email
        $mail = new PHPMailer(true); // enable exceptions
        // server settings
        $mail->SMTPDebug = 0;
        $mail->isSMTP();
        $mail->Host = $emailHost;
        $mail->SMTPAuth = true;
        $mail->Username = $emailUsername;
        $mail->Password = $emailPassword;
        $mail->SMTPSecure = 'ssl';
        $mail->Port = 465;
        $emailBody = '<table style="background-color: #d5d5d5;" border="0" width="100%" cellspacing="0">
                        <tbody>
                        <tr>
                        <td>
                        <table style="font-family: Helvetica,Arial,sans-serif; background-color: #fff; margin-top: 40px; margin-bottom: 40px;" border="0" width="600" cellspacing="0" cellpadding="0" align="center">
                        <tbody>
                        <tr>
                        <td style="padding-top: 40px; padding-right: 40px; padding-bottom: 15px;" colspan="2">
                        <p style="text-align: right;"><a href="https://awlo.org"><img src="http://awlo.org/email/awlo_lg.png" alt="African Women in Leadership Organisation" width="20%" border="0" /></a></p>
                        </td>
                        </tr>
                        <tr>
                        <td style="padding-right: 40px; text-align: right;" colspan="2"></td>
                        </tr>
                        <tr>
                        <td style="color: #000; font-size: 12pt; font-family: Helvetica; font-weight: normal; line-height: 15pt; padding: 40px 40px 80px 40px;" colspan="2" valign="top">Dear ' . $firstName . ' ' . $lastName . ',' . '
                        <p>Thank you for registering for African Women in Leadership Conference Rwanda 2019.
African Women in Leadership Conference is a meeting for strategies and capacity building to advance women\'s leadership. It engages Key decision makers to generate solutions, implement best practices, and build partnerships  for women\'s development.</p>
<p>AWLC mainly engages women, to increase leadership participation through capacity building.
To stay updated on Sessions, Speakers, and everything #AWLCRwanda2019 visit on awlo.org/awlc. Also check out our social media pages. See you soon in Rwanda</p>
                        </td>
                        </tr>
                        <tr>
                        <td style="border-top: 5px solid #940000; height: 10px; font-size: 7pt;" colspan="2" valign="top"><span>&nbsp;</span></td>
                        </tr>
                        <tr style="text-align: center;">
                        <td id="s1" style="padding-left: 20px;" valign="top"><span style="text-align: center; color: #333; font-size: 12pt;"><strong>AWLO Correspondence Team<span style="color: #cccccc; font-size: x-large;">&nbsp;|&nbsp;</span><span style="text-align: left; color: #333; font-size: 11pt; font-weight: normal;">International Headquarters</span></td>
                        </tr>
                        <tr style="text-align: center; padding-left: 40px; padding-right: 40px; padding-bottom: 0;">
                        <td colspan="2" valign="top"><span style="color: #333; font-size: 8pt; font-weight: normal; line-height: 17pt; padding-left: 40px; padding-right: 40px;">African Women in Leadership Organisation<br /><strong>International Headquarters:</strong> 6, Alhaji Bankole Crescent, Ikeja, Lagos - Nigeria<br />tel: +2347066819910 &nbsp;|&nbsp; mobile: +2348066285116 &nbsp;|&nbsp; +2348087719510<br /><strong>USA:</strong> 60 4800 Duval Point Way SW, Snellville, GA 30039, USA.<br />tel: +1 404-518-8194 &nbsp;| <span>+1 505-547-0528</span>&nbsp;&nbsp;<br /><strong>South Africa:</strong>&nbsp;Newlands Shopping Centre CNR. Dely Road/Lois Road, <br />1st Floor, Suite 104, Newlands, Pretoria, South Africa<br />tel: +27-845-105871<br /><strong>email:&nbsp;</strong>info@awlo.org &nbsp;|&nbsp; <strong>www.awlo.org</strong></span>
                        <p><a href="http://twitter.com/awloint"><img src="http://awlo.org/email/social/twitter_circle_color-20.png" width="20px" height="20px" /></a><a href="http://facebook.com/awloint"><img src="http://awlo.org/email/social/facebook_circle_color-20.png" width="20px" height="20px" /></a><a href="https://plus.google.com/103912934440599693779"><img src="http://awlo.org/email/social/google_circle_color-20.png" width="20px" height="20px" /></a><a href="http://linkedin.com/company/awloint"><img src="http://awlo.org/email/social/linkedin_circle_color-20.png" width="20px" height="20px" /></a><a href="http://instagram.com/awloint"><img src="http://awlo.org/email/social/instagram_circle_color-20.png" width="20px" height="20px" /></a><a href="https://www.youtube.com/channel/UCevvBafqeTjY16qd2gbceJw"><img src="http://awlo.org/email/social/youtube_circle_color-20.png" width="20px" height="20px" /></a></p>
                        </td>
                        </tr>
                        <tr>
                        <td id="s3" style="padding-left: 20px; padding-right: 20px;" colspan="2" valign="bottom">
                        <p style="font-family: Helvetica, sans-serif; text-align: center; font-size: 12px; line-height: 21px; color: #333;"><span style="margin-left: 4px;"><span style="opacity: 0.4; color: #333; font-size: 9px;">Disclaimer: This message and any files transmitted with it are confidential and privileged. If you have received it in error, please notify the sender by return e-mail and delete this message from your system. If you are not the intended recipient you are hereby notified that any dissemination, copy or disclosure of this e-mail is strictly prohibited.</span></span></p>
                        </td>
                        </tr>
                        <tr>
                        <td style="border-bottom: 5px solid #940000; height: 5px; font-size: 7pt;" colspan="2" valign="top">&nbsp;</td>
                        </tr>
                        </tbody>
                        </table>
                        </td>
                        </tr>
                        </tbody>
                        </table>';
        //Recipients
        $mail->setFrom('info@awlo.org', 'African Women in Leadership Organisation');
        $mail->addAddress($email, $firstName.' '.$lastName);
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Successful Registration for #AWLCRwanda2019';
        $mail->Body = $emailBody;
        
        $mail->send();




        header('Location: success.html');
    }

}