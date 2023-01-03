<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

/**
 *
 * @OA\Schema(
 *  required={"name","user_id", "due_date"},
 * @OA\Xml(name="Task"),
 * @OA\Property(property="id", type="string", example="98a18252-1533-4535-9845-d68400d83122"),
 * @OA\Property(property="name", type="string", example="clockdo meeting"),
 * @OA\Property(property="user_id", type="string", example="67625f4d-54b5-48d6-8c73-843f01ec69ad"),
 * @OA\Property(property="due_date", type="date", example="2021-05-23 19:45:00"),
 * @OA\Property(property="reminder", type="boolean", example="ture"),
 * @OA\Property(property="status", type="string", example="todo"),
 * @OA\Property(property="parent_id", type="string", example="98a18252-1533-4535-9845-d68400d83123")
 * )
 * Class Task
 *
 */




class Task extends Model implements Auditable
{
    use Authenticatable, Authorizable;
    use \OwenIt\Auditing\Auditable;

    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'name', 'user_id', 'due_date', 'reminder', 'status', 'parent_id'
    ];



    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [

    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function task()
    {
        return $this->belongsTo(Task::class, 'parent_id');
    }

    public function subTasks()
    {
        return $this->hasMany(Task::class, 'parent_id');
    }

    public function assignees()
    {
        return $this->belongsToMany(User::class)
                    ->withPivot('type')
                    ->wherePivot('type', '=', 'assign');
    }

    public function sharings()
    {
        return $this->belongsToMany(User::class)
                    ->withPivot('type')
                    ->wherePivot('type', '=', 'share');
    }

    public function remindered()
    {
        return Task::where('reminder', '1')
            ->where('status', 'todo')
            ->whereBetween('due_date', [Carbon::now(), Carbon::now()->addMinutes(60)         ]);
    }

    public function statusUpdate( $status ) {
        if ( $status !== 'done' )
        {
            $this->update( ['status' => $status ] );
            if ( !is_null($this->parent_id) && Task::findOrFail($this->parent_id)->status === 'done' ) {
                $mainTask = Task::findOrFail($this->parent_id);
                $mainTask->update( ['status' => 'todo' ] );
            }

        }
        elseif ( !is_null($this->parent_id) )//for done status
        {
            $this->update( ['status' => $status ] );

            //this is a subtask then i hv to check it's parent's sub task done or not to auto update parent done status
            $mainTask = Task::findOrFail($this->parent_id);

            $notDone = $mainTask->subTasks()->get()->where('status' , '!==', 'done');

            if ( $notDone->isEmpty() )
            {
                $mainTask->update( ['status' => 'done' ] );
            }

        }
        else{

            //this is for main task and i have to check all sub task done or not for it.
            $notDone = $this->subTasks()->get()->where('status' , '!==', 'done');

            if ( $notDone->isEmpty() )
            {
                $this->update( ['status' => 'done' ] );
            }

        }
    }

    public static function lateStatusUpdate($tasks) {

        //auto  status changed to late for all shared task and sub tasks of shared task for the user
        foreach ($tasks as $task) {

            if( $task->status === 'todo' && $task->due_date < date('Y-m-d H:i:s') )
            {
                $task->update( ['status' => 'late' ] );
            }

            foreach ($task->subTasks as $sub_task) {

                if( $sub_task->status === 'todo' && $sub_task->due_date < date('Y-m-d H:i:s') )
                {
                    $sub_task->update( ['status' => 'late' ] );
                }
            }
        }

        return $tasks;
    }


}
