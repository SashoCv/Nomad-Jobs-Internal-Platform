#!/usr/bin/env python3
"""
Script to add placeholders to the Nomad CV template
"""

import zipfile
from xml.etree import ElementTree as ET

# Namespaces
NS = {
    'w': 'http://schemas.openxmlformats.org/wordprocessingml/2006/main',
}

# Register all namespaces
NAMESPACES_TO_REGISTER = [
    ('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main'),
    ('wpc', 'http://schemas.microsoft.com/office/word/2010/wordprocessingCanvas'),
    ('cx', 'http://schemas.microsoft.com/office/drawing/2014/chartex'),
    ('mc', 'http://schemas.openxmlformats.org/markup-compatibility/2006'),
    ('o', 'urn:schemas-microsoft-com:office:office'),
    ('r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships'),
    ('m', 'http://schemas.openxmlformats.org/officeDocument/2006/math'),
    ('v', 'urn:schemas-microsoft-com:vml'),
    ('wp14', 'http://schemas.microsoft.com/office/word/2010/wordprocessingDrawing'),
    ('wp', 'http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing'),
    ('w10', 'urn:schemas-microsoft-com:office:word'),
    ('w14', 'http://schemas.microsoft.com/office/word/2010/wordml'),
    ('w15', 'http://schemas.microsoft.com/office/word/2012/wordml'),
    ('wpg', 'http://schemas.microsoft.com/office/word/2010/wordprocessingGroup'),
    ('wpi', 'http://schemas.microsoft.com/office/word/2010/wordprocessingInk'),
    ('wne', 'http://schemas.microsoft.com/office/word/2006/wordml'),
    ('wps', 'http://schemas.microsoft.com/office/word/2010/wordprocessingShape'),
    ('a', 'http://schemas.openxmlformats.org/drawingml/2006/main'),
    ('pic', 'http://schemas.openxmlformats.org/drawingml/2006/picture'),
]

for prefix, uri in NAMESPACES_TO_REGISTER:
    ET.register_namespace(prefix, uri)

def get_cell_text(cell):
    """Extract all text from a cell"""
    texts = cell.findall('.//w:t', NS)
    return ''.join([t.text or '' for t in texts]).strip()

def set_cell_text(cell, text):
    """Set text in a cell"""
    texts = cell.findall('.//w:t', NS)
    if texts:
        texts[0].text = text
        for t in texts[1:]:
            t.text = ''
    else:
        p = cell.find('.//w:p', NS)
        if p is None:
            p = ET.SubElement(cell, '{http://schemas.openxmlformats.org/wordprocessingml/2006/main}p')
        r = p.find('.//w:r', NS)
        if r is None:
            r = ET.SubElement(p, '{http://schemas.openxmlformats.org/wordprocessingml/2006/main}r')
        t = ET.SubElement(r, '{http://schemas.openxmlformats.org/wordprocessingml/2006/main}t')
        t.text = text

def set_paragraph_text(para, text):
    """Set text in a paragraph"""
    texts = para.findall('.//w:t', NS)
    if texts:
        texts[0].text = text
        for t in texts[1:]:
            t.text = ''

def process_template(input_path, output_path):
    """Process the template and add placeholders"""

    with zipfile.ZipFile(input_path, 'r') as zip_in:
        document_xml = zip_in.read('word/document.xml').decode('utf-8')
        root = ET.fromstring(document_xml)

        tables = root.findall('.//w:tbl', NS)
        print(f'Found {len(tables)} tables')

        rows_to_remove = []

        for tbl in tables:
            rows = list(tbl.findall('.//w:tr', NS))

            for row_idx, row in enumerate(rows):
                cells = row.findall('.//w:tc', NS)
                if not cells:
                    continue

                first_cell_text = get_cell_text(cells[0])

                # === Personal Information Section ===
                if first_cell_text == 'First Name Last Name' and len(cells) > 1:
                    set_cell_text(cells[1], '${first_name} ${last_name}')

                elif first_cell_text == 'Address of residence' and len(cells) > 1:
                    set_cell_text(cells[1], '${address_of_residence}')

                elif first_cell_text == 'Whatsapp phone number' and len(cells) > 1:
                    set_cell_text(cells[1], '${whatsapp_phone}')

                elif first_cell_text == 'Gender':
                    for cell in cells[1:]:
                        cell_text = get_cell_text(cell)
                        if 'Male' in cell_text:
                            set_cell_text(cell, '${gender_male} Male')
                        elif 'Female' in cell_text:
                            set_cell_text(cell, '${gender_female} Female')

                elif first_cell_text == 'Date of Birth' and len(cells) > 1:
                    set_cell_text(cells[1], '${date_of_birth}')

                elif first_cell_text == 'Marital Status/Children' and len(cells) > 1:
                    set_cell_text(cells[1], '${marital_status}')

                elif 'Height' in first_cell_text and 'Weight' in first_cell_text:
                    for cell in cells[1:]:
                        cell_text = get_cell_text(cell)
                        if 'cm' in cell_text:
                            set_cell_text(cell, '${height} cm')
                        elif 'kg' in cell_text:
                            set_cell_text(cell, '${weight} kg')

                elif first_cell_text == 'Citizenship' and len(cells) > 1:
                    set_cell_text(cells[1], '${citizenship}')

                elif first_cell_text == 'Passport Data' and len(cells) > 1:
                    set_cell_text(cells[1], '${passport_data}')

                elif first_cell_text == 'Chronic Diseases' and len(cells) > 1:
                    set_cell_text(cells[1], '${chronic_diseases}')

                elif first_cell_text == 'Country of visa application' and len(cells) > 1:
                    set_cell_text(cells[1], '${country_of_visa}')

                # === Education Section (cloneable rows) ===
                elif 'Name of higher education institution' in first_cell_text:
                    if row_idx + 1 < len(rows):
                        next_row = rows[row_idx + 1]
                        next_cells = next_row.findall('.//w:tc', NS)
                        if len(next_cells) >= 3:
                            set_cell_text(next_cells[0], '${edu_institution}')
                            set_cell_text(next_cells[1], '${edu_speciality}')
                            set_cell_text(next_cells[2], '${edu_year}')

                # === Driving License Section ===
                elif 'Yes' in first_cell_text and 'Category' in ''.join([get_cell_text(c) for c in cells]):
                    set_cell_text(cells[0], '${driving_yes} Yes')
                    if row_idx + 1 < len(rows):
                        next_row = rows[row_idx + 1]
                        next_cells = next_row.findall('.//w:tc', NS)
                        if len(next_cells) >= 4:
                            set_cell_text(next_cells[1], '${driving_category}')
                            set_cell_text(next_cells[2], '${driving_expiry}')
                            set_cell_text(next_cells[3], '${driving_country}')

                elif first_cell_text.strip() == 'No':
                    set_cell_text(cells[0], '${driving_no} No')

                # === Experience Section (cloneable rows) ===
                elif 'Company Name' in first_cell_text or 'Company' in first_cell_text and 'Name' in ''.join([get_cell_text(c) for c in cells]):
                    if row_idx + 1 < len(rows):
                        next_row = rows[row_idx + 1]
                        next_cells = next_row.findall('.//w:tc', NS)
                        if len(next_cells) >= 4:
                            set_cell_text(next_cells[0], '${exp_company}')
                            set_cell_text(next_cells[1], '${exp_position}')
                            set_cell_text(next_cells[2], '${exp_duration}')
                            set_cell_text(next_cells[3], '${exp_responsibilities}')

                # === Languages Section - SIMPLIFIED ===
                # Just show: Language | Level (no checkboxes)
                elif first_cell_text == 'English' or (first_cell_text and 'English' in first_cell_text):
                    # Set first cell to just "English" (remove any checkboxes)
                    set_cell_text(cells[0], 'English')
                    # Put level placeholder in second cell
                    if len(cells) > 1:
                        set_cell_text(cells[1], '${english_level}')
                    # Clear remaining cells
                    for cell in cells[2:]:
                        set_cell_text(cell, '')
                    # Mark next row for removal (the Elementary/Advanced row)
                    if row_idx + 1 < len(rows):
                        rows_to_remove.append(rows[row_idx + 1])

                elif first_cell_text == 'Russian' or (first_cell_text and 'Russian' in first_cell_text):
                    set_cell_text(cells[0], 'Russian')
                    if len(cells) > 1:
                        set_cell_text(cells[1], '${russian_level}')
                    for cell in cells[2:]:
                        set_cell_text(cell, '')
                    if row_idx + 1 < len(rows):
                        rows_to_remove.append(rows[row_idx + 1])

                elif first_cell_text and ('Other' in first_cell_text or '…' in first_cell_text):
                    set_cell_text(cells[0], '${other_language}')
                    if len(cells) > 1:
                        set_cell_text(cells[1], '${other_language_level}')
                    for cell in cells[2:]:
                        set_cell_text(cell, '')
                    if row_idx + 1 < len(rows):
                        rows_to_remove.append(rows[row_idx + 1])

                # === Comments Section ===
                elif 'Comments' in first_cell_text and len(cells) > 1:
                    set_cell_text(cells[1], '${comments}')

            # Remove the marked rows (Elementary/Advanced rows for languages)
            for row in rows_to_remove:
                try:
                    tbl.remove(row)
                except ValueError:
                    pass  # Row might already be removed

        # Process paragraphs after the table for photo sections
        body = root.find('.//w:body', NS)
        paragraphs_to_remove = []
        after_table = False

        for elem in list(body):
            if elem.tag == '{http://schemas.openxmlformats.org/wordprocessingml/2006/main}tbl':
                after_table = True
                continue

            if after_table and elem.tag == '{http://schemas.openxmlformats.org/wordprocessingml/2006/main}p':
                texts = elem.findall('.//w:t', NS)
                text = ''.join([t.text or '' for t in texts]).strip()

                if 'Photo of a candidate (full body' in text:
                    set_paragraph_text(elem, '${candidate_photo}')
                elif 'Photo of a candidate from a workplace' in text:
                    paragraphs_to_remove.append(elem)
                elif "passport" in text.lower() and "photo" in text.lower():
                    paragraphs_to_remove.append(elem)
                elif "diploma" in text.lower() or "certificate" in text.lower():
                    paragraphs_to_remove.append(elem)
                elif "driving" in text.lower() and "photo" in text.lower():
                    paragraphs_to_remove.append(elem)

        for para in paragraphs_to_remove:
            body.remove(para)

        # Save modified XML
        modified_xml = ET.tostring(root, encoding='unicode')

        with zipfile.ZipFile(output_path, 'w', zipfile.ZIP_DEFLATED) as zip_out:
            for item in zip_in.namelist():
                if item == 'word/document.xml':
                    zip_out.writestr(item, modified_xml.encode('utf-8'))
                else:
                    zip_out.writestr(item, zip_in.read(item))

    print(f'Template saved to: {output_path}')

if __name__ == '__main__':
    input_file = '/Users/sasho/Sites/nomad-cloud/storage/app/templates/cv_template_original.docx'
    output_file = '/Users/sasho/Sites/nomad-cloud/storage/app/templates/cv_template.docx'

    process_template(input_file, output_file)
    print('Done!')
