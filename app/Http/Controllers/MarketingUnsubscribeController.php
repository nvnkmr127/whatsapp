<?php

namespace App\Http\Controllers;

use App\Services\Email\AppMarketingService;
use Illuminate\Http\Request;

class MarketingUnsubscribeController extends Controller
{
    public function unsubscribe(Request $request, AppMarketingService $service)
    {
        $token = $request->query('token');

        if (!$token) {
            abort(404);
        }

        $success = $service->unsubscribe($token);

        return view('marketing.unsubscribe', [
            'success' => $success
        ]);
    }
}
