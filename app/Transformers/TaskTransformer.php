<?php

namespace App\Transformers;


use App\Models\Task;
use Illuminate\Support\Facades\Log;
use League\Fractal\TransformerAbstract;

class TaskTransformer extends TransformerAbstract
{

    protected $defaultIncludes = ['subTasks', 'user', 'assignees', 'sharings'];
    /**
     * @param $task
     * @return array
     */
    public function transform(Task $task)
    {
        return [
            'id' => $task->id,
            'name' => $task->name,
            'description' => $task->description,
            'dueDate' => convertTimeToUSERzone($task->due_date, $task->user->timezone),
            'status' => $task->status,
            'reminder' => $task->reminder,
        ];
    }

    public function includeSubTasks(Task $task)
    {
        return $this->collection($task->subTasks, new TaskTransformer());
    }
    public function includeUser(Task $task)
    {
        return $this->item($task->user, new TaskCreatorTransformer());
    }
    public function includeAssignees(Task $task)
    {
        return $this->collection($task->assignees, new TaskAssignShareTransformer());
    }
    public function includeSharings(Task $task)
    {
        return $this->collection($task->sharings, new TaskAssignShareTransformer());
    }
}
