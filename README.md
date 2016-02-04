# 使用php脚本接收邮件与附件下载

> 使用了php的IMAP协议，需要php支持IMAP扩展。

## imap.so扩展安装
```
yum -y install krb5-devel libc-client libc-client-devel
ln -sv /usr/lib64/libc-client.so /usr/lib/libc-client.so
cd /usr/local/src/php-5.4.26/ext/imap
/usr/local/php/bin/phpize
./configure --with-php-config=/usr/local/php/bin/php-config --with-imap=/usr/lib64 --with-imap-ssl --with-kerberos
make && make install
; 打开php.ini 加入imap.so 扩展支持
```

## DEMO
```
<?php

include("mail.class.php");

$obj = new receiveMail('邮箱地址', '客户端授权码', 'imap.163.com', 'imap', '993', “ssl加密，默认true”);
$obj->connect();
$emails = $obj->getTotalMails('NEW');
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
        echo "********************************************************************" . PHP_EOL;
        //$obj->deleteMails($email);
    }
}

$obj->close_mailbox();
```

`由于邮件服务商的安全限制，需要在163邮箱网页版认可下。` <http://config.mail.126.com/settings/imap/login.jsp?uid=xx@163.com>
