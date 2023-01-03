<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    <style>
        .brand-section {
            background: #1DBEF9;
            text-align: center;
            padding: 35px;
        }
        .mail-body {
            padding: 20px 40px;
        }
        .mail-body a {
            text-decoration: none;
            background: #1DBEF9;
            color: white;
            padding: 15px 30px;
            border-radius: 5px;
            margin-top: 30px;
            display: inline-block;
        }
    </style>
</head>
<body>
    <div class="brand-section">
        <img src="{{asset('assets/images/brand-logo.png')}}" alt="brand logo">
    </div>

    <div class="mail-body">
        <h4>Hi, {{$name}}</h4>
        <p>{{$mail_message}}</p>
        <a href="http://clockdo.test/shared-task/?id={{$task_id}}" target="_blank">See the tasks</a>
    </div>
</body>
</html>
