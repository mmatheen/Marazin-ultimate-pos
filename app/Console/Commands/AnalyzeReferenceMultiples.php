<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Ledger;
use Illuminate\Support\Facades\DB;

class AnalyzeReferenceMultiples extends Command
{
    protected $signature = 'ledger:analyze-references {reference? : Specific reference to analyze}';
    protected $description = 'Detailed analysis of reference numbers with multiple entries';

    public function handle()
    {
        $specificRef = $this->argument('reference');
        
        if ($specificRef) {
            $this->analyzeSpecificReference($specificRef);
        } else {
            $this->analyzeAllMultipleReferences();
        }
    }

    private function analyzeAllMultipleReferences()
    {
        $this->info('🔍 ANALYZING ALL REFERENCES WITH MULTIPLE ENTRIES...');
        $this->newLine();
        
        // Get all reference numbers with multiple entries
        $multipleRefs = DB::select("
            SELECT reference_no, COUNT(*) as count
            FROM ledgers 
            WHERE reference_no IS NOT NULL AND reference_no != ''
            GROUP BY reference_no
            HAVING COUNT(*) > 1
            ORDER BY count DESC
            LIMIT 20
        ");
        
        foreach ($multipleRefs as $ref) {
            $this->line("═══════════════════════════════════════════════════════");
            $this->info("📋 REFERENCE: {$ref->reference_no} ({$ref->count} entries)");
            $this->line("═══════════════════════════════════════════════════════");
            
            $this->analyzeSpecificReference($ref->reference_no, false);
            $this->newLine();
        }
    }

    private function analyzeSpecificReference($reference, $showHeader = true)
    {
        if ($showHeader) {
            $this->info("🔍 ANALYZING REFERENCE: {$reference}");
            $this->newLine();
        }
        
        // Get all entries for this reference
        $entries = Ledger::where('reference_no', $reference)
            ->orderBy('id')
            ->get();
            
        if ($entries->isEmpty()) {
            $this->error("❌ No entries found for reference: {$reference}");
            return;
        }
        
        // Analyze the pattern
        $this->analyzeEntryPattern($entries);
        
        // Show detailed breakdown
        $this->showDetailedBreakdown($entries);
        
        // Determine if this is problematic
        $this->assessPattern($entries);
    }
    
    private function analyzeEntryPattern($entries)
    {
        $this->info('📊 PATTERN ANALYSIS:');
        $this->line('─────────────────────');
        
        // Group by transaction type
        $typeGroups = $entries->groupBy('transaction_type');
        foreach ($typeGroups as $type => $typeEntries) {
            $count = $typeEntries->count();
            $totalDebit = $typeEntries->sum('debit');
            $totalCredit = $typeEntries->sum('credit');
            $this->line("  • {$type}: {$count} entries | Debit: {$totalDebit} | Credit: {$totalCredit}");
        }
        
        // Group by contact
        $contactGroups = $entries->groupBy('contact_id');
        $this->newLine();
        $this->line('👥 BY CONTACT:');
        foreach ($contactGroups as $contactId => $contactEntries) {
            $count = $contactEntries->count();
            $this->line("  • Contact {$contactId}: {$count} entries");
        }
        
        // Check dates
        $dates = $entries->pluck('transaction_date')->unique();
        $this->newLine();
        $this->line('📅 TRANSACTION DATES:');
        $this->line("  • Date range: " . $dates->min() . " to " . $dates->max());
        $this->line("  • Unique dates: " . $dates->count());
        
        $this->newLine();
    }
    
    private function showDetailedBreakdown($entries)
    {
        $this->info('📋 DETAILED BREAKDOWN:');
        $this->line('─────────────────────────────────────────────────────────────────────────────────────────');
        $this->line('ID    | Date       | Type                  | Contact | Debit      | Credit     | Status  | Notes');
        $this->line('─────────────────────────────────────────────────────────────────────────────────────────');
        
        foreach ($entries as $entry) {
            $id = str_pad($entry->id, 5);
            $date = $entry->transaction_date;
            $type = str_pad(substr($entry->transaction_type, 0, 20), 21);
            $contact = str_pad($entry->contact_id, 7);
            $debit = str_pad(number_format($entry->debit, 2), 10);
            $credit = str_pad(number_format($entry->credit, 2), 10);
            $status = str_pad($entry->status, 7);
            $notes = substr($entry->notes ?? '', 0, 40);
            
            $this->line("{$id} | {$date} | {$type} | {$contact} | {$debit} | {$credit} | {$status} | {$notes}");
        }
        
        $this->line('─────────────────────────────────────────────────────────────────────────────────────────');
        $this->newLine();
    }
    
    private function assessPattern($entries)
    {
        $this->info('🎯 ASSESSMENT:');
        $this->line('───────────────');
        
        $typeCount = $entries->groupBy('transaction_type')->count();
        $contactCount = $entries->groupBy('contact_id')->count();
        $dateCount = $entries->pluck('transaction_date')->unique()->count();
        
        // Check for legitimate patterns
        $isLegitimate = false;
        $reason = '';
        
        // Pattern 1: Sale with payments (legitimate)
        if ($entries->contains('transaction_type', 'sale') && 
            ($entries->contains('transaction_type', 'payment') || $entries->contains('transaction_type', 'payments'))) {
            $isLegitimate = true;
            $reason = 'Sale with payment transactions (NORMAL)';
        }
        
        // Pattern 2: Purchase with payments (legitimate)
        elseif ($entries->contains('transaction_type', 'purchase') && 
                ($entries->contains('transaction_type', 'payment') || $entries->contains('transaction_type', 'payments'))) {
            $isLegitimate = true;
            $reason = 'Purchase with payment transactions (NORMAL)';
        }
        
        // Pattern 3: Multiple different transaction types (legitimate)
        elseif ($typeCount > 1) {
            $isLegitimate = true;
            $reason = 'Multiple related transaction types (NORMAL)';
        }
        
        // Pattern 4: Same type but different dates (could be installments)
        elseif ($typeCount == 1 && $dateCount > 1) {
            $isLegitimate = true;
            $reason = 'Installment payments or multiple dates (LIKELY NORMAL)';
        }
        
        // Pattern 5: Same everything (suspicious)
        else {
            $sameDate = $dateCount == 1;
            $sameType = $typeCount == 1;
            $sameContact = $contactCount == 1;
            $sameAmounts = $entries->groupBy(function($item) {
                return $item->debit . '-' . $item->credit;
            })->count() == 1;
            
            if ($sameDate && $sameType && $sameContact && $sameAmounts) {
                $isLegitimate = false;
                $reason = 'Identical entries - POTENTIAL DUPLICATES';
            } else {
                $isLegitimate = true;
                $reason = 'Different amounts or details (NORMAL)';
            }
        }
        
        if ($isLegitimate) {
            $this->info("✅ LEGITIMATE: {$reason}");
        } else {
            $this->error("❌ SUSPICIOUS: {$reason}");
            $this->warn("   → Consider manual review or cleanup");
        }
        
        // Show totals
        $totalDebit = $entries->sum('debit');
        $totalCredit = $entries->sum('credit');
        $netEffect = $totalDebit - $totalCredit;
        
        $this->newLine();
        $this->line("💰 FINANCIAL IMPACT:");
        $this->line("   Total Debits:  Rs. " . number_format($totalDebit, 2));
        $this->line("   Total Credits: Rs. " . number_format($totalCredit, 2));
        $this->line("   Net Effect:    Rs. " . number_format($netEffect, 2) . ($netEffect > 0 ? ' (Customer owes)' : ($netEffect < 0 ? ' (Customer advance)' : ' (Balanced)')));
    }
}