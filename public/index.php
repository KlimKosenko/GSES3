<?php
require dirname(__DIR__) . '/vendor/autoload.php';

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Factory\AppFactory;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

$app = AppFactory::create();
$app->addErrorMiddleware(true, true, true);

$app->get('/', function (RequestInterface $request, ResponseInterface $response, array $args) {
    $response->getBody()->write("Hello");
    return $response;
});
$app->post('/sendEmails', function (RequestInterface $request, ResponseInterface $response, array $args) {
    $jsonBit=json_decode( file_get_contents('https://bitpay.com/api/rates/uah'));
    $mail = new PHPMailer(true);
    try {
        //Server settings
        $mail->isSMTP();                                            //Send using SMTP
        $mail->Host       = 'smtp.gmail.com';                     //Set the SMTP server to send through
        $mail->SMTPAuth   = true;                                   //Enable SMTP authentication
        $mail->Username   = 'btcinfoua@gmail.com';                     //SMTP username
        $mail->Password   = 'behtithemgwumxys';                               //SMTP password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;            //Enable implicit TLS encryption
        $mail->Port       = 587;                                    //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`
    
        //Recipients
        $mail->CharSet = "UTF-8";
        $mail->setFrom('btcinfoua@gmail.com');
        $handle = fopen("emailsData.txt", "r");
        if ($handle) {
            while (($line = fgets($handle)) !== false) {
                $mail->addAddress(trim($line));
            }
            fclose($handle);
        }
        $mail->Subject = 'Курс біткоіна до гривні';
        $mail->Body    = '1 біткоін = '.$jsonBit->rate.' грн';
    
        $mail->send();
        $response->getBody()->write(json_encode(array('Description'=>'Message has been sent')));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    } catch (Exception $e) {
        $response->getBody()->write("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return $response;
    }
});
$app->post('/subscribe', function (RequestInterface $request, ResponseInterface $response, array $args) {
    $email = trim($_POST['email']);
    $isExist = 0;
    $handle = fopen("emailsData.txt", "r");
    if ($handle) {
        while (($line = fgets($handle)) !== false) {
            if(trim($line)==$email){
                $isExist = true;
                break;
            }
        }
        fclose($handle);
    }
    if($isExist){
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(409,"e-mail is already in the database");
    }
    file_put_contents('emailsData.txt', $email.PHP_EOL , FILE_APPEND | LOCK_EX);
    $response->getBody()->write(json_encode(array('Description'=>'E-mail added')));
    return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    
});
$app->get('/rate', function (RequestInterface $request, ResponseInterface $response, array $args) {
        $url='https://bitpay.com/api/rates/uah';
        $json=json_decode( file_get_contents( $url ) );
        if(empty($json)) {
            return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(400,"Invalid status value");
        }

        $data = array('rate' => $json->rate);
        $payload = json_encode($data);
        $response->getBody()->write($payload);
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    });
$app->run();