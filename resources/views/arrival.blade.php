<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
    <title>Arrival Notification for {{ $data['candidateName'] }}</title>
    <style type="text/css">
        body {
            margin: 0;
            padding: 0;
            background-color: #f2f3f8;
            font-family: Arial, sans-serif;
        }
        .container {
            width: 100%;
            background-color: #ffffff;
            max-width: 600px;
            margin: 0 auto;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h2 {
            color: #333333;
        }
        p {
            font-size: 16px;
            color: #555555;
            line-height: 1.5;
        }
        .info {
            margin: 15px 0;
        }
        .calendar-link {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 15px;
            background-color: #4285f4;
            color: #ffffff;
            text-decoration: none;
            border-radius: 5px;
        }
        .calendar-link:hover {
            background-color: #3367d6;
        }
    </style>
</head>

<body>
<div class="container">
    <h2>Arrival Notification for {{ $data['candidateName'] }}</h2>
    <p>Hello,</p>
    <p>The following candidate is scheduled to arrive soon:</p>

    <div class="info">
        <p><strong>Candidate Name:</strong> {{ $data['candidateName'] }}</p>
        <p><strong>Company:</strong> {{ $data['companyName'] }}</p>
        <p><strong>Status:</strong> {{ $data['status'] }}</p>
        <p><strong>Arrival Date:</strong> {{ $data['arrival_date'] }}</p>
        <p><strong>Arrival Time:</strong> {{ $data['arrival_time'] }}</p>
        <p><strong>Location:</strong> {{ $data['arrival_location'] }}</p>
        <p><strong>Flight:</strong> {{ $data['arrival_flight'] }}</p>
        <p><strong>Accommodation:</strong> {{ $data['where_to_stay'] }}</p>
        <p><strong>Contact Phone:</strong> {{ $data['phone_number'] }}</p>
    </div>

    <!-- Google Calendar Link -->
    <a href="https://calendar.google.com/calendar/r/eventedit?text=Arrival+of+{{ urlencode($data['candidateName']) }}&dates={{ date('Ymd\THis\Z', strtotime($data['arrival_date'] . ' ' . $data['arrival_time'])) }}/{{ date('Ymd\THis\Z', strtotime($data['arrival_date'] . ' ' . $data['arrival_time'] . ' +1 hour')) }}&details=Arrival+of+{{ urlencode($data['candidateName']) }}+via+flight+{{ urlencode($data['arrival_flight']) }}+at+{{ urlencode($data['arrival_location']) }}.+Stay+at+{{ urlencode($data['where_to_stay']) }}&location={{ urlencode($data['arrival_location']) }}&sf=true&output=xml"
       class="calendar-link" target="_blank">Add to Google Calendar</a>
</div>
</body>
</html>
