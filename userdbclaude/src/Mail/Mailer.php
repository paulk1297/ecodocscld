<?php

declare(strict_types=1);

namespace App\Mail;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Enveloppe PHPMailer avec deux transports configurés (remplace
 * envoiemail.php / envoieecolink.php où identifiants et BCC perso étaient
 * codés en dur). Tout vient maintenant de la config (.env).
 */
final class Mailer
{
    /**
     * Envoi via le transport principal (Office365) : destiné aux utilisateurs.
     *
     * @param array<int,string> $to  Destinataires principaux
     * @param array<int,string> $cc  Copies
     * @param array<int,string> $bcc Copies cachées (ex. officier ECOLink)
     */
    public static function sendToUser(
        string $subject,
        string $htmlBody,
        array $to,
        array $cc = [],
        array $bcc = [],
        string $toName = ''
    ): void {
        $cfg  = $GLOBALS['config']['smtp'];
        $mail = new PHPMailer(true);

        $mail->isSMTP();
        $mail->Host       = $cfg['host'];
        $mail->Port       = $cfg['port'];
        $mail->SMTPAuth   = $cfg['auth'];
        if ($cfg['auth']) {
            $mail->Username = $cfg['user'];
            $mail->Password = $cfg['pass'];
        }
        if ($cfg['secure'] !== '') {
            $mail->SMTPSecure = $cfg['secure'];
        }

        self::compose($mail, $cfg['from'], $cfg['from_name'], $subject, $htmlBody);

        foreach ($to as $addr)  { if ($addr !== '') { $mail->addAddress($addr, $toName); } }
        foreach ($cc as $addr)  { if ($addr !== '') { $mail->addCC($addr); } }
        foreach ($bcc as $addr) { if ($addr !== '') { $mail->addBCC($addr); } }

        $mail->send();
    }

    /**
     * Envoi via le relais interne (sans auth) : destiné à l'officier ECOLink.
     */
    public static function sendToEcolink(
        string $subject,
        string $htmlBody,
        string $officer,
        array $cc = []
    ): void {
        $cfg  = $GLOBALS['config']['relay'];
        $from = $GLOBALS['config']['smtp']['from'];
        $name = $GLOBALS['config']['smtp']['from_name'];
        $mail = new PHPMailer(true);

        $mail->isSMTP();
        $mail->Host     = $cfg['host'];
        $mail->Port     = $cfg['port'];
        $mail->SMTPAuth = $cfg['auth'];
        if ($cfg['auth']) {
            $mail->Username = $cfg['user'];
        }

        self::compose($mail, $from, $name, $subject, $htmlBody);

        $mail->addAddress($officer, 'ECOLink Admin');
        foreach ($cc as $addr) { if ($addr !== '') { $mail->addCC($addr); } }

        $mail->send();
    }

    private static function compose(
        PHPMailer $mail,
        string $from,
        string $fromName,
        string $subject,
        string $htmlBody
    ): void {
        $mail->CharSet = PHPMailer::CHARSET_UTF8;
        $mail->setFrom($from, $fromName);
        $mail->addReplyTo($from, $fromName);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;
        $mail->AltBody = trim(strip_tags(str_replace(['<br>', '<br />'], "\n", $htmlBody)));
    }
}
