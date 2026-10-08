<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterBusinessPartner extends Model
{
    use HasFactory;

    public const CATEGORY_CUSTOMER = 'CUSTOMER';
    public const CATEGORY_VENDOR = 'VENDOR';

    protected $table = 'master_business_partners';

    protected $fillable = [
        'bp_code',
        'bp_name',
        'group_code',
        'category',
        'type',
        'foreign_name',
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
}
