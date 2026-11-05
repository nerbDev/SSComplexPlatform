@extends('layouts.staff')

@section('title', 'Equipment Inventory')

@push('styles')
    @include('admin.ob._styles')
@endpush

@section('content')

@if(session('error'))<div class="flash err">{{ session('error') }}</div>@endif
@if($errors->any())<div class="flash err">{{ $errors->first() }}</div>@endif

<div class="note-banner green">
    Record what's in stock. Adding or editing an item also counts as a check. Set the quantity to 0 instead of deleting an item. Damaged items should also get an incident report.
</div>

<div class="card">
    <div class="card-head">
        <div class="card-title">Stored items <span class="sub">· {{ number_format($totals['items']) }} item types, {{ number_format($totals['units']) }} units</span></div>
        <button type="button" class="btn-sm b-primary" style="padding:8px 14px;" onclick="openForm()">+ Add item</button>
    </div>

    <div class="filter-bar">
        <form class="filter-form" method="GET" action="{{ route('staff.inventory.index') }}">
            <div class="f"><label>Facility</label>
                <select class="f-input" name="facility_id">
                    <option value="">All</option>
                    <option value="shared" @selected($filters['facility_id'] === 'shared')>Shared / general stock</option>
                    @foreach($facilities as $f)<option value="{{ $f->id }}" @selected((string) $filters['facility_id'] === (string) $f->id)>{{ $f->name }}</option>@endforeach
                </select>
            </div>
            <div class="f"><label>Condition</label>
                <select class="f-input" name="condition">
                    <option value="">Any</option>
                    @foreach($conditions as $k => $l)<option value="{{ $k }}" @selected($filters['condition'] === $k)>{{ $l }}</option>@endforeach
                </select>
            </div>
            <div class="f"><label>Search</label><input class="f-input" type="search" name="q" value="{{ $filters['q'] }}" placeholder="Item name"></div>
            <button class="f-btn" type="submit">Apply</button>
            <a class="f-link" href="{{ route('staff.inventory.index') }}">Reset</a>
        </form>
    </div>

    @if(count($rows))
        <div class="table-scroll">
        <table class="ob-table" style="min-width:720px;">
            <thead><tr><th>Item</th><th>Facility</th><th class="num">Qty</th><th>Condition</th><th>Last checked</th><th>Actions</th></tr></thead>
            <tbody>
                @foreach($rows as $r)
                    <tr>
                        <td><strong>{{ $r['item'] }}</strong></td>
                        <td><span class="unit-pill">{{ $r['facility'] }}</span></td>
                        <td class="num"><strong>{{ number_format($r['quantity']) }}</strong>@if($r['unit']) <span class="sub">{{ $r['unit'] }}</span>@endif</td>
                        <td><span class="pill {{ ['good' => 'p-ok', 'damaged' => 'p-bad', 'under_maintenance' => 'p-warn'][$r['condition']] ?? 'p-gray' }}">{{ $r['condition_label'] }}</span></td>
                        <td>{{ $r['last_checked'] }}</td>
                        <td>
                            <div class="row-actions">
                                <button type="button" class="btn-sm b-ghost" onclick="openForm({{ $r['id'] }})">Edit</button>
                                <form method="POST" action="{{ $r['check_url'] }}">@csrf<button class="btn-sm b-dark" type="submit">Mark checked</button></form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        </div>

        <div class="pager">
            <div>Page {{ $page->currentPage() }} of {{ $page->lastPage() }}</div>
            <div style="display:flex;gap:8px;">
                @if($page->previousPageUrl())<a href="{{ $page->previousPageUrl() }}">Previous</a>@else<span class="off">Previous</span>@endif
                @if($page->nextPageUrl())<a href="{{ $page->nextPageUrl() }}">Next</a>@else<span class="off">Next</span>@endif
            </div>
        </div>
    @else
        <div class="empty-state">
            <div class="e-icon">📦</div>
            <div class="e-title">No items yet</div>
            <div class="e-sub">Use "+ Add item" to enter the first stock.</div>
        </div>
    @endif
</div>

<div class="ap-overlay" id="formModal">
    <div class="ap-box">
        <div class="ap-title" id="formTitle">Add item</div>
        <form method="POST" id="iForm" action="{{ route('staff.inventory.store') }}">
            @csrf
            <input type="hidden" name="_method" id="iMethod" value="POST">
            <input type="hidden" name="_mode" id="iMode" value="{{ old('_mode', 'create') }}">
            <input type="hidden" name="_id" id="iId" value="{{ old('_id') }}">
            <div class="fld"><label>Item name</label><input type="text" name="item_name" id="iName" required maxlength="255" placeholder="e.g. Basketball">
                @error('item_name')<div class="err">{{ $message }}</div>@enderror
            </div>
            <div class="fld"><label>Facility</label>
                <select name="facility_id" id="iFac">
                    <option value="">Shared / general stock</option>
                    @foreach($facilities as $f)<option value="{{ $f->id }}">{{ $f->name }}</option>@endforeach
                </select>
            </div>
            <div class="fld-row">
                <div class="fld"><label>Quantity</label><input type="number" name="quantity" id="iQty" min="0" required value="0"></div>
                <div class="fld"><label>Unit (optional)</label><input type="text" name="unit" id="iUnit" maxlength="30" placeholder="pcs, sets…"></div>
            </div>
            <div class="fld"><label>Condition</label>
                <select name="condition" id="iCond">@foreach($conditions as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select>
            </div>
            <div class="ap-actions">
                <button type="button" class="b-close" onclick="closeModal('formModal')">Cancel</button>
                <button type="submit" class="b-go" id="iSubmit">Add item</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const ROWS = @json($rows);
    const STORE_URL = @json(route('staff.inventory.store'));
    const OLD = { mode: @json(old('_mode')), id: @json(old('_id')), name: @json(old('item_name')), fac: @json(old('facility_id')), qty: @json(old('quantity')), unit: @json(old('unit')), cond: @json(old('condition')) };
    const $ = id => document.getElementById(id);
    function closeModal(id){ $(id).classList.remove('show'); }
    document.querySelectorAll('.ap-overlay').forEach(o => o.addEventListener('click', e => { if (e.target === o) o.classList.remove('show'); }));
    document.addEventListener('keydown', e => { if (e.key === 'Escape') document.querySelectorAll('.ap-overlay.show').forEach(o => o.classList.remove('show')); });

    function fill(v){ $('iName').value = v.name || ''; $('iFac').value = v.fac ?? ''; $('iQty').value = v.qty ?? 0; $('iUnit').value = v.unit || ''; $('iCond').value = v.cond || 'good'; }

    function openForm(id){
        if (id) {
            const r = ROWS.find(x => x.id === id); if (!r) return;
            $('formTitle').textContent = 'Edit item'; $('iSubmit').textContent = 'Save changes';
            $('iForm').action = r.update_url; $('iMethod').value = 'PUT'; $('iMode').value = 'edit'; $('iId').value = r.id;
            fill({name: r.item, fac: r.facility_id, qty: r.quantity, unit: r.unit, cond: r.condition});
        } else {
            $('formTitle').textContent = 'Add item'; $('iSubmit').textContent = 'Add item';
            $('iForm').action = STORE_URL; $('iMethod').value = 'POST'; $('iMode').value = 'create'; $('iId').value = '';
            fill({});
        }
        $('formModal').classList.add('show');
    }

    if (OLD.mode) {
        const r = OLD.mode === 'edit' ? ROWS.find(x => String(x.id) === String(OLD.id)) : null;
        openForm(r ? r.id : undefined);
        fill({name: OLD.name, fac: OLD.fac, qty: OLD.qty, unit: OLD.unit, cond: OLD.cond});
    }
</script>
@endpush