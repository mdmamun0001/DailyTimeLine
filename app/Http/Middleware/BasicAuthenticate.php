<?php

namespace App\Http\Middleware;
use Closure;
use Illuminate\Contracts\Auth\Factory as Auth;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class BasicAuthenticate  {




    public function handle($request, Closure $next)
    {
        $email = $request->getUser();
        $password = $request->getPassword();
        $user = User::whereEmail($email)->first();
        if ($user && Hash::check($password, $user->password) )
        {
            return $next($request);
        }

        return response('wrong email and password! please try again', 401, ['WWW-Authenticate' => 'Basic']);
    }
}
