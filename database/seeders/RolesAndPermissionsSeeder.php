<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $user = new User();
        $user->id = '7d5b5a83-0c38-4392-80cf-421f2c9516ab';
        $user->name = 'Tushar';
        $user->email = 'tushar@augnitive.com';
        $user->password = app('hash')->make('password');
        $user->device_id = 'device id';
        $user->profile_image = 'profile image';
        $user->date_of_birth = '1997-09-07';
        $user->timezone = 'GMT+6';
        $user->registration_type = 'confirmed';

        $user->save();

        // create permissions
        Permission::create(['name' => 'add user']);
        Permission::create(['name' => 'update user']);
        Permission::create(['name' => 'delete user']);

        // create roles and assign created permissions

        // this can be done as separate statements
        $role = Role::create(['name' => 'admin']);
        $role->givePermissionTo('add user');
        $role->givePermissionTo('update user');
        $role->givePermissionTo('delete user');

        $user->assignRole($role);

    }
}
