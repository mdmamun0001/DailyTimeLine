<?php

namespace App\Http\Controllers;

use App\Jobs\StoreUserDeviceIdJob;
use App\Repositories\UserRepository;
use App\Services\UserService;
use App\Transformers\UserTransformer;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use JWTAuth;

class AuthController extends Controller {
    private $userRepository;
    private $userService;

    public function __construct(UserRepository $userRepository, UserService $userService) {
        $this->userRepository = $userRepository;
        $this->userService    = $userService;
    }



    /**
     * @OA\Post(
     * path="/api/register",
     * summary="Sign up",
     * description="Register by  email",
     * operationId="authRegister",
     * tags={"User"},
     * @OA\RequestBody(
     *    required=true,
     *    description="A user needs to provide the required information ",
     *    @OA\JsonContent(
     *       required={"name", "email", "password"},
     *       @OA\Property(property="name", type="string", example="rafsan"),
     *       @OA\Property(property="email", type="string", format="email", example="rafsan@mail.com"),
     *       @OA\Property(property="password", type="string", format="password", example="PassWord12345"),
     *       @OA\Property(property="device_id", type="string",
     *                                          example="f8YQIuq_SVqMjEdWyQs8mq:APA91bFYUVkauPUb01kjJgco5L4egj28OjMf2vk4KTzVu615hJzHwGnC-tziJH705W4C753QTLx9QUoERg7ox_Gloyqf-yD_avCvq4RpSLSPNKavNmUnad1lB84XmesppchiQUNNwNQ4"),
     *
     *      @OA\Property(property="registration_type", type="string", example="confirmed"),
     *
     *    ),
     * ),
     * @OA\Response(
     *    response=400,
     *    description="Registeration failed if email has already exist",
     *    @OA\JsonContent(
     *         @OA\Property(property="code", type="string", example="400"),
     *         @OA\Property(property="success", type="string", example="false"),
     *         @OA\Property(property="error", type="string", example="Email has already exist or user not created"),
     *         @OA\Property(property="data", type="object", example="null")
     *        )
     *     ),
     * @OA\Response(
     *    response=200,
     *    description="Registeration successed",
     *    @OA\JsonContent(
     *        @OA\Property(property="code", type="string", example="201"),
     *        @OA\Property(property="success", type="string", example="true"),
     *        @OA\Property(property="error", type="string", example=""),
     *        @OA\Property(property="data", type="object", ref="#/components/schemas/User")
     *       )
     *    )
     * )
     */
    /**
     * Store a new user.
     *
     * @param Request $request
     *
     * @return array | JsonResponse
     * @throws ValidationException
     */
    public function register(Request $request) {
        //validate incoming request
        $this->validate($request, [
            'name' => 'required|string',
            'email' => 'required|email',
            'password' => 'required',
            'registration_type' => 'in:initialized,confirmed'
        ],
            [
            'registration_type.in' => 'would be initialized or confirmed'
        ]);

        try {
            $registration_type = config('constants.registration_type');
            array_push($registration_type, 'confirmed');
            $user = $this->userService->checkExistingUser($request->input('email'), $registration_type);
            if ($user) {
                return responseMessage(400, false, trans('messages.emailAlreadyExist'));
            }
            $user = $this->userService->checkUser($request);
            if ( ! $user) {
                return responseMessage(400, false, trans('messages.userNotCreated'));
            }
            $credentials = $request->only(['email', 'password']);
            $token       = Auth::attempt($credentials);

            //return successful response
            return responseMessage(201, true, '', $this->respondWithToken($token)->original);
        } catch (Exception $e) {
            //return error message
            return responseMessage($e->getCode(), false, $e->getMessage());
        }
    }

    /**
     * @OA\Post(
     * path="/api/login",
     * summary="login",
     * description="user login by email, password",
     * operationId="authLogin",
     * tags={"User"},
     * @OA\RequestBody(
     *    required=true,
     *    description="A user needs to provide the required information ",
     *    @OA\JsonContent(
     *       required={"email","password"},
     *       @OA\Property(property="email", type="string", format="email", example="shohag@gmail.com"),
     *       @OA\Property(property="password", type="string", format="password", example="12345678"),
     *       @OA\Property(property="device_id", type="string",
     *                                          example="f8YQIuq_SVqMjEdWyQs8mq:APA91bFYUVkauPUb01kjJgco5L4egj28OjMf2vk4KTzVu615hJzHwGnC-tziJH705W4C753QTLx9QUoERg7ox_Gloyqf-yD_avCvq4RpSLSPNKavNmUnad1lB84XmesppchiQUNNwNQ4")
     *    )
     * ),
     * @OA\Response(
     *    response=401,
     *    description="Wrong credentials response",
     *    @OA\JsonContent(
     *       @OA\Property(property="code", type="string", example="401"),
     *       @OA\Property(property="success", type="string", example="false"),
     *       @OA\Property(property="eroor", type="string", example="Credentials are not correct"),
     *       @OA\Property(property="data", type="object", example="null"),
     *        )
     *     ),
     * @OA\Response(
     *    response=412,
     *    description="social Login Only",
     *    @OA\JsonContent(
     *       @OA\Property(property="code", type="string", example="412"),
     *       @OA\Property(property="success", type="string", example="false"),
     *       @OA\Property(property="eroor", type="string", example="This email is used for social login only"),
     *       @OA\Property(property="data", type="object", example="null"),
     *      )
     *   ),
     * @OA\Response(
     *    response=200,
     *    description="login successed",
     *    @OA\JsonContent(
     *         @OA\Property(property="code", type="string", example="200"),
     *         @OA\Property(property="success", type="string", example="true"),
     *         @OA\Property(property="error", type="string", example=""),
     *         @OA\Property(property="data", type="object", ref="#/components/schemas/User")
     *        )
     *     )
     * )
     */
    /**
     * Get a JWT via given credentials.
     *
     * @param Request $request
     *
     * @return JsonResponse
     * @throws ValidationException
     */

    public function login(Request $request)
    {
        $user = $this->userService->checkExistingUser($request->input('email'), config('constants.registration_type'));
        if ($user) {
            return responseMessage(412, false, trans('messages.socialLoginOnly'));
        }
        //validate incoming request
        $this->validate($request, [
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);
        $credentials = $request->only(['email', 'password']);
        Auth::factory()->getTTL(config('auth.token_ttl'));
        if ( ! $token = Auth::attempt($credentials)) {
            return responseMessage(401, false, trans('messages.credentialsNotCorrect'));
        }
        dispatch(new StoreUserDeviceIdJob($request->input('email'), $request->input('device_id')));

        return responseMessage(200, true, '', $this->respondWithToken($token)->original);
    }

    /**
     * @OA\Post(
     * path="/api/logout",
     * summary="Logout",
     * description="Logout user and invalidate token",
     * operationId="authLogout",
     * tags={"User"},
     * security={{"bearerAuth":{}}},
     * @OA\RequestBody(
     *    required=false,
     *    description=" optional information",
     *    @OA\JsonContent(
     *       @OA\Property(property="device_id", type="string", format="string",
     *                                          example="f8YQIuq_SVqMjEdWyQs8mq:APA91bFYUVkauPUb01kjJgco5L4egj28OjMf2vk4KTzVu615hJzHwGnC-tziJH705W4C753QTLx9QUoERg7ox_Gloyqf-yD_avCvq4RpSLSPNKavNmUnad1lB84XmesppchiQUNNwNQ4"),
     *    ),
     * ),
     * @OA\Response(
     *    response=200,
     *    description="Success",
     *    @OA\JsonContent(
     *       @OA\Property(property="code", type="string", example="200"),
     *       @OA\Property(property="success", type="string", example="true"),
     *       @OA\Property(property="error", type="string", example=""),
     *       @OA\Property(property="user", type="object", ref="#/components/schemas/User"),
     *      )
     *   ),
     * @OA\Response(
     *    response=401,
     *    description="Returns when user is not authenticated",
     *    @OA\JsonContent(
     *       @OA\Property(property="code", type="string", example="401"),
     *       @OA\Property(property="success", type="string", example="false"),
     *       @OA\Property(property="error", type="string", example="Not authorized"),
     *    )
     * )
     * )
     */
    public function logout(Request $request) {
        $user      = Auth::user();
        $deviceIds = unserialize($user->device_id);
        if (($key = array_search($request->input('device_id'), $deviceIds)) !== false) {
            unset($deviceIds[$key]);
            $deviceIds       = array_values($deviceIds);
            $user->device_id = serialize($deviceIds);
            $user->save();
        }
        Auth::logout();

        return responseMessage(200, true, '', $user);
    }

    /**
     * @OA\Post(
     * path="/api/koobecaf",
     * summary="Facebook login",
     * description="user login by facebook",
     * operationId="authLoginFacebook",
     * tags={"User"},
     * @OA\RequestBody(
     *    required=true,
     *    description="A user needs to provide the required information ",
     *    @OA\JsonContent(
     *       required={"email","password", "profile_image", "device_id", "registration_type"},
     *       @OA\Property(property="email", type="string", format="email", example="shohag@gmail.com"),
     *       @OA\Property(property="password", type="string", format="password", example="12345678"),
     *       @OA\Property(property="profile_image", type="string", example="https://hello.com/image"),
     *       @OA\Property(property="device_id", type="string",
     *                                          example="f8YQIuq_SVqMjEdWyQs8mq:APA91bFYUVkauPUb01kjJgco5L4egj28OjMf2vk4KTzVu615hJzHwGnC-tziJH705W4C753QTLx9QUoERg7ox_Gloyqf-yD_avCvq4RpSLSPNKavNmUnad1lB84XmesppchiQUNNwNQ4"),
     *       @OA\Property(property="registration_type", type="string", example="confirmed"),
     *    )
     * ),
     * @OA\Response(
     *    response=400,
     *    description="returns when facebook login failed",
     *    @OA\JsonContent(
     *       @OA\Property(property="code", type="string", example="400"),
     *       @OA\Property(property="success", type="string", example="false"),
     *       @OA\Property(property="eroor", type="string", example="user not created"),
     *       @OA\Property(property="data", type="object", example="null")
     *        )
     *     ),
     * @OA\Response(
     *    response=401,
     *    description="returns when Credentials are not correct",
     *    @OA\JsonContent(
     *       @OA\Property(property="code", type="string", example="401"),
     *       @OA\Property(property="success", type="string", example="false"),
     *       @OA\Property(property="eroor", type="string", example="Credentials are not correct"),
     *       @OA\Property(property="data", type="object", example="null")
     *        )
     *     ),
     * @OA\Response(
     *    response=200,
     *    description="returns success true with logedin user's data when login successed",
     *    @OA\JsonContent(
     *         @OA\Property(property="code", type="string", example="200"),
     *         @OA\Property(property="success", type="string", example="true"),
     *         @OA\Property(property="error", type="string", example=""),
     *         @OA\Property(property="data", type="object", ref="#/components/schemas/User")
     *        )
     *     )
     * )
     */
    /**
     * @param Request $request
     *
     * @return JsonResponse
     * @throws ValidationException
     */
    public function socialLogin(Request $request) {
        $this->validate($request, [
            'name' => 'required|string',
            'email' => 'required|email',
            'device_id' => 'required|string',
            'registration_type' => 'required|in:facebook,gmail'
        ]);
        $user = $this->userRepository->socialLogin($request);
        if ( ! $user) {
            return responseMessage(400, false, trans('messages.userNotCreated'));
        }
        if ( ! $token = JWTAuth::fromUser($user)) {
            return responseMessage(401, false, trans('messages.credentialsNotCorrect'));
        }
        $userDeviceIds = unserialize($user->device_id);
        if ( ! in_array($request->input('device_id'), $userDeviceIds)) {
            array_push($userDeviceIds, $request->input('device_id'));
            $user->device_id = serialize($userDeviceIds);
            $user->save();
        }
        $data = response()->json([
            'token'      => $token,
            'token_type' => 'bearer',
            'expires_in' => Auth::factory()->getTTL() * 60,
            'user'       => $this->item($user, new UserTransformer()),
        ], 200)->original;

        return responseMessage(200, true, '', $data);
    }
}
