<?php

namespace App\Http\Controllers;

use App\Repositories\UserRepository;
use App\Transformers\UserTransformer;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use App\Models\User;
use JWTAuth;

class UserController extends Controller
{
    private $userRepository;

    /**
     * Instantiate a new UserController instance.
     *
     * @param UserRepository $userRepository
     */

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * @OA\Get(
     *     path="/api/profile",
     *     operationId="userProfile",
     *     tags={"User"},
     *     summary="logedin user info",
     *     description="get logedin user's information",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response="200",
     *         description="Returns authenticated user profile information",
     *         @OA\JsonContent(
     *           @OA\Property(property="code", type="string", example="200"),
     *           @OA\Property(property="success", type="string", example="true"),
     *           @OA\Property(property="error", type="string", example=""),
     *           @OA\Property(property="data", type="object", ref="#/components/schemas/User"),
     *        )
     *     ),
     * @OA\Response(
     *    response=401,
     *    description="Returns when user is not authenticated",
     *    @OA\JsonContent(
     *       @OA\Property(property="code", type="string", example="401"),
     *       @OA\Property(property="success", type="string", example="false"),
     *       @OA\Property(property="error", type="string", example="Not authorized"),
     *
     *    )
     * )
     * )
     */

    /**
     * @return JsonResponse
     */

    public function profile()
    {
        return responseMessage(200, true, '', $this->item(Auth::user(), new UserTransformer()));
    }

    /**
     * @OA\Get(
     *     path="/api/users",
     *     operationId="allUsers",
     *     summary="get all user's info",
     *     description="get all user's information ",
     *     tags={"User"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response="200",
     *         description="Returns all users profile information",
     *         @OA\JsonContent(
     *             @OA\Property(property="code", type="string", example="200"),
     *             @OA\Property(property="success", type="string", example="true"),
     *             @OA\Property(property="error", type="string", example=""),
     *             @OA\Property(property="data", type="array",
     *                  @OA\Items(
     *                      @OA\Property( type="object", ref="#/components/schemas/User" )
     *                   )
     *              )
     *        )
     *     ),
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

    /**
     * @return JsonResponse
     */
    public function index()
    {
        $users = $this->userRepository->all();
        return responseMessage(200, true, '', $this->collection($users, new UserTransformer()));
    }

    /**
     * @OA\Get(
     *     path="/api/users/{id}",
     *     operationId="userShow",
     *     summary="get user info",
     *     description="get a specific user's information by Id",
     *     tags={"User"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="User Id",
     *         required=true,
     *         @OA\Schema(type="string"),
     *         example= "7d5b5a83-0c38-4392-80cf-421f2c9516ab"
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Returns the specific user's profile information",
     *         @OA\JsonContent(
     *           @OA\Property(property="code", type="string", example="200"),
     *           @OA\Property(property="success", type="string", example="true"),
     *           @OA\Property(property="error", type="string", example=""),
     *           @OA\Property(property="data", type="object",ref="#/components/schemas/User"),
     *        )
     *     ),
     * @OA\Response(
     *    response=401,
     *    description="Returns when user is not authenticated",
     *    @OA\JsonContent(
     *       @OA\Property(property="code", type="string", example="401"),
     *       @OA\Property(property="success", type="string", example="false"),
     *       @OA\Property(property="error", type="string", example="Not authorized"),
     *    )
     * ),
     * @OA\Response(
     *    response=404,
     *    description="Returns when user not found",
     *    @OA\JsonContent(
     *       @OA\Property(property="code", type="string", example="404"),
     *       @OA\Property(property="success", type="string", example="false"),
     *       @OA\Property(property="error", type="string", example=" route not found"),
     *       @OA\Property(property="data", type="object", example="null")
     *    )
     * )
     * )
     */


    /**
     * @param $id
     * @return array|JsonResponse|null
     */
    public function show($id)
    {
        $user = $this->userRepository->findById($id);
        return responseMessage(200, true, '', $this->item($user, new UserTransformer()));
    }

    /**
     * @OA\Post(
     * path="/api/users/{id}",
     * summary="user update",
     * description="update user's information",
     * operationId="userUpdate",
     * tags={"User"},
     * security={{"bearerAuth":{}}},
     * @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="User Id",
     *         required=true,
     *         @OA\Schema(type="string"),
     *         example="7d5b5a83-0c38-4392-80cf-421f2c9516ab"
     *     ),
     * @OA\RequestBody(
     *    description="user  name, email, password, profile image etc to update",
     *    @OA\JsonContent(
     *       @OA\Property(property="name", type="string", example="mamun"),
     *       @OA\Property(property="email", type="string", format="email", example="user1@mail.com"),
     *       @OA\Property(property="password", type="string", format="password", example="PassWord12345"),
     *    ),
     * ),
     * @OA\Response(
     *    response=404,
     *    description="returns when failed update opertaion for user not found ",
     *    @OA\JsonContent(
     *         @OA\Property(property="code", type="string", example="400"),
     *         @OA\Property(property="success", type="string", example="false"),
     *         @OA\Property(property="error", type="string", example="route not found"),
     *         @OA\Property(property="data", type="object", example="null")
     *        )
     *     ),
     * @OA\Response(
     *    response=401,
     *    description="Returns when user is not authenticated",
     *    @OA\JsonContent(
     *       @OA\Property(property="code", type="string", example="401"),
     *       @OA\Property(property="success", type="string", example="false"),
     *       @OA\Property(property="error", type="string", example="Not authorized"),
     *    )
     * ),
     * @OA\Response(
     *    response=200,
     *    description="update successed",
     *    @OA\JsonContent(
     *          @OA\Property(property="code", type="string", example="200"),
     *          @OA\Property(property="success", type="string", example="ture"),
     *          @OA\Property(property="error", type="string", example=""),
     *          @OA\Property(property="data", type="object", ref="#/components/schemas/User")
     *        )
     *     )
     * )
     */


    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function update(Request $request)
    {
        if(isset($request->email))
        {
            $this->validate($request, [
                'email' => 'email|unique:users',
            ],
                [
                    'email.email' => 'please Provide right email address',
                    'email.unique' => 'This mail is used for another account',
                ]);
        }
       $user = $this->userRepository->update($request, Auth::user());
       return responseMessage(200, true, '', ['user'=>$this->item($user, new UserTransformer())]);
    }

    /**
     * @OA\Delete(
     *     path="/api/users/{id}",
     *     summary="Deletes a user",
     *     operationId="userDelte",
     *     tags={"User"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         description="user id to delete",
     *         in="path",
     *         name="id",
     *         required=true,
     *         example = "d7ad778c-0760-46c0-875b-9e7b37bf2604"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="returns when user successfully deleted with deleted user's info",
     *         @OA\JsonContent(
     *          @OA\Property(property="code", type="string", example="200"),
     *          @OA\Property(property="success", type="sting", example="true"),
     *          @OA\Property(property="error", type="string", example=""),
     *          @OA\Property(property="data", type="object", example="null"),
     *        )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="return when User delete is not successful for invalid id",
     *         @OA\JsonContent(
     *          @OA\Property(property="code", type="string", example="404"),
     *          @OA\Property(property="success", type="string", example="false"),
     *          @OA\Property(property="error", type="string", example="User delete is not successful"),
     *          @OA\Property(property="data", type="object", example="null"),
     *        )
     *     ),
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

    /**
     * @param $id
     * @return mixed
     */
    public function destroy($id)
    {
        $status = $this->userRepository->delete($id);

        if ($status == 0) {
            return responseMessage(404, false, trans('messages.userDeleteFailed'));
        }
        return responseMessage(200, true);
    }


    /**
     * @OA\Post(
     * path="/api/email-validate",
     * summary="email validation",
     * description="checking any user exist or not for this email",
     * operationId="emailValidate",
     * tags={"User"},
     * @OA\RequestBody(
     *    description="you have to give an email address ",
     *    @OA\JsonContent(
     *       @OA\Property(property="email", type="string", format="email", example="shohag@gmail.com"),
     *    ),
     * ),
     * @OA\Response(
     *    response=404,
     *    description="returns when user not found with this email ",
     *    @OA\JsonContent(
     *       @OA\Property(property="code", type="string", example="404"),
     *       @OA\Property(property="success", type="string", example="false"),
     *       @OA\Property(property="error", type="string", example="email not found"),
     *       @OA\Property(property="data", type="object", example="null")
     *        )
     *     ),
     * @OA\Response(
     *    response=200,
     *    description="returns when user found with this email",
     *    @OA\JsonContent(
     *       @OA\Property(property="code", type="string", example="200"),
     *       @OA\Property(property="success", type="string", example="true"),
     *       @OA\Property(property="error", type="string", example=""),
     *       @OA\Property(property="data", type="object", ref="#/components/schemas/User")
     *        )
     *     )
     * )
     */


    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function emailValidation(Request $request)
    {
        $user = $this->userRepository->emailValidation($request);
        if (!$user) {
            return responseMessage(404, false, trans('messages.emailNotFound'));
        }

        return responseMessage(200, true, '', $user);
    }


}
