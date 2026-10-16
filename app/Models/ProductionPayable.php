<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductionPayable extends Model
{
    use SoftDeletes;

    protected $table = 'production_payables';

    protected $fillable = [
        'document_number',
        'posting_date',
        'value_date',
        'item_no',
        'item_description',
        'quantity',
        'remarks',
        'status',
        'uploaded_by',
    ];

    protected $casts = [
        'posting_date' => 'date',
        'value_date' => 'date',
        'quantity' => 'integer',
    ];

    // Relationships (optional)
    public function uploadedByUser()
    {
        return $this->belongsTo(User::class, 'uploaded_by', 'id');
    }

    // Scopes
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByDocumentNumber($query, $documentNumber)
    {
        return $query->where('document_number', $documentNumber);
    }

    public function scopeByItemNo($query, $itemNo)
    {
        return $query->where('item_no', $itemNo);
    }

    public function scopeFromDate($query, $date)
    {
        return $query->where('posting_date', '>=', $date);
    }

    public function scopeToDate($query, $date)
    {
        return $query->where('posting_date', '<=', $date);
    }

    public function scopeByRemarks($query, $remarks)
    {
        return $query->where('remarks', 'like', '%' . $remarks . '%');
    }
}
