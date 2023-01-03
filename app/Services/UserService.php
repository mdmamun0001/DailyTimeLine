<?php
namespace App\Services;


use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Http\Request;

class UserService {
    private $userRepository;
    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }
    public function checkUser(Request $request) {
        $user = User::where('email', $request->input('email'))
            ->where('registration_type', 'initialized')
            ->orWhere('registration_type', null)
            ->first();

        if ($user) {
            return $this->userRepository->update($request, $user);
        }
        return $this->userRepository->create($request);
    }

    public function checkExistingUser(string $email, array $registration_type) {
        return User::where('email', $email)
            ->whereIn('registration_type', $registration_type)
            ->first();
    }
}
