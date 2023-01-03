<?php

namespace App\Repositories;

use App\Jobs\StoreUserDeviceIdJob;
use App\Models\User;
use App\Transformers\UserTransformer;
use BeyondCode\QueryDetector\Outputs\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Str;

class UserRepository {
    /**
     * @return User[]|\Illuminate\Database\Eloquent\Collection
     */
    public function all() {
        return User::all();
    }

    /**
     * @param $id
     *
     * @return mixed
     */
    public function findById($id) {
        return User::findOrFail($id);
    }

    /**
     * @param Request $request
     *
     * @return mixed
     */
    public function create(Request $request) {
        $userData                  = $request->only('name', 'email', 'password', 'registration_type');
        $userData['password']      = app('hash')->make($userData['password']);
        $userData['timezone']      = $request->has('timezone') ? $request->input('timezone') : 'GMT+6';
        $userData['device_id']     = serialize([$request->input('device_id')]);
        $userData['profile_image'] = asset('assets/images/' . 'profileimage.png');
        if ($request->has('id')) {
            $userData['id'] = $request->input('id');
        } else {
            $userData['id'] = Uuid::uuid4()->toString();
        }

        return User::create($userData);
    }

    /**
     * @param Request $request
     * @param         $user
     *
     * @return mixed
     */
    public function update(Request $request, object $user) {

        $data = $request->only([
            'name',
            'email',
            'password',
            'device_id',
            'profile_image',
            'file_name',
            'date_of_birth',
            'timezone',
            'registration_type',
            'start_week',
            'use_device_timezoon',
            'device_notification',
            'email_reminder',
        ]);
        if (isset($data['name'])) {
            $user->name = $data['name'];
        }
        if (isset($data['device_id'])) {
            dispatch(new StoreUserDeviceIdJob($user->email, $data['device_id']));
        }
        if (isset($data['email'])) {
            $user->email = $data['email'];
        }
        if (isset($data['password'])) {
            $user->password = app('hash')->make($data['password']);
        }
        if (isset($data['profile_image']) && isset($data['file_name'])) {
            if ($user->profile_image) {
                $fileurl = explode("/", $user->profile_image);
                if (file_exists("assets/images/profileImages/" . end($fileurl)) && end($fileurl) != 'profileimage.png') {
                    unlink("assets/images/profileImages/" . end($fileurl));
                }
            }
            $file_name       = explode('.', $data['file_name']);
            $image_extension = end($file_name);
            $fileName        = uniqid() . '.' . $image_extension;
            file_put_contents(base_path('public/assets/images/profileImages/' . $fileName),
                base64_decode($data['profile_image']));
            $image_url           = asset('assets/images/profileImages/' . $fileName);
            $user->profile_image = $image_url;
        }
        if (isset($data['date_of_birth'])) {
            $user->date_of_birth = $data['date_of_birth'];
        }
        if (isset($data['timezone'])) {
            $user->timezone = $data['timezone'];
        }
        if (isset($data['start_week'])) {
            $user->start_week = $data['start_week'];
        }
        if (isset($data['use_device_timezoon'])) {
            $user->use_device_timezoon = $data['use_device_timezoon'];
        }
        if (isset($data['device_notification'])) {
            $user->device_notification = $data['device_notification'];
        }
        if (isset($data['email_reminder'])) {
            $user->email_reminder = $data['email_reminder'];
        }
        if (isset($data['registration_type'])) {
            $user->registration_type = $data['registration_type'];
        }
        $user->save();

        return $user;
    }

    /**
     * @param $id
     *
     * @return mixed
     */
    public function delete($id) {
        $user = User::find($id);
        if ( ! $user) {
            return 0;
        }
        if ($user->profile_image) {
            $fileurl = explode("/", $user->profile_image);
            if (file_exists("assets/images/profileImages/" . end($fileurl)) && end($fileurl) != 'profileimage.png') {
                unlink("assets/images/profileImages/" . end($fileurl));
            }
        }

        return $user->delete();
    }

    /**
     * @param Request $request
     *
     * @return mixed
     */
    public function emailValidation(Request $request) {
        return User::where('email', $request->input('email'))->where('registration_type', 'confirmed')->first();
    }

    /**
     * @param Request $request
     *
     * @return mixed
     */
    public function socialLogin(Request $request) {
        $user = User::where('email', $request->input('email'))->first();
        if ( ! $user) {
            $userData                  = $request->only(['name', 'email', 'registration_type']);
            $userData['id']            = Uuid::uuid4()->toString();
            $userData['device_id']     = serialize([$request->input('device_id')]);
            $userData['timezone']      = $request->has('timezone') ? $request->input('timezone') : 'GMT+6';
            $userData['profile_image'] = asset('assets/images/' . 'profileimage.png');

            return User::create($userData);
        }

        return $user;
    }
}
