<?php

namespace App\Http\Controllers\Developer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ApiDocumentationController extends Controller
{
    public function index()
    {
        $baseUrl = url('/api/v1');
        $webhookUrl = url('/api/webhook/whatsapp');

        return view('developer.api-documentation', compact('baseUrl', 'webhookUrl'));
    }
}
