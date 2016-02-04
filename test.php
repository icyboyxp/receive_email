<?php

include("mail.class.php");

$obj = new receiveMail('邮箱地址', '客户端授权码', 'imap.163.com', 'imap', '993', true);
$obj->connect();
$emails = $obj->getTotalMails('NEW'); //NEW 获取最新未读的 ALL 获取所有的
echo "Total Mails:: " . count($emails) . PHP_EOL . PHP_EOL;

if ($emails) {
    foreach ($emails as $email) {
        $head = $obj->getHeaders($email);
        echo "Subjects :: " . $head['subject'] . PHP_EOL;
        echo "TO :: " . $head['to'] . PHP_EOL;
        echo "To Other :: " . $head['toOth'] . PHP_EOL;
        echo "ToName Other :: " . $head['toNameOth'] . PHP_EOL;
        echo "From :: " . $head['from'] . PHP_EOL;
        echo "FromName :: " . $head['fromName'] . PHP_EOL;
        //echo "Message Content ::".$obj->getBody($email);
        $files = $obj->GetAttach($email, "./");
        foreach ($files as $value) {
            echo "Atteched File :: " . $value . PHP_EOL;
        }
        echo "*******************************************************************************************" . PHP_EOL;
        //$obj->deleteMails($email);
    }
}

$obj->close_mailbox();
