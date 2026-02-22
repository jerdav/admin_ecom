<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class PageController extends Controller
{
    public function orders()
    {
        return view('admin.placeholder', [
            'title' => 'Commandes',
            'subtitle' => 'Gestion des commandes et transitions de statut.',
            'message' => 'La page commandes detaillee arrive dans la prochaine etape.',
        ]);
    }

    public function settings()
    {
        return view('admin.placeholder', [
            'title' => 'Parametres',
            'subtitle' => 'Configuration boutique et modules.',
            'message' => 'La page parametres complete est en preparation.',
        ]);
    }

    public function audit()
    {
        return view('admin.placeholder', [
            'title' => 'Audit',
            'subtitle' => 'Historique des actions sensibles.',
            'message' => 'La consultation detaillee des logs arrive bientot.',
        ]);
    }
}
