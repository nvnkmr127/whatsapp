<?php

namespace App\Livewire\Settings;

use BaconQrCode\Renderer\Color\Rgb;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\Fill;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

class MobileAppConfig extends Component
{
    public $showToken = false;
    public $setupToken = '';

    public function generateSetupToken()
    {
        $user = Auth::user();
        $tokenName = 'mobile_setup_' . now()->format('Y-m-d_H-i-s');
        
        // Create a token specifically for mobile
        $token = $user->createToken($tokenName, ['*']);
        
        $this->setupToken = $token->plainTextToken;
        $this->showToken = true;

        $this->dispatch('notify', 'Setup token generated successfully.');
    }

    public function getQrCodeProperty()
    {
        $payload = [
            'baseUrl' => config('app.url'),
            'platform' => 'Watxio',
            'teamId' => Auth::user()->current_team_id,
            'userId' => Auth::id(),
            'email' => Auth::user()->email,
        ];

        $renderer = new ImageRenderer(
            new RendererStyle(300, 1, null, null, Fill::uniformColor(
                new Rgb(255, 255, 255),
                new Rgb(0, 0, 0)
            )),
            new SvgImageBackEnd
        );

        $writer = new Writer($renderer);
        return $writer->writeString(json_encode($payload));
    }

    #[Layout('components.layouts.app')]
    public function render()
    {
        return view('livewire.settings.mobile-app-config');
    }
}
