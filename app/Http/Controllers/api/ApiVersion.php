<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;

use App\Models\Version;
use Illuminate\Http\Request;

class ApiVersion extends Controller
{
    public function getVersion(){
        $version = Version::where('status',1)->first();
        return response()->json([
            'version' => $version->version,
            'url'=> $version->url,
            'message' => 'Version is active'
        ]);
    }
}
