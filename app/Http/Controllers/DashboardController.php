<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Supplier;
use App\Models\User;

class DashboardController extends Controller
{
    /**
     * Display the dashboard based on the authenticated user's role.
     */
    public function index()
    {
        $user = auth()->user();

        /*
        |--------------------------------------------------------------------------
        | OWNER DASHBOARD
        |--------------------------------------------------------------------------
        |
        | The Owner monitors inventory, purchasing, sales, suppliers,
        | users, and recent system activity.
        |
        */

        if ($user->role === 'OWNER') {

            /*
            |--------------------------------------------------------------------------
            | Product Count
            |--------------------------------------------------------------------------
            |
            | Count active products only.
            |
            */

            $productCount = Product::where(
                'is_active',
                true
            )->count();


            /*
            |--------------------------------------------------------------------------
            | Category Count
            |--------------------------------------------------------------------------
            */

            $categoryCount = Category::where(
                'is_active',
                true
            )->count();


            /*
            |--------------------------------------------------------------------------
            | Supplier Count
            |--------------------------------------------------------------------------
            */

            $supplierCount = Supplier::where(
                'is_active',
                true
            )->count();


            /*
            |--------------------------------------------------------------------------
            | User Count
            |--------------------------------------------------------------------------
            */

            $userCount = User::where(
                'is_active',
                true
            )->count();


            /*
            |--------------------------------------------------------------------------
            | Inventory Count
            |--------------------------------------------------------------------------
            */

            $inventoryCount = Inventory::count();


            /*
            |--------------------------------------------------------------------------
            | Low Stock Count
            |--------------------------------------------------------------------------
            |
            | A product is considered low stock when:
            |
            | quantity_on_hand <= reorder_level
            |
            | Archived products are excluded.
            |
            */

            $lowStockCount = Inventory::whereColumn(
                'quantity_on_hand',
                '<=',
                'reorder_level'
            )
                ->whereHas('product', function ($query) {

                    $query->where(
                        'is_active',
                        true
                    );

                })
                ->count();


            /*
            |--------------------------------------------------------------------------
            | Total Purchase Transactions
            |--------------------------------------------------------------------------
            */

            $purchaseCount = Purchase::count();


            /*
            |--------------------------------------------------------------------------
            | Total Sales Transactions
            |--------------------------------------------------------------------------
            */

            $saleCount = Sale::count();


            /*
            |--------------------------------------------------------------------------
            | Today's Purchases
            |--------------------------------------------------------------------------
            */

            $todayPurchaseCount = Purchase::whereDate(
                'purchase_date',
                today()
            )->count();


            $todayPurchaseAmount = Purchase::whereDate(
                'purchase_date',
                today()
            )->sum('total_amount');


            /*
            |--------------------------------------------------------------------------
            | Today's Sales
            |--------------------------------------------------------------------------
            */

            $todaySalesCount = Sale::whereDate(
                'sale_date',
                today()
            )->count();


            $todaySalesAmount = Sale::whereDate(
                'sale_date',
                today()
            )->sum('total_amount');


            /*
            |--------------------------------------------------------------------------
            | Low Stock Products
            |--------------------------------------------------------------------------
            |
            | Used later when we improve the Owner dashboard.
            |
            */

            $lowStockItems = Inventory::with([
                'product.productUnits.unit'
            ])
                ->whereColumn(
                    'quantity_on_hand',
                    '<=',
                    'reorder_level'
                )
                ->whereHas('product', function ($query) {

                    $query->where(
                        'is_active',
                        true
                    );

                })
                ->orderBy('quantity_on_hand')
                ->limit(10)
                ->get();


            /*
            |--------------------------------------------------------------------------
            | Recent Sales
            |--------------------------------------------------------------------------
            */

            $recentSales = Sale::with('user')
                ->latest('sale_date')
                ->limit(5)
                ->get();


            /*
            |--------------------------------------------------------------------------
            | Recent Purchases
            |--------------------------------------------------------------------------
            */

            $recentPurchases = Purchase::with([
                'user',
                'supplier'
            ])
                ->latest('purchase_date')
                ->limit(5)
                ->get();


            /*
            |--------------------------------------------------------------------------
            | Recent Activity
            |--------------------------------------------------------------------------
            */

            $recentActivities = ActivityLog::with('user')
                ->latest('created_at')
                ->limit(10)
                ->get();


            /*
            |--------------------------------------------------------------------------
            | Send Data to Owner Dashboard
            |--------------------------------------------------------------------------
            */

            return view(
                'dashboard.owner',
                compact(
                    'productCount',
                    'categoryCount',
                    'supplierCount',
                    'userCount',
                    'inventoryCount',

                    'lowStockCount',

                    'purchaseCount',
                    'saleCount',

                    'todayPurchaseCount',
                    'todayPurchaseAmount',

                    'todaySalesCount',
                    'todaySalesAmount',

                    'lowStockItems',

                    'recentSales',
                    'recentPurchases',

                    'recentActivities'
                )
            );
        }


        /*
        |--------------------------------------------------------------------------
        | SALES CLERK DASHBOARD
        |--------------------------------------------------------------------------
        |
        | Sales Clerk only needs information related to sales/cashiering.
        |
        */

        $todaySalesCount = Sale::whereDate(
            'sale_date',
            today()
        )->count();


        $todaySalesAmount = Sale::whereDate(
            'sale_date',
            today()
        )->sum('total_amount');


        $recentSales = Sale::with('user')
            ->latest('sale_date')
            ->limit(5)
            ->get();


        /*
        |--------------------------------------------------------------------------
        | Send Data to Sales Clerk Dashboard
        |--------------------------------------------------------------------------
        */

        return view(
            'dashboard.sales_clerk',
            compact(
                'todaySalesCount',
                'todaySalesAmount',
                'recentSales'
            )
        );
    }
}