<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;


use OwenIt\Auditing\Contracts\Auditable;
use Spatie\Permission\Traits\HasRoles;
use Tymon\JWTAuth\Contracts\JWTSubject;

/**
 *
 * @OA\Schema(
 *  required={"name", "email","password", "device_id", "registration_type"},
 * @OA\Xml(name="User"),
 * @OA\Property(property="name", type="string", example="rafsan"),
 * @OA\Property(property="email", type="string", format="email", example="rafsan@mail.com"),
 * @OA\Property(property="password", type="string", format="password", example="PassWord12345"),
 * @OA\Property(property="device_id", type="string", example="f8YQIuq_SVqMjEdWyQs8mq:APA91bFYUVkauPUb01kjJgco5L4egj28OjMf2vk4KTzVu615hJzHwGnC-tziJH705W4C753QTLx9QUoERg7ox_Gloyqf-yD_avCvq4RpSLSPNKavNmUnad1lB84XmesppchiQUNNwNQ4"),
 * @OA\Property(property="registration_type", type="string", example="confirmed"),
 * @OA\Property(property="profile_image", type="string", example="https://clockdo.test/assets/images/profileImages/shohag-islam1621786916.jpg"),
 * @OA\Property(property="date_of_birth", type="date", example="01-01-1996"),
 * @OA\Property(property="timezone", type="string", example="GMT+6"),
 * @OA\Property(property="start_week", type="string", example="SUNDAY"),
 * @OA\Property(property="use_device_timezoon", type="boolean", example="false"),
 * @OA\Property(property="device_notification", type="boolean", example="false"),
 * @OA\Property(property="email_reminder", type="boolean", example="true")
 * )
 * Class User
 *
 */

class User extends Model implements AuthenticatableContract, AuthorizableContract, JWTSubject, Auditable
{
    use Authenticatable, Authorizable, HasRoles;
    use \OwenIt\Auditing\Auditable;

    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'name', 'email', 'password', 'device_id', 'profile_image', 'date_of_birth', 'timezone', 'registration_type'
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'password',
    ];


    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function assignTasks()
    {
        return $this->belongsToMany(Task::class)->withPivot('type');
    }

    public function sharedTasks() {
        return $this->belongsToMany(Task::class)
                    ->withPivot('type')
                    ->wherePivot('type', '=', 'share');
    }
}
