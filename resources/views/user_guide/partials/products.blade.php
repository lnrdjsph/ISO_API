{{-- ═══ 05 · PRODUCTS ═══ --}}
<x-guide.section id="products" number="05" title="Products Page" roles="personnel admin">

    <p class="text-sm text-gray-600">The product catalog. Search by SKU, description, or sub-department.</p>

    <div data-roles="personnel">
        <x-guide.callout type="info">The depot selector is <strong>not available</strong> for store personnel, and the page is <strong>view-only</strong> — contact your admin for product
            changes.</x-guide.callout>
    </div>
    <div data-roles="admin">
        <x-guide.callout type="tip">Full access: depot filter, <strong>Add Product</strong>, and <strong>Import CSV</strong>.</x-guide.callout>
    </div>


    <x-guide.screenshot src="products-table.png" caption="Product catalog with inventory levels and pricing" />

    <h3 class="mt-5 text-sm font-semibold text-gray-900">Column Descriptions</h3>
    <p class="mb-2 mt-1 text-xs text-gray-400">Each column gives specific inventory and pricing information:</p>
    <div class="space-y-1">
        <x-guide.field name="SKU">The product's unique item code (e.g. 12345).</x-guide.field>
        <x-guide.field name="Product Description">The product's full name (e.g. "BEARBRAND FORT POW MLK 128/33G").</x-guide.field>
        <x-guide.field name="Sub-Department">The product's category (e.g. Beverage, Snacks &amp; Biscuits, Confectionery).</x-guide.field>
        <x-guide.field name="Price (SRP)">The selling price per piece.</x-guide.field>
        @if (auth()->user()->role === 'super admin')
            <x-guide.field name="WMS Actual Inventory">The stock physically sitting in the warehouse right now.</x-guide.field>
            <x-guide.field name="WMS Virtual Inventory">The warehouse stock as the system currently sees it — including items already set aside for orders that haven't shipped yet, so it can
                differ from the physical count for a short while.</x-guide.field>
        @else
            <x-guide.field name="WMS Inventory">The warehouse stock available for your store. Goes up and down as orders are placed or cancelled.</x-guide.field>
        @endif
        <x-guide.field name="Stocks">How many you can still sell, after setting aside what's reserved for pending orders.</x-guide.field>
        <x-guide.field name="Allocation">Cases currently set aside for pending orders. Goes down as orders are placed, and back up when orders are cancelled or rejected.</x-guide.field>
        <x-guide.field name="Case Pack">How many pieces are in one case (e.g. 12). Some products list two pack sizes separated by "|" (e.g. "12|24").</x-guide.field>
        <x-guide.field name="C/BC Scheme">A "buy this many, get this many free" deal (e.g. 15+1).</x-guide.field>
        <x-guide.field name="PO15 Scheme">The same kind of "buy &amp; get free" deal for PO15% orders (e.g. 10+2).</x-guide.field>
        <x-guide.field name="Discount Scheme">Any standing discount on the product (a peso amount or a percentage).</x-guide.field>
        <x-guide.field name="Freebie SKU">The free item linked to this product. When you order it as a "Freebie" deal, the free item fills in automatically.</x-guide.field>
    </div>
</x-guide.section>
