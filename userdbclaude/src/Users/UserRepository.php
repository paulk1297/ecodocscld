<?php

declare(strict_types=1);

namespace App\Users;

use PDO;

use function App\db;

/**
 * Accès aux données de la table `user`.
 *
 * Toutes les requêtes sont préparées et paramétrées. Les noms de colonnes
 * utilisés pour le tri/la recherche sont validés contre une liste blanche
 * (on ne peut pas paramétrer un identifiant SQL, donc on le contrôle).
 */
final class UserRepository
{
    private const TABLE = 'user';

    /** Colonnes autorisées pour le tri (anti-injection sur ORDER BY). */
    private const SORTABLE = [
        'userid', 'codeUser', 'nom', 'poste', 'uname', 'maj', 'dmaj', 'created_at', 'sap',
    ];

    /** Critères autorisés pour la recherche textuelle. */
    private const SEARCHABLE = ['nom', 'poste', 'uname', 'instidu', 'codeUser'];

    /**
     * Liste paginée + filtrée d'utilisateurs.
     *
     * @param array{
     *   search?:string, criteria?:string, letter?:string,
     *   sort?:string, dir?:string, page?:int, perPage?:int
     * } $opts
     * @return array{rows:array<int,array>, total:int, page:int, perPage:int, pages:int}
     */
    public function paginate(array $opts): array
    {
        $perPage = max(1, (int) ($opts['perPage'] ?? 25));
        $page    = max(1, (int) ($opts['page'] ?? 1));
        $offset  = ($page - 1) * $perPage;

        [$where, $params] = $this->buildFilter($opts);

        $sort = in_array($opts['sort'] ?? '', self::SORTABLE, true) ? $opts['sort'] : 'nom';
        $dir  = strtoupper($opts['dir'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';

        // Total filtré (pour la pagination).
        $countStmt = db()->prepare('SELECT COUNT(*) FROM ' . self::TABLE . $where);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        // Page de résultats. LIMIT/OFFSET liés en entiers.
        $sql = 'SELECT * FROM ' . self::TABLE . $where
             . " ORDER BY `$sort` $dir LIMIT :limit OFFSET :offset";
        $stmt = db()->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'rows'    => $stmt->fetchAll(),
            'total'   => $total,
            'page'    => $page,
            'perPage' => $perPage,
            'pages'   => (int) ceil($total / $perPage),
        ];
    }

    /** Nombre total d'enregistrements (non filtré) — pour l'affichage [x/total]. */
    public function countAll(): int
    {
        return (int) db()->query('SELECT COUNT(*) FROM ' . self::TABLE)->fetchColumn();
    }

    /** Construit la clause WHERE et ses paramètres à partir des filtres. */
    private function buildFilter(array $opts): array
    {
        $letter   = trim((string) ($opts['letter'] ?? ''));
        $search   = trim((string) ($opts['search'] ?? ''));
        $criteria = in_array($opts['criteria'] ?? '', self::SEARCHABLE, true)
            ? $opts['criteria'] : 'nom';

        // Filtre alphabétique A..Z (sur le nom).
        if ($letter !== '' && ctype_alpha($letter) && strlen($letter) === 1) {
            return [' WHERE nom LIKE :letter', [':letter' => $letter . '%']];
        }

        // Recherche textuelle « contient » sur le critère choisi.
        if ($search !== '') {
            return [" WHERE `$criteria` LIKE :search", [':search' => '%' . $search . '%']];
        }

        return ['', []];
    }

    /** Récupère un utilisateur par sa clé primaire `userid`. */
    public function find(string $userid): ?array
    {
        $stmt = db()->prepare('SELECT * FROM ' . self::TABLE . ' WHERE userid = ? LIMIT 1');
        $stmt->execute([$userid]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Crée un utilisateur. Le mot de passe en clair est hashé ; on ne stocke
     * jamais le clair (l'admin le pose lui-même sur le serveur mail distant).
     *
     * @return string userid généré
     */
    public function create(array $d): string
    {
        // codeUser est toujours généré côté serveur (unique, non saisi).
        $d['codeUser'] = $this->generateUniqueCode();

        // Les colonnes cit300/cit400/usb/wifi/intercom/position ne sont plus
        // exposées : on les renseigne avec des valeurs par défaut pour rester
        // compatible avec un schéma NOT NULL, sans les piloter depuis l'UI.
        $sql = 'INSERT INTO ' . self::TABLE . '
            (codeUser, instidu, nom, poste, uname, pwd_hash, otheremail,
             cit300, cit400, usb, wifi, intercom, obs, maj, dmaj, created_at,
             position, commission, dept, sap)
            VALUES
            (:codeUser, :instidu, :nom, :poste, :uname, :pwd_hash, :otheremail,
             0, 0, 0, 0, \'\', :obs, :maj, :dmaj, :created_at,
             \'\', :commission, :dept, :sap)';

        $stmt = db()->prepare($sql);
        $stmt->execute($this->bindValues($d));

        return db()->lastInsertId();
    }

    /**
     * Génère un code utilisateur court et unique (ex. "ECW-7F3A9C").
     * Réessaie en cas de collision (extrêmement rare).
     */
    public function generateUniqueCode(): string
    {
        $check = db()->prepare('SELECT 1 FROM ' . self::TABLE . ' WHERE codeUser = ? LIMIT 1');
        do {
            $code = generate_user_code();
            $check->execute([$code]);
        } while ($check->fetchColumn() !== false);

        return $code;
    }

    /** Vérifie l'existence d'un codeUser (utile pour l'affichage/validation). */
    public function codeExists(string $code): bool
    {
        $stmt = db()->prepare('SELECT 1 FROM ' . self::TABLE . ' WHERE codeUser = ? LIMIT 1');
        $stmt->execute([$code]);
        return $stmt->fetchColumn() !== false;
    }

    /** Met à jour un utilisateur existant (le mot de passe est géré à part). */
    public function update(string $userid, array $d): void
    {
        $sql = 'UPDATE ' . self::TABLE . ' SET
            instidu = :instidu, nom = :nom, poste = :poste, uname = :uname,
            otheremail = :otheremail, obs = :obs, maj = :maj,
            commission = :commission, dept = :dept, sap = :sap
            WHERE userid = :userid';

        $params = $this->bindValues($d);
        // pwd_hash, dmaj, codeUser et created_at ne sont pas modifiables via update().
        unset($params[':pwd_hash'], $params[':dmaj'], $params[':codeUser'], $params[':created_at']);
        $params[':userid'] = $userid;

        $stmt = db()->prepare($sql);
        $stmt->execute($params);
    }

    /** Met à jour uniquement le hash du mot de passe (resend/reset). */
    public function updatePassword(string $userid, string $plainPassword): void
    {
        $stmt = db()->prepare('UPDATE ' . self::TABLE . ' SET pwd_hash = ? WHERE userid = ?');
        $stmt->execute([password_hash($plainPassword, PASSWORD_DEFAULT), $userid]);
    }

    public function delete(string $userid): void
    {
        $stmt = db()->prepare('DELETE FROM ' . self::TABLE . ' WHERE userid = ?');
        $stmt->execute([$userid]);
    }

    /** Normalise les paramètres d'INSERT/UPDATE (valeurs par défaut sûres). */
    private function bindValues(array $d): array
    {
        return [
            ':codeUser'   => (string) ($d['codeUser'] ?? ''),
            ':instidu'    => (string) ($d['instidu'] ?? ''),
            ':nom'        => (string) ($d['nom'] ?? ''),
            ':poste'      => (string) ($d['poste'] ?? ''),
            ':uname'      => str_replace(' ', '', (string) ($d['uname'] ?? '')),
            ':pwd_hash'   => isset($d['pwd']) && $d['pwd'] !== ''
                                ? password_hash((string) $d['pwd'], PASSWORD_DEFAULT) : '',
            ':otheremail' => strtolower(trim((string) ($d['otheremail'] ?? ''))),
            ':obs'        => (string) ($d['obs'] ?? ''),
            ':maj'        => (int) ($d['maj'] ?? 0),
            ':dmaj'       => dmaj_today(),
            ':created_at' => date('Y-m-d H:i:s'),
            ':commission' => (string) ($d['commission'] ?? ''),
            ':dept'       => (string) ($d['dept'] ?? ''),
            ':sap'        => (int) ($d['sap'] ?? 0),
        ];
    }
}
