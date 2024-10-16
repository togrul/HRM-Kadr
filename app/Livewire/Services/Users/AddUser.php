<?php

namespace App\Livewire\Services\Users;

use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

class AddUser extends Component
{
    use AuthorizesRequests;

    public $title;

    public $user = [];

    public $roleId;

    public $roleName;

    protected function rules()
    {
        return [
            'user.name' => 'required|string|min:1',
            'user.email' => 'required|email|unique:users,email',
            'user.password' => 'required|min:4',
            'user.confirm-password' => 'required_with:user.password|same:user.password|min:4',
            'roleId' => 'required|exists:roles,id',
        ];
    }

    protected function validationAttributes()
    {
        return [
            'user.name' => __('Name'),
            'user.email' => __('Email'),
            'user.password' => __('Password'),
            'user.confirm-password' => __('Confirm password'),
            'roleId' => __('Role'),
        ];
    }

    public function selectRole($name, $id)
    {
        $this->roleId = $id;
        $this->roleName = $name;
    }

    public function store()
    {
        $this->validate();

        $this->user['password'] = Hash::make($this->user['password']);

        DB::transaction(function () {
            $user = User::create($this->user);
            $user->assignRole($this->roleName);
        });

        $this->dispatch('userAdded', __('User was added successfully!'));
    }

    public function mount()
    {
        // $this->authorize('manage-settings',$this->user);
        $this->title = __('Add user');
        $this->roleName = '---';
    }

    public function render()
    {
        $roles = DB::table('roles')
            ->select('id', 'name')
            ->get();

        return view('livewire.services.users.add-user', compact('roles'));
    }
}
