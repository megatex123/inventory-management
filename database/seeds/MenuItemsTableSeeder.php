<?php

use Illuminate\Database\Seeder;
use App\Models\MenuItem;

class MenuItemsTableSeeder extends Seeder
{
    /**
     * Transcribes the sidebar exactly as it existed hardcoded in
     * welcome.blade.php as of 2026-07-20 — this seeder's job is to make the
     * DB-driven render byte-for-byte equivalent to what was there before,
     * not to redesign the menu.
     */
    public function run()
    {
        MenuItem::query()->delete();

        $tree = [
            [
                'type' => 'link',
                'label' => 'Dashboard',
                'icon' => 'fas fa-fw fa-tachometer-alt',
                'route' => '/dashboard',
            ],
            [
                'type' => 'group',
                'label' => 'Customer',
                'icon' => 'fas fa-users',
                'children' => [
                    [
                        'type' => 'header',
                        'label' => 'Customer Management',
                        'children' => [
                            ['type' => 'link', 'label' => 'Pre Register Customer', 'route' => '/customer/create'],
                            ['type' => 'link', 'label' => 'Customer List', 'route' => '/customer'],
                        ],
                    ],
                ],
            ],
            [
                'type' => 'group',
                'label' => 'Meeting',
                'icon' => 'fas fa-calendar-alt',
                'children' => [
                    [
                        'type' => 'header',
                        'label' => 'Meeting Management',
                        'children' => [
                            ['type' => 'link', 'label' => 'Meeting List', 'route' => '/meeting'],
                            ['type' => 'link', 'label' => 'Create Meeting', 'route' => '/meeting/create'],
                        ],
                    ],
                    [
                        'type' => 'header',
                        'label' => 'Requirement Meeting',
                        'divider_before' => true,
                        'children' => [
                            ['type' => 'link', 'label' => 'Requirement Meeting List', 'route' => '/meeting-details'],
                            ['type' => 'link', 'label' => 'Create Requirement Meeting', 'route' => '/meeting-details/create'],
                        ],
                    ],
                    [
                        'type' => 'header',
                        'label' => 'UAT Meeting',
                        'divider_before' => true,
                        'children' => [
                            ['type' => 'link', 'label' => 'UAT Meeting List', 'route' => '/uat-meeting'],
                            ['type' => 'link', 'label' => 'Create UAT Meeting', 'route' => '/uat-meeting/create'],
                        ],
                    ],
                ],
            ],
            [
                'type' => 'group',
                'label' => 'Customer Progress',
                'icon' => 'fas fa-fw fa-tasks',
                'children' => [
                    [
                        'type' => 'header',
                        'label' => 'Customer Progress Management',
                        'children' => [
                            ['type' => 'link', 'label' => 'All Progress Entries', 'route' => '/customer-progress'],
                            ['type' => 'link', 'label' => 'Add Progress Entry', 'route' => '/customer-progress/create'],
                        ],
                    ],
                ],
            ],
            [
                'type' => 'group',
                'label' => 'QuiviCraft',
                'icon' => 'fas fa-fw fa-tools',
                'children' => [
                    [
                        'type' => 'header',
                        'label' => 'QuiviCraft Operations',
                        'children' => [
                            ['type' => 'link', 'label' => 'Create QuiviCraft', 'route' => '/pos'],
                            ['type' => 'link', 'label' => "Today's QuiviCraft", 'route' => '/orders'],
                            ['type' => 'link', 'label' => 'Order QuiviCraft', 'route' => '/orders/all'],
                        ],
                    ],
                    [
                        'type' => 'header',
                        'label' => 'Lookup Tables',
                        'divider_before' => true,
                        'children' => [
                            ['type' => 'link', 'label' => 'QuiviCraft Lookup', 'route' => '/craft'],
                            ['type' => 'link', 'label' => 'Add QuiviCraft Lookup', 'route' => '/craft/create'],
                        ],
                    ],
                ],
            ],
            [
                'type' => 'group',
                'label' => 'QuiviServe',
                'icon' => 'fas fa-fw fa-hammer',
                'children' => [
                    [
                        'type' => 'header',
                        'label' => 'QuiviServe Records',
                        'children' => [
                            ['type' => 'link', 'label' => 'All QuiviServe', 'route' => '/serve-data'],
                        ],
                    ],
                    [
                        'type' => 'header',
                        'label' => 'QuiviServe BEK',
                        'divider_before' => true,
                        'children' => [
                            ['type' => 'link', 'label' => 'All QuiviServe BEK', 'route' => '/serve-bek'],
                            ['type' => 'link', 'label' => 'Add QuiviServe BEK', 'route' => '/serve-bek/create'],
                        ],
                    ],
                    [
                        'type' => 'header',
                        'label' => 'QuiviServe MPS',
                        'divider_before' => true,
                        'children' => [
                            ['type' => 'link', 'label' => 'All QuiviServe MPS', 'route' => '/serve-mps'],
                            ['type' => 'link', 'label' => 'Add QuiviServe MPS', 'route' => '/serve-mps/create'],
                        ],
                    ],
                    [
                        'type' => 'header',
                        'label' => 'QuiviServe PCE',
                        'divider_before' => true,
                        'children' => [
                            ['type' => 'link', 'label' => 'All QuiviServe PCE', 'route' => '/serve-pce'],
                            ['type' => 'link', 'label' => 'Add QuiviServe PCE', 'route' => '/serve-pce/create'],
                        ],
                    ],
                    [
                        'type' => 'header',
                        'label' => 'Lookup Tables',
                        'divider_before' => true,
                        'children' => [
                            ['type' => 'link', 'label' => 'QuiviServe Lookup', 'route' => '/serve'],
                            ['type' => 'link', 'label' => 'Add QuiviServe Lookup', 'route' => '/serve/create'],
                        ],
                    ],
                ],
            ],
            [
                'type' => 'group',
                'label' => 'QuiviCare',
                'icon' => 'fas fa-fw fa-stethoscope',
                'children' => [
                    [
                        'type' => 'header',
                        'label' => 'QuiviCare Records',
                        'children' => [
                            ['type' => 'link', 'label' => 'All QuiviCare', 'route' => '/care-data'],
                            ['type' => 'link', 'label' => 'Add QuiviCare', 'route' => '/care-data/create'],
                        ],
                    ],
                    [
                        'type' => 'header',
                        'label' => 'QuiviCare Warranties',
                        'divider_before' => true,
                        'children' => [
                            ['type' => 'link', 'label' => 'All QuiviCare Warranties', 'route' => '/care-warranty'],
                            ['type' => 'link', 'label' => 'Add QuiviCare Warranties', 'route' => '/care-warranty/create'],
                        ],
                    ],
                    [
                        'type' => 'header',
                        'label' => 'Lookup Tables',
                        'divider_before' => true,
                        'children' => [
                            ['type' => 'link', 'label' => 'QuiviCare Lookup', 'route' => '/care'],
                            ['type' => 'link', 'label' => 'Add QuiviCare Lookup', 'route' => '/care/create'],
                        ],
                    ],
                ],
            ],
            [
                'type' => 'group',
                'label' => 'QuiviPlus',
                'icon' => 'fas fa-fw fa-tools',
                'children' => [
                    [
                        'type' => 'header',
                        'label' => 'Service Catalog',
                        'children' => [
                            ['type' => 'link', 'label' => 'All Services', 'route' => '/plus-services'],
                            ['type' => 'link', 'label' => 'Add Service', 'route' => '/plus-services/create'],
                        ],
                    ],
                    [
                        'type' => 'header',
                        'label' => 'Plus Orders',
                        'divider_before' => true,
                        'children' => [
                            ['type' => 'link', 'label' => 'All Plus Orders', 'route' => '/plus-orders'],
                            ['type' => 'link', 'label' => 'Add Plus Order', 'route' => '/plus-orders/create'],
                        ],
                    ],
                ],
            ],
            [
                'type' => 'group',
                'label' => 'QuiviThread',
                'icon' => 'fas fa-fw fa-plug',
                'children' => [
                    [
                        'type' => 'header',
                        'label' => 'Bill of Materials',
                        'children' => [
                            ['type' => 'link', 'label' => 'All BOMs', 'route' => '/thread-bom'],
                            ['type' => 'link', 'label' => 'Add BOM', 'route' => '/thread-bom/create'],
                        ],
                    ],
                    [
                        'type' => 'header',
                        'label' => 'Thread Inventory',
                        'divider_before' => true,
                        'children' => [
                            ['type' => 'link', 'label' => 'All Thread Inventory', 'route' => '/inv-thread'],
                            ['type' => 'link', 'label' => 'Add Thread Inventory', 'route' => '/inv-thread/create'],
                        ],
                    ],
                    [
                        'type' => 'header',
                        'label' => 'Thread Orders',
                        'divider_before' => true,
                        'children' => [
                            ['type' => 'link', 'label' => 'All Thread Orders', 'route' => '/thread-orders'],
                            ['type' => 'link', 'label' => 'Add Thread Order', 'route' => '/thread-orders/create'],
                        ],
                    ],
                ],
            ],
            [
                'type' => 'group',
                'label' => 'QuiviMerch',
                'icon' => 'fas fa-fw fa-tshirt',
                'children' => [
                    [
                        'type' => 'header',
                        'label' => 'Merch Catalog',
                        'children' => [
                            ['type' => 'link', 'label' => 'All Merch Items', 'route' => '/merch-items'],
                            ['type' => 'link', 'label' => 'Add Merch Item', 'route' => '/merch-items/create'],
                        ],
                    ],
                    [
                        'type' => 'header',
                        'label' => 'Merch Orders',
                        'divider_before' => true,
                        'children' => [
                            ['type' => 'link', 'label' => 'All Merch Orders', 'route' => '/merch-orders'],
                            ['type' => 'link', 'label' => 'Add Merch Order', 'route' => '/merch-orders/create'],
                        ],
                    ],
                ],
            ],
            [
                'type' => 'group',
                'label' => 'Inventory',
                'icon' => 'fas fa-fw fa-truck',
                'children' => [
                    [
                        'type' => 'header',
                        'label' => 'Master SKU <br> Management',
                        'children' => [
                            ['type' => 'link', 'label' => 'All Master SKUs', 'route' => '/master-sku'],
                            ['type' => 'link', 'label' => 'Add Master SKU', 'route' => '/master-sku/create'],
                        ],
                    ],
                    [
                        'type' => 'header',
                        'label' => 'PC Parts Management',
                        'divider_before' => true,
                        'children' => [
                            ['type' => 'link', 'label' => 'All PC Parts', 'route' => '/product'],
                            ['type' => 'link', 'label' => 'Add PC Part', 'route' => '/product/create'],
                        ],
                    ],
                    [
                        'type' => 'header',
                        'label' => 'Stock Management',
                        'divider_before' => true,
                        'children' => [
                            ['type' => 'link', 'label' => 'All Stock', 'route' => '/product/stock'],
                        ],
                    ],
                    [
                        'type' => 'header',
                        'label' => 'Product Brand <br> Management',
                        'children' => [
                            ['type' => 'link', 'label' => 'All Products Brand', 'route' => '/brand'],
                            ['type' => 'link', 'label' => 'Add Product Brand', 'route' => '/brand/create'],
                        ],
                    ],
                    [
                        'type' => 'header',
                        'label' => 'Category Product',
                        'divider_before' => true,
                        'children' => [
                            ['type' => 'link', 'label' => 'Code Lookup', 'route' => '/category'],
                            ['type' => 'link', 'label' => 'Add Code Lookup', 'route' => '/category/create'],
                        ],
                    ],
                    [
                        'type' => 'header',
                        'label' => 'Sub Category Management',
                        'children' => [
                            ['type' => 'link', 'label' => 'Sub Code Lookup', 'route' => '/sub-category'],
                            ['type' => 'link', 'label' => 'Add Sub Code Lookup', 'route' => '/sub-category/create'],
                        ],
                    ],
                    [
                        'type' => 'header',
                        'label' => 'QuiviCare Inventory',
                        'divider_before' => true,
                        'children' => [
                            ['type' => 'link', 'label' => 'All QuiviCare Inventory', 'route' => '/inv-care'],
                            ['type' => 'link', 'label' => 'Add QuiviCare Inventory', 'route' => '/inv-care/create'],
                        ],
                    ],
                    [
                        'type' => 'header',
                        'label' => 'QS Excl. Inventory',
                        'children' => [
                            ['type' => 'link', 'label' => 'All QS Excl. Inventory', 'route' => '/inv-excl-serve'],
                            ['type' => 'link', 'label' => 'Add QS Excl. Inventory', 'route' => '/inv-excl-serve/create'],
                        ],
                    ],
                    [
                        'type' => 'header',
                        'label' => 'QM Inventory',
                        'children' => [
                            ['type' => 'link', 'label' => 'All QM Inventory', 'route' => '/inv-merch'],
                            ['type' => 'link', 'label' => 'Add QM Inventory', 'route' => '/inv-merch/create'],
                        ],
                    ],
                    [
                        'type' => 'header',
                        'label' => 'QM Excl. Inventory',
                        'children' => [
                            ['type' => 'link', 'label' => 'All QM Excl. Inventory', 'route' => '/inv-excl-merch'],
                            ['type' => 'link', 'label' => 'Add QM Excl. Inventory', 'route' => '/inv-excl-merch/create'],
                        ],
                    ],
                    [
                        'type' => 'header',
                        'label' => 'Inventory Movement',
                        'children' => [
                            ['type' => 'link', 'label' => 'All Movements', 'route' => '/inventory-movements'],
                            ['type' => 'link', 'label' => 'Add Movement', 'route' => '/inventory-movements/create'],
                        ],
                    ],
                ],
            ],
            [
                'type' => 'group',
                'label' => 'Suppliers',
                'icon' => 'fas fa-fw fa-truck-loading',
                'children' => [
                    [
                        'type' => 'header',
                        'label' => 'Supplier Management',
                        'children' => [
                            ['type' => 'link', 'label' => 'All Suppliers', 'route' => '/suppliers'],
                            ['type' => 'link', 'label' => 'Add Supplier', 'route' => '/supplier/create'],
                        ],
                    ],
                ],
            ],
        ];

        foreach ($tree as $index => $node) {
            $this->insertNode($node, null, $index);
        }
    }

    private function insertNode(array $node, ?int $parentId, int $sortOrder)
    {
        $item = MenuItem::create([
            'parent_id' => $parentId,
            'type' => $node['type'],
            'label' => $node['label'],
            'icon' => $node['icon'] ?? null,
            'route' => $node['route'] ?? null,
            'sort_order' => $sortOrder,
            'divider_before' => $node['divider_before'] ?? false,
            'is_active' => true,
        ]);

        foreach ($node['children'] ?? [] as $childIndex => $child) {
            $this->insertNode($child, $item->id, $childIndex);
        }
    }
}
