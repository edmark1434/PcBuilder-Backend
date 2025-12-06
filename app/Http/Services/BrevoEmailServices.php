<?php

namespace App\Http\Services;

use Brevo\Client\Api\TransactionalEmailsApi;
use Brevo\Client\Configuration;
use Brevo\Client\Model\SendSmtpEmail;

class BrevoEmailServices
{
    protected $apiInstance;

    public function __construct()
    {
        $config = Configuration::getDefaultConfiguration()->setApiKey(
            'api-key',
            env('BREVO_API_KEY')
        );

        // Instantiate the API
        $this->apiInstance = new TransactionalEmailsApi(null, $config);
    }

    public function sendResetCode($toEmail, $code)
    {
        $html = "
        <div style='background:black;padding:40px;text-align:center;color:white;font-family:Arial;'>
            <h1 style='font-size:40px;font-weight:bold;'>AutoBuild PC</h1>
            <p style='color:#cccccc;font-size:18px;margin-top:10px;'>
                Your perfect PC—automatically built by AI.
            </p>
            <div style='margin-top:40px;'>
                <p style='font-size:20px;color:#aaa;'>Your Reset Code</p>
                <h2 style='font-size:55px;color:#ff4088;margin:15px 0;'>
                    $code
                </h2>
            </div>
            <p style='margin-top:40px;color:#777;font-size:14px;'>
                This code will expire in 5 minutes.
            </p>
        </div>
        ";

        $email = new SendSmtpEmail([
            'to' => [[ 'email' => $toEmail ]],
            'sender' => [
                'email' => env('MAIL_FROM_ADDRESS'),
                'name' => env('MAIL_FROM_NAME')
            ],
            'subject' => 'Your AutoBuild PC Reset Code',
            'htmlContent' => $html
        ]);

        return $this->apiInstance->sendTransacEmail($email);
    }
}
