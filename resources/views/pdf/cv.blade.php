<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CV - {{ $personalInfo['fullName'] }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10.5pt;
            line-height: 1.5;
            color: #2c3e50;
            background: #f8f9fa;
        }

        .container {
            padding: 0;
            background: white;
        }

        .header {
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            color: white;
            padding: 40px 30px;
            position: relative;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .header-content {
            display: table;
            width: 100%;
        }

        .header-text {
            display: table-cell;
            vertical-align: middle;
        }

        .header-image {
            display: table-cell;
            vertical-align: middle;
            text-align: right;
            width: 130px;
            padding-left: 20px;
        }

        .profile-photo {
            width: 110px;
            height: 110px;
            border-radius: 50%;
            border: 5px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            object-fit: cover;
        }

        .header h1 {
            font-size: 32pt;
            margin-bottom: 8px;
            font-weight: bold;
            letter-spacing: -0.5px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }

        .header .subtitle {
            font-size: 15pt;
            opacity: 0.95;
            font-weight: 300;
            letter-spacing: 0.5px;
        }

        .content-wrapper {
            padding: 25px;
        }

        .section {
            margin-bottom: 20px;
            page-break-inside: avoid;
        }

        .section-title {
            font-size: 14pt;
            font-weight: bold;
            color: #2c3e50;
            border-bottom: 3px solid #3498db;
            padding-bottom: 6px;
            margin-bottom: 12px;
            position: relative;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }

        .info-grid {
            display: table;
            width: 100%;
            margin-bottom: 8px;
            background: #f8f9fa;
            padding: 12px;
            border-radius: 8px;
        }

        .info-row {
            display: table-row;
        }

        .info-label {
            display: table-cell;
            font-weight: 600;
            width: 38%;
            padding: 6px 15px 6px 0;
            color: #34495e;
            font-size: 10pt;
        }

        .info-value {
            display: table-cell;
            padding: 6px 0;
            color: #2c3e50;
            font-size: 10pt;
        }

        .experience-item,
        .education-item {
            margin-bottom: 12px;
            padding: 12px;
            padding-left: 18px;
            border-left: 4px solid #3498db;
            background: #f8f9fa;
            border-radius: 0 8px 8px 0;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            transition: all 0.3s;
        }

        .item-title {
            font-size: 12pt;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .item-subtitle {
            font-size: 10.5pt;
            color: #7f8c8d;
            margin-bottom: 6px;
            font-weight: 500;
        }

        .item-date {
            font-size: 9.5pt;
            color: #95a5a6;
            font-style: italic;
            display: inline-block;
            background: white;
            padding: 3px 10px;
            border-radius: 12px;
            margin-top: 5px;
        }

        .job-description-box {
            margin-top: 15px;
            padding: 18px;
            background: linear-gradient(135deg, #f8f9fa 0%, #ecf0f1 100%);
            border-left: 4px solid #3498db;
            border-radius: 0 8px 8px 0;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .job-description-title {
            font-weight: bold;
            margin-bottom: 10px;
            color: #2c3e50;
            font-size: 11pt;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .job-description-content {
            white-space: pre-wrap;
            line-height: 1.6;
            color: #34495e;
            font-size: 10pt;
        }

        .footer {
            text-align: center;
            padding: 25px 30px;
            margin-top: 40px;
            background: #2c3e50;
            color: white;
            font-size: 9pt;
        }

        .footer p {
            margin: 5px 0;
            opacity: 0.9;
        }

        .no-data {
            color: #95a5a6;
            font-style: italic;
            padding: 15px;
            background: #ecf0f1;
            border-radius: 8px;
            text-align: center;
        }

        .contact-info {
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, #f8f9fa 0%, #ecf0f1 100%);
            border-left: 4px solid #3498db;
            border-radius: 0 8px 8px 0;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            margin-bottom: 0;
        }

        .contact-info-item {
            margin-bottom: 10px;
            line-height: 1.6;
        }

        .contact-info-item:last-child {
            margin-bottom: 0;
        }

        .contact-info .info-label {
            color: #2c3e50;
            font-weight: 700;
            display: inline-block;
            width: 100px;
            font-size: 10pt;
        }

        .contact-info .info-value {
            color: #34495e;
            font-weight: 500;
            display: inline-block;
            font-size: 10pt;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-content">
                <div class="header-text">
                    <h1>{{ $personalInfo['fullName'] }}</h1>
                    @if($position)
                        <div class="subtitle">{{ $position->name }}</div>
                    @endif
                </div>
                @if($candidate->personPicturePath)
                <div class="header-image">
                    <img src="{{ public_path('storage/' . $candidate->personPicturePath) }}" alt="Profile Photo" class="profile-photo">
                </div>
                @endif
            </div>
        </div>

        <div class="content-wrapper">
            <!-- Personal Information -->
            <div class="section">
                <h2 class="section-title">Personal Information</h2>
                <div class="info-grid">
                    <div class="info-row">
                        <div class="info-label">Full Name:</div>
                        <div class="info-value">{{ $personalInfo['fullName'] }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Date of Birth:</div>
                        <div class="info-value">{{ $personalInfo['birthday'] }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Place of Birth:</div>
                        <div class="info-value">{{ $personalInfo['placeOfBirth'] }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Nationality:</div>
                        <div class="info-value">{{ $personalInfo['nationality'] }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Gender:</div>
                        <div class="info-value">{{ ucfirst($personalInfo['gender']) }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Passport Number:</div>
                        <div class="info-value">{{ $personalInfo['passport'] }}</div>
                    </div>
                </div>
            </div>

        <!-- Education Summary -->
        @if($personalInfo['education'] !== 'N/A' || $personalInfo['specialty'] !== 'N/A' || $personalInfo['qualification'] !== 'N/A')
        <div class="section">
            <h2 class="section-title">Education Summary</h2>
            <div class="info-grid">
                @if($personalInfo['education'] !== 'N/A')
                <div class="info-row">
                    <div class="info-label">Education Level:</div>
                    <div class="info-value">{{ $personalInfo['education'] }}</div>
                </div>
                @endif
                @if($personalInfo['specialty'] !== 'N/A')
                <div class="info-row">
                    <div class="info-label">Specialty:</div>
                    <div class="info-value">{{ $personalInfo['specialty'] }}</div>
                </div>
                @endif
                @if($personalInfo['qualification'] !== 'N/A')
                <div class="info-row">
                    <div class="info-label">Qualification:</div>
                    <div class="info-value">{{ $personalInfo['qualification'] }}</div>
                </div>
                @endif
            </div>
        </div>
        @endif

        <!-- Education Details -->
        @if($education && $education->count() > 0)
        <div class="section">
            <h2 class="section-title">Education</h2>
            @foreach($education as $edu)
            <div class="education-item">
                <div class="item-title">{{ $edu->degree ?? 'Degree' }}{{ $edu->field_of_study ? ' in ' . $edu->field_of_study : '' }}</div>
                <div class="item-subtitle">{{ $edu->school_name ?? 'School Name' }}</div>
                <div class="item-date">
                    {{ $edu->start_date ?? 'Start' }} - {{ $edu->end_date ?? 'End' }}
                </div>
            </div>
            @endforeach
        </div>
        @else
        <div class="section">
            <h2 class="section-title">Education</h2>
            <div class="no-data">No education records available</div>
        </div>
        @endif

        <!-- Work Experience -->
        @if($experience && $experience->count() > 0)
        <div class="section">
            <h2 class="section-title">Work Experience</h2>
            @foreach($experience as $exp)
            <div class="experience-item">
                <div class="item-title">{{ $exp->position ?? 'Position' }}</div>
                <div class="item-subtitle">{{ $exp->company_name ?? 'Company Name' }}</div>
                <div class="item-date">
                    {{ $exp->start_date ?? 'Start' }} - {{ $exp->end_date ?? 'Present' }}
                </div>
            </div>
            @endforeach
        </div>
        @else
        <div class="section">
            <h2 class="section-title">Work Experience</h2>
            <div class="no-data">No work experience records available</div>
        </div>
        @endif

        <!-- Applied Position -->
        @if($companyJob)
        <div class="section">
            <h2 class="section-title">Applied Position</h2>
            <div class="info-grid">
                @if($companyJob->job_title)
                <div class="info-row">
                    <div class="info-label">Job Title:</div>
                    <div class="info-value">{{ $companyJob->job_title }}</div>
                </div>
                @endif
                @if($companyJob->company && $companyJob->company->nameOfCompany)
                <div class="info-row">
                    <div class="info-label">Company:</div>
                    <div class="info-value">{{ $companyJob->company->nameOfCompany }}</div>
                </div>
                @endif
            </div>
            @if($companyJob->job_description)
            <div class="job-description-box">
                <div class="job-description-title">Job Description:</div>
                <div class="job-description-content">{{ $companyJob->job_description }}</div>
            </div>
            @endif
        </div>
        @endif

        <!-- Contact Information -->
        <div class="section">
            <h2 class="section-title">Contact Information</h2>
            <div class="contact-info">
                <div class="contact-info-item">
                    <span class="info-label">Email:</span>
                    <span class="info-value">nomad.konsult@gmail.com</span>
                </div>
                <div class="contact-info-item">
                    <span class="info-label">Phone:</span>
                    <span class="info-value">+359 88 9617910</span>
                </div>
            </div>
        </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>CV Generated on {{ $generatedAt }}</p>
            <p>Nomad Partners Internal Platform</p>
        </div>
    </div>
</body>
</html>
