<?php

namespace App\Http\Controllers\Inventory;

use App\Application\Inventory\DTOs\StockHealthFilterDTO;
use App\Application\Inventory\UseCases\GetStockHealthDashboard;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Thin controller — no business logic lives here.
 *
 * Responsibilities:
 *  1. Extract HTTP inputs into a DTO.
 *  2. Delegate to the Use Case.
 *  3. Pass the result to the view.
 */
class StockHealthController extends Controller
{
    public function __construct(
        private readonly GetStockHealthDashboard $useCase,
    ) {}

    public function index(Request $request): View
    {
        $filter = StockHealthFilterDTO::fromArray(
            $request->only(['search', 'process_owner', 'family'])
        );

        $data = $this->useCase->execute($filter);

        return view('inventory.stock-health', [
            'items' => $data['items'],
            'summary' => $data['summary'],
            'processOwners' => $data['processOwners'],
            'families' => $data['families'],
            'filter' => $filter,
        ]);
    }
}
