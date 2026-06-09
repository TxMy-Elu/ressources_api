<?php

namespace App\Controller\Api;

use App\Entity\Category;
use App\Entity\LogAction;
use App\Entity\Ressource;
use App\Entity\Comment;
use App\Entity\User;
use App\Form\ResourceType;
use App\Repository\LogActionRepository;
use App\Repository\RessourceRepository;
use App\Service\LogService;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Attribute\Security;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/api/resources')]
final class ResourceController extends AbstractController
{
    #[Route('/download/{filename}', name: 'api_resources_download', methods: ['GET'], requirements: ['filename' => '.+'])]
    public function download(Request $request, string $filename): Response
    {
        $dirs = [
            $this->getParameter('media_directory'),
            $this->getParameter('resource_directory'),
        ];

        $path = null;
        foreach ($dirs as $dir) {
            $candidate = $dir . '/' . $filename;
            if (file_exists($candidate)) {
                $path = $candidate;
                break;
            }
        }

        if (!$path) {
            return $this->json(['error' => 'Fichier introuvable'], Response::HTTP_NOT_FOUND);
        }

        // Nom affiché : supprime le préfixe uniqid_ ajouté au stockage
        // Format stocké : {uniqid}_{nomOriginal}.ext  →  affiche nomOriginal.ext
        // Ancien format  : {nomSlugifié}-{uniqid}.ext →  affiche le nom tel quel
        $displayName = str_contains($filename, '_')
            ? substr($filename, strpos($filename, '_') + 1)
            : $filename;

        $content  = file_get_contents($path);
        $mimeType = mime_content_type($path) ?: 'application/octet-stream';
        $origin   = $request->headers->get('Origin', '*');

        $response = new Response($content);
        $response->headers->set('Content-Type', $mimeType);
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $displayName . '"');
        $response->headers->set('Content-Length', (string) strlen($content));
        $response->headers->set('Access-Control-Allow-Origin', $origin);
        $response->headers->set('Access-Control-Allow-Credentials', 'true');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Authorization, Content-Type');

        return $response;
    }

    //Fonction qui liste tous les resources
    #[Route('', name: 'api_resources_list', methods: ['GET'])]
    #[OA\Get(
        path: "/api/resources",
        description: 'Retourne toutes les ressources publiées et visible uniquement. Accessible sans authentification.',
        summary: 'Lister les ressources',
    )]
    #[OA\Parameter(
        name: "categories",
        description: "Filtrer par nom de catégories",
        in: "query",
        required: false,
        schema: new OA\Schema(type: "string", example: 'Famille'),
    )]
    #[OA\Parameter(
        name: "type",
        description: "Filtrer par type de catégories",
        in: "query",
        required: false,
        schema: new OA\Schema(type: "string", enum: ['article', 'video', 'podcast', 'activite', 'jeu'])
    )]
    #[OA\Parameter(
        name: "sort",
        description: "Tri",
        in: "query",
        required: false,
        schema: new OA\Schema(type: "string", default: 'created_at', enum: ['created_at', 'titre', 'date_publication'])
    )]
    #[OA\Parameter(
        name: "order",
        description: "Ordre de tri",
        in: "query",
        required: false,
        schema: new OA\Schema(type: "string", default: 'DESC', enum: ['ASC', 'DESC'])
    )]
    #[OA\Response(
        response: 200,
        description: 'Liste des ressources',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(
                properties: [
                    new OA\Property(property: 'id', type: 'integer', example: 1),
                    new OA\Property(property: 'titre', type: 'string', example: 'Mieux communiquer en famille'),
                    new OA\Property(property: 'description', type: 'string', example: 'Example'),
                    new OA\Property(property: 'type_ressource', type: 'string', example: 1),
                    new OA\Property(property: 'statut', type: 'string', example: 'article'),
                    new OA\Property(property: 'visibilite', type: 'string', example: 'public'),
                    new OA\Property(property: 'created_at', type: 'string', example: "2024-02-15 10:30:00"),
                    new OA\Property(property: 'date_publication', type: 'string', example: '2024-02-16 09:00:00'),
                    new OA\Property(property: 'createur', type: 'string', example: "Dupont"),
                    new OA\Property(property: 'category', type: 'string', example: 'Famille'),
                ]
            )
        )
    )]
    public function list(Request $request, RessourceRepository $repo): JsonResponse
    {
        $category = $request->query->get('category');
        $type = $request->query->get('type');
        $sort = $request->query->get('sort', 'dateCreation');
        $order = strtoupper($request->query->get('order', 'DESC'));

        $qb = $repo->createQueryBuilder('r')
            ->leftJoin('r.category', 'c')
            ->addSelect('c')
            ->where('r.statut = :statut')
            ->andWhere('r.visibilite = :visibilite')
            ->setParameter('statut', Ressource::STATUS_PUBLISHED)
            ->setParameter('visibilite', Ressource::VISIBILITE_PUBLIC);

        if ($category) {
            $qb->andWhere('c.nom = :category')->setParameter('category', $category);
        }
        if ($type) {
            $qb->andWhere('r.type_ressource = :type')->setParameter('type', $type);
        }

        $sortMap = ['dateCreation' => 'created_at', 'titre' => 'titre', 'datePublication' => 'date_publication'];
        $sortField = $sortMap[$sort] ?? 'created_at';
        $qb->orderBy('r.' . $sortField, $order === 'ASC' ? 'ASC' : 'DESC');

        $resources = $qb->getQuery()->getResult();

        $data = array_map(fn($r) => [
            'id' => $r->getId(),
            'titre' => $r->getTitre(),
            'description' => $r->getDescription(),
            'type_ressource' => $r->getTypeRessource(),
            'statut' => $r->getStatut(),
            'visibilite' => $r->getVisibilite(),
            'created_at' => $r->getCreatedAt()?->format('Y-m-d H:i:s'),
            'datePublication' => $r->getDatePublication()?->format('Y-m-d H:i:s'),
            'createur' => $r->getCreateur()
                ? $r->getCreateur()->getFirstname() . ' ' . $r->getCreateur()->getLastname()
                : null,
            'category' => $r->getCategory()?->getNom(),
        ], $resources);

        return $this->json($data);
    }

    //Fonction pour tester la creation d'une ressource
    #[Route('/new', name: 'resource_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        $resource = new Ressource();
        $form = $this->createForm(ResourceType::class, $resource);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $media = $form->get('media')->getData();

            if ($media) {
                $originalFilename = pathinfo($media->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$media->guessExtension();

                try {
                    $media->move(
                        $this->getParameter('resource_directory'),
                        $newFilename
                    );
                    $resource->setMedia($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Une erreur est survenue lors de l\'upload de votre fichier');
                    return $this->render('resource/new.html.twig', ['form' => $form->createView()]);
                }
            }

            $user = $em->getRepository(\App\Entity\User::class)->findOneBy(['email' => 'admin@resources.fr']);
            $resource->setCreateur($user);
            //Remmettre cette ligne une fois que le système de connexion est mis en place puis supprimer les 2 lignes precedents || mettre en commentaire
            //$resource->setCreateur($this->getUser());

            // Toute création passe en attente, quelle que soit la visibilité
            $resource->setStatut(Ressource::STATUS_PENDING);
            $this->addFlash('info', 'Ressource soumise, en attente de validation.');

            $resource->setDatePublication(new \DateTimeImmutable());
            $resource->setEstPrivee(false);
            $resource->setEstVerifie(false);

            $em->persist($resource);
            $em->flush();

            return $this->redirectToRoute('resource_new');
        }

        return $this->render('resource/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    // ── Utilisateur : liste ses propres ressources ──
    #[Route('/mine', name: 'api_resources_mine', methods: ['GET'])]
    public function mine(RessourceRepository $repo, LogActionRepository $logActionRepo): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_CONNECTED');

        $user = $this->getUser();
        $resources = $repo->createQueryBuilder('r')
            ->leftJoin('r.category', 'c')->addSelect('c')
            ->where('r.createur = :user')
            ->setParameter('user', $user)
            ->orderBy('r.created_at', 'DESC')
            ->getQuery()->getResult();

        // Comptage des vues depuis log_action (action = resource_view)
        $ids     = array_map(fn($r) => $r->getId(), $resources);
        $viewMap = $logActionRepo->countViewsByRessource($ids);

        $data = array_map(fn($r) => [
            'id'             => $r->getId(),
            'titre'          => $r->getTitre(),
            'description'    => $r->getDescription(),
            'contenu'        => $r->getContenu(),
            'type_ressource' => $r->getTypeRessource(),
            'statut'         => $r->getStatut(),
            'visibilite'     => $r->getVisibilite(),
            'shareToken'     => $r->getVisibilite() === Ressource::VISIBILITE_SHARED ? $r->getLien() : null,
            'created_at'     => $r->getCreatedAt()?->format('Y-m-d H:i:s'),
            'category'       => $r->getCategory()?->getNom(),
            'categoryId'     => $r->getCategory()?->getId(),
            'lien'           => $r->getVisibilite() !== Ressource::VISIBILITE_SHARED ? $r->getLien() : null,
            'media'          => $r->getMedia(),
            'views'          => $viewMap[$r->getId()] ?? 0,
        ], $resources);

        return $this->json($data);
    }

    // ── Admin : liste toutes les ressources sans filtre de statut/visibilité ──
    #[Route('/admin', name: 'api_resources_admin_list', methods: ['GET'])]
    public function adminList(RessourceRepository $repo): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $resources = $repo->createQueryBuilder('r')
            ->leftJoin('r.category', 'c')->addSelect('c')
            ->leftJoin('r.createur', 'u')->addSelect('u')
            ->orderBy('r.created_at', 'DESC')
            ->getQuery()->getResult();

        $data = array_map(fn($r) => [
            'id'            => $r->getId(),
            'titre'         => $r->getTitre(),
            'description'   => $r->getDescription(),
            'contenu'       => $r->getContenu(),
            'type_ressource'=> $r->getTypeRessource(),
            'statut'        => $r->getStatut(),
            'visibilite'    => $r->getVisibilite(),
            'created_at'    => $r->getCreatedAt()?->format('Y-m-d H:i:s'),
            'datePublication'=> $r->getDatePublication()?->format('Y-m-d H:i:s'),
            'createur'      => $r->getCreateur()
                ? $r->getCreateur()->getFirstname() . ' ' . $r->getCreateur()->getLastname()
                : null,
            'category'   => $r->getCategory()?->getNom(),
            'categoryId' => $r->getCategory()?->getId(),
            'lien'       => $r->getLien(),
            'media'      => $r->getMedia(),
        ], $resources);

        return $this->json($data);
    }

    #[Route('/share/{token}', name: 'api_resources_share', methods: ['GET'])]
    public function showByShareToken(string $token, RessourceRepository $repo): JsonResponse
    {
        $resource = $repo->findOneBy(['lien' => $token]);

        if (!$resource || $resource->getVisibilite() !== Ressource::VISIBILITE_SHARED) {
            return $this->json(['error' => 'Lien de partage invalide ou expiré'], Response::HTTP_NOT_FOUND);
        }

        return $this->json([
            'id'             => $resource->getId(),
            'titre'          => $resource->getTitre(),
            'description'    => $resource->getDescription(),
            'contenu'        => $resource->getContenu(),
            'type'           => $resource->getTypeRessource(),
            'type_ressource' => $resource->getTypeRessource(),
            'statut'         => $resource->getStatut(),
            'visibilite'     => $resource->getVisibilite(),
            'created_at'     => $resource->getCreatedAt()?->format('Y-m-d H:i:s'),
            'createur'       => $resource->getCreateur()
                ? $resource->getCreateur()->getFirstname() . ' ' . $resource->getCreateur()->getLastname()
                : null,
            'category'       => $resource->getCategory()?->getNom(),
            'lien'           => null,
            'media'          => $resource->getMedia(),
        ]);
    }

    #[Route('/{id}', name: 'api_resources_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    #[OA\Get(
        path: "/api/resources/{id}",
        description: 'Retourne le contenu complet d\'une ressource. Les ressources privés sont accessible uniquement par leurs créateur',
        summary: 'Affiche détails d\'une ressources.',
    )]
    #[OA\Parameter(
        name: "id",
        description: "Id ressource",
        in: "path",
        required: true,
        schema: new OA\Schema(type: "integer", example: 1),
    )]
    #[OA\Response(
        response: 200,
        description: 'Detail de la ressources',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'id', type: 'integer', example: 1),
                new OA\Property(property: 'titre', type: 'string', example: 'Mieux communiquer en famille'),
                new OA\Property(property: 'description', type: 'string', example: 'Example'),
                new OA\Property(property: 'contenu', type: 'string', example: 'Example 123'),
                new OA\Property(property: 'type_ressource', type: 'string', example: 1),
                new OA\Property(property: 'statut', type: 'string', example: 'article'),
                new OA\Property(property: 'visibilite', type: 'string', example: 'public'),
                new OA\Property(property: 'media', type: 'string', example: 'audio.mp4'),
                new OA\Property(property: 'created_at', type: 'string', example: "2024-02-15 10:30:00"),
                new OA\Property(property: 'date_publication', type: 'string', example: '2024-02-16 09:00:00'),
                new OA\Property(property: 'createur', type: 'string', example: "Dupont"),
                new OA\Property(property: 'category', type: 'string', example: 'Famille'),
            ]
        )
    )]
    #[OA\Response(response: 403, description: 'Accès refusé — ressource privée')]
    #[OA\Response(response: 404, description: 'Ressource introuvable')]
    public function show(int $id, Request $request, RessourceRepository $repo, LogService $logger): JsonResponse
    {
        $resource = $repo->find($id);

        if(!$resource) {
            return $this->json(['error' => 'resource not found'], Response::HTTP_NOT_FOUND);
        }

        //Condition ou seulement le créateur de la ressource peut voir (privé)
        if($resource->getVisibilite() === Ressource::VISIBILITE_PRIVATE) {
            $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
            if($resource->getCreateur() !== $this->getUser()) {
                return $this->json(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
            }
        }

        // Tracer la consultation uniquement pour les utilisateurs connectés
        /** @var User|null $currentUser */
        $currentUser = $this->getUser();
        if ($currentUser instanceof User) {
            $logger->logAction(
                LogAction::ACTION_RESOURCE_VIEW,
                $currentUser,
                $request,
                $resource,
                "Consultation ressource #{$id} « {$resource->getTitre()} »"
            );
        }

        return $this->json([
            'id'          => $resource->getId(),
            'titre'       => $resource->getTitre(),
            'description' => $resource->getDescription(),
            'contenu'     => $resource->getContenu(),
            'type'        => $resource->getTypeRessource(),
            'type_ressource' => $resource->getTypeRessource(),
            'statut'      => $resource->getStatut(),
            'visibilite'  => $resource->getVisibilite(),
            'shareToken'   => $resource->getVisibilite() === Ressource::VISIBILITE_SHARED ? $resource->getLien() : null,
            'created_at'   => $resource->getCreatedAt()?->format('Y-m-d H:i:s'),
            'dateCreation' => $resource->getCreatedAt()?->format('Y-m-d H:i:s'),
            'createur'     => $resource->getCreateur()
                ? $resource->getCreateur()->getFirstname() . ' ' . $resource->getCreateur()->getLastname()
                : null,
            'category'   => $resource->getCategory()?->getNom(),
            'categoryId' => $resource->getCategory()?->getId(),
            'lien'       => $resource->getVisibilite() !== Ressource::VISIBILITE_SHARED ? $resource->getLien() : null,
            'media'      => $resource->getMedia(),
        ]);
    }

    #[Route('', name: 'api_resources_create', methods: ['POST'])]
    #[OA\Post(
        path: '/api/resources',
        description: 'Crée une nouvelle ressource. Si la visibilité est "public", la ressource passe en statut "en attente" et doit être validée par un modérateur avant publication. Nécessite un token JWT.',
        summary: 'Créer une ressource',
    )]
    #[Security(name: 'Bearer')]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['titre'],
                properties: [ new OA\Property(property: 'titre', type: 'string', example: 'Mieux communiquer en famille'),
                new OA\Property(property: 'description', type: 'string', example: 'Guide pratique...'),
                new OA\Property(property: 'contenu', type: 'string', example: 'Contenu complet...'),
                new OA\Property(property: 'type', type: 'string', enum: ['article', 'video', 'podcast', 'activite', 'jeu'], example: 'article'),
                new OA\Property(property: 'visibilite', type: 'string', enum: ['public', 'partage', 'prive'], example: 'public'),
                new OA\Property(property: 'categoryId', type: 'integer', example: 1), ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: 'Ressource créée',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'Ressource créée avec succès'),
                new OA\Property(property: 'id', type: 'integer', example: 1),
                new OA\Property(property: 'statut', type: 'string', example: 'en_attente'),
                ]
        )
    )]
    #[OA\Response(response: 400, description: 'Titre manquant')]
    #[OA\Response(response: 401, description: 'Token JWT manquant ou invalide')]
    public function create(Request $request, EntityManagerInterface $em, LogService $logger, SluggerInterface $slugger): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_CONNECTED');

        $contentType = $request->headers->get('Content-Type', '');
        $isMultipart = str_contains($contentType, 'multipart/form-data');
        $data = $isMultipart
            ? $request->request->all()
            : (json_decode($request->getContent(), true) ?? []);

        if(empty($data['titre'])) {
            return $this->json(['error' => 'le titre est requis'], Response::HTTP_BAD_REQUEST);
        }

        $resource = new Ressource();
        $resource->setTitre($data['titre']);
        $resource->setDescription($data['description'] ?? null);
        $resource->setContenu($data['contenu'] ?? null);
        $resource->setTypeRessource($data['type'] ?? 'article');
        $resource->setCreateur($this->getUser());

        // Normalisation : le frontend envoie 'public'/'partage'/'private',
        // les constantes de l'entité peuvent différer ('publie', etc.)
        $visibiliteRaw = $data['visibilite'] ?? 'public';
        $visibiliteMap = [
            'public'  => Ressource::VISIBILITE_PUBLIC,
            'partage' => Ressource::VISIBILITE_SHARED,
            'private' => Ressource::VISIBILITE_PRIVATE,
            'prive'   => Ressource::VISIBILITE_PRIVATE,
        ];
        $visibilite = $visibiliteMap[$visibiliteRaw] ?? $visibiliteRaw;
        $resource->setVisibilite($visibilite);
        $resource->setDatePublication(new \DateTimeImmutable());
        $resource->setEstPrivee(false);
        $resource->setEstVerifie(false);

        if ($visibilite === Ressource::VISIBILITE_SHARED) {
            // lien stocke le token pour les ressources partagées
            $resource->setLien(bin2hex(random_bytes(32)));
        } elseif (!empty($data['lien'])) {
            $resource->setLien($data['lien']);
        }

        // Média : upload fichier (PDF) ou URL (vidéo, podcast)
        $mediaFile = $request->files->get('media');
        if ($mediaFile) {
            $safeFilename = $slugger->slug(pathinfo($mediaFile->getClientOriginalName(), PATHINFO_FILENAME));
            $ext = $mediaFile->guessExtension() ?? 'bin';
            // Format : {uniqid}_{nomOriginalSlugifié}.{ext}
            // Le _ permet d'extraire le nom d'affichage côté download
            $newFilename = uniqid() . '_' . $safeFilename . '.' . $ext;
            try {
                $mediaFile->move($this->getParameter('media_directory'), $newFilename);
                $resource->setMedia($newFilename);
            } catch (FileException $e) {
                return $this->json(['error' => 'Erreur lors de l\'upload du fichier média'], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        } elseif (!empty($data['media'])) {
            $resource->setMedia($data['media']);
        }

        // Statut : toute création passe en "en attente" (validation admin requise)
        // Seul un admin peut forcer un statut différent à la création
        if (!empty($data['statut']) && $this->isGranted('ROLE_ADMIN')) {
            $resource->setStatut($data['statut']);
        } else {
            $resource->setStatut(Ressource::STATUS_PENDING);
        }

        if (!empty($data['categoryId'])) {
            $category = $em->getRepository(Category::class)->find($data['categoryId']);
            if ($category) { $resource->setCategory($category); }
        }

        // Champs obligatoires en base
        $resource->setDatePublication(new \DateTimeImmutable());
        $resource->setEstPrivee($visibilite === Ressource::VISIBILITE_PRIVATE);
        $resource->setEstVerifie(false);

        $em->persist($resource);
        $em->flush();

        /** @var User|null $currentUser */
        $currentUser = $this->getUser();
        $logger->logAction(
            LogAction::ACTION_RESOURCE_CREATE,
            $currentUser instanceof User ? $currentUser : null,
            $request,
            $resource,
            "Ressource #{$resource->getId()} « {$resource->getTitre()} » créée (statut: {$resource->getStatut()})"
        );

        return $this->json([
            'message'    => 'Ressource crée avec succès',
            'id'         => $resource->getId(),
            'statut'     => $resource->getStatut(),
            'shareToken' => $visibilite === Ressource::VISIBILITE_SHARED ? $resource->getLien() : null,
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_resources_update', methods: ['PUT'], requirements: ['id' => '\d+'])]
    #[OA\Put(
        path: '/api/resources/{id}',
        description: 'Modifie une ressource existante. Seul le créateur ou un administrateur peut modifier. Nécessite un token JWT.',
        summary: 'Modifier une ressource',
    )]
    #[Security(name: 'Bearer')]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\RequestBody(
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'titre', type: 'string', example: 'Nouveau titre'),
                new OA\Property(property: 'description', type: 'string', example: 'Nouvelle description'),
                new OA\Property(property: 'contenu', type: 'string', example: 'Nouveau contenu'),
                new OA\Property(property: 'type', type: 'string', example: 'video'),
                new OA\Property(property: 'visibilite', type: 'string', example: 'prive'),
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Ressource mise à jour')]
    #[OA\Response(response: 401, description: 'Token JWT manquant ou invalide')]
    #[OA\Response(response: 403, description: 'Accès refusé')]
    #[OA\Response(response: 404, description: 'Ressource introuvable')]
    public function update(int $id, Request $request, EntityManagerInterface $em, RessourceRepository $repo, LogService $logger): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_CONNECTED');

        $resource = $repo->find($id);
        if(!$resource) {
            return $this->json(['error' => 'resource not found'], Response::HTTP_NOT_FOUND);
        }

        if ($resource->getCreateur() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);

        if (!empty($data['titre']))       { $resource->setTitre($data['titre']); }
        if (isset($data['description']))  { $resource->setDescription($data['description']); }
        if (isset($data['contenu']))      { $resource->setContenu($data['contenu']); }
        if (!empty($data['type']))        { $resource->setTypeRessource($data['type']); }
        if (!empty($data['visibilite'])) {
            // Normalisation identique à la création
            $visibiliteMap = [
                'public'  => Ressource::VISIBILITE_PUBLIC,
                'partage' => Ressource::VISIBILITE_SHARED,
                'private' => Ressource::VISIBILITE_PRIVATE,
                'prive'   => Ressource::VISIBILITE_PRIVATE,
            ];
            $oldVis = $resource->getVisibilite();
            $newVis = $visibiliteMap[$data['visibilite']] ?? $data['visibilite'];
            $resource->setVisibilite($newVis);

            if ($newVis === Ressource::VISIBILITE_SHARED && $oldVis !== Ressource::VISIBILITE_SHARED) {
                // Passage à "partagée" : générer un token dans lien
                $resource->setLien(bin2hex(random_bytes(32)));
            } elseif ($newVis !== Ressource::VISIBILITE_SHARED && $oldVis === Ressource::VISIBILITE_SHARED) {
                // Quitte "partagée" : effacer le token
                $resource->setLien(null);
            }
        }
        // Mise à jour du lien uniquement pour les ressources non-partagées
        if ($resource->getVisibilite() !== Ressource::VISIBILITE_SHARED && array_key_exists('lien', $data)) {
            $resource->setLien($data['lien'] ?: null);
        }

        // Statut : toute modification repasse en "en attente" (re-validation admin)
        // Exception : un admin peut forcer un statut précis via le champ 'statut'
        if ($this->isGranted('ROLE_ADMIN') && !empty($data['statut'])) {
            $allowed = [Ressource::STATUS_DRAFT, Ressource::STATUS_PENDING, Ressource::STATUS_PUBLISHED, Ressource::STATUS_SUSPENDED];
            if (in_array($data['statut'], $allowed, true)) {
                $resource->setStatut($data['statut']);
            }
        } else {
            // Non-admin ou admin sans statut explicite → retour en attente
            $resource->setStatut(Ressource::STATUS_PENDING);
        }

        // Catégorie
        if (isset($data['categoryId'])) {
            $category = $em->getRepository(Category::class)->find($data['categoryId']);
            if ($category) { $resource->setCategory($category); }
        }

        $em->flush();

        /** @var User|null $currentUser */
        $currentUser = $this->getUser();
        $logger->logAction(
            LogAction::ACTION_RESOURCE_UPDATE,
            $currentUser instanceof User ? $currentUser : null,
            $request,
            $resource,
            "Ressource #{$id} « {$resource->getTitre()} » mise à jour"
        );

        return $this->json([
            'message'    => 'Ressource mise à jour',
            'categoryId' => $resource->getCategory()?->getId(),
            'category'   => $resource->getCategory()?->getNom(),
            'statut'     => $resource->getStatut(),
            'shareToken' => $resource->getVisibilite() === Ressource::VISIBILITE_SHARED ? $resource->getLien() : null,
        ]);
    }

    #[Route('/{id}', name: 'api_resources_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    #[OA\Delete(
        path: '/api/resources/{id}',
        description: 'Supprime définitivement une ressource. Réservé aux administrateurs. Nécessite un token JWT.',
        summary: 'Supprimer une ressource',
    )]
    #[Security(name: 'Bearer')]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(response: 200, description: 'Ressource supprimée')]
    #[OA\Response(response: 401, description: 'Token JWT manquant ou invalide')]
    #[OA\Response(response: 403, description: 'Accès refusé — Admin requis')]
    #[OA\Response(response: 404, description: 'Ressource introuvable')]
    public function delete(int $id, Request $request, EntityManagerInterface $em, RessourceRepository $repo, LogService $logger): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $resource = $repo->find($id);
        if(!$resource) {
            return $this->json(['error' => 'resource not found'], Response::HTTP_NOT_FOUND);
        }

        $titre = $resource->getTitre();

        /** @var User|null $currentUser */
        $currentUser = $this->getUser();
        $logger->logAction(
            LogAction::ACTION_RESOURCE_DELETE,
            $currentUser instanceof User ? $currentUser : null,
            $request,
            null, // ressource supprimée, pas de FK conservée
            "Ressource #{$id} « {$titre} » supprimée définitivement"
        );

        $em->remove($resource);
        $em->flush();

        return $this->json(['message' => 'Ressource supprimée']);
    }

    #[Route('/{id}/comments', name: 'api_resources_comments', methods: ['GET'])]
    public function getComments(int $id, Request $request, RessourceRepository $repo): JsonResponse
    {
        $resource = $repo->find($id);
        if (!$resource) {
            return $this->json(['error' => 'Ressource non trouvée'], 404);
        }

        $page = max(1, (int) $request->query->get('page', 1));
        $limit = min(20, max(1, (int) $request->query->get('limit', 20)));

        $allComments = $resource->getComments();
        $total = $allComments->count();
        $comments = $allComments->slice(($page - 1) * $limit, $limit);

        $data = array_map(fn($c) => [
            'id' => $c->getId(),
            'author' => $c->getAuthor()
                ? $c->getAuthor()->getFirstname() . ' ' . $c->getAuthor()->getLastname()
                : 'Utilisateur supprimé',
            'content' => $c->getContent(),
            'rating' => $c->getRating(),
            'createdAt' => $c->getCreatedAt()?->format('Y-m-d H:i:s') ?? ''
        ], $comments);

        return $this->json([
            'data' => $data,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => (int) ceil($total / $limit) ?: 1
            ]
        ]);
    }

    #[Route('/{id}/comments', name: 'api_resources_add_comment', methods: ['POST'])]
    public function addComment(int $id, Request $request, RessourceRepository $repo, EntityManagerInterface $em, LogService $logger): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_CONNECTED');

        $resource = $repo->find($id);
        if (!$resource) {
            return $this->json(['error' => 'Ressource non trouvée'], 404);
        }

        $data = json_decode($request->getContent(), true);
        if (empty($data['content'])) {
            return $this->json(['error' => 'Contenu requis'], 400);
        }

        $comment = new Comment();
        $comment->setContent($data['content']);
        $comment->setRating($data['rating'] ?? 5);
        $comment->setAuthor($this->getUser());
        $comment->setResource($resource);

        $em->persist($comment);
        $em->flush();

        /** @var User|null $currentUser */
        $currentUser = $this->getUser();
        $logger->logMessage(
            \App\Entity\LogMessage::ACTION_MESSAGE_SEND,
            $currentUser instanceof User ? $currentUser : null,
            $request,
            $comment,
            "Commentaire #{$comment->getId()} ajouté sur ressource #{$id} « {$resource->getTitre()} »"
        );

        return $this->json(['message' => 'Commentaire créé', 'id' => $comment->getId()], 201);
    }

    #[Route('/favorites', name: 'api_resources_favorites', methods: ['GET'])]
    public function favorites(): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_CONNECTED');

        /** @var User $user */
        $user = $this->getUser();

        $data = $user->getFavorites()->map(fn($r) => [
            'id'          => $r->getId(),
            'titre'       => $r->getTitre(),
            'description' => $r->getDescription(),
            'type'        => $r->getTypeRessource(),
            'category'    => $r->getCategory()?->getNom(),
            'media'       => $r->getMedia(),
            'lien'        => $r->getLien(),
            'statut'      => $r->getStatut(),
            'created_at'  => $r->getCreatedAt()?->format('Y-m-d'),
        ])->toArray();

        return $this->json(array_values($data));
    }

    #[Route('/{id}/save', name: 'api_resources_save', methods: ['POST'])]
    public function saveResource(int $id, Request $request, RessourceRepository $repo, EntityManagerInterface $em, LogService $logger): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_CONNECTED');

        $resource = $repo->find($id);
        if (!$resource) {
            return $this->json(['error' => 'Ressource non trouvée'], 404);
        }

        /** @var User $user */
        $user = $this->getUser();
        if ($user->getFavorites()->contains($resource)) {
            return $this->json(['error' => 'Ressource déjà sauvegardée'], 409);
        }

        $user->addFavorite($resource);
        $em->flush();

        $logger->logAction(
            LogAction::ACTION_RESOURCE_SAVE,
            $user instanceof User ? $user : null,
            $request,
            $resource,
            "Ressource #{$id} « {$resource->getTitre()} » sauvegardée en favori"
        );

        return $this->json(['message' => 'Ressource sauvegardée', 'saved' => true]);
    }
}
