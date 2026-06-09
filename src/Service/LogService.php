<?php

namespace App\Service;

use App\Entity\LogAction;
use App\Entity\LogConnexion;
use App\Entity\Comment;
use App\Entity\LogMessage;
use App\Entity\LogSysteme;
use App\Entity\Ressource;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Service centralisé de journalisation applicative.
 *
 * Trois familles de logs :
 *  - connexion  : tentatives de login / logout
 *  - action     : actions utilisateurs sur les ressources (CRUD, modération, admin)
 *  - systeme    : événements techniques (erreurs JWT, mail, etc.)
 */
class LogService
{
    public function __construct(private readonly EntityManagerInterface $em) {}

    // ─────────────────────────────────────────────────
    //  Logs de connexion
    // ─────────────────────────────────────────────────

    /**
     * Enregistre une tentative / déconnexion.
     *
     * @param string       $statut  LogConnexion::STATUT_*
     * @param Request|null $request Pour extraire l'IP
     * @param User|null    $user    Utilisateur concerné (null si email inconnu)
     */
    public function logConnexion(string $statut, ?Request $request = null, ?User $user = null): void
    {
        $ip  = $request?->getClientIp();
        $log = new LogConnexion($statut, $ip, $user);
        $this->em->persist($log);
        $this->em->flush();
    }

    // ─────────────────────────────────────────────────
    //  Logs d'action
    // ─────────────────────────────────────────────────

    /**
     * Enregistre une action utilisateur.
     *
     * @param string        $action    LogAction::ACTION_*
     * @param User|null     $user      Auteur de l'action
     * @param Request|null  $request   Pour extraire l'IP
     * @param Ressource|null $ressource Ressource concernée (optionnel)
     * @param string|null   $details   Informations complémentaires
     */
    public function logAction(
        string    $action,
        ?User     $user      = null,
        ?Request  $request   = null,
        ?Ressource $ressource = null,
        ?string   $details   = null
    ): void {
        $ip  = $request?->getClientIp();
        $log = new LogAction($action, $user, $ressource, $details, $ip);
        $this->em->persist($log);
        $this->em->flush();
    }

    // ─────────────────────────────────────────────────
    //  Logs de message
    // ─────────────────────────────────────────────────

    /**
     * Enregistre une action sur un commentaire.
     *
     * @param string        $action  LogMessage::ACTION_*
     * @param User|null     $user    Auteur de l'action
     * @param Request|null  $request Pour extraire l'IP
     * @param Comment|null  $comment Commentaire concerné
     * @param string|null   $details Informations complémentaires
     */
    public function logMessage(
        string   $action,
        ?User    $user    = null,
        ?Request $request = null,
        ?Comment $comment = null,
        ?string  $details = null
    ): void {
        $ip  = $request?->getClientIp();
        $log = new LogMessage($action, $user, $comment, $details, $ip);
        $this->em->persist($log);
        $this->em->flush();
    }

    // ─────────────────────────────────────────────────
    //  Logs système
    // ─────────────────────────────────────────────────

    /**
     * Enregistre un événement technique.
     *
     * @param string      $niveau   LogSysteme::NIVEAU_*
     * @param string      $message  Description courte
     * @param string|null $contexte Contexte (classe::méthode, route, etc.)
     */
    public function logSysteme(string $niveau, string $message, ?string $contexte = null): void
    {
        $log = new LogSysteme($niveau, $message, $contexte);
        $this->em->persist($log);
        $this->em->flush();
    }

    // ─────────────────────────────────────────────────
    //  Raccourcis sémantiques
    // ─────────────────────────────────────────────────

    public function info(string $message, ?string $contexte = null): void
    {
        $this->logSysteme(LogSysteme::NIVEAU_INFO, $message, $contexte);
    }

    public function warning(string $message, ?string $contexte = null): void
    {
        $this->logSysteme(LogSysteme::NIVEAU_WARNING, $message, $contexte);
    }

    public function error(string $message, ?string $contexte = null): void
    {
        $this->logSysteme(LogSysteme::NIVEAU_ERROR, $message, $contexte);
    }

    public function critical(string $message, ?string $contexte = null): void
    {
        $this->logSysteme(LogSysteme::NIVEAU_CRITICAL, $message, $contexte);
    }
}
