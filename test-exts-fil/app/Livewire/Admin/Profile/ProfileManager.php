<?php

namespace App\Livewire\Admin\Profile;

use App\Events\PasswordChanged;
use App\Models\User;
use App\Rules\PurifiedInput;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;
use Livewire\WithFileUploads;

class ProfileManager extends Component
{
    use WithFileUploads;

    public User $user;

    public $profile_image_url;

    public $profile_image_path;

    public $remove_existing_image = false;

    public $firstname;

    public $lastname;

    public $email;

    public $phone;

    public $default_language;

    public $current_password;

    public $password;

    public $password_confirmation;

    public $id;

    public function mount()
    {
        $this->id                 = $this->getId();
        $this->user               = Auth::user();
        $this->firstname          = $this->user->firstname;
        $this->lastname           = $this->user->lastname;
        $this->email              = $this->user->email;
        $this->phone              = $this->user->phone;
        $this->default_language   = $this->user->default_language;
        $this->profile_image_path = $this->user->profile_image_url;
    }

    public function rules()
    {
        return [
            'firstname'         => ['required', 'string', new PurifiedInput(t('sql_injection_error'))],
            'lastname'          => ['required', 'string', new PurifiedInput(t('sql_injection_error'))],
            'email'             => ['required', 'email', 'unique:users,email,' . $this->user->id],
            'phone'             => ['required', 'unique:users,phone,' . $this->user->id],
            'profile_image_url' => ['nullable', 'image', 'max:5120', 'mimes:jpg,jpeg,png'],
        ];
    }

    public function changeProfile()
    {
        $this->validate();
        try {
            // Handle existing image removal
            if ($this->remove_existing_image && $this->user->profile_image_url) {
                Storage::delete('public/' . $this->user->profile_image_url);
                $this->user->profile_image_url = null;
                $this->remove_existing_image   = false;
            }

            // Handle new image upload
            if ($this->profile_image_url) {
                $path                          = $this->profile_image_url->store('profile-images', 'public');
                $this->user->profile_image_url = $path;
            }

            // Update only changed fields
            $this->user->fill([
                'firstname'        => $this->firstname,
                'lastname'         => $this->lastname,
                'email'            => $this->email,
                'default_language' => $this->default_language,
                'phone'            => $this->phone ?? $this->user->phone,
            ]);

            if ($this->user->isDirty()) {
                $this->user->save();
                $this->notify(['type' => 'success', 'message' => t('profile_update_successfully')]);
            }
        } catch (\Exception $e) {
            app_log(
                'Failed to update user profile: ' . $e->getMessage(),
                'error',
                $e,
                [
                    'user_id'      => $this->user->id ?? null,
                    'email'        => $this->email,
                    'image_update' => $this->profile_image_url ? true : false,
                ]
            );

            $this->notify(['type' => 'danger', 'message' => t('profile_update_failed')]);
        }
    }

    public function changePassword()
    {
        $this->validate([
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', 'confirmed', Password::defaults()],
        ]);

        $this->user->password = Hash::make($this->password);
        if ($this->user->isDirty()) {
            $this->user->save();

            event(new PasswordChanged($this->user));

            return redirect()->route('login')->with('status', t('new_password_changed'));
        }
    }

    public function removeProfileImage()
    {
        if ($this->user->profile_image_url) {
            Storage::disk('public')->delete($this->user->profile_image_url);
            $this->user->profile_image_url = null;
            $this->user->save();
        }
    }

    public function render()
    {
        return view('livewire.admin.profile.profile-manager');
    }
}
