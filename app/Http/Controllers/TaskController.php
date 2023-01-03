<?php

namespace App\Http\Controllers;

use App\Repositories\TaskRepository;
use App\Transformers\TaskStatsTransformer;
use App\Transformers\TaskByDayTransformer;
use App\Transformers\TaskTransformer;
use App\Transformers\UserTransformer;
use Exception;
use http\Env\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use App\Models\User;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use phpDocumentor\Reflection\Type;
use function PHPUnit\Framework\isEmpty;

class TaskController extends Controller
{
    private $taskRepository;

    /**
     * Instantiate a new TaskController instance.
     *
     * @param TaskRepository $taskRepository
     */
    public function __construct(TaskRepository $taskRepository)
    {
        $this->taskRepository = $taskRepository;
    }

    /**
     *
     * @OA\Schema(
     * schema="TaskResponse",
     * @OA\Xml(name="TaskResponse"),
     *  @OA\Property(property="id", type="string", example="98a18252-1533-4535-9845-d68400d83122"),
     *  @OA\Property(property="name", type="string", example="clockdo meeting"),
     *  @OA\Property(property="user_id", type="string", example="67625f4d-54b5-48d6-8c73-843f01ec69ad"),
     *  @OA\Property(property="due_date", type="date", example="2021-05-23 19:45:00"),
     *  @OA\Property(property="reminder", type="boolean", example="ture"),
     *  @OA\Property(property="status", type="string", example="todo"),
     *  @OA\Property(property="parent_id", type="string", example="98a18252-1533-4535-9845-d68400d83123"),
     *  @OA\Property(property="sub_tasks", type="array",
     *         @OA\Items(
     *             @OA\Property( property="data", type="object", ref="#/components/schemas/Task" )
     *             )
     *      ),
     *  @OA\Property(property="user", type="array", collectionFormat= "multi",
     *       @OA\Items(
     *             @OA\Property( property="name", type="string", example="mamun" ),
     *             @OA\Property( property="email", type="string", example="mamun@gmail.com" )
     *            )
     *      ),
     *  @OA\Property(property="sharings", type="array", collectionFormat= "multi",
     *       @OA\Items(
     *             @OA\Property( property="name", type="string", example="mamun" ),
     *             @OA\Property( property="email", type="string", example="mamun@gmail.com" )
     *            )
     *      )
     *
     * )
     *
     *
     */


    /**
     * @OA\Get(
     *     path="/api/tasks",
     *     operationId="allTask",
     *     summary="all task info",
     *     description="get all task's information for authentic user",
     *     tags={"Task"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response="200",
     *         description="Returns all task information",
     *         @OA\JsonContent(
     *           @OA\Property(property="code", type="string", example="200"),
     *           @OA\Property(property="success", type="string", example="true"),
     *           @OA\Property(property="error", type="string", example=""),
     *           @OA\Property(property="data", type="array",
     *                  @OA\Items(
     *                      @OA\Property( type="object", ref="#/components/schemas/TaskResponse" )
     *                   )
     *            )
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

        $tasks = $this->taskRepository->all();

        return responseMessage(200, true, '', $this->collection($tasks, new TaskTransformer()));
    }

    /**
     * @OA\Get(
     *     path="/api/hasAny-task",
     *     operationId="hasAny-task",
     *     summary="has any task",
     *     description="checking user has any task or not",
     *     tags={"Task"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response="200",
     *         description="Returns true in data if user has any task and viceversa",
     *         @OA\JsonContent(
     *          @OA\Property(property="code", type="string", example="200"),
     *          @OA\Property(property="success", type="string", example="true"),
     *          @OA\Property(property="error", type="string", example=""),
     *          @OA\Property(property="data", type="string", example="true"),
     *        )
     *     ),
     * @OA\Response(
     *    response=401,
     *    description="Returns when user is not authenticated",
     *    @OA\JsonContent(
     *      @OA\Property(property="code", type="string", example="401"),
     *       @OA\Property(property="success", type="string", example="false"),
     *       @OA\Property(property="error", type="string", example="Not authorized"),
     *    )
     * )
     * )
     */



    public function hasAnyTask()
    {
        $hasAnyTask = $this->taskRepository->hasAnyTask();

       if ( $hasAnyTask ) {
           return responseMessage(200, true, '', 'true');
       }
        return responseMessage(200, false, '', 'false');

    }


    /**
     * @OA\Get(
     *     path="/api/tasks/{id}",
     *     operationId="taskShow",
     *     summary="get task details",
     *     description="get a specific task's details by Id",
     *     tags={"Task"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Task Id",
     *         required=true,
     *
     *         example= "98a18252-1533-4535-9845-d68400d83122"
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Returns the specific task's details",
     *         @OA\JsonContent(
     *          @OA\Property(property="code", type="string", example="200"),
     *          @OA\Property(property="success", type="string", example="true"),
     *          @OA\Property(property="error", type="string", example=""),
     *          @OA\Property(property="data", type="object", ref="#/components/schemas/TaskResponse"),
     *        )
     *     ),
     * @OA\Response(
     *    response=401,
     *    description="Returns when user is not authenticated",
     *    @OA\JsonContent(
     *          @OA\Property(property="code", type="string", example="200"),
     *          @OA\Property(property="success", type="string", example="false"),
     *          @OA\Property(property="error", type="string", example="Not authorized"),
     *    )
     * ),
     * @OA\Response(
     *    response=404,
     *    description="Returns when task not found",
     *    @OA\JsonContent(
     *       @OA\Property(property="code", type="string", example="404"),
     *       @OA\Property(property="success", type="string", example="false"),
     *       @OA\Property(property="error", type="string", example=" route not found"),
     *       @OA\Property(property="data", type="object", example="null"),
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
        $task = $this->taskRepository->findById($id);

        if ($task->isEmpty()) {
            return responseMessage(404, false, trans('messages.taskNotFoundById'));
        }
        return responseMessage(200, true, '', $this->collection($task, new TaskTransformer()));
    }


    /**
     * @OA\Post(
     * path="/api/tasks",
     * summary="Task crate",
     * description="create new task",
     * operationId="createTask",
     * tags={"Task"},
     * security={{"bearerAuth":{}}},
     * @OA\RequestBody(
     *    required=true,
     *    description="needs name, user_id, due_date must ",
     *    @OA\JsonContent(
     *       required={"name", "user_id", "due_date" },
     *       @OA\Property(property="id", type="string", example="98a18252-1533-4535-9845-d68400d83122"),
     *       @OA\Property(property="name", type="string", example="clockdo meeting"),
     *       @OA\Property(property="user_id", type="string", example="67625f4d-54b5-48d6-8c73-843f01ec69ad"),
     *       @OA\Property(property="due_date", type="date", example="2021-05-23 19:45:00"),
     *       @OA\Property(property="reminder", type="boolean", example="ture"),
     *       @OA\Property(property="status", type="string", example="todo"),
     *       @OA\Property(property="parent_id", type="string", example="98a18252-1533-4535-9845-d68400d83123"),
     *       @OA\Property(property="sub_tasks", type="array",
     *                  @OA\Items(
     *                      @OA\Property( type="object", ref="#/components/schemas/Task" )
     *                   )
     *            )
     *      ),
     *  ),
     * @OA\Response(
     *    response=404,
     *    description="returns validation errors",
     *    @OA\JsonContent(
     *          @OA\Property(property="code", type="string", example="404"),
     *          @OA\Property(property="success", type="string", example="false"),
     *          @OA\Property(property="eroor", type="string", example=" requied task name user id etc"),
     *          @OA\Property(property="data", type="object",  example="null")
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
     *    response=201,
     *    description="returns when task create successed with created task deatils",
     *    @OA\JsonContent(
     *          @OA\Property(property="code", type="string", example="201"),
     *          @OA\Property(property="success", type="string", example="true"),
     *          @OA\Property(property="error", type="string", example=""),
     *          @OA\Property(property="data", type="object", ref="#/components/schemas/TaskResponse")
     *        )
     *     )
     *
     * )
     */




    /**
     * @param Request $request
     * @return array|JsonResponse|null
     */
    public function store(Request $request)
    {
        //validate incoming request
        $rules = [
            'name' => 'required|string',
            'due_date' => 'required|date|after:today',
            'status' => 'in:todo,done,late',
            'user_id' => 'required'
        ];

        $customMessages = trans('validation.custom');
        $validator = Validator::make($request->all(), $rules, $customMessages);
        if ( $validator->fails() )
        {
            return responseMessage(400, false, '', $validator->errors());
        }

        $task = $this->taskRepository->store($request);
        return responseMessage(201, true, '', $this->item($task, new TaskTransformer()));

    }

    /**
     * @OA\Put(
     * path="/api/tasks/{id}",
     * summary="Task update",
     * description="Update a task information",
     * operationId="updateTask",
     * tags={"Task"},
     * security={{"bearerAuth":{}}},
     * @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Task Id",
     *         required=true,
     *         example= "98a18252-1533-4535-9845-d68400d83122"
     *     ),
     * @OA\RequestBody(
     *    required=true,
     *    description=" set name, reminder, due_date, status etc to update the task ",
     *    @OA\JsonContent(
     *       @OA\Property(property="name", type="string", example="clockdo meeting"),
     *       @OA\Property(property="due_date", type="date", example="2021-05-23 19:45:00"),
     *       @OA\Property(property="reminder", type="boolean", example="ture"),
     *       @OA\Property(property="status", type="string", example="todo"),
     *       @OA\Property(property="sub_tasks", type="array",
     *                  @OA\Items(
     *                      @OA\Property( type="object", ref="#/components/schemas/Task" )
     *                    )
     *            )
     *    )
     * ),
     * @OA\Response(
     *    response=401,
     *    description="Returns when user is not authenticated",
     *    @OA\JsonContent(
     *       @OA\Property(property="code", type="string", example="401"),
     *       @OA\Property(property="success", type="string", example="false"),
     *       @OA\Property(property="error", type="string", example="Not authorized or route not found"),
     *    )
     * ),
     * @OA\Response(
     *    response=404,
     *    description="Returns when user id is not valid",
     *    @OA\JsonContent(
     *       @OA\Property(property="code", type="string", example="404"),
     *       @OA\Property(property="success", type="string", example="false"),
     *       @OA\Property(property="error", type="string", example="route not found"),
     *       @OA\Property(property="data", type="object",  example="null")
     *    )
     * ),
     * @OA\Response(
     *    response=200,
     *    description="returns updated task details",
     *    @OA\JsonContent(
     *       @OA\Property(property="code", type="string", example="200"),
     *       @OA\Property(property="success", type="string", example="true"),
     *       @OA\Property(property="error", type="string", example=""),
     *       @OA\Property(property="data", type="object", ref="#/components/schemas/TaskResponse")
     *        )
     *     )
     *
     * )
     */


    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function update(Request $request)
    {
        $task = $this->taskRepository->update($request);
        return responseMessage(200, true, '', $this->item($task, new TaskTransformer()));
    }


    /**
     * @OA\Delete(
     *     path="/api/tasks/{id}",
     *     summary="Delete a task",
     *     operationId="taskDelte",
     *     tags={"Task"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         description="task id to delete",
     *         in="path",
     *         name="id",
     *         required=true,
     *         example = "98a18252-1533-4535-9845-d68400d83122"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="returns when task successfully deleted with deleted user's info",
     *         @OA\JsonContent(
     *          @OA\Property(property="code", type="string", example="200"),
     *          @OA\Property(property="success", type="sting", example="successfully deleted")
     *        )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="return when task delete is not successful or invalid task id",
     *         @OA\JsonContent(
     *          @OA\Property(property="code", type="string", example="404"),
     *           @OA\Property(property="success", type="string", example="false"),
     *           @OA\Property(property="error", type="string", example="task delete is not successful")
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
        $status = $this->taskRepository->delete($id);

        if ($status){
            return responseMessage(200, trans('messages.taskDeleted'));
        }

        return responseMessage(404, false, trans('messages.taskDeleteFailed'));
    }


    /**
     * @OA\Get(
     *     path="/api/tasks-by-date/{date}",
     *     operationId="taskBydate",
     *     summary="get tasks by date",
     *     description="get all task for the specific date and next date by a date",
     *     tags={"Task"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="date",
     *         in="path",
     *         description="date for getiing task",
     *         required=true,
     *         example= "2021-05-23"
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Returns all task for the date with next dated task ",
     *         @OA\JsonContent(
     *           @OA\Property(property="code", type="string", example="200"),
     *           @OA\Property(property="success", type="string", example="true"),
     *           @OA\Property(property="data", type="array",
     *                  @OA\Items(
     *                      @OA\Property(property="currentday", type="object", ref="#/components/schemas/TaskResponse" ),
     *                      @OA\Property(property="nextday", type="object", ref="#/components/schemas/TaskResponse" )
     *                   )
     *            )
     *
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
     *       @OA\Property(property="error", type="string", example="Task not found or route not found"),
     *    )
     * )
     * )
     */


    /**
     * @param $date
     * @return JsonResponse
     */
    public function taskByDate($date) {
        $tasks = $this->taskRepository->taskByDate($date);

        $tasks->today = $this->collection($tasks->today, new TaskTransformer());
        $tasks->tomorrow = $this->collection($tasks->tomorrow, new TaskTransformer());

        return responseMessage(200, true, '', $this->item(collect($tasks), new TaskByDayTransformer()));
    }



    /**
     * @OA\Get(
     *     path="/api/tasks-stats",
     *     operationId="tasks-stats",
     *     summary="weekly, monthly, yearly task",
     *     description="get user's weekly task, monthly task and yearly task for progress report",
     *     tags={"Task"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response="200",
     *         description="Returns all weekly, monthly and yearly wise task ",
     *         @OA\JsonContent(
     *           @OA\Property(property="code", type="string", example="200"),
     *           @OA\Property(property="success", type="string", example="true"),
     *           @OA\Property(property="data", type="array",
     *                  @OA\Items(
     *                      @OA\Property(property="weekly", type="object", ref="#/components/schemas/TaskResponse" ),
     *                      @OA\Property(property="monthly", type="object", ref="#/components/schemas/TaskResponse" ),
     *                      @OA\Property(property="yearly", type="object", ref="#/components/schemas/TaskResponse" )
     *                   )
     *            )
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
     * )
     */


    /**
     * @param $date
     * @return JsonResponse
     */
    public function progressStats() {

        $tasks = $this->taskRepository->tasksStats();


        $newMonthData = [];
        foreach ( $tasks->monthly as  $key => $task) {

            $newMonthData[$key] = $this->collection($tasks->monthly[$key], new TaskTransformer());
        }

        $tasks->weekly = $this->collection($tasks->weekly, new TaskTransformer());
        $tasks->monthly =  $newMonthData;
        $tasks->yearly = $this->collection($tasks->yearly, new TaskTransformer());

        return responseMessage(200, true, '', $this->item(collect($tasks), new TaskStatsTransformer()));
    }


    /**
     * @OA\Post(
     * path="/api/tasks/{id}/{type}",
     * summary="Task sharing",
     * description="share a task to multiple users",
     * operationId="shareTask",
     * tags={"Task"},
     * security={{"bearerAuth":{}}},
     * @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="task id wich will be shared to another user",
     *         required=true,
     *         example= "98a18252-1533-4535-9845-d68400d83122"
     *     ),
     * @OA\Parameter(
     *         name="type",
     *         in="path",
     *         description="type means share or assign",
     *         required=true,
     *         example= "share"
     *     ),
     * @OA\RequestBody(
     *    required=true,
     *    @OA\JsonContent(
     *         @OA\Property(property="sharings", type="array",
     *             @OA\Items(
     *                @OA\Property(property="email", type="email", example="mamun@gmial.com")
     *              )
     *          )
     *      )
     *  ),
     * @OA\Response(
     *    response=401,
     *    description="Returns when user is not authenticated",
     *    @OA\JsonContent(
     *       @OA\Property(property="code", type="string", example="401" ),
     *       @OA\Property(property="success", type="string", example="false" ),
     *       @OA\Property(property="error", type="string", example="Not authorized"),
     *    )
     * ),
     * @OA\Response(
     *    response=200,
     *    description="returns when task shared successfully",
     *    @OA\JsonContent(
     *      @OA\Property(property="code", type="string", example="200" ),
     *       @OA\Property(property="success", type="string", example="true" ),
     *       @OA\Property(property="error", type="string", example="" ),
     *       @OA\Property(property="data", type="object", example="null"  )
     *        )
     *     ),
     *  @OA\Response(
     *    response=400,
     *    description="returns for validation error",
     *    @OA\JsonContent(
     *       @OA\Property(property="code", type="string", example="400" ),
     *       @OA\Property(property="success", type="string", example="false" ),
     *       @OA\Property(property="error", type="string", example="must be an eamil" ),
     *        )
     *     )
     *
     * )
     */


    /**
     * @param Request $request
     * @param $id
     * @param $type
     * @return JsonResponse
     * @throws ValidationException
     */
    public function taskAssigning(Request $request, $id, $type) {

        $rules = [
            'email.*' => 'required|email'
        ];
        $validator = Validator::make($request->all(), $rules, trans('validation.custom'));
        if ( $validator->fails() )
        {
            return responseMessage(400, false,  $validator->errors());
        }
        $task = $this->taskRepository->taskAssigning($request, $id, $type);
        $parentTask = Task::with('user', 'subTasks', 'assignees', 'sharings')->find($task->parent_id);
        if($parentTask)
        {
            return   responseMessage(200, true, '', $this->item($parentTask, new TaskTransformer()) );
        }
     return   responseMessage(200, true, '', $this->item($task, new TaskTransformer()) );

    }

    /**
     * @OA\Delete(
     *     path="/api/tasks/{id}/{type}/removeAssignedUser",
     *     summary="remove shared user",
     *     description="remove a user from shared task",
     *     operationId="removeShared",
     *     tags={"Task"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         description="user id to remove a user from shared task",
     *         in="path",
     *         name="id",
     *         required=true,
     *         example = "d7ad778c-0760-46c0-875b-9e7b37bf2604"
     *     ),
     *     @OA\Parameter(
     *         description="type like share or assign ",
     *         in="path",
     *         name="type",
     *         required=true,
     *         example = "share"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="returns when user successfully removed from shared task",
     *         @OA\JsonContent(
     *           @OA\Property(property="code", type="string", example="200" ),
     *           @OA\Property(property="success", type="string", example="successfully deleted" ),
     *        )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="return when User remove from a task is not successful",
     *         @OA\JsonContent(
     *           @OA\Property(property="code", type="string", example="404" ),
     *           @OA\Property(property="success", type="string", example="false" ),
     *           @OA\Property(property="error", type="string", example="failed to delte")
     *        )
     *     ),
     * @OA\Response(
     *    response=401,
     *    description="Returns when user is not authenticated",
     *    @OA\JsonContent(
     *       @OA\Property(property="code", type="string", example="401" ),
     *       @OA\Property(property="success", type="string", example="false" ),
     *       @OA\Property(property="error", type="string", example="Not authorized"),
     *    )
     * )
     * )
     * @param Request $request
     * @param         $id
     * @param         $type
     *
     * @return JsonResponse
     */


    public function removeAssignedUser(Request $request, $id, $type){

        $rules = [
            'email' => 'required|email'
        ];

        $validator = Validator::make($request->all(), $rules, trans('validation.custom'));
        if ( $validator->fails() )
        {
            return responseMessage(400, false, '', $validator->errors());
        }
        $removed = $this->taskRepository->removeAssignedUser($request, $id, $type);
        $taskToShare = Task::with('user', 'subTasks', 'assignees', 'sharings')->findOrFail($id);
        if( $removed ){
            return responseMessage(200, trans('messages.shareRemoved'), '', $this->item($taskToShare, new TaskTransformer()));
        }
        return responseMessage(201, 'false', trans('messages.shareRemovedFailed'), $this->item($taskToShare, new TaskTransformer()));
    }

    public function sharedTask($id){

        $tasks = $this->taskRepository->sharedTask(User::findOrFail($id))->get();

        return responseMessage(200, true, '', $this->collection($tasks, new TaskTransformer()));
    }

    public function sharedUser($id){

        $sharings = $this->taskRepository->sharedUser($id);
        return responseMessage(200, true, '', $this->collection($sharings->sharings, new UserTransformer()));

    }
}
