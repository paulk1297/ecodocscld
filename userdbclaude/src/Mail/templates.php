<?php

declare(strict_types=1);

namespace App\Mail;

use function e;

/**
 * Gabarits HTML des e-mails ECOWASmail.
 *
 * Chaque fonction retourne le corps HTML complet d'un message. Le contenu
 * reprend fidèlement les messages du legacy, mais centralisé, échappé et
 * sans HTML cassé (les "with:" → "width:", styles dupliqués, etc.).
 */
final class MailTemplates
{
    /** Encadré commun à tous les messages. */
    private static function wrap(string $inner): string
    {
        return '<div style="font-family:Arial,Helvetica,sans-serif;color:#1f2937;'
             . 'border:2px solid #0f766e;border-radius:8px;max-width:640px;'
             . 'margin:16px auto;padding:24px;line-height:1.6;">'
             . $inner
             . '<br><p style="margin-top:24px;">Regards,<br><b>WebMaster</b></p>'
             . '</div>';
    }

    private static function h1(string $text): string
    {
        return '<h1 style="font-size:18px;background:#f0fdfa;border-radius:6px;'
             . 'text-align:center;padding:12px;margin:0 0 16px;">' . e($text) . '</h1>';
    }

    private static function credentials(string $cemail, string $mailUrl, string $pwd): string
    {
        return '<p style="margin:16px 0;"><b>To log into your account:</b></p>'
             . '<table style="border-collapse:collapse;">'
             . self::row('Site address', $mailUrl)
             . self::row('User ID', $cemail)
             . self::row('Init. password', $pwd)
             . '</table>';
    }

    private static function row(string $label, string $value): string
    {
        return '<tr><td style="padding:4px 12px 4px 0;font-weight:bold;">' . e($label) . ' :</td>'
             . '<td style="padding:4px 0;">' . e($value) . '</td></tr>';
    }

    /** Nouveau compte / renvoi des identifiants (newuser, rs). */
    public static function accountDetails(array $d): string
    {
        return self::wrap(
            '<p>Dear ' . e($d['nom']) . ',</p>'
            . self::h1('Your ECOWASMAIL account details')
            . '<table style="border-collapse:collapse;">'
            . self::row('Name', $d['nom'])
            . self::row('Job title/Department', $d['poste'])
            . self::row('E-mail', $d['cemail'])
            . '</table>'
            . self::credentials($d['cemail'], $d['mail_url'], $d['pwd'])
        );
    }

    /** Compte générique (generic). */
    public static function genericAccount(array $d): string
    {
        return self::wrap(
            '<p>Dear ' . e($d['nom']) . ',</p>'
            . '<p>The ECOWASmail account for <b>' . e($d['poste']) . '</b> is as follows:</p>'
            . self::credentials($d['cemail'], $d['mail_url'], $d['pwd'])
        );
    }

    /** Réinitialisation de mot de passe (rspw). */
    public static function passwordReset(array $d): string
    {
        return self::wrap(
            '<p>Dear ' . e($d['nom']) . ',</p>'
            . '<p>On request, your ECOWASmail password has been reset as follows:</p>'
            . '<table style="border-collapse:collapse;">'
            . self::row('E-mail / User ID', $d['cemail'])
            . self::row('New password', $d['pwd'])
            . '</table>'
        );
    }

    /** Notification de phishing (phishing). */
    public static function phishing(array $d): string
    {
        return self::wrap(
            '<p>Dear ' . e($d['nom']) . ',</p>'
            . '<h2 style="color:#b91c1c;">Your account is sending phishing mails to users.</h2>'
            . '<p>To protect you, the following security measures have been applied automatically:</p>'
            . '<p>1 — Your ECOWASmail password is reset to: <b>' . e($d['pwd']) . '</b></p>'
            . '<p>2 — A second level authentication has been applied to your account.</p>'
        );
    }

    /** Réactivation de compte (reactivation). */
    public static function reactivation(array $d): string
    {
        $today = date('d/m/Y');
        return self::wrap(
            '<p>Dear ' . e($d['nom']) . ',</p>'
            . '<p>Your ECOWASmail account <b>' . e($d['cemail']) . '</b> has been reactivated.</p>'
            . '<p>Date: ' . e($today) . '</p>'
        );
    }

    /** Notification à l'officier ECOLink (ecolink). */
    public static function ecolink(array $d): string
    {
        return self::wrap(
            '<p>Dear ECOLINK OFFICER,</p>'
            . self::h1('New ECOWASMAIL account details for ECOLINK')
            . '<table style="border-collapse:collapse;">'
            . self::row('Staff Name', $d['nom'])
            . self::row('Job title/Department', $d['poste'])
            . self::row('Staff ECOWASmail', $d['cemail'])
            . self::row('Staff personal E-mail', $d['otheremail'] ?? '')
            . self::row('Init. password', $d['pwd'])
            . '</table>'
        );
    }
}
