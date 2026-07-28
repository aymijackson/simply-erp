<?php

namespace Modules\Finance\Services\Posting;

use Illuminate\Support\Facades\DB;
use Modules\Finance\Models\Expense;
use Modules\Finance\Models\ExpenseLine;
use Modules\Finance\Models\JournalEntry;
use Modules\Finance\Models\JournalEntryLine;

class ExpensePostingService
{

    public static function post($companyId, $expenseId)
    {

        return DB::transaction(function() use ($companyId,$expenseId){

            $expense = Expense::with('lines','category')->findOrFail($expenseId);

            if($expense->journal_entry_id)
                return $expense->journal_entry_id;

            $mapping = DB::table('finance_account_mappings')
                ->where('company_id',$companyId)
                ->first();

            if(!$mapping)
                throw new \Exception("Finance account mapping not configured");


            /*
            CREATE JE
            */

            $jeId = DB::table('finance_journal_entries')->insertGetId([

                'company_id'=>$companyId,
                'entry_no'=>null,
                'entry_date'=>$expense->expense_date,
                'reference'=>$expense->reference,
                'memo'=>"Expense ".$expense->expense_no,
                'status'=>'posted',
                'source_type'=>'expense',
                'source_id'=>$expense->id,
                'posted_at'=>now(),
                'posted_by'=>auth()->id(),
                'created_at'=>now()

            ]);


            $totalDebit=0;


            /*
            DEBIT LINES
            */

            foreach($expense->lines as $line){

                DB::table('finance_journal_entry_lines')->insert([

                    'journal_entry_id'=>$jeId,
                    'account_id'=>$line->gl_account_id,
                    'description'=>$line->description,
                    'debit'=>$line->line_total,
                    'credit'=>0,
                    'currency_code'=>$expense->currency_code,
                    'fx_rate'=>$expense->fx_rate,
                    'created_at'=>now()

                ]);

                $totalDebit+=$line->line_total;

            }


            /*
            TAX
            */

            if($expense->tax_total>0){

                DB::table('finance_journal_entry_lines')->insert([

                    'journal_entry_id'=>$jeId,
                    'account_id'=>$mapping->vat_output_account_id,
                    'description'=>'VAT',
                    'debit'=>$expense->tax_total,
                    'credit'=>0,
                    'currency_code'=>$expense->currency_code,
                    'fx_rate'=>$expense->fx_rate,
                    'created_at'=>now()

                ]);

                $totalDebit+=$expense->tax_total;
            }



            /*
            CREDIT SIDE
            */

            if($expense->payment_mode=='bank'){

                DB::table('finance_journal_entry_lines')->insert([

                    'journal_entry_id'=>$jeId,
                    'account_id'=>$mapping->default_bank_gl_account_id,
                    'bank_account_id'=>$expense->bank_account_id,
                    'description'=>'Bank Payment',
                    'debit'=>0,
                    'credit'=>$totalDebit,
                    'currency_code'=>$expense->currency_code,
                    'fx_rate'=>$expense->fx_rate,
                    'created_at'=>now()

                ]);

            }
            elseif($expense->payment_mode=='cash'){

                DB::table('finance_journal_entry_lines')->insert([

                    'journal_entry_id'=>$jeId,
                    'account_id'=>$mapping->default_bank_gl_account_id,
                    'description'=>'Cash Payment',
                    'debit'=>0,
                    'credit'=>$totalDebit,
                    'currency_code'=>$expense->currency_code,
                    'fx_rate'=>$expense->fx_rate,
                    'created_at'=>now()

                ]);

            }
            else{

                DB::table('finance_journal_entry_lines')->insert([

                    'journal_entry_id'=>$jeId,
                    'account_id'=>$mapping->ap_account_id,
                    'description'=>'Accounts Payable',
                    'debit'=>0,
                    'credit'=>$totalDebit,
                    'currency_code'=>$expense->currency_code,
                    'fx_rate'=>$expense->fx_rate,
                    'created_at'=>now()

                ]);

            }



            DB::table('finance_expenses')
                ->where('id',$expenseId)
                ->update([

                    'journal_entry_id'=>$jeId,
                    'status'=>'posted',
                    'posted_at'=>now(),
                    'posted_by'=>auth()->id()

                ]);


            return $jeId;

        });

    }

}