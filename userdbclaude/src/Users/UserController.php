<?php

declare(strict_types=1);

namespace App\Users;

use App\Auth;

use function view;
use function redirect;
use function flash;
use function csrf_verify;
use function remember_old;
use function clear_old;

/**
 * Contrôleur des utilisateurs : liste, création, édition, suppression.
 * Les vues ne contiennent aucune requête SQL — tout passe par le repository.
 */
final class UserController
{
    public function __construct(private UserRepository $repo) {}

    /** Liste paginée + filtrée. */
    public function index(): void
    {
        Auth::requireLogin();

        $result = $this->repo->paginate([
            'search'   => $_GET['search']   ?? '',
            'criteria' => $_GET['criteria'] ?? 'nom',
            'letter'   => $_GET['letter']   ?? '',
            // Par défaut : les créations les plus récentes en haut.
            'sort'     => $_GET['sort']     ?? 'created_at',
            'dir'      => $_GET['dir']      ?? 'DESC',
            'page'     => (int) ($_GET['page'] ?? 1),
            'perPage'  => 25,
        ]);

        view('users_list', [
            'result'   => $result,
            'totalAll' => $this->repo->countAll(),
            'filters'  => $_GET,
        ], 'Liste des utilisateurs');
    }

    /** Formulaire de création. */
    public function createForm(): void
    {
        Auth::requireAdmin();
        view('user_form', ['mode' => 'create', 'user' => null], 'Nouvel utilisateur');
        clear_old();
    }

    /** Traitement de la création + e-mail de bienvenue. */
    public function store(): void
    {
        Auth::requireAdmin();
        csrf_verify();

        $uname = str_replace(' ', '', trim((string) ($_POST['uname'] ?? '')));
        if ($uname === '') {
            flash('error', "L'identifiant (Uname) ne peut pas être vide.");
            remember_old($_POST);
            redirect('index.php?action=users.new');
        }

        $userid = $this->repo->create($_POST);
        flash('success', "Utilisateur « " . ($_POST['nom'] ?? '') . " » créé.");

        // Envoi de l'e-mail de bienvenue (le mot de passe saisi part une fois en clair).
        $this->sendWelcome($_POST);

        clear_old();
        redirect('index.php?action=users.list');
    }

    /** Formulaire d'édition. */
    public function editForm(): void
    {
        Auth::requireAdmin();
        $user = $this->repo->find((string) ($_GET['id'] ?? ''));
        if (!$user) {
            flash('error', 'Utilisateur introuvable.');
            redirect('index.php?action=users.list');
        }
        view('user_form', ['mode' => 'edit', 'user' => $user], 'Modifier un utilisateur');
        clear_old();
    }

    /** Traitement de la mise à jour. */
    public function update(): void
    {
        Auth::requireAdmin();
        csrf_verify();

        $userid = (string) ($_POST['userid'] ?? '');
        if (!$this->repo->find($userid)) {
            flash('error', 'Utilisateur introuvable.');
            redirect('index.php?action=users.list');
        }

        $uname = str_replace(' ', '', trim((string) ($_POST['uname'] ?? '')));
        if ($uname === '') {
            flash('error', "L'identifiant (Uname) ne peut pas être vide.");
            remember_old($_POST);
            redirect('index.php?action=users.edit&id=' . urlencode($userid));
        }

        $this->repo->update($userid, $_POST);
        flash('success', "Utilisateur « " . ($_POST['nom'] ?? '') . " » mis à jour.");
        redirect('index.php?action=users.list');
    }

    /** Suppression (POST + CSRF + confirmation côté UI). */
    public function delete(): void
    {
        Auth::requireAdmin();
        csrf_verify();

        $userid = (string) ($_POST['userid'] ?? '');
        $user   = $this->repo->find($userid);
        if (!$user) {
            flash('error', 'Utilisateur introuvable.');
            redirect('index.php?action=users.list');
        }

        $this->repo->delete($userid);
        flash('success', "Utilisateur « " . ($user['nom'] ?? '') . " » supprimé.");
        redirect('index.php?action=users.list');
    }

    /** Envoie l'e-mail de bienvenue à la création. */
    private function sendWelcome(array $d): void
    {
        $info = parse_uname((string) ($d['uname'] ?? ''));
        $data = [
            'nom'        => (string) ($d['nom'] ?? ''),
            'poste'      => (string) ($d['poste'] ?? ''),
            'cemail'     => $info['cemail'],
            'mail_url'   => $info['mail_url'],
            'pwd'        => (string) ($d['pwd'] ?? ''),
            'otheremail' => strtolower(trim((string) ($d['oemail'] ?? $d['otheremail'] ?? ''))),
        ];

        try {
            \App\Mail\Mailer::sendToUser(
                'ECOWASMAIL ACCOUNT',
                \App\Mail\MailTemplates::accountDetails($data),
                [$data['cemail']],
                [$data['otheremail']]
            );
            flash('success', 'E-mail de bienvenue envoyé à ' . $data['cemail'] . '.');

            // Notification ECOLink si compte SAP.
            if (!empty($d['sap'])) {
                \App\Mail\Mailer::sendToEcolink(
                    'ECOWASMAIL ACCOUNT FOR ECOLINK',
                    \App\Mail\MailTemplates::ecolink($data),
                    $GLOBALS['config']['ecolink_officer']
                );
                flash('info', 'Notification ECOLink envoyée.');
            }
        } catch (\Throwable $e) {
            flash('error', "Compte créé mais l'e-mail n'a pas pu être envoyé : " . $e->getMessage());
        }
    }
}
