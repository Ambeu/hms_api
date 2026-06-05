<?php

namespace App\Http\Controllers\Api;

use App\Models\AvisClient;
use App\Models\Chambre;
use App\Models\CheckinCheckout;
use App\Models\Client;
use App\Models\CommandeRestaurant;
use App\Models\Facture;
use App\Models\Reservation;
use App\Models\TacheMenage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends BaseController
{
    public function index()
    {
        $etabId = $this->etabId();
        $today  = Carbon::today();
        $month  = Carbon::now()->startOfMonth();

        return $this->success([
            'chambres'     => $this->statsChambres($etabId),
            'reservations' => $this->statsReservations($etabId, $today),
            'checkins'     => $this->statsCheckins($etabId, $today),
            'revenus'      => $this->statsRevenus($etabId, $today, $month),
            'menage'       => $this->statsMenage($etabId),
            'restaurant'   => $this->statsRestaurant($etabId, $today),
            'clients'      => $this->statsClients($etabId, $month),
            'avis'         => $this->statsAvis($etabId),
        ]);
    }

    // ─── Chambres ────────────────────────────────────────────────────────────

    private function statsChambres(int $etabId): array
    {
        $totaux = Chambre::where('etablissement_id', $etabId)
            ->select('statut', DB::raw('COUNT(*) as total'))
            ->groupBy('statut')
            ->pluck('total', 'statut');

        $total       = $totaux->sum();
        $occupees    = $totaux->get('occupee', 0);
        $disponibles = $totaux->get('disponible', 0);
        $nettoyage   = $totaux->get('en_nettoyage', 0);
        $horsService = $totaux->get('hors_service', 0);

        return [
            'total'          => $total,
            'disponibles'    => $disponibles,
            'occupees'       => $occupees,
            'en_nettoyage'   => $nettoyage,
            'hors_service'   => $horsService,
            'taux_occupation'=> $total > 0 ? round($occupees / $total * 100, 1) : 0,
        ];
    }

    // ─── Réservations ────────────────────────────────────────────────────────

    private function statsReservations(int $etabId, Carbon $today): array
    {
        $base = Reservation::where('etablissement_id', $etabId);

        return [
            'arrivees_aujourd_hui'  => (clone $base)->whereDate('date_arrivee', $today)
                                                     ->whereIn('statut', ['confirmee', 'en_attente'])->count(),
            'departs_aujourd_hui'   => (clone $base)->whereDate('date_depart', $today)
                                                     ->whereIn('statut', ['confirmee', 'en_cours'])->count(),
            'en_cours'              => (clone $base)->where('statut', 'en_cours')->count(),
            'confirmees'            => (clone $base)->where('statut', 'confirmee')->count(),
            'en_attente'            => (clone $base)->where('statut', 'en_attente')->count(),
            'ce_mois'               => (clone $base)->whereMonth('date_arrivee', $today->month)
                                                     ->whereYear('date_arrivee', $today->year)->count(),
        ];
    }

    // ─── Check-ins / Check-outs ───────────────────────────────────────────────

    private function statsCheckins(int $etabId, Carbon $today): array
    {
        $base = CheckinCheckout::whereHas(
            'reservation', fn($q) => $q->where('etablissement_id', $etabId)
        )->whereDate('date_heure', $today);

        return [
            'checkins_aujourd_hui'  => (clone $base)->where('type', 'checkin')->count(),
            'checkouts_aujourd_hui' => (clone $base)->where('type', 'checkout')->count(),
        ];
    }

    // ─── Revenus ─────────────────────────────────────────────────────────────

    private function statsRevenus(int $etabId, Carbon $today, Carbon $month): array
    {
        $base = Facture::where(function ($q) use ($etabId) {
            $q->whereHas('reservation', fn($r) => $r->where('etablissement_id', $etabId))
              ->orWhereHas('commandeRestaurant', fn($r) => $r->where('etablissement_id', $etabId));
        });

        $payees = (clone $base)->where('statut', 'payee');

        return [
            'aujourd_hui'    => (clone $payees)->whereDate('date_paiement', $today)
                                               ->sum('montant_ttc'),
            'ce_mois'        => (clone $payees)->whereDate('date_paiement', '>=', $month)
                                               ->sum('montant_ttc'),
            'en_attente'     => (clone $base)->whereIn('statut', ['brouillon', 'emise'])
                                             ->sum('montant_ttc'),
            'sejour_mois'    => (clone $payees)->where('type_facture', 'sejour')
                                               ->whereDate('date_paiement', '>=', $month)
                                               ->sum('montant_ttc'),
            'restaurant_mois'=> (clone $payees)->where('type_facture', 'restaurant')
                                               ->whereDate('date_paiement', '>=', $month)
                                               ->sum('montant_ttc'),
        ];
    }

    // ─── Ménage ───────────────────────────────────────────────────────────────

    private function statsMenage(int $etabId): array
    {
        $base = TacheMenage::whereHas('chambre', fn($q) => $q->where('etablissement_id', $etabId));

        return [
            'non_assignees' => (clone $base)->where('statut', 'non_assignee')->count(),
            'assignees'     => (clone $base)->where('statut', 'assignee')->count(),
            'en_cours'      => (clone $base)->where('statut', 'en_cours')->count(),
            'terminees_aujourd_hui' => (clone $base)->where('statut', 'terminee')
                                                     ->whereDate('date_fin', Carbon::today())->count(),
        ];
    }

    // ─── Restaurant ───────────────────────────────────────────────────────────

    private function statsRestaurant(int $etabId, Carbon $today): array
    {
        $base = CommandeRestaurant::where('etablissement_id', $etabId);

        return [
            'commandes_aujourd_hui' => (clone $base)->whereDate('created_at', $today)->count(),
            'en_attente'            => (clone $base)->where('statut', 'en_attente')->count(),
            'en_preparation'        => (clone $base)->where('statut', 'en_preparation')->count(),
            'ca_aujourd_hui'        => (clone $base)->whereDate('created_at', $today)
                                                     ->where('statut', '!=', 'annulee')
                                                     ->sum('montant_total'),
        ];
    }

    // ─── Clients ──────────────────────────────────────────────────────────────

    private function statsClients(int $etabId, Carbon $month): array
    {
        $base = Client::where(function ($q) use ($etabId) {
            $q->where('etablissement_id', $etabId)
              ->orWhereHas('reservations', fn($r) => $r->where('etablissement_id', $etabId));
        });

        return [
            'total'      => (clone $base)->count(),
            'ce_mois'    => (clone $base)->where('created_at', '>=', $month)->count(),
        ];
    }

    // ─── Avis ─────────────────────────────────────────────────────────────────

    private function statsAvis(int $etabId): array
    {
        $base = AvisClient::where(function ($q) use ($etabId) {
            $q->where('etablissement_id', $etabId)
              ->orWhereHas('reservation', fn($r) => $r->where('etablissement_id', $etabId));
        });

        $count = (clone $base)->count();

        return [
            'total'   => $count,
            'moyenne' => $count ? round((clone $base)->avg('note_globale'), 1) : null,
        ];
    }
}
