<?php

namespace App\Exports;
use http\Env\Response;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class ApporivsionnementExport implements FromCollection, WithHeadings, WithEvents
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $approDistributeur = DB::table("appro_distributeurs")
        ->join("distributeurs","distributeurs.id", "appro_distributeurs.distributeur_id")
       ->select("appro_distributeurs.reference", "appro_distributeurs.date_operation","appro_distributeurs.amount","appro_distributeurs.balance_before","appro_distributeurs.balance_after",DB::raw('(CASE
        WHEN appro_distributeurs.status = "0" THEN "EN COURS"
        WHEN appro_distributeurs.status = "1" THEN "VALIDE"
        ELSE "REJETE"
        END) AS status'),"appro_distributeurs.date_validation","appro_distributeurs.date_reject")->orderByDesc("appro_distributeurs.date_operation")->get();

        return $approDistributeur;
    }

    /**
     * @return response()
     */

    public function headings(): array
    {
        return [
            'REFERENCE',
            'DATE DEMANDE',
            'MONTANT',
            'BALANCE AVANT',
            'BALANCE APRES',
            'STATUT',
            'DATE VALIDATION',
            'DATE REJET',

        ];
    }

    /**

     * Write code on Method

     *

     * @return response()

     */

    public function registerEvents(): array
    {
        return [

            AfterSheet::class    => function(AfterSheet $event) {
                $event->sheet->getDelegate()->getStyle('A1:H1')
                    ->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setARGB('FF0000');
            },
        ];
    }
}
