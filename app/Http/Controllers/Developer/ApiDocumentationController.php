<?php

namespace App\Http\Controllers\Developer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ApiDocumentationController extends Controller
{
    public function index(\App\Services\Developer\DocumentationService $docs)
    {
        $baseUrl = url('/api/v1');
        $webhookUrl = url('/api/webhook/whatsapp');
        $sections = $docs->getApiSections();

        return view('developer.api-documentation', compact('baseUrl', 'webhookUrl', 'sections'));
    }
}
