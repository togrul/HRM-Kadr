<?php

namespace App\Livewire\Services\Users;

use App\Models\User;
use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class EditUser extends Component
{
    use AuthorizesRequests;

    public $userModel;

    public $title;
    public $user;

    public $roleId,$roleName;

    protected function rules()
    {
        $rules = [
            'user.name' => 'required|min:1',
            'user.email' => 'required|unique:users,email,' . $this->userModel->id,
            'roleId' => 'required|exists:roles,id',
        ];

        if(!empty($this->user['old_password']))
        {
            if(Hash::check($this->user['old_password'], $this->userModel->password))
            {
                $rules['user.old_password'] = 'required|min:4';
                $rules['user.password'] = 'required|min:4|different:user.old_password';
            }
            else
            {
                $rules['user.old_password'] = ['required', function ($attribute, $value, $fail) {
                    if (!Hash::check($this->user['old_password'], $this->userModel->password)) {
                        $fail(__('Old Password didn\'t match'));
                    }
                },];
            }
        }

        return $rules;
    }

    protected function validationAttributes()
    {
        return [
            'user.name'=>__('Name'),
            'user.email' => __('Email'),
            'user.password'=> __('Password'),
            'user.confirm-password' => __('Confirm password'),
            'roleId' => __('Role'),
        ];
    }

    public function selectRole($name,$id)
    {
        $this->roleId = $id;
        $this->roleName = $name;
    }

    public function mount()
    {
        // $this->authorize('manage-settings',$this->user);
        $this->title = __('Edit user');
        $this->userModel = User::where('id',$this->userModel['id'])->first();
        $this->userModel->load('roles');
        if(count($this->userModel->roles)>0)
        {
            $this->roleName = $this->userModel->roles[0]->name;
            $this->roleId = $this->userModel->roles[0]->id;
        }
        else
        {
            $this->roleName = '---';
            $this->roleId = -1;
        }

        $this->user['name'] = $this->userModel->name;
        $this->user['email'] = $this->userModel->email;
        $this->user['is_active'] = (bool)$this->userModel->is_active;
    }

    public function store()
    {
        $this->validate();

        if(isset($this->user['password']))
        {
            $this->user['password'] = Hash::make($this->user['password']);
        }

        DB::transaction(function () {
            $this->userModel->update($this->user);
            $this->userModel->roles()->sync($this->roleId);
        });

        $this->dispatch('userAdded',__('User was updated successfully!'));
    }

    public function render()
    {
        $roles = DB::table('roles')
                ->select('id','name')
                ->get();

        return view('livewire.services.users.edit-user',compact('roles'));
    }
}
