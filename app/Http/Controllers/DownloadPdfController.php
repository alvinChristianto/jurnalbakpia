<?php

namespace App\Http\Controllers;

use App\Support\LegacyIsiLabel;
use Carbon\Carbon;
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
        $transaction_detail = LegacyIsiLabel::applyAll($transaction_detail);

        // PARSING DATE
        $record->created_at = Carbon::parse($record->created_at)->format('d M Y H:i:s');
        $record->transaction_admin = Auth::user()->name;
        // dd($record);

        $pdf = App::make('dompdf.wrapper');
        $pdf->loadView('pdf.bakpia_transaction_report', compact('record', 'transaction_detail')); // Pass the variable $record to the blade file

        return $pdf->stream(); // renders the PDF in the browser
    }

    public function otherProductTransaction($id)
    {
        // $record = Pengajuan::find($id);
        // dd($record);
        $record = DB::table('other_product_transactions')
            ->join('outlets', 'other_product_transactions.id_outlet', '=', 'outlets.id_outlet')
            ->join('customers', 'other_product_transactions.id_customer', '=', 'customers.id')
            ->join('payments', 'other_product_transactions.id_payment', '=', 'payments.id')
            ->select('other_product_transactions.*', 'outlets.name  as outlet_name', 'customers.name  as customer_name', 'payments.name  as payment_name')
            ->where('other_product_transactions.id_transaction', $id)
            ->first();

        $transaction_detail = json_decode($record->other_transaction_detail);
        $transaction_detail = LegacyIsiLabel::applyAll($transaction_detail);

        // PARSING DATE
        $record->created_at = Carbon::parse($record->created_at)->format('d M Y H:i:s');
        $record->transaction_admin = Auth::user()->name;
        // dd($record);

        $pdf = App::make('dompdf.wrapper');
        $pdf->loadView('pdf.other_product_transaction_report', compact('record', 'transaction_detail')); // Pass the variable $record to the blade file

        return $pdf->stream(); // renders the PDF in the browser
    }

    public function transaction($id)
    {
        $record = DB::table('transactions')
            ->join('outlets', 'transactions.id_outlet', '=', 'outlets.id_outlet')
            ->join('customers', 'transactions.id_customer', '=', 'customers.id')
            ->join('payments', 'transactions.id_payment', '=', 'payments.id')
            ->select('transactions.*', 'outlets.name  as outlet_name', 'customers.name  as customer_name', 'payments.name  as payment_name')
            ->where('transactions.id_transaction', $id)
            ->first();

        $transaction_detail = json_decode($record->transaction_details);
        $transaction_detail = LegacyIsiLabel::applyAll($transaction_detail);

        // PARSING DATE
        $record->created_at = Carbon::parse($record->created_at)->format('d M Y H:i:s');
        $record->transaction_admin = Auth::user()->name;
        // dd($record);

        $pdf = App::make('dompdf.wrapper');
        $pdf->loadView('pdf.transaction_report', compact('record', 'transaction_detail')); // Pass the variable $record to the blade file

        return $pdf->stream(); // renders the PDF in the browser
    }
}
