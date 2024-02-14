<?php

namespace App\Exports;

use App\Http\Enums\UserRolesEnum;
use App\Models\Transaction;
use http\Env\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class RechargeExport implements  FromCollection, WithHeadings, WithEvents
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {

        $transactions  = DB::table('transactions')
            ->join("users","users.id","transactions.created_by")
            ->join("users as agent","agent.id","transactions.agent_id")
            ->join('services', 'transactions.service_id', '=', 'services.id')
            ->select('transactions.date_transaction','transactions.reference','agent.login as ref_agent','transactions.debit','transactions.credit' ,'transactions.balance_before_partenaire','transactions.balance_after_partenaire' ,'services.name_service',DB::raw('concat(users.name," ", users.surname ) as operateur'))
            ->where("transactions.fichier","distributeur")
            ->where('transactions.status',1)
            ->where("users.type_user_id", UserRolesEnum::DISTRIBUTEUR->value)
            ->where("users.distributeur_id",Auth::user()->distributeur_id)
            ->orderByDesc('transactions.date_transaction')->get();
        return $transactions;
    }

    /**
     * @return response()
     */

    public function headings(): array
    {
        return [
            'DATE TRANSACTION',
            'REFERENCE',
            'AGENT',
            'DEBIT',
            'CREDIT',
            'SOLDE AVANT',
            'SOLDE APRES',
            'SERVICE',
            'OPERATEUR',
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
                $event->sheet->getDelegate()->getStyle('A1:I1')
                    ->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setARGB('FF0000');
            },
        ];
    }
}
