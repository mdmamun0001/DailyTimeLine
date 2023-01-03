<?php

return [
    'custom' => [
        'name' => [
            'required' => 'please set your name!',
            'max' => 'Your name is too long!'
        ],
        'due_date' => [
            'after' => 'We need to :attribute after today'
        ],
        'email' => [
            'email' => 'must be an email',
            'required' => 'Provide a email address'
        ],
        'user_id' => [
            'required' => 'Provide a user ID'
        ],
    ]
];
