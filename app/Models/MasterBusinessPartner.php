<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterBusinessPartner extends Model
{
    use HasFactory;

    public const CATEGORY_CUSTOMER = 'CUSTOMER';
    public const CATEGORY_VENDOR = 'VENDOR';

    public const INDUSTRY_AUTOMOTIVE = 'AUTOMOTIVE';
    public const INDUSTRY_ELECTRONICS = 'ELECTRONICS';
    public const INDUSTRY_MOULDING = 'MOULDING';
    public const INDUSTRY_GENERAL = 'GENERAL';

    protected $table = 'master_business_partners';

    protected $fillable = [
        'bp_code',
        'bp_name',
        'group_code',
        'category',
        'industry',
        'type',
        'foreign_name',
        'sales_employee',
    ];

    /**
     * Determine category deterministically from SAP Group Code (100 = Customer, 101/102 = Vendor).
     */
    public static function classifyCategory(string $groupCode, string $bpCode = ''): string
    {
        $grp = trim($groupCode);
        if ($grp === '100') {
            return self::CATEGORY_CUSTOMER;
        }

        if ($grp !== '') {
            return self::CATEGORY_VENDOR;
        }

        // Fallback to prefix if group_code is omitted
        $code = strtoupper(trim($bpCode));
        if (
            str_starts_with($code, 'KD') ||
            str_starts_with($code, 'D') ||
            str_starts_with($code, 'F') ||
            str_starts_with($code, 'CT') ||
            str_starts_with($code, 'A')
        ) {
            return self::CATEGORY_CUSTOMER;
        }

        return self::CATEGORY_VENDOR;
    }

    /**
     * Scope for Customers only.
     */
    public function scopeCustomers($query)
    {
        return $query->where('category', self::CATEGORY_CUSTOMER);
    }

    /**
     * Scope for Vendors only.
     */
    public function scopeVendors($query)
    {
        return $query->where('category', self::CATEGORY_VENDOR);
    }

    /**
     * Scope by Group Code.
     */
    public function scopeGroup($query, string $groupCode)
    {
        return $query->where('group_code', $groupCode);
    }

    /**
     * Scope by Industry.
     */
    public function scopeIndustry($query, string $industry)
    {
        return $query->where('industry', strtoupper(trim($industry)));
    }

    /**
     * Scope for Automotive only.
     */
    public function scopeAutomotive($query)
    {
        return $query->where('industry', self::INDUSTRY_AUTOMOTIVE);
    }

    /**
     * Scope for Electronics only.
     */
    public function scopeElectronics($query)
    {
        return $query->where('industry', self::INDUSTRY_ELECTRONICS);
    }

    /**
     * Scope for Moulding only.
     */
    public function scopeMoulding($query)
    {
        return $query->where('industry', self::INDUSTRY_MOULDING);
    }

    /**
     * Determine if this business partner is automotive.
     */
    public function getIsAutomotiveAttribute(): bool
    {
        return $this->industry === self::INDUSTRY_AUTOMOTIVE;
    }

    /**
     * Determine if this business partner is moulding.
     */
    public function getIsMouldingAttribute(): bool
    {
        return $this->industry === self::INDUSTRY_MOULDING;
    }

    /**
     * Smart detection for industry:
     * - Moulding when type or foreign_name has value like "mould" or "moulding" (or "mold")
     * - Automotive when sales employee is Andriani (Ext 131) or Anik (Ext 155)
     * - Electronics when name or alias matches electronics keywords
     * - General for the rest
     */
    public static function detectIndustry(string $name, string $foreignName = '', ?string $salesEmployee = null, ?string $type = null): string
    {
        $typeLower = strtolower(trim((string) $type));
        $foreignLower = strtolower(trim($foreignName));
        if (
            str_contains($typeLower, 'mould') || str_contains($typeLower, 'mold') ||
            str_contains($foreignLower, 'mould') || str_contains($foreignLower, 'mold')
        ) {
            return self::INDUSTRY_MOULDING;
        }

        $sales = trim((string) $salesEmployee);
        if ($sales !== '') {
            $salesLower = strtolower($sales);
            if (str_contains($salesLower, 'andriani') || str_contains($salesLower, 'anik')) {
                return self::INDUSTRY_AUTOMOTIVE;
            }
        }

        $text = strtolower(trim($name . ' ' . $foreignName));
        $electronicsKeywords = [
            'sharp', 'toshiba', 'sanken', 'panasonic', 'hartono istana', 'hit',
            'bumjin', 'samsung', 'lg', 'electra', 'sony', 'electronic',
        ];

        foreach ($electronicsKeywords as $kw) {
            if (str_contains($text, $kw)) {
                return self::INDUSTRY_ELECTRONICS;
            }
        }

        return self::INDUSTRY_GENERAL;
    }
}
