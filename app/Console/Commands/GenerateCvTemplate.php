<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Style\Font;
use PhpOffice\PhpWord\SimpleType\Jc;

class GenerateCvTemplate extends Command
{
    protected $signature = 'cv:generate-template';
    protected $description = 'Generate CV DOCX template with placeholders';

    public function handle()
    {
        $phpWord = new PhpWord();

        // Define styles
        $phpWord->addTitleStyle(1, ['bold' => true, 'size' => 16], ['alignment' => Jc::CENTER]);
        $phpWord->addTitleStyle(2, ['bold' => true, 'size' => 12, 'color' => '333333']);

        $sectionStyle = [
            'marginTop' => 600,
            'marginBottom' => 600,
            'marginLeft' => 720,
            'marginRight' => 720,
        ];

        $section = $phpWord->addSection($sectionStyle);

        // Title
        $section->addTitle('APPLICANT QUESTIONNAIRE', 1);
        $section->addTextBreak();

        // Personal Information
        $section->addTitle('Personal Information', 2);
        $table = $section->addTable(['borderSize' => 6, 'borderColor' => '999999']);

        $this->addTableRow($table, 'First Name', '${first_name}');
        $this->addTableRow($table, 'Last Name', '${last_name}');
        $this->addTableRow($table, 'Address of residence', '${address_of_residence}');
        $this->addTableRow($table, 'WhatsApp phone number', '${whatsapp_phone}');
        $this->addTableRow($table, 'Gender', '${gender_male} Male  ${gender_female} Female');
        $this->addTableRow($table, 'Date of Birth', '${date_of_birth}');
        $this->addTableRow($table, 'Marital Status/Children', '${marital_status}');
        $this->addTableRow($table, 'Height', '${height}');
        $this->addTableRow($table, 'Weight', '${weight}');
        $this->addTableRow($table, 'Citizenship', '${citizenship}');

        $section->addTextBreak();

        // Passport Data
        $section->addTitle('Passport Data', 2);
        $table = $section->addTable(['borderSize' => 6, 'borderColor' => '999999']);
        $this->addTableRow($table, 'Passport Information', '${passport_data}');

        $section->addTextBreak();

        // Health
        $section->addTitle('Health Information', 2);
        $table = $section->addTable(['borderSize' => 6, 'borderColor' => '999999']);
        $this->addTableRow($table, 'Chronic Diseases', '${chronic_diseases}');
        $this->addTableRow($table, 'Country of visa application', '${country_of_visa}');

        $section->addTextBreak();

        // Education
        $section->addTitle('Education', 2);
        $table = $section->addTable(['borderSize' => 6, 'borderColor' => '999999']);
        $this->addTableRow($table, 'Name of institution', '${education_institution}');
        $this->addTableRow($table, 'Speciality', '${education_speciality}');
        $this->addTableRow($table, 'Year of Ending', '${education_year}');

        $section->addTextBreak();

        // Driving License
        $section->addTitle('Driving License', 2);
        $table = $section->addTable(['borderSize' => 6, 'borderColor' => '999999']);
        $this->addTableRow($table, 'Has Driving License', '${driving_yes} Yes  ${driving_no} No');
        $this->addTableRow($table, 'Category', '${driving_category}');
        $this->addTableRow($table, 'Expiry Date', '${driving_expiry}');
        $this->addTableRow($table, 'Country of Issuing', '${driving_country}');

        $section->addTextBreak();

        // Experience
        $section->addTitle('Experience', 2);
        $table = $section->addTable(['borderSize' => 6, 'borderColor' => '999999']);

        // Header row
        $table->addRow();
        $table->addCell(2500)->addText('Company Name', ['bold' => true]);
        $table->addCell(2500)->addText('Job Title', ['bold' => true]);
        $table->addCell(2000)->addText('Duration', ['bold' => true]);
        $table->addCell(3000)->addText('Responsibilities', ['bold' => true]);

        // Data row (will be cloned)
        $table->addRow();
        $table->addCell(2500)->addText('${exp_company}');
        $table->addCell(2500)->addText('${exp_position}');
        $table->addCell(2000)->addText('${exp_duration}');
        $table->addCell(3000)->addText('${exp_responsibilities}');

        $section->addTextBreak();

        // Foreign Languages
        $section->addTitle('Foreign Languages', 2);
        $table = $section->addTable(['borderSize' => 6, 'borderColor' => '999999']);

        // Header
        $table->addRow();
        $table->addCell(2000)->addText('Language', ['bold' => true]);
        $table->addCell(2000)->addText('No knowledge', ['bold' => true]);
        $table->addCell(2000)->addText('Elementary', ['bold' => true]);
        $table->addCell(2000)->addText('Average', ['bold' => true]);
        $table->addCell(2000)->addText('Advanced', ['bold' => true]);

        // English
        $table->addRow();
        $table->addCell(2000)->addText('English');
        $table->addCell(2000)->addText('${eng_none}');
        $table->addCell(2000)->addText('${eng_elementary}');
        $table->addCell(2000)->addText('${eng_average}');
        $table->addCell(2000)->addText('${eng_advanced}');

        // Russian
        $table->addRow();
        $table->addCell(2000)->addText('Russian');
        $table->addCell(2000)->addText('${rus_none}');
        $table->addCell(2000)->addText('${rus_elementary}');
        $table->addCell(2000)->addText('${rus_average}');
        $table->addCell(2000)->addText('${rus_advanced}');

        // Other
        $table->addRow();
        $table->addCell(2000)->addText('${other_language}');
        $table->addCell(2000)->addText('${other_none}');
        $table->addCell(2000)->addText('${other_elementary}');
        $table->addCell(2000)->addText('${other_average}');
        $table->addCell(2000)->addText('${other_advanced}');

        $section->addTextBreak();

        // Comments
        $section->addTitle('Comments', 2);
        $section->addText('${comments}');

        // Save template
        $templatePath = storage_path('app/templates/cv_template.docx');
        $dir = dirname($templatePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($templatePath);

        $this->info('CV template generated successfully at: ' . $templatePath);

        return Command::SUCCESS;
    }

    protected function addTableRow($table, string $label, string $value): void
    {
        $table->addRow();
        $table->addCell(3000)->addText($label, ['bold' => true]);
        $table->addCell(7000)->addText($value);
    }
}
