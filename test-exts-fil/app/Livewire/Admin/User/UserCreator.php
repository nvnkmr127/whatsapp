<?php

namespace App\Livewire\Admin\User;

use App\Mail\WelcomeMail;
use App\Models\User;
use App\Rules\PurifiedInput;
use App\Traits\SendMailTrait;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;
use Livewire\WithFileUploads;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserCreator extends Component
{
    use SendMailTrait;

    use WithFileUploads;

    public $id;

    public User $user;

    public $profile_image_url;

    public $is_admin = false;

    public $role_id;

    public $password;

    public $password_confirmation;

    public $roles;

    public $sendWelcomeMail = false;

    public $isVerified = false;

    public $selectedPermissions = [];

    public $rolePermissions = [];

    public $userAdditionalPermissions = [];

    public $roleAdditionalPermissions = [];

    protected $listeners = [
        'editUser' => 'editUser',
    ];

    protected function rules()
    {
        return [
            'user.firstname' => [
                'required',
                'string',
                new PurifiedInput(t('sql_injection_error')),
                'max:255',
            ],
            'user.lastname' => [
                'required',
                'string',
                new PurifiedInput(t('sql_injection_error')),
                'max:255',
            ],
            'user.email' => [
                'required',
                'email',
                'unique:users,email,' . ($this->user->id ?? 'NULL'),
                new PurifiedInput(t('sql_injection_error')),
                'max:255',
            ],
            'is_admin'               => 'nullable|boolean',
            'user.phone'             => ['required', 'unique:users,phone,' . $this->user->id, new PurifiedInput(t('sql_injection_error'))],
            'user.default_language'  => 'nullable',
            'user.profile_image_url' => is_object($this->user->profile_image_url) ? ['nullable', 'image', 'mimes:png,jpg,jpeg'] : 'nullable',
            'password'               => ($this->user->id) ? ['nullable', Password::defaults(), 'min:8', 'max:12'] : ['required', 'confirmed', Password::defaults(), 'min:8', 'max:12'],
            'role_id'                => [$this->is_admin ? 'nullable' : 'required', 'integer', 'exists:roles,id'],
        ];
    }

    public function mount()
    {
        if (! checkPermission(['user.create', 'user.edit'])) {
            $this->notify(['type' => 'danger', 'message' => t('access_denied_note')], true);

            return redirect()->route('admin.dashboard');
        }
        $this->id              = $this->getId();
        $userId                = request()->route('userId') ?? null;
        $this->roles           = Role::where('name', '!=', 'Admin')->pluck('name', 'id');
        $this->user            = ($userId) ? User::findOrFail($userId) : new User;
        $this->role_id         = ($userId && ! empty($this->user->role_id)) ? optional($this->user->roles->first())->id : null;
        $this->is_admin        = $this->user->is_admin;
        $this->sendWelcomeMail = $this->user->send_welcome_mail ?? false;
        $this->isVerified      = ! empty($this->user->email_verified_at) ? true : false;
        if ($this->role_id) {
            $this->loadPermissions($this->role_id);
        }
    }

    public function updatedRoleId($roleId)
    {
        // Save current additional permissions for the existing role
        if ($this->role_id) {
            if ($this->user->role_id == $this->role_id) {
                $this->roleAdditionalPermissions[$this->role_id] = array_diff(
                    $this->selectedPermissions,
                    $this->rolePermissions
                );
            } else {
                $this->roleAdditionalPermissions = [];
            }
        }

        $this->loadPermissions($roleId);

        // Restore additional permissions if available for the selected role
        $this->userAdditionalPermissions = $this->roleAdditionalPermissions[$roleId] ?? [];

        $this->selectedPermissions = array_merge(
            $this->rolePermissions,
            $this->userAdditionalPermissions
        );
    }

    private function loadPermissions($roleId)
    {
        // Clear previous permissions
        $this->rolePermissions           = [];
        $this->userAdditionalPermissions = [];
        $this->selectedPermissions       = [];

        if (! empty($roleId) && ! $this->is_admin) {
            $role = Role::findOrFail($roleId);

            // Get role permissions
            $this->rolePermissions = $role->permissions->pluck('name')->toArray();

            // Check if there are saved additional permissions for this role
            if (isset($this->roleAdditionalPermissions[$roleId])) {
                $this->userAdditionalPermissions = $this->roleAdditionalPermissions[$roleId];
            } else {
                // Calculate user-specific permissions not included in the role
                $userPermissions                 = $this->user->permissions->pluck('name')->toArray();
                $this->userAdditionalPermissions = array_diff($userPermissions, $this->rolePermissions);
            }

            // Combine role permissions and additional permissions
            $this->selectedPermissions = array_merge(
                $this->rolePermissions,
                $this->userAdditionalPermissions
            );
        }
    }

    public function save()
    {
        if (checkPermission(['user.create', 'user.delete'])) {
            $this->validate();

            try {

                $this->handleProfileImageUpload();

                $role = $this->is_admin
                    ? Role::where('name', 'Admin')->first()
                    : Role::find($this->role_id);

                $this->user->is_admin = $this->is_admin ?? false;

                if (! empty($this->password)) {
                    $this->user->password = Hash::make($this->password);
                }

                $this->user->role_id = $role ? $role->id : null;

                $this->user->email_verified_at = (! can_send_email('email-confirmation')) || $this->isVerified ? to_sql_date(now(), true) : null;

                $isChanged                     = $this->user->getOriginal('send_welcome_mail');
                $this->user->send_welcome_mail = $this->sendWelcomeMail;

                $this->user->save();

                if (isSmtpValid() && can_send_email('welcome-mail') && $this->user->send_welcome_mail && $isChanged !== $this->user->send_welcome_mail) {
                    $this->sendMail($this->user->email, new WelcomeMail('welcome-mail', $this->user->id));
                }

                if ($role) {
                    $this->user->syncRoles([$role->name]);
                }

                $additionalPermissions = array_diff(
                    $this->selectedPermissions,
                    $this->rolePermissions
                );

                $this->user->syncPermissions($additionalPermissions);

                $this->notify([
                    'type'    => 'success',
                    'message' => $this->user->wasRecentlyCreated
                        ? t('user_save_successfully')
                        : t('user_update_successfully'),
                ], true);

                $this->redirect(route('admin.users.list'));
            } catch (\Exception $e) {
                app_log('User save failed: ' . $e->getMessage(), 'error', $e, [
                    'user_id'     => $this->user->id ?? null,
                    'is_admin'    => $this->is_admin,
                    'role_id'     => $this->role_id,
                    'permissions' => $this->selectedPermissions,
                ]);

                $this->notify([
                    'type'    => 'danger',
                    'message' => t('user_save_failed'),
                ], true);
            }
        }
    }

    public function cancel()
    {
        $this->resetValidation();
        $this->redirect(route('admin.users.list'), navigate: true);
    }

    public function getPermissionProperty()
    {
        return Permission::all();
    }

    protected function handleProfileImageUpload()
    {
        try {

            if (isset($this->user->profile_image_url) && is_object($this->user->profile_image_url)) {

                create_storage_link();

                // Delete old profile image if it exists
                if ($this->user->getOriginal('profile_image_url')) {
                    Storage::disk('public')->delete($this->user->getOriginal('profile_image_url'));
                }

                $filename = 'profile_' . time() . '.' . $this->user->profile_image_url->getClientOriginalExtension();
                $path     = $this->user->profile_image_url->storeAs('profile-images', $filename, 'public');

                $this->user->profile_image_url = $path;
            }
        } catch (\Exception $e) {
            app_log('Profile image upload failed: ' . $e->getMessage(), 'error', $e, [
                'user_id' => $this->user->id ?? null,
            ]);

            throw new \Exception('Failed to upload profile image: ' . $e->getMessage());
        }
    }

    public function removeProfileImage()
    {
        try {
            if ($this->user->profile_image_url) {
                // Check if file exists before attempting deletion
                if (Storage::disk('public')->exists($this->user->profile_image_url)) {
                    Storage::disk('public')->delete($this->user->profile_image_url);
                }

                $this->user->profile_image_url = null;
                $this->user->save();

                $this->notify(['type' => 'success', 'message' => t('profile_image_removed_successfully')]);
            }
        } catch (\Exception $e) {
            app_log('Profile image removal failed: ' . $e->getMessage(), 'error', $e, [
                'user_id' => $this->user->id ?? null,
            ]);

            $this->notify(['type' => 'danger', 'message' => t('failed_to_remove_profile_image')]);
        }
    }

    public function render()
    {
        return view('livewire.admin.user.user-creator');
    }
}
