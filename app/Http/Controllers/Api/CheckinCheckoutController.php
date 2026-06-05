<?php

namespace App\Http\Controllers\Api;

use App\Models\CheckinCheckout;
use App\Models\Facture;
use App\Models\Reservation;
use App\Models\TacheMenage;
use Illuminate\Http\Request;

class CheckinCheckoutController extends BaseController
{
    public function checkin(Request $request, Reservation $reservation)
    {
        if ($reservation->etablissement_id !== $this->etabId()) abort(403);

        if ($reservation->statut !== 'confirmee') {
            return $this->error('Seules les reservations confirmees peuvent faire l\'objet d\'un check-in.', 422);
        }

        $data = $request->validate([
            'numero_cle'    => 'nullable|string|max:50',
            'early_checkin' => 'boolean',
            'observations'  => 'nullable|string',
        ]);

        $checkin = CheckinCheckout::create([
            'reservation_id'  => $reservation->id,
            'utilisateur_id'  => auth()->id(),
            'type'            => 'checkin',
            'date_heure'      => now(),
            'numero_cle'      => $data['numero_cle'] ?? null,
            'early_checkin'   => $data['early_checkin'] ?? false,
            'observations'    => $data['observations'] ?? null,
        ]);

        $reservation->update(['statut' => 'en_cours']);
        $reservation->chambre->update(['statut' => 'occupee']);

        return $this->success($checkin->load('utilisateur'), 'Check-in effectue.');
    }

    public function checkout(Request $request, Reservation $reservation)
    {
        if ($reservation->etablissement_id !== $this->etabId()) abort(403);

        $data = $request->validate([
            'late_checkout' => 'boolean',
            'observations'  => 'nullable|string',
        ]);

        $checkout = CheckinCheckout::create([
            'reservation_id' => $reservation->id,
            'utilisateur_id' => auth()->id(),
            'type'           => 'checkout',
            'date_heure'     => now(),
            'late_checkout'  => $data['late_checkout'] ?? false,
            'observations'   => $data['observations'] ?? null,
        ]);

        $reservation->update(['statut' => 'terminee']);
        $reservation->chambre->update(['statut' => 'en_nettoyage']);

        TacheMenage::create([
            'chambre_id'       => $reservation->chambre_id,
            'agent_id'         => null,
            'type_tache'       => 'depart',
            'statut'           => 'non_assignee',
            'date_assignation' => now(),
        ]);

        $reservation->factures()
            ->where('statut', '!=', 'payee')
            ->update(['statut' => 'payee', 'date_paiement' => now()]);

        return $this->success($checkout->load('utilisateur'), 'Check-out effectue.');
    }

    public function index(Request $request)
    {
        $query = CheckinCheckout::whereHas('reservation', fn($q) => $q->where('etablissement_id', $this->etabId()))
            ->with(['reservation.client', 'utilisateur']);

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('date')) {
            $query->whereDate('date_heure', $request->date);
        }

        return $this->success($query->latest('date_heure')->paginate(20));
    }
}
