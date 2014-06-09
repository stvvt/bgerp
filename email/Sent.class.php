<?php


/**
 * Изпращане на писма
 * 
 * @category  bgerp
 * @package   email
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.11
 * @see       https://github.com/bgerp/bgerp/issues/108
 */
class email_Sent
{
    
    
    /**
     * Изпраща имейл
     */
    static function sendOne($boxFrom, $emailsTo, $subject, $body, $options, $emailsCc = NULL)
    {
        if ($options['encoding'] == 'ascii') {
            $body->html = str::utf2ascii($body->html);
            $body->text = str::utf2ascii($body->text);
            $subject    = str::utf2ascii($subject);
        } elseif (!empty($options['encoding']) && $options['encoding'] != 'utf-8') {
            $body->html = iconv('UTF-8', $options['encoding'] . '//IGNORE', $body->html);
            $body->text = iconv('UTF-8', $options['encoding'] . '//IGNORE', $body->text);
            $subject    = iconv('UTF-8', $options['encoding'] . '//IGNORE', $subject);
        }
        
        $messageBase = array(
            'subject' => $subject,
            'html'    => $body->html,
            'text'    => $body->text,
            'attachments' => array_merge((array)$body->attachmentsFh, (array)$body->documentsFh),
            'headers' => array(),
            'emailFrom' => email_Inboxes::fetchField($boxFrom, 'email'),
            'charset'   => $options['encoding'],
        );
        
        // Добавя манипулатора на нишката в събджекта на имейла, ако няма забрана да направи това
        if (empty($options['no_thread_hnd'])) {
            $messageBase['subject'] = email_ThreadHandles::decorateSubject($messageBase['subject'], $body->threadId);
        }
        
        $sentRec = (object)array(
            'boxFrom' => $boxFrom,
            'mid'     => $body->__mid,
            'encoding' => $options['encoding'],
            'attachments' => (is_array($body->attachments)) ? keylist::fromArray($body->attachments) :$body->attachments,
            'documents' => (is_array($body->documents)) ? keylist::fromArray($body->documents) :$body->documents,
        );
        
        $message = (object)$messageBase;
     
        static::prepareMessage($message, $sentRec, $options['is_fax']);
    
        return static::doSend($message, $emailsTo, $emailsCc);
    }
    
    
    /**
     * Подготвя за изпращане по имейл
     */
    protected static function prepareMessage($message, $sentRec, $isFax = NULL)
    {        
        list($senderName, $senderDomain) = explode('@', $message->emailFrom, 2);
        
        expect(is_array($message->headers));
        
        // Намираме сметка за входящи писма от корпоративен тип, с домейла на имейла
        $corpAccRec = email_Accounts::getCorporateAcc();

        if($corpAccRec->domain == $senderDomain && !$isFax) {
            $message->headers['Return-Path'] = "{$senderName}+returned={$sentRec->mid}@{$senderDomain}";
        }
        
        $message->headers += array(
            
            'X-Confirm-Reading-To'        => "{$senderName}+received={$sentRec->mid}@{$senderDomain}",
            'Disposition-Notification-To' => "{$senderName}+received={$sentRec->mid}@{$senderDomain}",
            'Return-Receipt-To'           => "{$senderName}+received={$sentRec->mid}@{$senderDomain}",
        );
        
        $message->messageId = email_Router::createMessageIdFromMid($sentRec->mid);
        
        // Заместване на уникалния идентификатор на писмото с генерираната тук стойност
        $message->html = str_replace('[#mid#]', $sentRec->mid, $message->html);
        $message->text = str_replace('[#mid#]', $sentRec->mid, $message->text);
        
        return $message;
    }
    
    
    /**
     * Реално изпращане на писмо по електронна поща
     *
     * @param stdClass $message
     * @param string $emailFrom
     * @param string $emailTo
     * @return bool
     */
    protected static function doSend($message, $emailsTo, $emailsCc = NULL)
    {
        expect($emailsTo);
        expect($message->emailFrom);
        expect($message->subject);
        expect($message->html || $message->text);
        
        /** @var $PML PHPMailer */
        $PML = email_Accounts::getPML($message->emailFrom);

        if ($emailsTo) {
            $toArr = type_Emails::toArray($emailsTo);
            foreach ($toArr as $to) {
                $PML->AddAddress($to);        
            }
        }
        
        if ($emailsCc) {
            $ccArr = type_Emails::toArray($emailsCc);
            foreach ($ccArr as $cc) {
                $PML->AddCC($cc);        
            }
        }
        $PML->SetFrom($message->emailFrom);
        $PML->Subject   = $message->subject;
        $PML->CharSet   = $message->charset;
        $PML->MessageID = $message->messageId;
        
        /* 
         * Ако не е зададено е 8bit
         * Проблема се появява при дълъг стринг - без интервали и на кирилица.
         * Понеже е entity се режи грешно от phpmailer -> class.smtpl.php - $max_line_length = 998;
         */
        $PML->Encoding = "quoted-printable";
        
        $PML->ClearReplyTos();
        
        if (!empty($message->html)) {
            $PML->Body = $message->html;
            
            //Вкарваме всички статични файлове в съобщението
            self::embedSbfImg($PML); 
            $PML->IsHTML(TRUE);
        }
        
        if (!empty($message->text)) {
            if (empty($message->html)) {
                $PML->Body = $message->text;
                $PML->IsHTML(FALSE);
            } else {
                $PML->AltBody = $message->text;
            }
        }
        
        // Добавяме атачмънтите, ако има такива
        if (count($message->attachments)) {
            foreach ($message->attachments as $fh) {
                //Ако няма fileHandler да не го добавя
                if (!$fh) continue;
                
                $name = fileman_Files::fetchByFh($fh, 'name');
                $path = fileman_Files::fetchByFh($fh, 'path');
                $PML->AddAttachment($path, $name);
            }
        }
        
        // Задаване хедър "Return-Path"
        if (isset($message->headers['Return-Path'])) {
            $PML->Sender = $message->headers['Return-Path'];
            unset($message->headers['Return-Path']);
        }
        
        // Ако има още някакви хедъри, добавяме ги
        if (count($message->headers)) {
            foreach ($message->headers as $name => $value) {
                $PML->AddCustomHeader("{$name}:{$value}");
            }
        }
        
        if (!empty($message->inReplyTo)) {
            $PML->AddReplyTo($message->inReplyTo);
        }
        
        return $PML->Send();
    }


     
    
    /**
     * Вкарва всички статични изображения, като cid' ове
     * Приема обект.
     * Прави промените в $PML->Body
     */
    static function embedSbfImg(&$PML)
    {
        //Енкодинг
        $encoding = 'base64';
        
        //Ескейпваме името на директорията. Също така, допълнително ескейпваме и '/'
        $efSbf = preg_quote(EF_SBF, '/');
        
        //Шаблон за намиране на всички статични изображения в img таг
        $patternImg = "/<img[^>]+src=\"([^\">]+[\\\\\/]+" .  $efSbf . "[\\\\\/]+[^\">]+)\"/im";
        
        //Намираме всички статични изображения в img таг
        preg_match_all($patternImg, $PML->Body, $matchesImg);
        
        //Шаблон за намиране на всички статични изображения в background
        $patternBg = "/background[-image]*:[\s]*url[\s]*\(\"([^\)\"]+[\\\\\/]+" .  $efSbf . "[\\\\\/]+[^\)\"]+)\"/im";
        
        //Намираме всички статични изображения в background
        preg_match_all($patternBg, $PML->Body, $matchesBg);
        
        //Ако и двета масива съществуват, обединяваме ги
        if ((count($matchesImg[1])) && (count($matchesBg[1]))) {
            foreach ($matchesBg[1] as $key => $value) {
                $matchesImg[0][] = $matchesBg[0][$key];
                $matchesImg[1][] = $matchesBg[1][$key];
            }
            $matches = $matchesImg;
        }
        
        //Ако не сме открили съвпадения за background използваме img
        if ((count($matchesImg[1])) && (!count($matchesBg[1]))) {
            $matches = $matchesImg;
        }
        
        //Ако не сме открили съвпадения за img използваме background
        if ((!count($matchesImg[1])) && (count($matchesBg[1]))) {
            $matches = $matchesBg;
        }
        
        //Ако сме открили съвпадение
        if (count($matches[1])) {
                        
            //Обхождаме всички открите изображения
            foreach ($matches[1] as $imgPath) {
                                
                //Превръщаме абсолютния линк в реален, за да може да работи phpmailer' а
                $imgFile = self::absoluteUrlToReal($imgPath);
                
                //Масив с данните за линка
                $imgPathInfo = pathinfo($imgPath);
                
                //Името на файла
                $filename = $imgPathInfo['basename'];
                
                //Последната точка в името на файла
                $dotPos = mb_strrpos($filename, ".");
                
                //Добавяме стойността на брояча между името и разширението на cid'а за да е уникално
                $cidName = mb_substr($filename, 0, $dotPos) . $i . mb_substr($filename, $dotPos);
                
                //cid' а, с който ще заместваме
                $cidPath = "cid:" . $cidName;
                
                //Вземаме mimeType' а на файла
                $mimeType = fileman_Mimes::getMimeByExt($imgPathInfo['extension']);
                
                //Шаблона, за намиране на URL' то на файла
                $pattern = "/" . preg_quote($imgPath, '/') . "/im";
                
                //Заместваме URL' то на файла със съответния cid
                $PML->Body = preg_replace($pattern, $cidPath, $PML->Body, 1);
                
                //Ембедваме изображението
                $PML->AddEmbeddedImage($imgFile, $cidName, $filename, $encoding, $mimeType);
                
                //Брояч
                $i++;
            }
        }
    }
    
    
    /**
     * Превръша абсолютново URL в линк в системата
     */
    static function absoluteUrlToReal($link)
    {
        $link = decodeUrl($link);

        //sbf директорията
        $sbfPath = str_ireplace(EF_INDEX_PATH, '', EF_SBF_PATH);
        
        //Намираме позицията където се среща sbf директорията
        $spfPos = mb_stripos($link, $sbfPath);
        
        //Ако сме открили съвпадание
        if ($spfPos !== FALSE) {
            //Пътя на файла след sbf директорията
            $sbfPart = mb_substr($link, $spfPos + mb_strlen($sbfPath));
            
            //Връщаме вътрешното URL на файла в системата
            $realLink = EF_SBF_PATH . $sbfPart;
            
            return $realLink;
        }
    }
}
