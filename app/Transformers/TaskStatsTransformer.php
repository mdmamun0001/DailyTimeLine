<?php

namespace App\Transformers;

use League\Fractal\TransformerAbstract;

class TaskStatsTransformer extends TransformerAbstract
{

    /**
     * @param $task
     * @return array
     */
    public function transform($task)
    {
        return [
            'weekly' => $task['weekly'],
            'monthly' => $task['monthly'],
            'yearly' => $task['yearly'],
        ];
    }

}
