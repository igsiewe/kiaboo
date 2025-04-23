<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ApiImageUploadController extends Controller
{
    public function upload(Request $request)
    {
        if ($request->hasFile('image')) {
            $file = $request->file('image');

            // Enregistre dans storage/app/public/uploads
            $path = $file->store('uploads', 'public');

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
