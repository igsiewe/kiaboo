<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\prospect;
use Illuminate\Http\Request;

class ApiImageUploadController extends Controller
{
    public function upload(Request $request)
    {
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            // Récupère le nom original du fichier (ex: "photo123.jpg")
            $originalName = $file->getClientOriginalName();
            // Stocke le fichier avec ce nom dans le dossier "uploads" du disque public
            $path = $file->storeAs('uploads', $originalName, 'public');

            return response()->json([
                'message' => 'Image envoyée avec succès',
                'path' => $path,
            ]);
        } else {
            return response()->json([
                'message' => 'Aucune image reçue',
            ], 400);
        }
    }
}
