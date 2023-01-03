<?php

namespace App\Transformers;

use League\Fractal\TransformerAbstract;

class TaskByDayTransformer extends TransformerAbstract
{

    /**
     * @param $task
     * @return array
     */
    public function transform($task)
    {
        return [
            'currentDay' => $task['today'],
            'nextDay' => $task['tomorrow']
        ];
    }

}
