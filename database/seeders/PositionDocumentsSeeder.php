<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\PositionDocument;

class PositionDocumentsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $documents = [
            // Чистач, хигиенист (ID: 14)
            ['position_id' => 14, 'document_name' => 'Медицинско уверение'],
            ['position_id' => 14, 'document_name' => 'Сертификат за обука - хигиена'],
            ['position_id' => 14, 'document_name' => 'Лична карта'],

            // Готвач (ID: 44)
            ['position_id' => 44, 'document_name' => 'Медицинско уверение'],
            ['position_id' => 44, 'document_name' => 'Сертификат за обука - работа со храна'],
            ['position_id' => 44, 'document_name' => 'Сертификат HACCP'],
            ['position_id' => 44, 'document_name' => 'Диплома или сертификат за готвач'],

            // Сервитьор (ID: 23)
            ['position_id' => 23, 'document_name' => 'Медицинско уверение'],
            ['position_id' => 23, 'document_name' => 'Сертификат за обука - работа со храна'],
            ['position_id' => 23, 'document_name' => 'Лична карта'],

            // Шофьор, товарен автомобил (международни превози) (ID: 32)
            ['position_id' => 32, 'document_name' => 'Возачка дозвола категорија C+E'],
            ['position_id' => 32, 'document_name' => 'Професионална компетентност (CPC)'],
            ['position_id' => 32, 'document_name' => 'Дигитална тахографска картичка'],
            ['position_id' => 32, 'document_name' => 'Медицинско уверение'],
            ['position_id' => 32, 'document_name' => 'Пасош'],

            // Камериер/камериерка (ID: 19)
            ['position_id' => 19, 'document_name' => 'Лична карта'],
            ['position_id' => 19, 'document_name' => 'Медицинско уверение'],
            ['position_id' => 19, 'document_name' => 'Сертификат за обука - хотелски услуги'],

            // Рецепционист, хотел (ID: 60)
            ['position_id' => 60, 'document_name' => 'Лична карта'],
            ['position_id' => 60, 'document_name' => 'CV'],
            ['position_id' => 60, 'document_name' => 'Сертификат за познавање на странски јазик'],
            ['position_id' => 60, 'document_name' => 'Диплома'],

            // Барман (ID: 17)
            ['position_id' => 17, 'document_name' => 'Медицинско уверение'],
            ['position_id' => 17, 'document_name' => 'Сертификат за обука - работа со храна и пијалоци'],
            ['position_id' => 17, 'document_name' => 'Сертификат за бармански курс'],

            // Работник в ресторант (ID: 54)
            ['position_id' => 54, 'document_name' => 'Медицинско уверение'],
            ['position_id' => 54, 'document_name' => 'Сертификат за обука - работа со храна'],
            ['position_id' => 54, 'document_name' => 'Лична карта'],

            // Пазач, невъоръжена охрана (ID: 62)
            ['position_id' => 62, 'document_name' => 'Лична карта'],
            ['position_id' => 62, 'document_name' => 'Уверение за неказнуваност'],
            ['position_id' => 62, 'document_name' => 'Сертификат за обука - обезбедување'],
            ['position_id' => 62, 'document_name' => 'Медицинско уверение'],

            // Продавач, консултант (ID: 22)
            ['position_id' => 22, 'document_name' => 'Лична карта'],
            ['position_id' => 22, 'document_name' => 'CV'],
            ['position_id' => 22, 'document_name' => 'Диплома'],
        ];

        foreach ($documents as $document) {
            PositionDocument::create($document);
        }
    }
}
