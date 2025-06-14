<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DownloadPdfController extends Controller
{

    public function bakpiaTransaction($id)
    {
        // $record = Pengajuan::find($id);
        // dd($record);
        $record = DB::table('bakpia_transactions')
            ->join('outlets', 'bakpia_transactions.id_outlet', '=', 'outlets.id_outlet')
            ->join('customers', 'bakpia_transactions.id_customer', '=', 'customers.id')
            ->join('payments', 'bakpia_transactions.id_payment', '=', 'payments.id')
            ->select('bakpia_transactions.*', 'outlets.name  as outlet_name', 'customers.name  as customer_name', 'payments.name  as payment_name')
            ->where('bakpia_transactions.id_transaction', $id)
            ->first();

        $transaction_detail = json_decode($record->transaction_detail);
        // dd($transaction_detail);
        foreach ($transaction_detail as $key => $value) {

            if (isset($value->box_varian)) { // Always good to check if property exists
                switch ($value->box_varian) {
                    case "box_8":
                        $value->isi = 'isi 8';
                        break;
                    case "box_18":
                        $value->isi = 'isi 18';
                        break;
                }
            }
        }

        //PARSING DATE
        $record->created_at = Carbon::parse($record->created_at)->format('d M Y h:i:s');
        $record->transaction_admin = Auth::user()->name;
        // dd($record);

        $pdf = App::make('dompdf.wrapper');
        $pdf->loadView('pdf.bakpia_transaction_report', compact('record', 'transaction_detail')); // Pass the variable $record to the blade file
        return $pdf->stream(); // renders the PDF in the browser
    }
}
