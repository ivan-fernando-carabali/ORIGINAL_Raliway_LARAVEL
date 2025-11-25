<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserService extends BaseService
{
    public function __construct(User $user)
    {
        parent::__construct($user);
    }

    public function createUser(array $data)
    {
        $data['password'] = Hash::make($data['password']);
        return $this->create($data);
    }

    public function updateUser($id, array $data)
    {
        if (isset($data['password']) && $data['password']) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        return $this->update($id, $data);
    }

    public function getProfile($user)
    {
        return $user->load('role');
    }

    public function getAll()
    {
        return $this->model->with('role')->get();
    }

    public function getById($id)
    {
        return $this->model->with('role')->findOrFail($id);
    }
}