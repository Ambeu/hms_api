<?php

use App\Http\Controllers\Api\ArticleMenuController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\RolePermissionController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AvisClientController;
use App\Http\Controllers\Api\CanalDistributionController;
use App\Http\Controllers\Api\CategorieMenuController;
use App\Http\Controllers\Api\ChambreController;
use App\Http\Controllers\Api\CheckinCheckoutController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\CommandeRestaurantController;
use App\Http\Controllers\Api\DeviseController;
use App\Http\Controllers\Api\EtablissementController;
use App\Http\Controllers\Api\EtageController;
use App\Http\Controllers\Api\FactureController;
use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\ObjetOublieController;
use App\Http\Controllers\Api\ProgrammeFideliteController;
use App\Http\Controllers\Api\ReservationController;
use App\Http\Controllers\Api\StockArticleController;
use App\Http\Controllers\Api\TacheMenageController;
use App\Http\Controllers\Api\TarifController;
use App\Http\Controllers\Api\TransactionPointsController;
use App\Http\Controllers\Api\TypeChambreController;
use App\Http\Controllers\Api\TypeEtablissementController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\Public\PublicController;
use App\Http\Controllers\Api\Public\ClientAuthController;
use App\Http\Controllers\Api\Public\ClientPortalController;
use App\Http\Controllers\Api\Public\TachePubliqueController;
use Illuminate\Support\Facades\Route;

// ─────────────────────────────────────────────────────────────────────────────
// Module Grand Public
// ─────────────────────────────────────────────────────────────────────────────
Route::prefix('public')->group(function () {

    // Données publiques par slug d'établissement
    Route::prefix('{slug}')->group(function () {
        Route::get('info',     [PublicController::class, 'info']);
        Route::get('stats',    [PublicController::class, 'stats']);
        Route::get('chambres', [PublicController::class, 'chambres']);
        Route::get('menus',    [PublicController::class, 'menus']);
        Route::get('articles', [PublicController::class, 'articles']);
        Route::get('avis',     [PublicController::class, 'avis']);
        Route::get('tarifs',   [PublicController::class, 'tarifs']);
    });

    // Auth client
    Route::post('auth/register', [ClientAuthController::class, 'register']);
    Route::post('auth/login',    [ClientAuthController::class, 'login']);

    // Portail client (JWT guard client)
    Route::middleware('auth:client')->group(function () {
        Route::get ('auth/me',           [ClientAuthController::class, 'me']);
        Route::post('auth/logout',       [ClientAuthController::class, 'logout']);

        Route::get ('mes-reservations',       [ClientPortalController::class, 'mesReservations']);
        Route::post('reserver',               [ClientPortalController::class, 'reserver']);
        Route::post('annuler-reservation/{reservation}', [ClientPortalController::class, 'annulerReservation']);

        Route::get ('mes-commandes',          [ClientPortalController::class, 'mesCommandes']);
        Route::post('commander',              [ClientPortalController::class, 'commander']);

        Route::get ('mes-avis',               [ClientPortalController::class, 'mesAvis']);
        Route::post('avis',                   [ClientPortalController::class, 'laisserAvis']);

        Route::get ('mes-objets-perdus',      [ClientPortalController::class, 'mesObjets']);
        Route::post('signaler-objet',         [ClientPortalController::class, 'signalerObjet']);
    });
});

// Accès agent ménage via lien token (sans JWT)
Route::prefix('tache-agent')->group(function () {
    Route::get('{token}',              [TachePubliqueController::class, 'show']);
    Route::post('{token}/auth',        [TachePubliqueController::class, 'authentifier']);
    Route::patch('{token}/maj',        [TachePubliqueController::class, 'mettreAJour']);
});

// ─────────────────────────────────────────────────────────────────────────────
// Authentification (publique)
// ─────────────────────────────────────────────────────────────────────────────
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']); // inscription vitrine
    Route::post('login',    [AuthController::class, 'login']);
    Route::post('refresh',  [AuthController::class, 'refresh']);
});

// Référentiels publics (accessibles sans authentification)
Route::get('devises',                                      [DeviseController::class,            'index']);
Route::get('devises/{devise}',                             [DeviseController::class,            'show']);
Route::get('type-etablissements',                          [TypeEtablissementController::class, 'index']);
Route::get('type-etablissements/{typeEtablissement}',      [TypeEtablissementController::class, 'show']);

// ─────────────────────────────────────────────────────────────────────────────
// Routes protégées (JWT requis)
// ─────────────────────────────────────────────────────────────────────────────
Route::middleware('auth:api')->group(function () {

    // Auth
    Route::prefix('auth')->group(function () {
        Route::post('logout',               [AuthController::class, 'logout']);
        Route::get('me',                    [AuthController::class, 'me']);
        Route::put('me',                    [AuthController::class, 'updateProfile']);
        Route::put('password',              [AuthController::class, 'updatePassword']);
        Route::post('switch-etablissement', [AuthController::class, 'switchEtablissement']);
    });

    // Données de référence — lecture ouverte à tous les authentifiés
    Route::get('programme-fidelites',  [ProgrammeFideliteController::class,  'index']);
    Route::get('programme-fidelites/{programmeFidelite}', [ProgrammeFideliteController::class, 'show']);
    Route::get('canal-distributions',  [CanalDistributionController::class,  'index']);
    Route::get('canal-distributions/{canalDistribution}', [CanalDistributionController::class, 'show']);

    // Données de référence — écriture réservée au super_admin
    Route::middleware('role:super_admin')->group(function () {
        Route::apiResource('devises',              DeviseController::class)->except(['index', 'show']);
        Route::apiResource('type-etablissements',  TypeEtablissementController::class)->except(['index', 'show']);
        Route::apiResource('programme-fidelites',  ProgrammeFideliteController::class)->except(['index', 'show']);
        Route::apiResource('canal-distributions',  CanalDistributionController::class)->except(['index', 'show']);
    });

    // Gestion des établissements
    Route::apiResource('etablissements', EtablissementController::class);

    // ─────────────────────────────────────────────────────────────────────────
    // Routes scopées à l'établissement courant
    // ─────────────────────────────────────────────────────────────────────────
    Route::middleware('etab.scope')->group(function () {

        // ── Dashboard ────────────────────────────────────────────────────────
        Route::get('dashboard', [DashboardController::class, 'index']);
        Route::get ('utilisateurs/{user}/permissions',      [RolePermissionController::class, 'permissionsUtilisateur']);
        Route::get ('permissions',                          [RolePermissionController::class, 'permissions']);



        // ── Rôles & Permissions ──────────────────────── admin seulement
        Route::middleware('permission:utilisateurs.manage')->group(function () {
            Route::get ('roles',                                [RolePermissionController::class, 'roles']);
            Route::get ('roles/{role}',                         [RolePermissionController::class, 'showRole']);
            Route::put ('roles/{role}/permissions',             [RolePermissionController::class, 'updateRolePermissions']);
            Route::post('utilisateurs/{user}/role',             [RolePermissionController::class, 'assignerRole']);
        });

        // ── Utilisateurs ─────────────────────────────── permission: utilisateurs.manage
        Route::middleware('permission:utilisateurs.manage')->group(function () {
            Route::apiResource('utilisateurs', UserController::class);
        });

        // ── Hébergement ──────────────────────────────── permission: chambres.manage
        Route::middleware('permission:chambres.manage')->group(function () {
            Route::apiResource('etages',        EtageController::class);
            Route::apiResource('type-chambres', TypeChambreController::class);
            Route::apiResource('chambres',      ChambreController::class);
            Route::get('chambres-reservees',                                    [ChambreController::class,  'reservees']);
            Route::post('chambres/{chambre}/images',                            [ChambreController::class,  'ajouterImages']);
            Route::delete('chambres/{chambre}/images/{chambreImage}',           [ChambreController::class,  'supprimerImage']);
            Route::patch('chambres/{chambre}/images/{chambreImage}/principale', [ChambreController::class,  'definirPrincipale']);
            Route::apiResource('tarifs', TarifController::class);
        });

        // ── Clients ──────────────────────────────────── permission: clients.view / clients.manage
        Route::middleware('permission:clients.view')->group(function () {
            Route::get('clients',          [ClientController::class, 'index']);
            Route::get('clients/{client}', [ClientController::class, 'show']);
        });
        Route::middleware('permission:clients.manage')->group(function () {
            Route::post('clients',              [ClientController::class, 'store']);
            Route::put('clients/{client}',      [ClientController::class, 'update']);
            Route::patch('clients/{client}',    [ClientController::class, 'update']);
            Route::delete('clients/{client}',   [ClientController::class, 'destroy']);
        });

        // ── Réservations ─────────────────────────────── permission: reservations.view / reservations.manage / checkin.manage
        Route::middleware('permission:reservations.view')->group(function () {
            Route::get('reservations',               [ReservationController::class, 'index']);
            Route::get('reservations/{reservation}', [ReservationController::class, 'show']);
        });
        Route::middleware('permission:reservations.manage')->group(function () {
            Route::post('reservations',                   [ReservationController::class, 'store']);
            Route::put('reservations/{reservation}',      [ReservationController::class, 'update']);
            Route::patch('reservations/{reservation}',    [ReservationController::class, 'update']);
            Route::delete('reservations/{reservation}',   [ReservationController::class, 'destroy']);
        });
        Route::middleware('permission:checkin.manage')->group(function () {
            Route::post('reservations/{reservation}/checkin',  [CheckinCheckoutController::class, 'checkin']);
            Route::post('reservations/{reservation}/checkout', [CheckinCheckoutController::class, 'checkout']);
            Route::get('checkins',                             [CheckinCheckoutController::class, 'index']);
        });

        // ── Facturation ──────────────────────────────── permission: factures.view / factures.manage
        Route::middleware('permission:factures.view')->group(function () {
            Route::get('factures',           [FactureController::class, 'index']);
            Route::get('factures/{facture}', [FactureController::class, 'show']);
        });
        Route::middleware('permission:factures.manage')->group(function () {
            Route::post('factures',                      [FactureController::class, 'store']);
            Route::delete('factures/{facture}',          [FactureController::class, 'destroy']);
            Route::patch('factures/{facture}/payer',     [FactureController::class, 'payer']);
        });
        Route::middleware('permission:factures.view')->get('factures/{facture}/pdf', [FactureController::class, 'imprimer']);

        // ── Ménage ───────────────────────────────────── permission: menage.view / menage.manage
        Route::middleware('permission:menage.view')->group(function () {
            Route::get('tache-menages',               [TacheMenageController::class, 'index']);
            Route::get('tache-menages/{tacheMenage}', [TacheMenageController::class, 'show']);
            Route::get('objet-oublies',               [ObjetOublieController::class, 'index']);
            Route::get('objet-oublies/{objetOublie}', [ObjetOublieController::class, 'show']);
        });
        Route::middleware('permission:menage.manage')->group(function () {
            Route::post('tache-menages',                               [TacheMenageController::class, 'store']);
            Route::put('tache-menages/{tacheMenage}',                  [TacheMenageController::class, 'update']);
            Route::patch('tache-menages/{tacheMenage}',                [TacheMenageController::class, 'update']);
            Route::delete('tache-menages/{tacheMenage}',               [TacheMenageController::class, 'destroy']);
            Route::patch('tache-menages/{tacheMenage}/assigner',       [TacheMenageController::class, 'assigner']);
            Route::patch('tache-menages/{tacheMenage}/transferer',     [TacheMenageController::class, 'transferer']);
            Route::post('objet-oublies',                               [ObjetOublieController::class, 'store']);
            Route::put('objet-oublies/{objetOublie}',                  [ObjetOublieController::class, 'update']);
            Route::patch('objet-oublies/{objetOublie}',                [ObjetOublieController::class, 'update']);
            Route::delete('objet-oublies/{objetOublie}',               [ObjetOublieController::class, 'destroy']);
        });

        // ── Restauration ─────────────────────────────── permission: restaurant.view / restaurant.manage / stock.manage
        Route::middleware('permission:restaurant.view')->group(function () {
            Route::get('menus',                        [MenuController::class,                 'index']);
            Route::get('menus/{menu}',                 [MenuController::class,                 'show']);
            Route::get('categories-menu',              [CategorieMenuController::class,        'index']);
            Route::get('categories-menu/{categorieMenu}', [CategorieMenuController::class,     'show']);
            Route::get('articles-menu',                [ArticleMenuController::class,          'index']);
            Route::get('articles-menu/{articleMenu}',  [ArticleMenuController::class,          'show']);
            Route::get('commandes-restaurant',         [CommandeRestaurantController::class,   'index']);
            Route::get('commandes-restaurant/{commandeRestaurant}', [CommandeRestaurantController::class, 'show'])
                ->defaults('commandeRestaurant', null);
        });
        Route::middleware('permission:restaurant.manage')->group(function () {
            Route::post('menus',                                                    [MenuController::class, 'store']);
            Route::put('menus/{menu}',                                              [MenuController::class, 'update']);
            Route::patch('menus/{menu}',                                            [MenuController::class, 'update']);
            Route::delete('menus/{menu}',                                           [MenuController::class, 'destroy']);
            Route::post('menus/{menu}/articles',                                    [MenuController::class, 'attacher']);
            Route::delete('menus/{menu}/articles',                                  [MenuController::class, 'detacher']);
            Route::post('menus/{menu}/images',                                      [MenuController::class, 'ajouterImages']);
            Route::delete('menus/{menu}/images/{menuImage}',                        [MenuController::class, 'supprimerImage']);
            Route::patch('menus/{menu}/images/{menuImage}/principale',              [MenuController::class, 'definirPrincipale']);
            Route::post('categories-menu',              [CategorieMenuController::class, 'store']);
            Route::put('categories-menu/{categorieMenu}', [CategorieMenuController::class, 'update']);
            Route::patch('categories-menu/{categorieMenu}', [CategorieMenuController::class, 'update']);
            Route::delete('categories-menu/{categorieMenu}', [CategorieMenuController::class, 'destroy']);
            Route::post('articles-menu',                [ArticleMenuController::class, 'store']);
            Route::put('articles-menu/{articleMenu}',   [ArticleMenuController::class, 'update']);
            Route::patch('articles-menu/{articleMenu}', [ArticleMenuController::class, 'update']);
            Route::delete('articles-menu/{articleMenu}',[ArticleMenuController::class, 'destroy']);
            Route::apiResource('commandes-restaurant', CommandeRestaurantController::class)
                ->parameters(['commandes-restaurant' => 'commandeRestaurant'])
                ->except(['index', 'show']);
            Route::post('commandes-restaurant/{commandeRestaurant}/lignes',           [CommandeRestaurantController::class, 'ajouterLigne']);
            Route::patch('commandes-restaurant/{commandeRestaurant}/lignes/{ligneCommande}', [CommandeRestaurantController::class, 'modifierLigne']);
            Route::delete('commandes-restaurant/{commandeRestaurant}/lignes/{ligneCommande}',[CommandeRestaurantController::class, 'supprimerLigne']);
        });
        Route::middleware('permission:stock.manage')->group(function () {
            Route::get('stock-articles',                         [StockArticleController::class, 'index']);
            Route::patch('stock-articles/{stockArticle}',        [StockArticleController::class, 'update']);
            Route::post('stock-articles/{stockArticle}/ajuster', [StockArticleController::class, 'ajuster']);
        });

        // ── Fidélité & Avis ──────────────────────────── permission: avis.view / avis.manage / fidelite.manage
        Route::middleware('permission:avis.view')->group(function () {
            Route::get('avis-clients/statistiques', [AvisClientController::class, 'statistiques']);
            Route::get('avis-clients',              [AvisClientController::class, 'index']);
            Route::get('avis-clients/{avisClient}', [AvisClientController::class, 'show']);
        });
        Route::middleware('permission:avis.manage')->group(function () {
            Route::post('avis-clients',              [AvisClientController::class, 'store']);
            Route::delete('avis-clients/{avisClient}',[AvisClientController::class, 'destroy']);
        });
        Route::middleware('permission:fidelite.manage')->group(function () {
            Route::apiResource('transaction-points', TransactionPointsController::class)->only(['index', 'store']);
            Route::get('clients/{client}/points',    [TransactionPointsController::class, 'show']);
        });
    });
});
