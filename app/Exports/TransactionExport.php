<?php

namespace App\Exports;

use App\Http\Enums\TypeServiceEnum;
use App\Http\Enums\UserRolesEnum;
use App\Models\Transaction;
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
        $transactions  = DB::table('transactions')
            ->join("users","users.id","transactions.source")
            ->join('services', 'transactions.service_id', '=', 'services.id')
            ->join('partenaires', 'services.partenaire_id', '=', 'partenaires.id')
            ->join('type_services', 'services.type_service_id', '=', 'type_services.id')
            ->select('transactions.reference','transactions.reference_partenaire','transactions.date_transaction','partenaires.name_partenaire','services.name_service','transactions.debit','transactions.credit' ,'transactions.customer_phone','transactions.commission_agent','transactions.commission_distributeur','transactions.balance_before','transactions.balance_after','transactions.description as status','users.login as agent')
            ->where("transactions.fichier","agent")
            ->where("users.distributeur_id",Auth::user()->distributeur_id)
            ->where("users.type_user_id", UserRolesEnum::AGENT->value)
            ->where('transactions.status',1)
            ->where("services.type_service_id",TypeServiceEnum::ENVOI->value)
            ->orwhere("services.type_service_id",TypeServiceEnum::RETRAIT->value)
            ->orwhere("services.type_service_id",TypeServiceEnum::FACTURE->value)
            ->orwhere("services.type_service_id",TypeServiceEnum::APPROVISIONNEMENT->value)
            ->orwhere("services.type_service_id",TypeServiceEnum::ANNULATION->value)
            ->orderByDesc('transactions.date_transaction')->get();

         return $transactions;
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
            'PARTENAIRE',
            'SERVICE',
            'DEBIT',
            'CREDIT',
            'CLIENT',
            'COMMISSION AGENT',
            'COMMISSION DISTRIBUTEUR',
            'SOLDE AVANT',
            'SOLDE APRES',
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
                $event->sheet->getDelegate()->getStyle('A1:N1')
                    ->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()
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
          $row->name_partenaire,
          $row->name_service,
          $row->debit,
          $row->credit,
          $row->customer_phone,
          $row->commission_agent,
          $row->commission_distributeur,
          $row->balance_before,
          $row->balance_after, $row->status,
          $row->agent,
        ];
    }
}
