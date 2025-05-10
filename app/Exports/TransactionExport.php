<?php

namespace App\Exports;

use App\Http\Enums\StatusTransEnum;
use App\Http\Enums\TypeServiceEnum;
use App\Http\Enums\UserRolesEnum;
use App\Models\Transaction;
use App\Models\User;
use http\Env\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Events\AfterSheet;

class TransactionExport implements FromCollection, WithHeadings, WithEvents, WithStrictNullComparison, WithMapping
{
    public function collection()
    {


        $auth = Auth::user()->type_user_id==UserRolesEnum::DISTRIBUTEUR->value ? User::where("type_user_id",UserRolesEnum::AGENT->value)->where("distributeur_id",Auth::user()->distributeur_id)->pluck('id') :  User::where("type_user_id",UserRolesEnum::AGENT->value)->pluck('id');

        $query = Transaction::with(['service.typeService','auteur.distributeur'])
            ->where("fichier","agent")
            ->where('status',StatusTransEnum::VALIDATED->value)
            ->whereHas('service',function ($query){
                $query->whereIn("type_service_id",[TypeServiceEnum::ENVOI->value,TypeServiceEnum::RETRAIT->value,TypeServiceEnum::PAYMENT->value]);
            })->whereHas('auteur',function ($query) use ($auth){
                $query->whereIn("id",$auth);
            })->orderByDesc('transactions.date_transaction')->get();

         return $query;
    }

    /**
     * @return response()
     */

    public function headings(): array
    {
        return [
            'REFERENCE',
            'REFERENCE PARTENAIRE',
            'DATE TRANSACTION',
            'DATE_FIN_TRANSACTION',
            'PARTENAIRE',
            'SERVICE',
            'DEBIT',
            'CREDIT',
            'SOLDE AVANT',
            'SOLDE APRES',
            'CLIENT',
            'COMMISSION AGENT',
            'COMMISSION DISTRIBUTEUR',
            'STATUT',
            'AGENT',
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
                $event->sheet->getDelegate()->getStyle('A1:O1')
                    ->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('FFFFFF')
                    ->setARGB('FF0000');


            },
        ];
    }

    public function map($row):array
    {
        return[
          $row->reference,
          $row->reference_partenaire,
          $row->date_transaction,
          $row->date_end_trans,
          $row->service->partenaire->name_partenaire,
          $row->service->name_service,
          $row->debit,
          $row->credit,
          $row->balance_before,
          $row->balance_after,
          $row->customer_phone,
          $row->commission_agent,
          $row->commission_distributeur,
          $row->description,
          $row->auteur->telephone,
        ];
    }
}
