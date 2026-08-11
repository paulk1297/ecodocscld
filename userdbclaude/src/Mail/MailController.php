<?php

declare(strict_types=1);

namespace App\Mail;

use App\Auth;
use App\Users\UserRepository;

use function view;
use function redirect;
use function flash;
use function csrf_verify;

/**
 * Contrôleur des actions e-mail (resend, reset, generic, reactivation,
 * phishing, ecolink) + aperçu (preview).
 *
 * Différence clé avec le legacy : ces actions ne sont plus déclenchées par
 * un simple lien GET. On affiche d'abord un écran de confirmation (GET) ;
 * l'envoi réel ne se fait qu'en POST avec jeton CSRF. Comme on ne stocke
 * que le hash du mot de passe, l'écran demande à l'admin de saisir le mot
 * de passe en clair (qu'il a posé sur le serveur mail) pour les messages
 * qui en contiennent un.
 */
final class MailController
{
    /** Métadonnées de chaque type d'action. */
    private const ACTIONS = [
        'rs'           => ['label' => 'Renvoyer les identifiants',     'needsPwd' => true,  'updatePwd' => false],
        'generic'      => ['label' => 'Renvoyer (compte générique)',   'needsPwd' => true,  'updatePwd' => false],
        'rspw'         => ['label' => 'Réinitialiser le mot de passe', 'needsPwd' => true,  'updatePwd' => true],
        'phishing'     => ['label' => 'Notification phishing + reset',  'needsPwd' => true,  'updatePwd' => true],
        'ecolink'      => ['label' => 'Notifier l\'officier ECOLink',   'needsPwd' => true,  'updatePwd' => false],
        'reactivation' => ['label' => 'Réactivation du compte',         'needsPwd' => false, 'updatePwd' => false],
    ];

    public function __construct(private UserRepository $repo) {}

    /** Écran de confirmation (GET). */
    public function confirm(): void
    {
        Auth::requireAdmin();
        [$type, $user] = $this->resolve();
        $meta = self::ACTIONS[$type];

        view('mail_action', [
            'type' => $type,
            'meta' => $meta,
            'user' => $user,
            'info' => parse_uname($user['uname']),
        ], $meta['label']);
    }

    /** Exécution réelle de l'envoi (POST). */
    public function send(): void
    {
        Auth::requireAdmin();
        csrf_verify();
        [$type, $user] = $this->resolve();
        $meta = self::ACTIONS[$type];

        $info = parse_uname($user['uname']);
        $pwd  = (string) ($_POST['pwd'] ?? '');

        if ($meta['needsPwd'] && $pwd === '') {
            flash('error', 'Le mot de passe est requis pour cette action.');
            redirect($this->confirmUrl($type, (string) $user['userid']));
        }

        $data = [
            'nom'        => $user['nom'],
            'poste'      => $user['poste'],
            'cemail'     => $info['cemail'],
            'mail_url'   => $info['mail_url'],
            'pwd'        => $pwd,
            'otheremail' => $user['otheremail'] ?? '',
        ];

        try {
            $this->dispatch($type, $data);

            // Met à jour le hash si l'action correspond à un (re)set de mot de passe.
            if ($meta['updatePwd'] && $pwd !== '') {
                $this->repo->updatePassword((string) $user['userid'], $pwd);
            }

            flash('success', $meta['label'] . ' : e-mail envoyé à ' . $info['cemail'] . '.');
        } catch (\Throwable $e) {
            flash('error', "Échec de l'envoi : " . $e->getMessage());
        }

        redirect('index.php?action=users.list');
    }

    /** Aperçu du message (af) — affichage seul, aucun envoi. */
    public function preview(): void
    {
        Auth::requireAdmin();
        $user = $this->repo->find((string) ($_GET['id'] ?? ''));
        if (!$user) {
            flash('error', 'Utilisateur introuvable.');
            redirect('index.php?action=users.list');
        }
        $info = parse_uname($user['uname']);
        $html = MailTemplates::accountDetails([
            'nom'      => $user['nom'],
            'poste'    => $user['poste'],
            'cemail'   => $info['cemail'],
            'mail_url' => $info['mail_url'],
            'pwd'      => '••••••••',
        ]);

        view('mail_preview', [
            'user'    => $user,
            'info'    => $info,
            'preview' => $html,
        ], 'Aperçu du message');
    }

    /** Construit et envoie le bon message selon le type. */
    private function dispatch(string $type, array $data): void
    {
        switch ($type) {
            case 'rs':
                Mailer::sendToUser('ECOWASMAIL ACCOUNT',
                    MailTemplates::accountDetails($data), [$data['cemail']], [$data['otheremail']]);
                break;
            case 'generic':
                Mailer::sendToUser('ECOWASMAIL generic User info',
                    MailTemplates::genericAccount($data), [$data['cemail']], [$data['otheremail']]);
                break;
            case 'rspw':
                Mailer::sendToUser('ECOWASMAIL Password Reset',
                    MailTemplates::passwordReset($data), [$data['cemail']], [$data['otheremail']]);
                break;
            case 'phishing':
                Mailer::sendToUser('ECOWASMAIL PHISHING',
                    MailTemplates::phishing($data), [$data['cemail']], [$data['otheremail']]);
                break;
            case 'reactivation':
                Mailer::sendToUser('ECOWASMAIL Reactivation',
                    MailTemplates::reactivation($data), [$data['cemail']], [$data['otheremail']]);
                break;
            case 'ecolink':
                Mailer::sendToEcolink('ECOWASMAIL ACCOUNT FOR ECOLINK',
                    MailTemplates::ecolink($data), $GLOBALS['config']['ecolink_officer'], [$data['cemail']]);
                break;
        }
    }

    /** Valide le type + charge l'utilisateur ; redirige si invalide. */
    private function resolve(): array
    {
        $type = (string) ($_REQUEST['type'] ?? '');
        if (!isset(self::ACTIONS[$type])) {
            flash('error', 'Action e-mail inconnue.');
            redirect('index.php?action=users.list');
        }
        $user = $this->repo->find((string) ($_REQUEST['id'] ?? ''));
        if (!$user) {
            flash('error', 'Utilisateur introuvable.');
            redirect('index.php?action=users.list');
        }
        return [$type, $user];
    }

    private function confirmUrl(string $type, string $id): string
    {
        return 'index.php?action=mail.confirm&type=' . urlencode($type) . '&id=' . urlencode($id);
    }
}
