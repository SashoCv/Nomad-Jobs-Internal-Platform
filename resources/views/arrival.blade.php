<!DOCTYPE html>
<html lang="en-US">

<head>
    <meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
    <title>Candidate Arrival Notification</title>
    <meta name="description" content="Candidate Arrival Details">
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
    <h2>Candidate Arrival Information</h2>
    <p>Hello,</p>
    <p>A candidate is arriving soon with the following details:</p>

    <div class="info">
        <p><strong>Company ID:</strong> {{ $data['company_id'] }}</p>
        <p><strong>Candidate ID:</strong> {{ $data['candidate_id'] }}</p>
        <p><strong>Arrival Date:</strong> {{ $data['arrival_date'] }}</p>
        <p><strong>Arrival Time:</strong> {{ $data['arrival_time'] }}</p>
        <p><strong>Arrival Location:</strong> {{ $data['arrival_location'] }}</p>
        <p><strong>Flight:</strong> {{ $data['arrival_flight'] }}</p>
        <p><strong>Where to Stay:</strong> {{ $data['where_to_stay'] }}</p>
        <p><strong>Contact Phone:</strong> {{ $data['phone_number'] }}</p>
    </div>

    <!-- Google Calendar Link -->
    <a href="https://calendar.google.com/calendar/r/eventedit?text=Candidate+Arrival&dates={{ date('Ymd\THis\Z', strtotime($data['arrival_date'] . ' ' . $data['arrival_time'])) }}/{{ date('Ymd\THis\Z', strtotime($data['arrival_date'] . ' ' . $data['arrival_time'] . ' +1 hour')) }}&details=Candidate+arriving+from+flight+{{ $data['arrival_flight'] }}&location={{ urlencode($data['arrival_location']) }}&sf=true&output=xml"
       class="calendar-link" target="_blank">Add to Google Calendar</a>
</div>

</body>

</html>
