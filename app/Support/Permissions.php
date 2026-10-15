<?php

namespace App\Support;

/** Module × action permission catalog for branch staff. */
class Permissions
{
    public const ACTIONS = ['view', 'add', 'edit', 'delete', 'approve', 'export', 'print'];

    /** module => allowed actions */
    public const MODULES = [
        'dashboard' => ['view'],
        'products' => ['view', 'add', 'edit', 'delete', 'export', 'print'],
        'orders' => ['view', 'edit', 'approve', 'delete', 'export', 'print'],
        'customers' => ['view', 'add', 'edit', 'delete', 'export'],
        'returns' => ['view', 'edit', 'approve'],
        'reviews' => ['view', 'approve', 'delete'],
        'coupons' => ['view', 'add', 'edit', 'delete'],
        'website' => ['view', 'edit'],
        'inventory' => ['view', 'add', 'edit', 'approve', 'export'],
        'suppliers' => ['view', 'add', 'edit', 'delete'],
        'purchases' => ['view', 'add', 'edit', 'delete', 'print'],
        'pos' => ['view', 'add', 'approve', 'print'],
        'reports' => ['view', 'export', 'print'],
        'accounts' => ['view', 'add', 'edit', 'delete'],
        'marketing' => ['view', 'add', 'edit'],
        'blog' => ['view', 'add', 'edit', 'delete'],
        'hrm' => ['view', 'add', 'edit', 'delete', 'approve'],
        'staff' => ['view', 'add', 'edit', 'delete'],
        'settings' => ['view', 'edit'],
        'subscription' => ['view', 'edit'],
        'activity' => ['view'],
    ];

    public const LABELS = [
        'dashboard' => 'Dashboard', 'products' => 'Products (+Brands)', 'orders' => 'Orders', 'customers' => 'Customers / CRM',
        'returns' => 'Returns & Refunds', 'reviews' => 'Reviews', 'coupons' => 'Coupons & Promotions', 'website' => 'Website builder (theme, homepage, pages, sliders)',
        'inventory' => 'Inventory / Warehouse', 'suppliers' => 'Suppliers', 'purchases' => 'Purchases', 'pos' => 'POS (add=sell, approve=discount/price/return)',
        'reports' => 'Reports', 'accounts' => 'Accounts, Expenses & Cash book', 'marketing' => 'Marketing (SMS/Email campaigns, notifications)', 'blog' => 'Blog posts', 'hrm' => 'HRM (employees, attendance, leave; approve = leave & salary)', 'staff' => 'Staff & Roles', 'settings' => 'Settings',
        'subscription' => 'Subscription & Billing', 'activity' => 'Activity Log',
    ];

    public static function all(): array
    {
        $all = [];
        foreach (self::MODULES as $module => $actions) {
            foreach ($actions as $a) {
                $all[] = "{$module}.{$a}";
            }
        }

        return $all;
    }

    /** Default roles created for every new branch. */
    public static function defaultRoles(): array
    {
        $only = fn (array $modules) => collect(self::all())->filter(fn ($p) => in_array(explode('.', $p)[0], $modules, true))->values()->all();

        return [
            'Manager' => ['Full access except billing & staff', array_values(array_diff(self::all(), ['subscription.edit', 'staff.delete']))],
            'Salesman' => ['Orders, products (view), POS', ['dashboard.view', 'products.view', 'orders.view', 'orders.edit', 'orders.print', 'customers.view', 'customers.add', 'pos.view', 'pos.add', 'pos.print']],
            'Cashier' => ['POS sales, payments, invoices', ['dashboard.view', 'products.view', 'orders.view', 'orders.edit', 'orders.approve', 'orders.print', 'customers.view', 'customers.add', 'pos.view', 'pos.add', 'pos.print']],
            'Inventory Manager' => ['Products, stock, purchases', array_merge(['dashboard.view'], $only(['products', 'inventory', 'purchases', 'suppliers']))],
            'Accountant' => ['Payments & reports', ['dashboard.view', 'orders.view', 'orders.approve', 'orders.export', 'reports.view', 'reports.export', 'reports.print', 'accounts.view', 'accounts.add', 'accounts.edit', 'purchases.view', 'suppliers.view']],
            'Delivery Manager' => ['Delivery status updates', ['dashboard.view', 'orders.view', 'orders.edit', 'orders.print']],
            'Customer Support' => ['Customers & order lookups', ['dashboard.view', 'orders.view', 'customers.view', 'customers.edit', 'returns.view', 'returns.edit', 'reviews.view', 'reviews.approve']],
        ];
    }
}
