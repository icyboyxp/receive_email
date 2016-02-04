<?php

class receiveMail
{
    var $server   = '';
    var $username = '';
    var $password = '';
    var $mailbox  = '';

    function receiveMail(
        $username,
        $password,
        $mailServer = 'localhost',
        $serverType = 'pop',
        $port = '110',
        $ssl = true
    ) {
        if ($serverType == 'imap') {
            if ($port == '') {
                $port = '143';
            }
            $strConnect = $ssl ? '{' . $mailServer . ':' . $port . '/imap/ssl}INBOX' : '{' . $mailServer . ':' . $port . '}INBOX';
        } else {
            $strConnect = '{' . $mailServer . ':' . $port . '/pop3' . ($ssl ? "/ssl" : "") . '}INBOX';
        }
        $this->server   = $strConnect;
        $this->username = $username;
        $this->password = $password;
    }

    function connect()
    {
        $this->mailbox = imap_open($this->server, $this->username, $this->password);

        if (!$this->mailbox) {
            echo "Error: Connecting to mail server";
            exit;
        }
    }

    function getHeaders($mid)
    {
        if (!$this->mailbox) {
            return false;
        }

        $mail_header    = imap_header($this->mailbox, $mid);
        $sender         = $mail_header->from[0];
        $sender_replyTo = $mail_header->reply_to[0];
        if (strtolower($sender->mailbox) != 'mailer-daemon' && strtolower($sender->mailbox) != 'postmaster') {
            $subject      = $this->decodeToUTF8($mail_header->subject);
            $toNameOth    = $this->decodeToUTF8($sender_replyTo->personal);
            $fromName     = $this->decodeToUTF8($sender->personal);
            $mail_details = array(
                'from'      => strtolower($sender->mailbox) . '@' . $sender->host,
                'fromName'  => $fromName,
                'toOth'     => strtolower($sender_replyTo->mailbox) . '@' . $sender_replyTo->host,
                'toNameOth' => $toNameOth,
                'subject'   => $subject,
                'to'        => strtolower($mail_header->toaddress)
            );
        }

        return $mail_details;
    }

    function get_mime_type(&$structure)
    {
        $primary_mime_type = array("TEXT", "MULTIPART", "MESSAGE", "APPLICATION", "AUDIO", "IMAGE", "VIDEO", "OTHER");

        if ($structure->subtype) {
            return $primary_mime_type[(int)$structure->type] . '/' . $structure->subtype;
        }

        return "TEXT/PLAIN";
    }

    function get_part(
        $stream,
        $msg_number,
        $mime_type,
        $structure = false,
        $part_number = false
    ) {
        if (!$structure) {
            $structure = imap_fetchstructure($stream, $msg_number);
        }

        if ($structure) {
            if ($mime_type == $this->get_mime_type($structure)) {
                if (!$part_number) {
                    $part_number = "1";
                }
                $text = imap_fetchbody($stream, $msg_number, $part_number);
                if ($structure->encoding == 3) {
                    return imap_base64($text);
                } else {
                    if ($structure->encoding == 4) {
                        return imap_qprint($text);
                    } else {
                        return $text;
                    }
                }
            }
            if ($structure->type == 1) {
                while (list($index, $sub_structure) = each($structure->parts)) {
                    if ($part_number) {
                        $prefix = $part_number . '.';
                    }
                    $data = $this->get_part($stream, $msg_number, $mime_type, $sub_structure, $prefix . ($index + 1));
                    if ($data) {
                        return $data;
                    }
                }
            }
        }

        return false;
    }

    function getTotalMails(
        $criteria = 'ALL',
        $options = null,
        $charset = null
    ) {
        if (!$this->mailbox) {
            return false;
        }

        //$headers=imap_headers($this->mailbox);
        $emails = imap_search($this->mailbox, $criteria, $options, $charset);
        if ($emails === false) {
            return array();
        }
        arsort($emails);

        return $emails;
    }

    function GetAttach($mid, $path)
    {
        if (!$this->mailbox) {
            return false;
        }

        $structure = imap_fetchstructure($this->mailbox, $mid);
        $files     = array();
        if ($structure->parts) {
            foreach ($structure->parts as $key => $value) {
                $enc = $structure->parts[$key]->encoding;
                if ($structure->parts[$key]->ifdparameters) {
                    $name = $structure->parts[$key]->dparameters[0]->value;
                    $file = $this->_toFile($enc, $mid, $key + 1, $name, $path);
                    array_push($files, $file);
                }

                if ($structure->parts[$key]->parts) {
                    foreach ($structure->parts[$key]->parts as $k => $v) {
                        $enc = $structure->parts[$key]->parts[$k]->encoding;
                        if ($structure->parts[$key]->parts[$k]->ifdparameters) {
                            $name = $structure->parts[$key]->parts[$k]->dparameters[0]->value;
                            $val  = ($key + 1) . "." . ($k + 1);
                            $file = $this->_toFile($enc, $mid, $val, $name, $path);
                            array_push($files, $file);
                        }
                    }
                }
            }
        }

        return $files;
    }

    function getBody($mid)
    {
        if (!$this->mailbox) {
            return false;
        }

        $body = $this->get_part($this->mailbox, $mid, "TEXT/HTML");
        if ($body == "") {
            $body = $this->get_part($this->mailbox, $mid, "TEXT/PLAIN");
        }
        if ($body == "") {
            return "";
        }

        return iconv("GBK", "UTF-8//IGNORE", $body);
    }

    function deleteMails($mid)
    {
        if (!$this->mailbox) {
            return false;
        }

        imap_delete($this->mailbox, $mid);
    }

    function close_mailbox()
    {
        if (!$this->mailbox) {
            return false;
        }

        imap_close($this->mailbox, CL_EXPUNGE);
    }

    private function decodeToUTF8($stringQP, $base = 'windows-1252')
    {
        $pairs    = array(
            '?x-unknown?' => "?$base?"
        );
        $stringQP = strtr($stringQP, $pairs);

        return imap_utf8($stringQP);
    }

    private function _toFile($enc, $mid, $value, $name, $path)
    {
        $message = imap_fetchbody($this->mailbox, $mid, $value);
        switch ($enc) {
            case 0:
                $message = imap_8bit($message);
                break;
            case 1:
                $message = imap_8bit($message);
                break;
            case 2:
                $message = imap_binary($message);
                break;
            case 3:
                $message = imap_base64($message);
                break;
            case 4:
                $message = quoted_printable_decode($message);
                break;
        }

        $name = $this->decodeToUTF8($name);
        $fp   = fopen($path . $name, "w");
        fwrite($fp, $message);
        fclose($fp);

        return $name;
    }
}

?>
