<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
    <title>{{ $data['status'] }} Notification for {{ $data['candidateName'] }}</title>
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
    <h2>{{ $data['status'] }} Notification for {{ $data['candidateName'] }}</h2>
    <p>Hello,</p>
    <p>The following candidate is scheduled to arrive soon:</p>

    <div class="info">
        <p><strong>Candidate Name:</strong> {{ $data['candidateName'] }}</p>
        <p><strong>Company:</strong> {{ $data['companyName'] }}</p>
        <p><strong>Status:</strong> {{ $data['status'] }}</p>
        <p><strong>Date:</strong> {{ $data['changedStatusDate'] }}</p>
        <p><strong>Contact Phone:</strong> {{ $data['phone_number'] }}</p>
        <p><strong>Description:</strong> {{ $data['description'] }}</p>
    </div>

    <!-- Google Calendar Link -->
    <a href="https://calendar.google.com/calendar/r/eventedit?text={{ $data['status'] }}+of+{{ urlencode($data['candidateName']) }}&dates={{ date('Ymd\THis\Z', strtotime($data['changedStatusDate'])) }}/{{ date('Ymd\THis\Z', strtotime($data['changedStatusDate'] . ' +1 hour')) }}&details=Arrival+of+{{ urlencode($data['candidateName']) }}+at+{{ urlencode($data['companyName']) }}&location={{ urlencode($data['companyName']) }}&sf=true&output=xml"
       class="calendar-link" target="_blank">Add to Google Calendar</a>

    <a href="data:text/calendar;charset=utf-8,BEGIN:VCALENDAR%0D%0AVERSION:2.0%0D%0ABEGIN:VEVENT%0D%0AUID:{{ uniqid() }}@hotmail.com%0D%0ADTSTAMP:{{ date('Ymd\THis\Z') }}%0D%0ADTSTART:{{ date('Ymd\THis\Z', strtotime($data['changedStatusDate'])) }}%0D%0ADTEND:{{ date('Ymd\THis\Z', strtotime($data['changedStatusDate'] . ' +1 hour')) }}%0D%0ASUMMARY:{{ urlencode($data['status']) }}+of+{{ urlencode($data['candidateName']) }}%0D%0ADESCRIPTION:Arrival+of+{{ urlencode($data['candidateName']) }}+at+{{ urlencode($data['companyName']) }}%0D%0ALOCATION:{{ urlencode($data['companyName']) }}%0D%0AEND:VEVENT%0D%0AEND:VCALENDAR"
       class="calendar-link" download="arrival_of_{{ urlencode($data['candidateName']) }}.ics">Add to Outlook Calendar</a>
</div>
</body>
</html>
