<?php

namespace App\Repositories;

use App\Jobs\SendPushNotificationJob;
use App\Jobs\SendTaskShareEmailJob;
use App\Models\Task;
use App\Models\User;
use App\Transformers\TaskTransformer;
use Carbon\CarbonTimeZone;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Facades\Auth;
use function PHPUnit\Framework\isEmpty;
use Illuminate\Support\Collection;

class TaskRepository {
    private $userRepository;

    /**
     * Instantiate a new TaskController instance.
     *
     * @param UserRepository $userRepository
     */
    public function __construct(UserRepository $userRepository) {
        $this->userRepository = $userRepository;
    }

    public function all() {
        //auto  status changed to late for all task for the user
        Task::where('user_id', Auth::id())->where('due_date', '<', date('Y-m-d H:i:s'))->where('status',
                'todo')->update(['status' => 'late']);
        $tasks = Task::with('user', 'subTasks', 'assignees', 'sharings')->where('parent_id', null)->where('user_id',
                Auth::id())->orderBy('created_at', 'asc')->get();
        $sharedTask = $this->sharedTask(Auth::user())->with('user', 'subTasks', 'assignees', 'sharings')->get();
        $sharedTask = Task::lateStatusUpdate($sharedTask);
        // if main task and sub task both are shared get only main task
        $sharedTask = $this->removeItem($sharedTask);
        $tasks = $tasks->merge($sharedTask);

        return $tasks;
    }

    public function hasAnyTask() {
        if (Auth::user()->tasks()->where('parent_id', null)->count()) {
            return 1;
        }
        if ($this->sharedTask(Auth::user())->count()) {
            return 1;
        }

        return 0;
    }

    public function findById($id) {
        $task = Task::with('user', 'subTasks', 'assignees', 'sharings')->where('user_id', Auth::id())->where('id',
                $id)->get();
        $task = Task::lateStatusUpdate($task);

        return $task;
    }

    public function store(Request $request) {
        $taskData = $request->only('name', 'due_date', 'reminder', 'status', 'user_id', 'parent_id');
        $taskData['due_date'] = convertTimeToUTCzone($taskData['due_date'], Auth::user()->timezone);
        $max_date             = $taskData['due_date'];
        if ($request->has('id')) {
            $taskData['id'] = $request->input('id');
        } else {
            $taskData['id'] = Uuid::uuid4()->toString();
        }
        $task = Task::updateOrCreate(['id' => $taskData['id']], $taskData);
        if ($request->has('sub_tasks') && ! empty($request->sub_tasks)) {
            foreach ($request->sub_tasks as $item) {
                if ( ! array_key_exists('id', $item)) {
                    $item['id'] = Uuid::uuid4()->toString();
                }
                if (array_key_exists('due_date', $item)) {
                    $item['due_date'] = convertTimeToUTCzone($item['due_date'], Auth::user()->timezone);
                    $max_date         = $max_date < $item['due_date'] ? $item['due_date'] : $max_date;
                }
                Task::updateOrCreate(['id' => $item['id']], $item);
            }
        }
        $task->update(['due_date' => $max_date]);

        return $task;
    }

    public function update(Request $request) {
        $task = Task::findOrFail($request->id);
        $data = $request->only([
            'name',
            'due_date',
            'status',
            'reminder',
        ]);
        if (isset($data['name'])) {
            $task->name = $data['name'];
        }
        if (isset($data['due_date'])) {
            $updateDateTime = convertTimeToUTCzone($data['due_date'], Auth::user()->timezone);
            $task->update(['due_date' => $updateDateTime]);
            if ($task->parent_id) {
                $this->maxDateTimeToParent($task->parent_id);
            }
            if ($task->due_date > Carbon::now() && $task->status === 'late') {
                $task->statusUpdate('todo');
            }
        }
        if (isset($data['status'])) {
            $task->statusUpdate($data['status']);
        }
        if (isset($data['reminder'])) {
            $task->reminder = $data['reminder'];
        }
        if ($request->has('sub_tasks') && ! empty($request->sub_tasks)) {
            $max_date = $task->due_date;
            foreach ($request->sub_tasks as $item) {
                if ( ! array_key_exists('id', $item)) {
                    $item['id'] = Uuid::uuid4()->toString();
                }
                if (array_key_exists('due_date', $item)) {
                    $item['due_date'] = convertTimeToUTCzone($item['due_date'], Auth::user()->timezone);
                    $max_date         = $max_date < $item['due_date'] ? $item['due_date'] : $max_date;
                }
                $sub_task = Task::updateOrCreate(['id' => $item['id']], $item);
                if ($sub_task->status !== 'done' && $task->status === 'done') {
                    $task->statusUpdate('todo');
                }
            }
            $task->update(['due_date' => $max_date]);
        }
        $task->save();
        $parent_task = Task::find($task->parent_id);
        if ($parent_task) {
            return $parent_task;
        }

        return $task;
    }

    public function delete($id) {
        $task = Task::with('subTasks')->where('user_id', Auth::id())->findOrFail($id);
        foreach ($task->subTasks as $sub_task) {
            $sub_task->delete();
        }

        return $task->delete();
    }

    /**
     * @param $date
     *
     * @return object
     */
    public function taskByDate($date) {
        //auto  status changed to late for all task for the date
        Task::where('user_id', Auth::id())->where('due_date', '<', date('Y-m-d H:i:s'))->where('status',
                'todo')->update(['status' => 'late']);
        $userTimezone = CarbonTimeZone::create(Auth::user()->timezone)->toOffsetName();
        $today = Task::with('user', 'subTasks', 'assignees',
            'sharings')->whereRaw('DATE(CONVERT_TZ(due_date, "+00:00", "' . $userTimezone . '" )) = ?',
                $date)->where('user_id', Auth::id())->get();
        // if user has main task and sub task both then should keep  only main task and has sub task only then keep it's main task
        $today = $this->removeItemFromOwnTask($today);
        $sharedtaskToday = $this->sharedTask(Auth::user())->with('user', 'subTasks', 'assignees',
                'sharings')->whereRaw('DATE(CONVERT_TZ(due_date, "+00:00", "' . $userTimezone . '" )) = ?',
                $date)->get();
        $sharedtaskToday = Task::lateStatusUpdate($sharedtaskToday);
        // if main task and sub task both are shared get only main task
        $sharedtaskToday = $this->removeItem($sharedtaskToday);
        $today = $today->merge($sharedtaskToday);
        $tomorrow = Task::with('user', 'subTasks', 'assignees',
            'sharings')->whereRaw('DATE(CONVERT_TZ(due_date, "+00:00", "' . $userTimezone . '" )) = ?',
                date('Y-m-d', strtotime($date . ' +1 day')))->where('user_id', Auth::id())->get();
        // if user has main task and sub task both then should keep  only main task
        $tomorrow = $this->removeItemFromOwnTask($tomorrow);
        $sharedtaskTomorrow = $this->sharedTask(Auth::user())->with('user', 'subTasks', 'assignees',
                'sharings')->whereRaw('DATE(CONVERT_TZ(due_date, "+00:00", "' . $userTimezone . '" )) = ?',
                date('Y-m-d', strtotime($date . ' +1 day')))->get();
        $sharedtaskTomorrow = Task::lateStatusUpdate($sharedtaskTomorrow);
        // if main task and sub task both are shared get only main task
        $sharedtaskTomorrow = $this->removeItem($sharedtaskTomorrow);
        $tomorrow = $tomorrow->merge($sharedtaskTomorrow);

        return (object) [
            'today'    => $today,
            'tomorrow' => $tomorrow,
        ];
    }

    /**
     * @param $date
     *
     * @return object
     */
    public function tasksStats() {
        // auto status changed to late for the last week tasks for the user
        Task::whereDate('due_date', '>=',
            Carbon::now()->startOfWeek(Carbon::SUNDAY)->format('Y-m-d'))->whereDate('due_date', '<=',
                Carbon::now()->endOfWeek(Carbon::SATURDAY)->format('Y-m-d'))->where('user_id',
                Auth::id())->where('due_date', '<', date('Y-m-d H:i:s'))->where('status',
                'todo')->update(['status' => 'late']);
        $weekly = Task::with('user', 'subTasks', 'assignees', 'sharings')->whereDate('due_date', '>=',
                Carbon::now()->startOfWeek(Carbon::SUNDAY)->format('Y-m-d'))->whereDate('due_date', '<=',
                Carbon::now()->endOfWeek(Carbon::SATURDAY)->format('Y-m-d'))->where('user_id',
                Auth::id())->orderBy('due_date')->get();
        // if user has main task and sub task both then should keep  only main task and if has only sub task thn keep it's main task
        $weekly = $this->removeItemFromOwnTask($weekly);
        $shared_tasks_weekly = $this->sharedTask(Auth::user())->with('user', 'subTasks', 'assignees',
                'sharings')->whereDate('due_date', '>=',
                Carbon::now()->startOfWeek(Carbon::SUNDAY)->format('Y-m-d'))->whereDate('due_date', '<=',
                Carbon::now()->endOfWeek(Carbon::SATURDAY)->format('Y-m-d'))->orderBy('due_date')->get();
        $shared_tasks_weekly = Task::lateStatusUpdate($shared_tasks_weekly);
        // if main task and sub task both are shared get only main task
        $shared_tasks_weekly = $this->removeItem($shared_tasks_weekly);
        $weekly              = $weekly->merge($shared_tasks_weekly);
        $weekly              = $weekly->sortBy('due_date');
        //auto status changed to late for the last month tasks
        Task::whereDate('due_date', '>=', Carbon::now()->subMonth()->format('Y-m-d'))->whereDate('due_date', '<=',
                Carbon::now()->format('Y-m-d'))->where('user_id', Auth::id())->where('due_date', '<',
                date('Y-m-d H:i:s'))->where('status', 'todo')->update(['status' => 'late']);
        $monthly = Task::with('user', 'subTasks', 'assignees', 'sharings')->whereDate('due_date', '>=',
                Carbon::now()->subMonth()->format('Y-m-d'))->whereDate('due_date', '<=',
                Carbon::now()->format('Y-m-d'))->where('user_id', Auth::id())->orderBy('due_date')->get();
        // if user has main task and sub task both then should keep  only main task and if has only sub task thn keep it's main task
        $monthly = $this->removeItemFromOwnTask($monthly);
        $shared_tasks_monthly = $this->sharedTask(Auth::user())->whereDate('due_date', '>=',
                Carbon::now()->subMonth()->format('Y-m-d'))->whereDate('due_date', '<=',
                Carbon::now()->format('Y-m-d'))->orderBy('due_date')->get();
        $shared_tasks_monthly = Task::lateStatusUpdate($shared_tasks_monthly);
        // if main task and sub task both are shared get only main task
        $shared_tasks_monthly = $this->removeItem($shared_tasks_monthly);
        $monthly              = $monthly->merge($shared_tasks_monthly);
        $monthly              = $monthly->sortBy('due_date');
        $monthly = $monthly->groupBy(function($monthly) {
            return Carbon::parse($monthly->due_date)->format('W');
        });
        $firstwk = Carbon::parse(Carbon::now()->subMonth())->format('W');
        $lastwk  = Carbon::parse(Carbon::now())->format('W');
        $newMonthData = [];
        $count        = 1;
        for ($key = $firstwk; $key <= $lastwk; $key ++) {
            if (isset($monthly[$key])) {
                $newMonthData[$count] = $monthly[$key];
            } else {
                $newMonthData[$count] = [];
            }
            $count ++;
        }
        $yearly = Task::with('user', 'subTasks', 'assignees', 'sharings')->whereYear('due_date',
                Carbon::now()->year)->where('parent_id', null)->where('user_id', Auth::id())->get();

        return (object) [
            'weekly'  => $weekly,
            'monthly' => $newMonthData,
            'yearly'  => $yearly,
        ];
    }

    /**
     * @param Request $request
     * @param         $id
     * @param         $type
     *
     * @return JsonResponse
     */
    public function taskAssigning(Request $request, $id, $type) {
        $task  = Task::with('user', 'subTasks', 'assignees', 'sharings')->findOrFail($id);
        $users = User::whereIn('email', $request->input('email'))->get();
        foreach ($request->input('email') as $user_email) {
            $user = $users->where('email', $user_email)->first();
            if ( ! $user) {
                $userData                      = [];
                $userData['id']                = Uuid::uuid4()->toString();
                $userData['name']              = $user_email;
                $userData['email']             = $user_email;
                $userData['password']          = app('hash')->make($user_email);
                $userData['timezone']          = $request->has('timezone') ? $request->input('timezone') : 'GMT+6';
                $userData['profile_image']     = asset('assets/images/' . 'profileimage.png');
                $userData['device_id']         = serialize([$request->input('device_id')]);
                $userData['registration_type'] = 'initialized';
                $user                          = User::create($userData);
            }
            if ($user && $task) {
                if ($user->assignTasks()->get()->where('id', $id)->count() < 1) {
                    $user->assignTasks()->attach($task, ['type' => $type]);
                    if ($user->registration_type == 'initialized') {
                        $subject = trans('messages.emailSubject');
                        $data    = [
                            'name'         => $user->name,
                            'mail_message' => $task->user->name . ' shared a task with you.',
                            'task_id'      => $task->id,
                        ];
                        dispatch(new SendTaskShareEmailJob($subject, $user, $data));
                    } else {
                        $userDeviceIds = unserialize($user->device_id);
                        $title         = $task->name;
                        $message       = trans('messages.pushNotificationMessage');
                        $time          = convertTimeToUSERzone(Carbon::now(), $task->user->timezone);
                        dispatch(new SendPushNotificationJob($userDeviceIds, $task->user, $title, $message, $time));
                    }
                }
            }
        }

        return Task::with('user', 'subTasks', 'assignees', 'sharings')->findOrFail($id);
    }

    public function removeAssignedUser(Request $request, $id, $type) {
        $user = User::where('email', $request->input('email'))->first();
        if ($user) {
            return $user->assignTasks()->wherePivot('task_id', '=', $id)->detach();
        }

        return 0;
    }

    public function sharedTask(User $user) {
        return $user->sharedTasks();
    }

    public function sharedUser($id) {
        return Task::with('sharings')->findOrFail($id);
    }

    public function isMainTasktShared($sharedTask, $item) {
        foreach ($sharedTask as $shrdTsk) {
            if ($shrdTsk->id === $item->parent_id) {
                return 1;
            }
        }

        return 0;
    }

    public function removeItem($sharedTask) {
        foreach ($sharedTask as $key => $item) {
            if ($this->isMainTasktShared($sharedTask, $item)) {
                $sharedTask->forget($key);
            }
        }

        return $sharedTask;
    }

    public function removeItemFromOwnTask($all_task) {
        foreach ($all_task as $key => $item) {
            if ($this->isMainTasktExit($all_task, $item)) {
                $all_task->forget($key);
            }
            $main_task = Task::find($item->parent_id);
            if ($main_task) {
                $all_task->forget($key);
                $all_task->push($main_task);
            }
        }

        return $all_task;
    }

    public function isMainTasktExit($all_task, $item) {
        foreach ($all_task as $task) {
            if ($task->id === $item->parent_id) {
                return 1;
            }
        }

        return 0;
    }

    public function maxDateTimeToParent($id) {
        $parent_task = Task::with('subTasks')->find($id);
        if ($parent_task) {
            $minTime = $parent_task->due_date;
            foreach ($parent_task->subTasks as $sb_tsk) {
                if ($sb_tsk->due_date < $minTime) {
                    $minTime = $sb_tsk->due_date;
                }
            }
            $maxTime = $minTime;
            foreach ($parent_task->subTasks as $sb_tsk) {
                if ($sb_tsk->due_date > $maxTime) {
                    $maxTime = $sb_tsk->due_date;
                }
            }
            $parent_task->update(['due_date' => $maxTime]);
        }
    }
}
