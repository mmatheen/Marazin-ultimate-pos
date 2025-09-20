<?php

namespace App\Exports;

use App\Models\Discount;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;

class DiscountsExport implements FromCollection, WithHeadings, WithMapping
{
    protected $from;
    protected $to;
    protected $status;

    public function __construct($from = null, $to = null, $status = null)
    {
        $this->from = $from;
        $this->to = $to;
        $this->status = $status;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function headings(): array
    {
        return [
            'ID',
            'Name',
            'Description',
            'Type',
            'Amount',
            'Start Date',
            'End Date',
            'Status',
            'Products Count',
            'Created At',
            'Updated At',
        ];
    }

    public function collection()
    {
        $query = Discount::with(['products']);

        // Apply date filters
        if ($this->from) {
            $query->whereDate('start_date', '>=', $this->from);
        }

        if ($this->to) {
            $query->whereDate('start_date', '<=', $this->to);
        }

        // Apply status filter
        if ($this->status !== null && $this->status !== '') {
            $query->where('is_active', $this->status);
        }

        return $query->get();
    }

    /**
     * Map the data for each row
     */
    public function map($discount): array
    {
        return [
            $discount->id,
            $discount->name,
            $discount->description ?? 'N/A',
            ucfirst($discount->type),
            $discount->type === 'percentage' ? $discount->amount . '%' : 'Rs. ' . $discount->amount,
            $discount->start_date ? $discount->start_date->format('Y-m-d') : 'N/A',
            $discount->end_date ? $discount->end_date->format('Y-m-d') : 'No end date',
            $discount->is_active ? 'Active' : 'Inactive',
            $discount->products->count(),
            $discount->created_at->format('Y-m-d H:i:s'),
            $discount->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}