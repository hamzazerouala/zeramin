<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;

class SpaController extends Controller
{
    /**
     * Sert le point d'entrée du SPA React compilé (public/spa/index.html).
     */
    public function index()
    {
        $spa = public_path('spa/index.html');

        if (file_exists($spa)) {
            return response()->file($spa);
        }

        return response(
            '<!doctype html><html lang="fr"><head><meta charset="utf-8">'
            .'<title>DropShop</title></head><body>'
            .'<h1>DropShop</h1><p>Le SPA React n\'est pas encore compilé. '
            .'Lancez <code>npm run build</code> pour générer <code>public/spa/</code>.</p>'
            .'</body></html>',
            200
        )->header('Content-Type', 'text/html');
    }
}
